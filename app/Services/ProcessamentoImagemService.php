<?php

namespace App\Services;

use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\ProdutoHistoricoController;
use App\Http\Controllers\Api\OpenFoodFactsController;
use App\Models\Imagem;
use App\Models\Produto;
use App\Models\ProdutoHistorico;
use App\Models\Categoria;
use App\Models\Receita;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use GeminiAPI\Client;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\GenerationConfig;
use Exception;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;



class ProcessamentoImagemService
{
    protected ?int $idUsuario;
    protected ?int $idReceita;
    protected Imagem $imagem;

    /**
     * Processa a imagem recebida, extraindo os dados utilizando gemini.
     * Este método é utilizado para processar imagens de cupons fiscais e extrair as informações estruturadas.
     *
     * @param Imagem $imagem O model da imagem.
     * @param int $idUsuario O ID do user que fez upload da imagem.
     * @param int|null $idReceita Opcional - ID da receita em caso de 'Cupom Fiscal Receita'.
     * @return array Resultado do processamento.
     */
    public function processarCupom(Imagem $imagem, int $idUsuario, ?int $idReceita = null): array
    {
        $this->imagem = $imagem;
        $this->idUsuario = $idUsuario;
        $this->idReceita = $idReceita;

        $dadosExtraidos = $this->ExtrairDadosGemini($imagem);

        if (!$dadosExtraidos || !isset($dadosExtraidos['items']) || !isset($dadosExtraidos['sale_date'])) {
            Log::error("Falha ao extrair dados da imagem ID: {$imagem->id}");
            return ['sucesso' => false, 'message' => 'Falha ao extrair dados da imagem.'];
        }

        try {
            $dataCupom = Carbon::createFromFormat('d/m/Y - H:i', $dadosExtraidos['sale_date'])->format('Y-m-d');
        } catch (Exception $e) {
            Log::error("Formato de data inválido recebido do processamento da imagem ID: {$imagem->id}. Date: " . $dadosExtraidos['sale_date']);
            return ['sucesso' => false, 'message' => 'Formato de data inválido recebido do processamento da imagem.'];
        }

        $itensProcessados = 0;
        $itensIgnorados = 0;
        $erros = [];
        $historicosCriados = [];

        //variação percentual
        $percentualVariacao = 0.01;

        //validação de valores numéricos extraídos
        foreach ($dadosExtraidos['items'] as $item) {
            $precoCalculado = Helper::truncate_float($item->unit_price * $item->quantity - ($item->discount ?? 0.0),2);
            if ($precoCalculado !=  $item->total_price && abs($precoCalculado - $item->total_price) > $percentualVariacao * $item->total_price) {
                Log::warning("Preço total calculado ({$precoCalculado}) difere do preço total extraído ({$item->total_price}) para o item: " . json_encode($item));
                $item->total_price = $precoCalculado;
            }
        }

        //verificação se o produto pertence à receita, se uma receita foi especificada
        $idProdutoReceita = null;
        if ($this->idReceita) {
            $receita = Receita::with('ingredientes')->find($this->idReceita);
            if ($receita) {
                $idProdutoReceita = $receita->ingredientes->pluck('id_produto')->unique()->toArray();
            } else {
                Log::warning("Receita com ID {$this->idReceita} não encontrada para processamento da imagem com ID {$imagem->id}.");
            }
        }

        DB::beginTransaction();
        try {
            foreach ($dadosExtraidos['items'] as $item) {
                $produto = $this->indexOrStoreProduto($item);

                if (!isset($item->barcode) || empty($item->barcode)) {
                    Log::warning("Item sem código de barras encontrado: " . json_encode($item));
                    $erros[] = "Item sem código de barras encontrado: " . json_encode($item);
                    $itensIgnorados++;
                    continue;
                }

                if (!$produto) {
                    Log::warning("Produto não encontrado ou criado para o item: " . ($item->name ?? $item->barcode) . " na imagem ID {$imagem->id}");
                    $erros[] = "Produto não encontrado/criado para: " . ($item->name ?? $item->barcode);
                    $itensIgnorados++;
                    continue;
                }

                if ($idProdutoReceita !== null && !in_array($produto->id, $idProdutoReceita)) {
                    Log::info("Produto {$produto->nome} (ID: {$produto->id}) do cupom (Imagem ID: {$imagem->id}) não pertence à receita ID {$this->idReceita}. Pulando.");
                    $itensIgnorados++;
                    continue;
                }

                $quantidade = (float)str_replace(',', '.', $item->quantity);
                $precoUnitario = (float)str_replace(',', '.', $item->unit_price);
                $desconto = isset($item->discount) ? (float)str_replace(',', '.', $item->discount) : 0.0;
                $precoTotalCalculado = ($quantidade * $precoUnitario) - $desconto;

                $historico = ProdutoHistorico::create([
                    'id_usuario' => $this->idUsuario,
                    'id_produto' => $produto->id,
                    'preco_unitario' => $precoUnitario,
                    'quantidade' => $quantidade,
                    'preco_total' => $precoTotalCalculado,
                    'data_compra' => $dataCupom,
                    'desconto' => $desconto,
                ]);
                $historicosCriados[] = $historico->id;
                $itensProcessados++;
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro ao salvar históricos de produto para imagem ID {$imagem->id}: " . $e->getMessage());
            return ['sucesso' => false, 'message' => 'Erro ao salvar dados no banco.', 'errors' => [$e->getMessage()]];
        }

        Log::info("Processamento do cupom concluído para imagem ID {$imagem->id}. Itens processados: {$itensProcessados}, Itens ignorados: {$itensIgnorados}.");
        return [
            'sucesso' => true,
            'mensagem' => "Processamento do cupom concluído. {$itensProcessados} itens processados, {$itensIgnorados} itens ignorados.",
            'itensProcessados' => $itensProcessados,
            'itensIgnorados' => $itensIgnorados,
            'errosProcessamento' => $erros
        ];
    }

    /**
     * Encontra o produto pelo código de barras ou cria um novo produto se não existir.
     * @param object $item Informações do item extraídas da imagem.
     * @return Produto|null Retorna o produto encontrado ou criado, ou null se não for possível processar.
     */
    protected function indexOrStoreProduto(Object $item): ?Produto
    {
        $codigoDeBarras = $item->barcode ?? null;

        if ($codigoDeBarras) {
            $produto = Produto::where('codigo_barra', $codigoDeBarras)->first();
            if (!$produto) {
                $produtoController = new ProdutoController();
                $openFoodFactsController = new OpenFoodFactsController($produtoController);
                try {
                    $openFoodFactsController->getProductByBarcode($codigoDeBarras);
                    $produto = Produto::where('codigo_barra', $codigoDeBarras)->first();
                } catch (Exception $e) {
                    Log::error("Erro ao buscar produto por código de barras {$codigoDeBarras}: " . $e->getMessage());
                    return null;
                }
            }
        }
        return $produto;
    }

    /**
     * Extrai os dados da imagem utilizando a API Gemini.
     * Este método é responsável por enviar a imagem para o Gemini e processar a resposta.
     *
     * @param Imagem $image O model da imagem a ser processada.
     * @return array|null Retorna os dados extraídos em formato de array ou null em caso de erro.
     */
    protected function ExtrairDadosGemini(Imagem $image): ?array
    {
        $imageUrl = $image->display_url;
        if (!$imageUrl) {
            Log::error("Imagem ID {$image->id} não possui URL acessível para processamento Gemini.");
            return null;
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error("GEMINI_API_KEY não configurada no .env");
            return null;
        }

        $prompt = <<<PROMPT
        ATENÇÃO: Você é um assistente especializado em analisar cupons fiscais brasileiros e extrair informações estruturadas de vendas.
        Você receberá uma imagem de um cupom fiscal brasileiro e deve extrair as informações de venda em formato JSON estruturado.
        Você deve analisar o texto da imagem e criar o JSON com as informações solicitadas. 
        GARANTA QUE TODOS OS ITENS SEJAM EXTRAÍDOS CORRETAMENTE E QUE O JSON ESTEJA FORMATADO DE ACORDO COM AS INSTRUÇÕES ABAIXO.
        é CRUCIAL que você siga as instruções rigorosamente e retorne apenas o JSON solicitado, sem texto adicional ou explicações.

        LEIA ITEM A ITEM PARA EVITAR MISTURAR INFORMAÇÕES E GARANTIR A EXATIDÃO DOS DADOS EXTRAÍDOS.
        Para isso Determine de é necessário ler 2 ou 3 linhas de cada item para extrair todas as informações necessárias.
        2 linhas quando não houver desconto e 3 linhas quando houver desconto.

        Utilize multiplas passagens de OCR com filtros de imagem distinto para garantir a melhor qualidade de leitura.
        Utilize um sistema de votação para determinar a melhor leitura de cada item.
        Faça isso especialmente para os campos de preço unitário, quantidade e desconto, que podem variar entre as linhas.
        Atenção redobrada em quantidade quando for um número decimal, especialmente no valor inteiro e nas primeiras casas decimais.

        Para garantir que você está aplicando o desconto no item correto, compare o número do item da linha 1 e o número do item na linha 3 (linha de desconto)

        Analise a seguinte imagem de "cupom fiscal" brasileiro.
        Extraia as seguintes informações em formato JSON:
        1. A data principal da venda (geralmente indicada como "VENDA" ou próxima ao cabeçalho). Formato: "DD/MM/YYYY - HH:MM".
        2. Uma lista de todos os itens. Para cada item, extraia:
            - "item_code": O número sequencial do item (ex: "001", "002").
            - "barcode": O código de barras do produto ou código numérico se disponível (pode ser nulo).
            - "name": A descrição do produto.
            - "quantity": A quantidade comprada (numérico, use ponto como separador decimal).
            - "unit_of_measure": A unidade de medida (ex: "UN", "KG", "PC").
            - "unit_price": O preço por unidade (numérico, use ponto como separador decimal).
            - "total_price": O preço total para essa linha de item antes de qualquer desconto específico do item (numérico, use ponto como separador decimal).
            - "discount": O valor do desconto aplicado especificamente a este item. Se uma linha "Desconto sobre item XXX" aparecer abaixo do item, extraia este valor como um número positivo. Se não houver desconto, use 0.

        Garanta que todos os valores numéricos (quantity, unit_price, total_price, discount) sejam retornados como números.
        Se um campo não puder ser determinado com segurança, retorne null para strings ou 0 para números onde apropriado (exceto barcode que pode ser null).
        Priorize a data e hora da operação de VENDA.
        Retorne apenas o JSON, nada além do JSON.
        Não retorne texto adicional, apenas o JSON formatado corretamente com todos os itens da lista. 
        Sua resposta será processada por um sistema automatizado, então não inclua explicações ou formatação adicional.
        Respostas incompletas ou com erros de formatação podem causar falhas no processamento.
        Garanta que todos os itens estão no JSON, não utilize "// ... (rest of the items)" ou semelhante.

        Muita atenção aos detalhes, especialmente com os campos numéricos. Os valores entre parênteses são impostos e devem ser desconsiderados.
        
        Exemplo de estrutura de item:
        Linha 1: 001 078982794210 REF MAX PURE 30ML PAT
        Linha 2: 1,000 UN X 56,50 (0,00)  56,50
        Deve ser extraído como:
        { "item_code": "001", "barcode": "078982794210", "name": "REF MAX PURE 30ML PAT", "quantity": 1.000, "unit_of_measure": "UN", "unit_price": 56.50, "total_price": 56.50, "discount": 0 }

        Exemplo de item com desconto:
        Linha 1: 003 07891350034640 D MONANGE 150ML NY IN
        Linha 2: 1,000 UN X 8,99 (0,00)II  8,99
        Linha 3: Desconto sobre item 003  -1,00
        Deve ser extraído como:
        { "item_code": "003", "barcode": "07891350034640", "name": "D MONANGE 150ML NY IN", "quantity": 1.000, "unit_of_measure": "UN", "unit_price": 8.99, "total_price": 7.99, "discount": 1.00 }

        Certifique-se que o total_price seja o valor total do item após aplicar o desconto, se houver.
        Se não houver itens, retorne uma lista vazia.

        Estrutura JSON de saída esperada:
        {
          "sale_date": "DD/MM/YYYY - HH:MM",
          "items": [
            { "item_code": "...", "barcode": "...", "name": "...", "quantity": ..., "unit_of_measure": "...", "unit_price": ..., "total_price": ..., "discount": ... },
            // mais itens
          ]
        }

PROMPT;

        try {
            $generationConfig = (new GenerationConfig())
                ->withTemperature(0);

            $client = (new Client(env('GEMINI_API_KEY')))
                ->withV1BetaVersion();

            $path = Storage::disk('public')->path($image->caminho_storage);

            $mimeType = MimeType::tryFrom($image->mime_type);

            if (!$mimeType) {
                throw new InvalidArgumentException("MIME type não suportado: {$image->mime_type}");
            }

            $response = $client->generativeModel(ModelName::GEMINI_2_0_FLASH_001)
                ->withGenerationConfig($generationConfig)
                ->generateContent(
                    new TextPart($prompt),
                    new ImagePart(
                        $mimeType,
                        base64_encode(file_get_contents($path)),
                    ),
                );
            $responseData = $response->text();
            $responseData = str_replace("```json", "", $responseData);
            $responseData = str_replace("```", "", $responseData);

            $responseJson = json_decode($responseData);
            return $responseJson ? (array)$responseJson : null;
        } catch (Exception $e) {
            Log::error("Falha ao chamar a API durante o processamento da imagem com ID: {$image->id}: " . $e->getMessage());
            return null;
        }
    }
}

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // For Gemini (conceptual) or use Google SDK
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use GeminiAPI\Client;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\GenerationConfig;
use DateTime;

use Illuminate\Support\Facades\Storage;



class ReceiptProcessingService
{
    protected ?int $userId;
    protected ?int $recipeId;
    protected Imagem $image;

    /**
     * Processes the uploaded receipt image.
     *
     * @param Imagem $image The image model.
     * @param int $userId The ID of the user who uploaded the image.
     * @param int|null $recipeId Optional recipe ID if type is 'Cupom Fiscal Receita'.
     * @return array Result of the processing.
     */
    public function processReceipt(Imagem $image, int $userId, ?int $recipeId = null): array
    {
        $this->image = $image;
        $this->userId = $userId;
        $this->recipeId = $recipeId;

        $extractedData = $this->extractDataFromImageViaGemini($image);
        Log::info("Dados extraídos da imagem ID {$image->id}: " . json_encode($extractedData));

        if (!isset($item->barcode) || empty($item->barcode)) {
        }
        if (!$extractedData || !isset($extractedData['items']) || !isset($extractedData['sale_date'])) {
            Log::error("Failed to extract valid data from image ID: {$image->id}");
            return ['success' => false, 'message' => 'Falha ao extrair dados da imagem.'];
        }

        try {
            $saleDate = Carbon::createFromFormat('d/m/Y - H:i', $extractedData['sale_date'])->format('Y-m-d');
            $saleDateTime = new DateTime($saleDate);
        } catch (\Exception $e) {
            Log::error("Invalid date format from Gemini for image ID: {$image->id}. Date: " . $extractedData['sale_date']);
            return ['success' => false, 'message' => 'Formato de data inválido recebido do processamento da imagem.'];
        }

        $processedItems = 0;
        $skippedItems = 0;
        $errors = [];
        $createdHistoricos = [];

        //variação percentual
        $percentualVariacao = 0.01;
        
        foreach ($extractedData['items'] as $item) {
            $precoCalculado = Helper::truncate_float($item->unit_price * $item->quantity - ($item->discount ?? 0.0),2);
            if ($precoCalculado !=  $item->total_price && abs($precoCalculado - $item->total_price) > $percentualVariacao * $item->total_price) {
                Log::warning("Preço total calculado ({$precoCalculado}) difere do preço total extraído ({$item->total_price}) para o item: " . json_encode($item));
                $item->total_price = $precoCalculado;
            }
        }
        $recipeProductIds = null;
        if ($this->recipeId) {
            $recipe = Receita::with('ingredientes')->find($this->recipeId);
            if ($recipe) {
                $recipeProductIds = $recipe->ingredientes->pluck('id_produto')->unique()->toArray();
            } else {
                Log::warning("Recipe ID {$this->recipeId} not found for receipt processing of image ID {$image->id}.");
                // Decide if to proceed as a generic list or fail
            }
        }

        DB::beginTransaction();
        try {
            foreach ($extractedData['items'] as $itemData) {
                $produto = $this->findProdutoAndCreateProdutoHistorico($itemData, $saleDateTime);

                if (!isset($itemData->barcode) || empty($itemData->barcode)) {
                    Log::warning("Item sem código de barras encontrado: " . json_encode($itemData));
                    $errors[] = "Item sem código de barras encontrado: " . json_encode($itemData);
                    $skippedItems++;
                    continue;
                }

                if (!$produto) {
                    Log::warning("Produto não encontrado ou criado para o item: " . ($itemData->name ?? $itemData->barcode) . " na imagem ID {$image->id}");
                    $errors[] = "Produto não encontrado/criado para: " . ($itemData->name ?? $itemData->barcode);
                    $skippedItems++;
                    continue;
                }

                if ($recipeProductIds !== null && !in_array($produto->id, $recipeProductIds)) {
                    Log::info("Produto {$produto->nome} (ID: {$produto->id}) do cupom (Imagem ID: {$image->id}) não pertence à receita ID {$this->recipeId}. Pulando.");
                    $skippedItems++;
                    continue;
                }

                $quantidade = (float)str_replace(',', '.', $itemData->quantity);
                $precoUnitario = (float)str_replace(',', '.', $itemData->unit_price);
                $desconto = isset($itemData->discount) ? (float)str_replace(',', '.', $itemData->discount) : 0.0;
                $precoTotalCalculado = ($quantidade * $precoUnitario) - $desconto;

                $historico = ProdutoHistorico::create([
                    'id_usuario' => $this->userId,
                    'id_produto' => $produto->id,
                    'preco_unitario' => $precoUnitario,
                    'quantidade' => $quantidade,
                    'preco_total' => $precoTotalCalculado,
                    'data_compra' => $saleDate,
                    'desconto' => $desconto,
                ]);
                $createdHistoricos[] = $historico->id;
                $processedItems++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao salvar históricos de produto para imagem ID {$image->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao salvar dados no banco.', 'errors' => [$e->getMessage()]];
        }

        Log::info("Processamento do cupom concluído para imagem ID {$image->id}. Itens processados: {$processedItems}, Itens pulados: {$skippedItems}.");
        return [
            'success' => true,
            'message' => "Processamento do cupom concluído. {$processedItems} itens processados, {$skippedItems} itens pulados.",
            'processed_count' => $processedItems,
            'skipped_count' => $skippedItems,
            'processing_errors' => $errors
        ];
    }

    /**
     * Finds an existing product or creates a new one.
     */
    protected function findProdutoAndCreateProdutoHistorico(Object $itemData, DateTime $sale_date): ?Produto
    {
        $barcode = $itemData->barcode ?? null;

        if ($barcode) {
            $produto = Produto::where('codigo_barra', $barcode)->first();
            if (!$produto) {
                $produtoController = new ProdutoController();
                $openFoodFacts = new OpenFoodFactsController($produtoController);
                try {
                    $getProductByBarcode = $openFoodFacts->getProductByBarcode($barcode);
                    $produto = Produto::where('codigo_barra', $barcode)->first();
                } catch (\Exception $e) {
                    Log::error("Erro ao buscar produto por código de barras {$barcode}: " . $e->getMessage());
                    return null;
                }
            }

            // if ($produto) {
            //     $produtoHistorico = new ProdutoHistoricoController();
            //     $produtoHistorico->store(new \Illuminate\Http\Request([
            //         'id_usuario' => $this->userId,
            //         'id_produto' => $produto->id,
            //         'preco_unitario' => (float)str_replace(',', '.', $itemData->unit_price),
            //         'quantidade' => (float)str_replace(',', '.', $itemData->quantity),
            //         'data_compra' => $sale_date,
            //         'desconto' => isset($itemData->discount) ? (float)str_replace(',', '.', $itemData->discount) : 0.0,
            //         'preco_total' => isset($itemData->discount) ?
            //             (float)str_replace(',', '.', $itemData->total_price) - (float)str_replace(',', '.', $itemData->discount) :
            //             (float)str_replace(',', '.', $itemData->total_price)
            //     ]));
            //     return $produto;
            // }
        }
        return $produto;
    }

    /**
     * (Conceptual) Calls Gemini API to extract structured data from the image.
     * Replace with actual Gemini SDK/API call.
     */
    protected function extractDataFromImageViaGemini(Imagem $image): ?array
    {
        $imageUrl = $image->display_url;
        if (!$imageUrl) {
            Log::error("Imagem ID {$image->id} não possui URL acessível para processamento Gemini.");
            return null;
        }

        $apiKey = env('GEMINI_API_KEY'); // Ensure you have your Gemini API Key in .env
        if (!$apiKey) {
            Log::error("GEMINI_API_KEY não configurada no .env");
            return null;
        }

        // This is a placeholder for the Gemini API call structure.
        // You'll need to use Google's official PHP SDK for Gemini (google-gemini-php).
        // Example prompt:
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

            // Convert string MIME type to MimeType enum
            $mimeType = MimeType::tryFrom($image->mime_type);

            if (!$mimeType) {
                throw new \InvalidArgumentException("Unsupported MIME type: {$image->mime_type}");
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
            $encodedResponse = $response->text();
            $encodedResponse = str_replace("```json", "", $encodedResponse);
            $encodedResponse = str_replace("```", "", $encodedResponse);

            $decodedResponse = json_decode($encodedResponse);
            return $decodedResponse ? (array)$decodedResponse : null;
        } catch (\Exception $e) {
            Log::error("Gemini API call failed for image ID {$image->id}: " . $e->getMessage());
            return null;
        }
    }
}

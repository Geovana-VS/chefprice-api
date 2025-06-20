<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Imagem;
use App\Models\TipoImagem;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class ProdutoController extends Controller
{
    public function index()
    {
        $produtos = Produto::with(['categoria', 'imagens'])->latest()->get();
        return response()->json($produtos);
    }

    public function store(Request $request)
    {
        $messages = [
            'codigo_barra.unique' => 'Este código de barra já está em uso.',
            'codigo_barra.max' => 'O código de barra não pode ter mais que :max caracteres.',
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais que :max caracteres.',
            'id_categoria.required' => 'A categoria é obrigatória.',
            'id_categoria.exists' => 'A categoria selecionada é inválida.',
            'unidade_medida.max' => 'A unidade de medida não pode ter mais que :max caracteres.',
            'preco_padrao.numeric' => 'O preço padrão deve ser um valor numérico.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.'
        ];

        $rules = [
            'codigo_barra' => 'nullable|string|max:100|unique:produtos,codigo_barra',
            'nome' => 'required|string|max:200',
            'id_categoria' => 'required|integer|exists:categorias,id',
            'unidade_medida' => 'nullable|string|max:50',
            'preco_padrao' => 'nullable|numeric|min:0',
            'imagens' => 'nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $produto = null;

        DB::transaction(function () use ($validatedData, &$produto) {

            $produtoData = [
                'nome' => $validatedData['nome'],
                'id_categoria' => $validatedData['id_categoria'],
                'codigo_barra' => $validatedData['codigo_barra'] ?? null,
                'unidade_medida' => $validatedData['unidade_medida'] ?? null,
                'preco_padrao' => $validatedData['preco_padrao'] ?? null,
            ];

            $produto = Produto::create($produtoData);

            if (!empty($validatedData['imagens'])) {
                $produto->imagens()->attach($validatedData['imagens']);
            }
        });

        if ($produto === null) {
            return response()->json(['message' => 'Produto não criado.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json($produto->load(['categoria', 'imagens']), Response::HTTP_CREATED);
    }

    public function show(Produto $produto)
    {
        return response()->json($produto->load(['categoria', 'imagens', 'historico']));
    }

    public function update(Request $request, Produto $produto)
    {

        $messages = [
            'codigo_barra.unique' => 'Este código de barra já está em uso.',
            'codigo_barra.max' => 'O código de barra não pode ter mais que :max caracteres.',
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais que :max caracteres.',
            'id_categoria.required' => 'A categoria é obrigatória.',
            'id_categoria.exists' => 'A categoria selecionada é inválida.',
            'unidade_medida.max' => 'A unidade de medida não pode ter mais que :max caracteres.',
            'preco_padrao.numeric' => 'O preço padrão deve ser um valor numérico.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.',
        ];

        $rules = [
            'codigo_barra' => 'sometimes|nullable|string|max:100|unique:produtos,codigo_barra,' . $produto->id,
            'nome' => 'sometimes|required|string|max:200',
            'id_categoria' => 'sometimes|required|integer|exists:categorias,id',
            'unidade_medida' => 'sometimes|nullable|string|max:50',
            'preco_padrao' => 'nullable|numeric|min:0',
            'imagens' => 'sometimes|nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $produto) {
            $produtoData = [];
            if (array_key_exists('nome', $validatedData)) $produtoData['nome'] = $validatedData['nome'];
            if (array_key_exists('id_categoria', $validatedData)) $produtoData['id_categoria'] = $validatedData['id_categoria'];
            if (array_key_exists('codigo_barra', $validatedData)) $produtoData['codigo_barra'] = $validatedData['codigo_barra'];
            if (array_key_exists('unidade_medida', $validatedData)) $produtoData['unidade_medida'] = $validatedData['unidade_medida'];
            if (array_key_exists('preco_padrao', $validatedData)) $produtoData['preco_padrao'] = $validatedData['preco_padrao'];

            if (!empty($produtoData)) {
                $produto->update($produtoData);
            }

            if (array_key_exists('imagens', $validatedData)) {
                $produto->imagens()->sync($validatedData['imagens'] ?? []);
            }
        });


        return response()->json($produto->fresh()->load(['categoria', 'imagens']));
    }

    public function destroy(Produto $produto)
    {
        if ($produto->ingredienteEmReceitas()->exists()) {
            return response()->json(['message' => 'Não pode deletar produto usado como ingrediente em receitas.'], Response::HTTP_CONFLICT); // 409 Conflict
        }

        DB::transaction(function () use ($produto) {
            $produto->imagens()->detach();

            $produto->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT); // 204 No Content
    }

    public function storeOrUpdateOpenFoodFacts(array $productData)
    {
        $categoriaId = null;
        $categoriaNome = null;

        if (isset($productData['categories_tags']) && is_array($productData['categories_tags'])) {
            foreach ($productData['categories_tags'] as $tag) {
                if (str_starts_with($tag, 'pt:')) {
                    $categoriaNome = Str::ucfirst(str_replace('-', ' ', substr($tag, 3)));
                    break;
                }
            }
        }

        if (!$categoriaNome && !empty($productData['generic_name_pt'])) {
            $categoriaNome = Str::ucfirst($productData['generic_name_pt']);
        }

        if (!$categoriaNome) {
            $categoriaNome = 'Indefinida';
        }

        try {
            $categoria = Categoria::firstOrCreate(
                ['nome' => $categoriaNome]
            );
            $categoriaId = $categoria->id;
        } catch (Exception $e) {
            Log::error("Erro ao encontrar ou criar a categoria '{$categoriaNome}': " . $e->getMessage());
        }

        $descricao = $productData['generic_name_pt'] ?? null;
        if (!empty($productData['brands'])) {
            $descricao = ($descricao ? $descricao . ' - ' : '') . 'Marca: ' . $productData['brands'];
        }

        $unidadeMedida = null;
        $quantidadeNumerica = null;
        if (isset($productData['quantity'])) {
            if (preg_match('/^([0-9.]+)\s*([a-zA-Zμ]*)$/', trim($productData['quantity']), $matches)) {
                $quantidadeNumerica = (float) $matches[1];
                $unidadeMedida = !empty($matches[2]) ? strtolower($matches[2]) : null;
            } else {
                Log::warning("Não foi possivel extrair e parsear o valor de : " . $productData['quantity']);
            }
        }

        $produtoInput = [
            'nome' => $productData['product_name_pt'] ?? 'Produto Sem Nome',
            'id_categoria' => $categoriaId,
            'codigo_barra' => $productData['code'],
            'unidade_medida' => $unidadeMedida,
            'quantidade' => $quantidadeNumerica,
            'descricao' => $descricao,
        ];

        $produto = null;
        try {
            DB::transaction(function () use ($produtoInput, $productData, &$produto) {

                $produto = Produto::updateOrCreate(
                    ['codigo_barra' => $produtoInput['codigo_barra']],
                    $produtoInput
                );

                $imageUrl = $productData['image_front_url'] ?? $productData['image_url'] ?? null;

                if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    try {
                        $tipoImagem = TipoImagem::firstOrCreate(['nome' => 'Produto']); //

                        $imagem = Imagem::firstOrCreate([
                            'id_usuario' => Auth::id(),
                            'id_tipo_imagem' => $tipoImagem->id,
                            'url' => $imageUrl,
                            'nome_arquivo' => $produtoInput['nome'],
                            'mime_type' => Http::get($imageUrl)->header('Content-Type'),
                            'is_publico' => true,
                        ]);

                        $produto->imagens()->syncWithoutDetaching([$imagem->id]);
                    } catch (Exception $e) {
                        Log::error("Erro processando a imagem {$imageUrl}: " . $e->getMessage());
                    }
                }
            });
        } catch (Exception $e) {
            Log::error("Erro armazenando o produto de OpenFoodFacts (Código de barras: {$productData['code']}): " . $e->getMessage());
            return response()->json(['message' => 'Erro ao salvar o produto no banco de dados.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($produto === null) {
            return response()->json(['message' => 'Produto não foi salvo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($produto->load(['categoria', 'imagens']), Response::HTTP_OK);
    }

    public function handleOpenFoodFactsWebhook(Request $request)
    {
        $productData = $request->input('product');

        if (!$productData || !isset($productData['code'])) {
            return response()->json(['message' => 'Dados inválidos recebidos.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->storeOrUpdateOpenFoodFacts($productData);
    }
}

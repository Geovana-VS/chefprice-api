<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaCompra;
use App\Models\ListaCompraStatus;
use App\Models\ProdutoHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class ListaCompraController extends Controller
{
    /**
     * Display a listing of the shopping list templates for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ListaCompra::where('id_usuario', $user->id)
            ->with(['status:id,nome', 'produtos' => function ($query) {
                $query->with('categoria:id,nome');
            }]);

        if ($request->has('id_lista_compra_status')) {
            $query->where('id_lista_compra_status', $request->input('id_lista_compra_status'));
        }
        if ($request->has('nome_lista')) {
            $query->where('nome_lista', 'like', '%' . $request->input('nome_lista') . '%');
        }

        $listasCompra = $query->orderBy('created_at', 'desc')->get();
        return response()->json($listasCompra);
    }

    /**
     * Store a newly created shopping list template.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $statusAtiva = ListaCompraStatus::where('nome', 'Ativa')->first();

        if (!$statusAtiva && !$request->has('id_lista_compra_status')) {
            return response()->json(['message' => 'Status padrão "Ativa" não encontrado e nenhum status fornecido.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $messages = [
            'nome_lista.required' => 'O nome da lista é obrigatório.',
            'id_lista_compra_status.exists' => 'O status selecionado é inválido.',
            'produtos.*.id_produto.required' => 'O ID do produto é obrigatório para cada item.',
            'produtos.*.quantidade.required' => 'A quantidade é obrigatória para cada item.',
        ];

        $rules = [
            'nome_lista' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'id_lista_compra_status' => 'sometimes|integer|exists:lista_compra_status,id',
            'produtos' => 'nullable|array',
            'produtos.*.id_produto' => 'required|integer|exists:produtos,id',
            'produtos.*.quantidade' => 'required|numeric|min:0.001',
            'produtos.*.unidade_medida' => 'nullable|string|max:50',
            'produtos.*.observacao' => 'nullable|string|max:255',
            'produtos.*.comprado' => 'sometimes|boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $listaCompra = null;

        DB::transaction(function () use ($validatedData, $user, $statusAtiva, &$listaCompra) {
            $listaCompra = ListaCompra::create([
                'id_usuario' => $user->id,
                'nome_lista' => $validatedData['nome_lista'],
                'descricao' => $validatedData['descricao'] ?? null,
                'id_lista_compra_status' => $validatedData['id_lista_compra_status'] ?? $statusAtiva->id,
            ]);

            if (!empty($validatedData['produtos'])) {
                $produtosParaSincronizar = [];
                foreach ($validatedData['produtos'] as $produtoData) {
                    $produtosParaSincronizar[$produtoData['id_produto']] = [
                        'quantidade' => $produtoData['quantidade'],
                        'unidade_medida' => $produtoData['unidade_medida'] ?? null,
                        'observacao' => $produtoData['observacao'] ?? null,
                        'comprado' => $produtoData['comprado'] ?? false,
                    ];
                }
                $listaCompra->produtos()->sync($produtosParaSincronizar);
            }
        });

        if ($listaCompra === null) {
            return response()->json(['message' => 'Erro ao criar lista de compras.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($listaCompra->load(['status:id,nome', 'produtos']), Response::HTTP_CREATED);
    }

    /**
     * Display the specified shopping list template.
     */
    public function show(int $listaCompraId)
    {
        $listaCompra = ListaCompra::findOrFail($listaCompraId);
        if (!$listaCompra) {
            return response()->json(['message' => 'Lista de compras não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();
        if ($listaCompra->id_usuario !== $user->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }
        return response()->json($listaCompra->load(['status:id,nome', 'usuario:id,name', 'produtos' => function ($query) {
            $query->with('categoria:id,nome');
        }]));
    }

    /**
     * Update the specified shopping list template.
     */
    public function update(Request $request, int $listaCompraId)
    {
        $listaCompra = ListaCompra::findOrFail($listaCompraId);
        if (!$listaCompra) {
            return response()->json(['message' => 'Lista de compras não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();
        if ($listaCompra->id_usuario !== $user->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $messages = [ /* ... mesmas mensagens do store ... */];
        $rules = [
            'nome_lista' => 'sometimes|required|string|max:255',
            'descricao' => 'sometimes|nullable|string',
            'id_lista_compra_status' => 'sometimes|required|integer|exists:lista_compra_status,id',
            'produtos' => 'sometimes|nullable|array',
            'produtos.*.id_produto' => 'required|integer|exists:produtos,id',
            'produtos.*.quantidade' => 'required|numeric|min:0.001',
            'produtos.*.unidade_medida' => 'nullable|string|max:50',
            'produtos.*.observacao' => 'nullable|string|max:255',
            'produtos.*.comprado' => 'sometimes|boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $listaCompra) {
            $dadosLista = [];
            if (isset($validatedData['nome_lista'])) $dadosLista['nome_lista'] = $validatedData['nome_lista'];
            if (array_key_exists('descricao', $validatedData)) $dadosLista['descricao'] = $validatedData['descricao'];
            if (isset($validatedData['id_lista_compra_status'])) $dadosLista['id_lista_compra_status'] = $validatedData['id_lista_compra_status'];

            if (!empty($dadosLista)) {
                $listaCompra->update($dadosLista);
            }

            if (array_key_exists('produtos', $validatedData)) {
                $produtosParaSincronizar = [];
                if (!empty($validatedData['produtos'])) {
                    foreach ($validatedData['produtos'] as $produtoData) {
                        $pivotData = [
                            'quantidade' => $produtoData['quantidade'],
                            'comprado' => $produtoData['comprado'] ?? false,
                        ];
                        // Apenas atualiza unidade_medida e observacao se forem enviados, caso contrário, mantém o valor existente.
                        if (array_key_exists('unidade_medida', $produtoData)) {
                            $pivotData['unidade_medida'] = $produtoData['unidade_medida'];
                        }
                        if (array_key_exists('observacao', $produtoData)) {
                            $pivotData['observacao'] = $produtoData['observacao'];
                        }
                        $produtosParaSincronizar[$produtoData['id_produto']] = $pivotData;
                    }
                }
                $listaCompra->produtos()->sync($produtosParaSincronizar);
            }
        });

        return response()->json($listaCompra->fresh()->load(['status:id,nome', 'produtos']));
    }

    /**
     * Remove the specified shopping list template.
     */
    public function destroy(int $listaCompraId)
    {
        $listaCompra = ListaCompra::findOrFail($listaCompraId);
        if (!$listaCompra) {
            return response()->json(['message' => 'Lista de compras não encontrada.'], Response::HTTP_NOT_FOUND);
        }
        $user = Auth::user();
        if ($listaCompra->id_usuario !== $user->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }
        $listaCompra->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Records a purchase event, creating historical product entries.
     * This action does not modify the shopping list template itself.
     */
    public function registrarEventoDeCompra(Request $request)
    {
        $user = Auth::user();

        $messages = [
            'produtos_comprados.required' => 'A lista de produtos comprados é obrigatória.',
            'produtos_comprados.array' => 'Os produtos comprados devem ser um array.',
            'produtos_comprados.*.id_produto.required' => 'O ID do produto é obrigatório para cada item comprado.',
            'produtos_comprados.*.id_produto.exists' => 'Um ou mais IDs de produto comprados são inválidos.',
            'produtos_comprados.*.quantidade.required' => 'A quantidade comprada é obrigatória.',
            'produtos_comprados.*.quantidade.numeric' => 'A quantidade comprada deve ser um número.',
            'produtos_comprados.*.quantidade.min' => 'A quantidade comprada deve ser maior que zero.',
            'produtos_comprados.*.preco_unitario.required' => 'O preço unitário é obrigatório para cada item comprado.',
            'produtos_comprados.*.preco_unitario.numeric' => 'O preço unitário deve ser um número.',
            'produtos_comprados.*.preco_unitario.min' => 'O preço unitário deve ser maior que zero.',
            'produtos_comprados.*.desconto.numeric' => 'O desconto deve ser um número.',
            'data_compra_efetiva.date_format' => 'A data da compra deve estar no formato AAAA-MM-DD.'
        ];

        $rules = [
            'data_compra_efetiva' => 'sometimes|date_format:Y-m-d',
            'produtos_comprados' => 'required|array|min:1',
            'produtos_comprados.*.id_produto' => 'required|integer|exists:produtos,id',
            'produtos_comprados.*.quantidade' => 'required|numeric|min:0.001',
            'produtos_comprados.*.preco_unitario' => 'required|numeric|min:0.01',
            'produtos_comprados.*.desconto' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $produtosRegistrados = $validatedData['produtos_comprados'];
        $dataCompra = $validatedData['data_compra_efetiva'] ?? now()->format('Y-m-d');

        $historicosCriados = [];

        DB::transaction(function () use ($user, $produtosRegistrados, $dataCompra, &$historicosCriados) {
            foreach ($produtosRegistrados as $itemComprado) {
                $precoTotal = ($itemComprado['quantidade'] * $itemComprado['preco_unitario']) - ($itemComprado['desconto'] ?? 0);
                $historico = ProdutoHistorico::create([
                    'id_usuario' => $user->id,
                    'id_produto' => $itemComprado['id_produto'],
                    'preco_unitario' => $itemComprado['preco_unitario'],
                    'quantidade' => $itemComprado['quantidade'],
                    'preco_total' => $precoTotal,
                    'data_compra' => $dataCompra,
                    'desconto' => $itemComprado['desconto'] ?? null,
                ]);
                $historicosCriados[] = $historico;
            }
        });

        return response()->json([
            'message' => 'Compra registrada com sucesso e histórico de produtos criado.',
            'produtos_historicos' => $historicosCriados
        ], Response::HTTP_CREATED);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaCompra;
use App\Models\ListaCompraStatus;
use App\Models\ListaCompraProduto;
use App\Models\ProdutoHistorico;
use App\Models\Produto;
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
            ->with(['status:id,nome', 'itens.produto.categoria:id,nome']);

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
            return response()->json(['message' => 'Status padrão "Ativa" não encontrado e nenhum status fornecido.'],
            Response::HTTP_INTERNAL_SERVER_ERROR);
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
                foreach ($validatedData['produtos'] as $produtoData) {
                    $listaCompra->itens()->create($produtoData);
                }
            }
        });

        if ($listaCompra === null) {
            return response()->json(['message' => 'Erro ao criar lista de compras.'], 
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(
            $listaCompra->load(
                ['status:id,nome', 'itens.produto']
            ),
            Response::HTTP_CREATED
        );
        // return response()->json($listaCompra->load(['status:id,nome', 'produtos']),
        // Response::HTTP_CREATED);
    }

    /**
     * Display the specified shopping list template.
     */
    public function show(int $listaCompraId)
    {
        $user = Auth::user();
        $listaCompra = ListaCompra::with(['status:id,nome', 'usuario:id,name', 'itens.produto.categoria:id,nome'])
            ->where('id_usuario', $user->id)
            ->findOrFail($listaCompraId);

        return response()->json($listaCompra);
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
            'id_lista_compra_status' => 'sometimes|integer|exists:lista_compra_status,id',
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

        DB::transaction(function () use ($validatedData, $listaCompra,$request) {
            $listaCompra->update($request->only(['nome_lista', 'descricao', 'id_lista_compra_status']));

            if (array_key_exists('produtos', $validatedData)) {
                $listaCompra->itens()->delete();
                if (!empty($validatedData['produtos'])) {
                    foreach ($validatedData['produtos'] as $produtoData) {
                        $listaCompra->itens()->create($produtoData);
                    }
                }
            }
        });

        return response()->json($listaCompra->fresh()->load(['status:id,nome', 'itens.produto']));
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
            'id_lista_compra.required' => 'O ID da lista de compras é obrigatório.',
            'id_lista_compra.exists' => 'A lista de compras especificada não existe.',
            'data_compra_efetiva.date_format' => 'A data da compra deve estar no formato AAAA-MM-DD.',
            'detalhes_produtos.required' => 'Os detalhes dos produtos comprados são obrigatórios.',
            'detalhes_produtos.array' => 'Os detalhes dos produtos devem ser um array.',
            'detalhes_produtos.*.id_produto.required' => 'O ID do produto é obrigatório para cada item.',
            'detalhes_produtos.*.id_produto.integer' => 'O ID do produto deve ser um número inteiro.',
            'detalhes_produtos.*.preco_unitario.required' => 'O preço unitário é obrigatório para cada item.',
            'detalhes_produtos.*.preco_unitario.numeric' => 'O preço unitário deve ser numérico.',
            'detalhes_produtos.*.preco_unitario.min' => 'O preço unitário deve ser no mínimo 0.01.',
            'detalhes_produtos.*.desconto.numeric' => 'O desconto deve ser numérico.',
            'detalhes_produtos.*.desconto.min' => 'O desconto deve ser no mínimo 0.',
        ];

        $rules = [
            'id_lista_compra' => 'required|integer|exists:listas_compras,id',
            'data_compra_efetiva' => 'sometimes|date_format:Y-m-d',
            'detalhes_produtos' => 'required|array|min:1',
            'detalhes_produtos.*.id_produto' => 'required|integer|exists:produtos,id', // Basic existence check
            'detalhes_produtos.*.preco_unitario' => 'required|numeric|min:0.01',
            'detalhes_produtos.*.desconto' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $listaCompraId = $validatedData['id_lista_compra'];
        $dataCompra = $validatedData['data_compra_efetiva'] ?? now()->format('Y-m-d');
        $detalhesProdutosInput = $validatedData['detalhes_produtos'];

        $listaCompra = ListaCompra::with('produtos')->findOrFail($listaCompraId); // Eager load products

        if ($listaCompra->id_usuario !== $user->id) {
            return response()->json(['message' => 'Acesso não autorizado a esta lista de compras.'], Response::HTTP_FORBIDDEN);
        }

        $historicosCriados = [];
        $errosProcessamento = [];

        // Create a map of products in the shopping list for efficient lookup and to get their quantities
        $produtosNaListaMap = [];
        foreach ($listaCompra->produtos as $produtoNaLista) {
            $produtosNaListaMap[$produtoNaLista->id] = [
                'quantidade' => $produtoNaLista->pivot->quantidade, // Get quantity from pivot
                'unidade_medida' => $produtoNaLista->pivot->unidade_medida
            ];
        }

        DB::transaction(function () use (
            $user,
            $produtosNaListaMap,
            $detalhesProdutosInput,
            $dataCompra,
            &$historicosCriados,
            &$errosProcessamento
        ) {
            foreach ($detalhesProdutosInput as $itemDetalhe) {
                $produtoId = $itemDetalhe['id_produto'];

                if (!isset($produtosNaListaMap[$produtoId])) {
                    $errosProcessamento[] = "Produto com ID {$produtoId} não faz parte da lista de compras especificada ou já foi processado.";
                    continue; // Skip this item
                }

                $infoProdutoLista = $produtosNaListaMap[$produtoId];
                $quantidadeDaLista = $infoProdutoLista['quantidade'];

                if ($quantidadeDaLista <= 0) {
                    $errosProcessamento[] = "Produto com ID {$produtoId} na lista de compras possui quantidade zero ou inválida e não será registrado no histórico.";
                    continue;
                }

                $precoUnitario = $itemDetalhe['preco_unitario'];
                $desconto = $itemDetalhe['desconto'] ?? 0;
                $precoTotal = ($quantidadeDaLista * $precoUnitario) - $desconto;

                $historico = ProdutoHistorico::create([
                    'id_usuario' => $user->id,
                    'id_produto' => $produtoId,
                    'preco_unitario' => $precoUnitario,
                    'quantidade' => $quantidadeDaLista, // Use quantity from the shopping list's pivot data
                    'preco_total' => $precoTotal,
                    'data_compra' => $dataCompra,
                    'desconto' => $desconto,
                    // 'id_lista_compra_origem' => $listaCompra->id, // Optional: if you add this column to ProdutoHistorico
                ]);
                $historicosCriados[] = $historico;
            }
        });

        if (!empty($errosProcessamento)) {
            // Decide on the response: partial success or failure if crucial items failed
            $responseStatus = count($historicosCriados) > 0 ? Response::HTTP_ACCEPTED : Response::HTTP_BAD_REQUEST;
            return response()->json([
                'message' => 'Evento de compra processado com observações.',
                'erros' => $errosProcessamento,
                'produtos_historicos_criados' => $historicosCriados
            ], $responseStatus);
        }

        return response()->json([
            'message' => 'Histórico de compra registrado com sucesso para os produtos da lista.',
            'lista_compra_id' => $listaCompra->id,
            'produtos_historicos_criados' => $historicosCriados
        ], Response::HTTP_CREATED);
    }
}

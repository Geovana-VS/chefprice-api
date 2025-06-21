<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\ProdutoHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class ProdutoHistoricoController extends Controller
{
    public function index(Request $request)
    {
        $query = ProdutoHistorico::query();
        if ($request->has('id_produto')) {
            $query->where('id_produto', $request->input('id_produto'));
        }
        if ($request->has('id_usuario')) {
            $query->where('id_usuario', $request->input('id_usuario'));
        }
        $historicos = $query->with(['produto', 'usuario'])->latest()->get();
        return response()->json($historicos);
    }

    public function store(Request $request)
    {
        $messages = [
            'id_produto.required' => 'O produto é obrigatório.',
            'id_produto.exists' => 'O produto selecionado é inválido.',
            'id_usuario.exists' => 'O usuário selecionado é inválido.',
            'preco_unitario.numeric' => 'O preço unitário deve ser numérico.',
            'quantidade.numeric' => 'A quantidade deve ser numérica.',
            'preco_total.numeric' => 'O preço total deve ser numérico.',
            'desconto.numeric' => 'O desconto deve ser numérico.',
            'data_compra.date' => 'A data da compra deve ser uma data válida.',
        ];

        $rules = [
            'id_usuario' => 'nullable|integer|exists:users,id',
            'id_produto' => 'required|integer|exists:produtos,id',
            'preco_unitario' => 'nullable|numeric',
            'quantidade' => 'nullable|numeric',
            'preco_total' => 'nullable|numeric',
            'data_compra' => 'nullable|date',
            'desconto' => 'nullable|numeric',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $historico = ProdutoHistorico::create($validator->validated());

        return response()->json($historico->load(['produto', 'usuario']), Response::HTTP_CREATED);
    }

    public function show(ProdutoHistorico $produtoHistorico)
    {
        return response()->json($produtoHistorico->load(['produto', 'usuario']));
    }

    public function update(Request $request, ProdutoHistorico $produtoHistorico)
    {
        //Não é recomendado atualizar o histórico diretamente.
    }

    public function destroy(ProdutoHistorico $produtoHistorico)
    {
        // Não é recomendado deletar o histórico diretamente.
    }

    public function getHistoricoPreco($idProduto)
    {
        $preco = ProdutoHistorico::where('id_produto', $idProduto)
        ->orderBy('data_compra', 'desc')
        ->first(['preco_unitario']);

        if (!$preco) {
            $preco = Produto::where('id', $idProduto)->first(['preco_padrao as preco_unitario']);
        }

        return response()->json($preco);
    }
}

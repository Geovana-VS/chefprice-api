<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IngredienteReceita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class IngredienteReceitaController extends Controller
{
    public function index(Request $request)
    {
        $query = IngredienteReceita::query();
        if ($request->has('id_receita')) {
            $query->where('id_receita', $request->input('id_receita'));
        }
        $ingredientes = $query->with(['produto'])->get();
        return response()->json($ingredientes);
    }

    public function store(Request $request)
    {
        $messages = [
            'id_receita.required' => 'A receita é obrigatória.',
            'id_receita.exists' => 'A receita selecionada é inválida.',
            'id_produto.required' => 'O produto é obrigatório.',
            'id_produto.exists' => 'O produto selecionado é inválido.',
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.numeric' => 'A quantidade deve ser um número.',
            'unidade.required' => 'A unidade é obrigatória.',
        ];

        $rules = [
            'id_receita' => 'required|integer|exists:receitas,id',
            'id_produto' => 'required|integer|exists:produtos,id',
            'quantidade' => 'required|numeric',
            'unidade' => 'required|string|max:50',
            'observacoes' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ingrediente = IngredienteReceita::create($validator->validated());

        return response()->json($ingrediente->load('produto'), Response::HTTP_CREATED);
    }

    public function show(IngredienteReceita $IngredienteReceita) // {IngredienteReceita}
    {
        $ingrediente = $IngredienteReceita; // Rename
        return response()->json($ingrediente->load('produto'));
    }

    public function update(Request $request, IngredienteReceita $IngredienteReceita)
    {
        $ingrediente = $IngredienteReceita; // Rename

        $messages = [
             // Add messages as needed, similar to store
            'id_receita.exists' => 'A receita selecionada é inválida.',
            'id_produto.exists' => 'O produto selecionado é inválido.',
            'quantidade.numeric' => 'A quantidade deve ser um número.',
        ];

        $rules = [
            'id_receita' => 'sometimes|required|integer|exists:receitas,id',
            'id_produto' => 'sometimes|required|integer|exists:produtos,id',
            'quantidade' => 'sometimes|required|numeric',
            'unidade' => 'sometimes|required|string|max:50',
            'observacoes' => 'sometimes|nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ingrediente->update($validator->validated());

        return response()->json($ingrediente->load('produto'));
    }

    public function destroy(IngredienteReceita $IngredienteReceita)
    {
        $ingrediente = $IngredienteReceita; // Rename
        $ingrediente->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
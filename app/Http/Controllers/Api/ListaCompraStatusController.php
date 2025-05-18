<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaCompraStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ListaCompraStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = ListaCompraStatus::orderBy('nome')->get();
        return response()->json($statuses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'nome.required' => 'O nome do status é obrigatório.',
            'nome.unique' => 'Este nome de status já existe.',
            'nome.max' => 'O nome do status não pode ter mais que :max caracteres.',
            'descricao.string' => 'A descrição deve ser um texto.',
        ];

        $rules = [
            'nome' => 'required|string|max:50|unique:lista_compra_status,nome',
            'descricao' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $status = ListaCompraStatus::create($validator->validated());

        return response()->json($status, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(ListaCompraStatus $listaCompraStatus)
    {
        return response()->json($listaCompraStatus);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ListaCompraStatus $listaCompraStatus)
    {
        $messages = [
            'nome.required' => 'O nome do status é obrigatório.',
            'nome.unique' => 'Este nome de status já existe.',
            'nome.max' => 'O nome do status não pode ter mais que :max caracteres.',
            'descricao.string' => 'A descrição deve ser um texto.',
        ];

        $rules = [
            'nome' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('lista_compra_status', 'nome')->ignore($listaCompraStatus->id),
            ],
            'descricao' => 'sometimes|nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $listaCompraStatus->update($validator->validated());

        return response()->json($listaCompraStatus);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ListaCompraStatus $listaCompraStatus)
    {
        if ($listaCompraStatus->listaCompra()->exists()) {
            return response()->json(['message' => 'Não é possível excluir o status, pois ele está associado a uma ou mais listas de compras.'], Response::HTTP_CONFLICT);
        }

        $listaCompraStatus->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
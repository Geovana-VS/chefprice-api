<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoImagem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class TipoImagemController extends Controller
{
    public function index()
    {
        $tipos = TipoImagem::orderBy('nome')->get();
        return response()->json($tipos);
    }

    public function store(Request $request)
    {
        $messages = [
            'nome.required' => 'O nome do tipo é obrigatório.',
            'nome.unique' => 'Este tipo de imagem já existe.',
            'nome.max' => 'O nome do tipo não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:50|unique:tipo_imagens,nome',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tipo = TipoImagem::create($validator->validated());

        return response()->json($tipo, Response::HTTP_CREATED);
    }

    public function show(TipoImagem $tipoImagem) // {tipoImagem}
    {
        return response()->json($tipoImagem);
    }

    public function update(Request $request, TipoImagem $tipoImagem)
    {
         $messages = [
             'nome.required' => 'O nome do tipo é obrigatório.',
            'nome.unique' => 'Este tipo de imagem já existe.',
            'nome.max' => 'O nome do tipo não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:50|unique:tipo_imagens,nome,' . $tipoImagem->id,
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tipoImagem->update($validator->validated());

        return response()->json($tipoImagem);
    }

    public function destroy(TipoImagem $tipoImagem)
    {
        // Check if type is associated with any images
         if ($tipoImagem->imagens()->exists()) {
             return response()->json(['message' => 'Não pode deletar tipo associado a imagens.'], Response::HTTP_CONFLICT);
        }

        $tipoImagem->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
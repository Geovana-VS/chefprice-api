<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoImagem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    public function show(int $idTipoImagem)
    {
        TipoImagem::where('id', $idTipoImagem)->firstOrFail();
        $tipoImagem = TipoImagem::find($idTipoImagem);
        if (!$tipoImagem) {
            return response()->json(['message' => 'Tipo de imagem ' . $tipoImagem . ' não encontrado.'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($tipoImagem);
    }

    public function update(Request $request, int $idTipoImagem)
    {
        $tipoImagem = TipoImagem::findOrFail($idTipoImagem);

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

    public function destroy(int $idTipoImagem)
    {
        try {
            $tipoImagem = TipoImagem::findOrFail($idTipoImagem);

            if ($tipoImagem->imagens()->exists()) {
                return response()->json(
                    ['message' => 'Não pode deletar o tipo de imagem pois está associado a uma ou mais imagens.'],
                    Response::HTTP_CONFLICT // 409
                );
            }

            $deleted = $tipoImagem->delete(); 

            if ($deleted) {
                return response()->json(null, Response::HTTP_NO_CONTENT); // 204
            } else {
                return response()->json(
                    ['message' => 'O tipo de imagem não pôde ser deletado. Verifique os logs ou eventos do modelo.'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(
                ['message' => 'Tipo de imagem com ID ' . $idTipoImagem . ' não encontrado.'],
                Response::HTTP_NOT_FOUND // 404
            );
        }
    }
}

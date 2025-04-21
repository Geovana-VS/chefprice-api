<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceitaTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class ReceitaTagController extends Controller
{
    public function index()
    {
        $tags = ReceitaTag::orderBy('nome')->get();
        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $messages = [
            'nome.required' => 'O nome da tag é obrigatório.',
            'nome.unique' => 'Esta tag já existe.',
            'nome.max' => 'O nome da tag não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:100|unique:receita_tags,nome',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tag = ReceitaTag::create($validator->validated());

        return response()->json($tag, Response::HTTP_CREATED);
    }

    public function show(ReceitaTag $receitaTag) // {receitaTag}
    {
        return response()->json($receitaTag);
    }

    public function update(Request $request, ReceitaTag $receitaTag)
    {
         $messages = [
            'nome.required' => 'O nome da tag é obrigatório.',
            'nome.unique' => 'Esta tag já existe.',
            'nome.max' => 'O nome da tag não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:100|unique:receita_tags,nome,' . $receitaTag->id,
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $receitaTag->update($validator->validated());

        return response()->json($receitaTag);
    }

    public function destroy(ReceitaTag $receitaTag)
    {
        if ($receitaTag->receitas()->exists()) {
             return response()->json(['message' => 'Não pode deletar tag associada a receitas.'], Response::HTTP_CONFLICT);
        }

        $receitaTag->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtapaReceita;
use App\Models\Receita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class EtapaReceitaController extends Controller
{
    public function index(Request $request)
    {
        $query = EtapaReceita::query();
        if ($request->has('id_receita')) {
            $query->where('id_receita', $request->input('id_receita'));
        }
        $etapas = $query->orderBy('numero_etapa')->get();
        return response()->json($etapas);
    }

    public function store(Request $request)
    {
        $messages = [
            'id_receita.required' => 'A receita é obrigatória.',
            'id_receita.exists' => 'A receita selecionada é inválida.',
            'numero_etapa.required' => 'O número da etapa é obrigatório.',
            'numero_etapa.integer' => 'O número da etapa deve ser um inteiro.',
            'instrucoes.required' => 'As instruções são obrigatórias.',
        ];

        $rules = [
            'id_receita' => 'required|integer|exists:receitas,id',
            'numero_etapa' => [
                'required',
                'integer',
                Rule::unique('etapa_receitas')->where(function ($query) use ($request) {
                    return $query->where('id_receita', $request->input('id_receita'));
                }),
            ],
            'instrucoes' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapa = EtapaReceita::create($validator->validated());

        return response()->json($etapa, Response::HTTP_CREATED);
    }

    public function show(EtapaReceita $etapaReceita)
    {
        return response()->json($etapaReceita);
    }

    public function update(Request $request, EtapaReceita $etapaReceita)
    {
        $etapa = $etapaReceita;

        $messages = [
            'id_receita.required' => 'A receita é obrigatória.',
            'id_receita.exists' => 'A receita selecionada é inválida.',
            'numero_etapa.required' => 'O número da etapa é obrigatório.',
            'numero_etapa.integer' => 'O número da etapa deve ser um inteiro.',
            'instrucoes.required' => 'As instruções são obrigatórias.',
        ];

        $rules = [
            'id_receita' => 'required|integer|exists:receitas,id',
            'numero_etapa' => [
                'required',
                'integer',
                Rule::unique('etapa_receitas')->where(function ($query) use ($request) {
                    return $query->where('id_receita', $request->input('id_receita'));
                })->ignore($etapa->id),
            ],
            'instrucoes' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapa->update($validator->validated());

        return response()->json($etapa);
    }

    public function destroy(EtapaReceita $etapaReceita)
    {
        $etapaReceita->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
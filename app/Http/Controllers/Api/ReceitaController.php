<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceitaController extends Controller
{
    public function index()
    {
        $receitas = Receita::with(['usuario', 'tags', 'imagens'])->latest()->get();
        return response()->json($receitas);
    }

    public function store(Request $request)
    {
        $messages = [
            'titulo.required' => 'O título é obrigatório.',
            'titulo.max' => 'O título não pode ter mais que :max caracteres.',
            'tags.array' => 'As tags devem ser um array.',
            'tags.*.exists' => 'Uma ou mais tags selecionadas são inválidas.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.',
            'etapas.array' => 'As etapas devem ser um array.',
            'etapas.*.numero_etapa.required' => 'O número da etapa é obrigatório para todas as etapas.',
            'etapas.*.numero_etapa.integer' => 'O número da etapa deve ser um inteiro.',
            'etapas.*.instrucoes.required' => 'As instruções são obrigatórias para todas as etapas.',
            'ingredientes.array' => 'Os ingredientes devem ser um array.',
            'ingredientes.*.id_produto.required' => 'O ID do produto é obrigatório para todos os ingredientes.',
            'ingredientes.*.id_produto.exists' => 'Um ou mais IDs de produto são inválidos.',
            'ingredientes.*.quantidade.required' => 'A quantidade é obrigatória para todos os ingredientes.',
            'ingredientes.*.quantidade.numeric' => 'A quantidade deve ser um número.',
            'ingredientes.*.unidade.required' => 'A unidade é obrigatória para todos os ingredientes.',
        ];

        $rules = [
            'titulo' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'rendimento' => 'nullable|string|max:100',
            'tempo_preparo' => 'nullable|string|max:100',

            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:receita_tags,id',
            'imagens' => 'nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
            'etapas' => 'nullable|array',
            'etapas.*.numero_etapa' => 'required|integer|min:1',
            'etapas.*.instrucoes' => 'required|string',
            'ingredientes' => 'nullable|array',
            'ingredientes.*.id_produto' => 'required|integer|exists:produtos,id',
            'ingredientes.*.quantidade' => 'required|numeric',
            'ingredientes.*.unidade' => 'required|string|max:50',
            'ingredientes.*.observacoes' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $receita = null;

        DB::transaction(function () use ($validatedData, &$receita) {
            $receitaData = [
                'id_usuario' => Auth::id(),
                'titulo' => $validatedData['titulo'],
                'descricao' => $validatedData['descricao'] ?? null,
                'rendimento' => $validatedData['rendimento'] ?? null,
                'tempo_preparo' => $validatedData['tempo_preparo'] ?? null,
            ];
            $receita = Receita::create($receitaData);

            if (!empty($validatedData['tags'])) {
                $receita->tags()->attach($validatedData['tags']);
            }

            if (!empty($validatedData['imagens'])) {
                $receita->imagens()->attach($validatedData['imagens']);
            }

            if (!empty($validatedData['etapas'])) {
                foreach ($validatedData['etapas'] as $etapaData) {
                    $receita->etapas()->create([
                        'numero_etapa' => $etapaData['numero_etapa'],
                        'instrucoes' => $etapaData['instrucoes'],
                    ]);
                }
            }

            if (!empty($validatedData['ingredientes'])) {
                foreach ($validatedData['ingredientes'] as $ingredienteData) {
                    $receita->ingredientes()->create([
                        'id_produto' => $ingredienteData['id_produto'],
                        'quantidade' => $ingredienteData['quantidade'],
                        'unidade' => $ingredienteData['unidade'],
                        'observacoes' => $ingredienteData['observacoes'] ?? null,
                    ]);
                }
            }
        });

        if($receita === null) {
            return response()->json(['message' => 'Erro ao criar receita'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json($receita->load(['usuario', 'tags', 'imagens', 'etapas', 'ingredientes.produto']), Response::HTTP_CREATED);
    }

    public function show(Receita $receita)
    {
        return response()->json($receita->load([
            'usuario',
            'ingredientes.produto',
            'etapas',
            'tags',
            'imagens'
        ]));
    }

    public function update(Request $request, Receita $receita)
    {
        if ($receita->id_usuario !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $messages = [
            'titulo.required' => 'O título é obrigatório.',
            'titulo.max' => 'O título não pode ter mais que :max caracteres.',
            'tags.array' => 'As tags devem ser um array.',
            'tags.*.exists' => 'Uma ou mais tags selecionadas são inválidas.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.',
            'etapas.array' => 'As etapas devem ser um array.',
            'etapas.*.numero_etapa.required' => 'O número da etapa é obrigatório para todas as etapas.',
            'etapas.*.numero_etapa.integer' => 'O número da etapa deve ser um inteiro.',
            'etapas.*.instrucoes.required' => 'As instruções são obrigatórias para todas as etapas.',
            'ingredientes.array' => 'Os ingredientes devem ser um array.',
            'ingredientes.*.id_produto.required' => 'O ID do produto é obrigatório para todos os ingredientes.',
            'ingredientes.*.id_produto.exists' => 'Um ou mais IDs de produto são inválidos.',
            'ingredientes.*.quantidade.required' => 'A quantidade é obrigatória para todos os ingredientes.',
            'ingredientes.*.quantidade.numeric' => 'A quantidade deve ser um número.',
            'ingredientes.*.unidade.required' => 'A unidade é obrigatória para todos os ingredientes.',
        ];

        $rules = [
            'titulo' => 'sometimes|required|string|max:200',
            'descricao' => 'sometimes|nullable|string',
            'rendimento' => 'sometimes|nullable|string|max:100',
            'tempo_preparo' => 'sometimes|nullable|string|max:100',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'integer|exists:receita_tags,id',
            'imagens' => 'sometimes|nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
            'etapas' => 'sometimes|nullable|array',
            'etapas.*.numero_etapa' => 'required|integer|min:1',
            'etapas.*.instrucoes' => 'required|string',
            'ingredientes' => 'sometimes|nullable|array',
            'ingredientes.*.id_produto' => 'required|integer|exists:produtos,id',
            'ingredientes.*.quantidade' => 'required|numeric',
            'ingredientes.*.unidade' => 'required|string|max:50',
            'ingredientes.*.observacoes' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $receita) {
            // 1. Update the main Receita fields
            $receitaData = [];
            if (isset($validatedData['titulo'])) $receitaData['titulo'] = $validatedData['titulo'];
            if (array_key_exists('descricao', $validatedData)) $receitaData['descricao'] = $validatedData['descricao']; // Handle potential null
            if (array_key_exists('rendimento', $validatedData)) $receitaData['rendimento'] = $validatedData['rendimento'];
            if (array_key_exists('tempo_preparo', $validatedData)) $receitaData['tempo_preparo'] = $validatedData['tempo_preparo'];

            if (!empty($receitaData)) {
                $receita->update($receitaData);
            }

            if (array_key_exists('tags', $validatedData)) {
                $receita->tags()->sync($validatedData['tags'] ?? []);
            }

             if (array_key_exists('imagens', $validatedData)) {
                $receita->imagens()->sync($validatedData['imagens'] ?? []);
            }

             if (array_key_exists('etapas', $validatedData)) {
                 $receita->etapas()->delete();
                 if (!empty($validatedData['etapas'])) {
                     foreach ($validatedData['etapas'] as $etapaData) {
                        $receita->etapas()->create([
                            'numero_etapa' => $etapaData['numero_etapa'],
                            'instrucoes' => $etapaData['instrucoes'],
                        ]);
                    }
                 }
             }

             if (array_key_exists('ingredientes', $validatedData)) {
                 $receita->ingredientes()->delete();
                 if (!empty($validatedData['ingredientes'])) {
                     foreach ($validatedData['ingredientes'] as $ingredienteData) {
                        $receita->ingredientes()->create([
                            'id_produto' => $ingredienteData['id_produto'],
                            'quantidade' => $ingredienteData['quantidade'],
                            'unidade' => $ingredienteData['unidade'],
                            'observacoes' => $ingredienteData['observacoes'] ?? null,
                        ]);
                    }
                 }
             }
        });

        return response()->json($receita->fresh()->load(['usuario', 'tags', 'imagens', 'etapas', 'ingredientes.produto']));
    }

    public function destroy(Receita $receita)
    {

        if ($receita->id_usuario !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($receita) {
            $receita->tags()->detach();
            $receita->imagens()->detach();

            $receita->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
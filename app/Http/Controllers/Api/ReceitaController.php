<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ReceitaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Receita::query();

        $query->where(function ($q) use ($user) {
            $q->where('is_public', true);
            if ($user) {
                $q->orWhere('id_usuario', $user->id);
            }
        });

        $query->with(['usuario:id,name', 'ingredientes', 'etapas', 'tags', 'imagens']); // Carrega relacionamentos

        $receitas = $query->orderBy('created_at', 'desc')->get();

        return response()->json($receitas);
    }

    public function store(Request $request)
    {
        //Mensagens e regras de validação
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
            'custos_adicionais' => 'O custo adicional deve ser um número.',
            'lucro_esperado' => 'O lucro esperado deve ser um número.'
        ];

        $rules = [
            'titulo' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'rendimento' => 'nullable|string|max:100',
            'tempo_preparo' => 'nullable|string|max:100',
            'custos_adicionais' => 'nullable|numeric|min:0',
            'lucro_esperado' => 'nullable|numeric|min:0',

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

        //Validação dos dados
        $validator = Validator::make($request->all(), $rules, $messages);

        //Verifica se a validação falhou
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $receita = null;

        //Inicia uma transação para garantir a integridade dos dados
        DB::transaction(function () use ($validatedData, &$receita) {
            $receitaData = [
                'id_usuario' => Auth::id(),
                'titulo' => $validatedData['titulo'],
                'descricao' => $validatedData['descricao'] ?? null,
                'rendimento' => $validatedData['rendimento'] ?? null,
                'tempo_preparo' => $validatedData['tempo_preparo'] ?? null,
                'custos_adicionais' => $validatedData['custos_adicionais'] ?? 0,
                'lucro_esperado' => $validatedData['lucro_esperado'] ?? 0,
            ];
            // Cria a receita
            $receita = Receita::create($receitaData);

            // Adiciona os relacionamentos de tags, imagens, etapas e ingredientes se existirem
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

        if ($receita === null) {
            return response()->json(
                ['message' => 'Erro ao criar receita'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        return response()->json(
            $receita->load(
                ['usuario', 'tags', 'imagens', 'etapas', 'ingredientes.produto']
            ),
            Response::HTTP_CREATED
        );
    }

    public function show(Request $request, string $id)
    {
        try {
            $receita = Receita::with([
                'usuario:id,name',
                'ingredientes',
                'etapas',
                'tags:id,nome',
                'imagens'
            ])->findOrFail($id);

            $user = $request->user();

            if ($receita->is_public || ($user && $user->id === $receita->id_usuario)) {
                return response()->json($receita);
            } else {
                // Se não for pública e não for do usuário, retorna não autorizado
                return response()->json(['message' => 'Acesso não autorizado a esta receita.'], 403); // 403 Forbidden
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Receita não encontrada.'], 404);
        }
    }

    public function update(Request $request, Receita $receita)
    {
        //Verifica se a receita pertence ao usuário autenticado
        if ($receita->id_usuario != Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], Response::HTTP_FORBIDDEN);
        }

        //Mensagens e regras de validação
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
            'custos_adicionais' => 'O custo adicional deve ser um número.',
            'lucro_esperado' => 'O lucro esperado deve ser um número.'
        ];

        $rules = [
            'titulo' => 'sometimes|required|string|max:200',
            'descricao' => 'sometimes|nullable|string',
            'rendimento' => 'sometimes|nullable|string|max:100',
            'tempo_preparo' => 'sometimes|nullable|string|max:100',
            'custos_adicionais' => 'nullable|numeric|min:0',
            'lucro_esperado' => 'nullable|numeric|min:0',
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
            'is_public' => 'sometimes|boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        //Verifica se a validação falhou
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        //Inicia uma transação para garantir a integridade dos dados
        DB::transaction(function () use ($validatedData, $receita) {
            // Atualiza os dados da receita caso existam
            $receitaData = [];
            if (isset($validatedData['titulo'])) $receitaData['titulo'] = $validatedData['titulo'];
            if (array_key_exists('descricao', $validatedData)) $receitaData['descricao'] = $validatedData['descricao'];
            if (array_key_exists('rendimento', $validatedData)) $receitaData['rendimento'] = $validatedData['rendimento'];
            if (array_key_exists('tempo_preparo', $validatedData)) $receitaData['tempo_preparo'] = $validatedData['tempo_preparo'];
            if (array_key_exists('is_public', $validatedData)) $receitaData['is_public'] = $validatedData['is_public'];
            if (array_key_exists('custos_adicionais', $validatedData)) $receitaData['custos_adicionais'] = $validatedData['custos_adicionais'];
            if (array_key_exists('lucro_esperado', $validatedData)) $receitaData['lucro_esperado'] = $validatedData['lucro_esperado'];

            if (!empty($receitaData)) {
                $receita->update($receitaData);
            }

            // Atualiza os relacionamentos de tags, imagens, etapas e ingredientes se existirem
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

        return response()->json(
            $receita->fresh()->load(
                ['usuario', 'tags', 'imagens', 'etapas', 'ingredientes.produto']
            )
        );
    }

    public function destroy(Receita $receita)
    {
        //Deleta uma receita específica

        //Verifica se a receita pertence ao usuário autenticado
        if ($receita->id_usuario != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        //Inicia uma transação para garantir a integridade dos dados
        //Remove os relacionamentos de tags e imagens
        DB::transaction(function () use ($receita) {
            $receita->tags()->detach();
            $receita->imagens()->detach();

            $receita->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function clone(Request $request, string $id)
    {
        $user = $request->user(); // Usuário autenticado que está clonando

        try {
            // Encontra a receita original com seus relacionamentos
            $originalReceita = Receita::with(['ingredientes', 'etapas', 'tags', 'imagens'])->findOrFail($id);

            if (!$originalReceita->is_public || !$originalReceita->id_usuario == $user->id) {
                return response()->json(['message' => 'Você só pode clonar receitas públicas.'], 403);
            }

            $clonedReceita = DB::transaction(function () use ($originalReceita, $user) {


                $newReceita = $originalReceita->replicate();


                $newReceita->id_usuario = $user->id;
                $newReceita->is_public = false;
                $newReceita->titulo = '[Cópia] ' . $originalReceita->titulo;
                $newReceita->created_at = now();
                $newReceita->updated_at = now();
                $newReceita->push();

                foreach ($originalReceita->ingredientes as $ingrediente) {
                    $newIngrediente = $ingrediente->replicate();
                    $newIngrediente->id_receita = $newReceita->id; // Associa ao novo ID da receita
                    $newIngrediente->save();
                }

                foreach ($originalReceita->etapas as $etapa) {
                    $newEtapa = $etapa->replicate();
                    $newEtapa->id_receita = $newReceita->id; // Associa ao novo ID da receita
                    $newEtapa->save();
                }

                // Associa relacionamentos BelongsToMany (Tags, Imagens)
                // Apenas copia as associações, não duplica as tags/imagens em si
                $tagIds = $originalReceita->tags->pluck('id');
                $newReceita->tags()->attach($tagIds);

                $imagemIds = $originalReceita->imagens->pluck('id');
                $newReceita->imagens()->attach($imagemIds);

                return $newReceita; // Retorna a receita clonada de dentro da transação
            });

            // Carrega os relacionamentos na receita clonada para retornar a resposta completa
            $clonedReceita->load(['usuario:id,name', 'ingredientes', 'etapas', 'tags', 'imagens']);

            return response()->json($clonedReceita, 201); // 201 Created

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Receita original não encontrada.'], 404);
        } catch (\Exception $e) {
            // Log do erro para depuração
            Log::error('Erro ao clonar receita: ' . $e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro ao clonar a receita.'], 500); // Internal Server Error
        }
    }
}

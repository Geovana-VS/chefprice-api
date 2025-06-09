<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ColecaoReceita;
use App\Models\Receita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class ColecaoReceitaController extends Controller
{
    /**
     * Display a listing of the user's recipe collections.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ColecaoReceita::where('id_usuario', $user->id)
            ->with('receitas:id,titulo'); // Carrega IDs e títulos das receitas

        if ($request->has('nome')) {
            $query->where('nome', 'like', '%' . $request->input('nome') . '%');
        }

        $colecoes = $query->orderBy('created_at', 'desc')->get();
        return response()->json($colecoes);
    }

    /**
     * Store a newly created recipe collection in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $messages = [
            'nome.required' => 'O nome da coleção é obrigatório.',
            'nome.max' => 'O nome da coleção não pode ter mais que :max caracteres.',
            'receitas.array' => 'As receitas devem ser um array de IDs.',
            'receitas.*.integer' => 'Cada ID de receita deve ser um número inteiro.',
            'receitas.*.exists' => 'Uma ou mais receitas selecionadas são inválidas ou não pertencem a você/não são públicas.',
        ];

        $rules = [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'receitas' => 'nullable|array',
            // Validação para garantir que o usuário pode adicionar a receita (ou é dele ou é pública)
            'receitas.*' => [
                'integer',
                Rule::exists('receitas', 'id')->where(function ($query) use ($user) {
                    $query->where('is_public', true)->orWhere('id_usuario', $user->id);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $colecao = null;

        DB::transaction(function () use ($validatedData, $user, &$colecao) {
            $colecao = ColecaoReceita::create([
                'id_usuario' => $user->id,
                'nome' => $validatedData['nome'],
                'descricao' => $validatedData['descricao'] ?? null,
            ]);

            if (!empty($validatedData['receitas'])) {
                $colecao->receitas()->sync($validatedData['receitas']);
            }
        });

        if ($colecao === null) {
            return response()->json(['message' => 'Erro ao criar coleção de receitas.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($colecao->load('receitas:id,titulo'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified recipe collection.
     */
    public function show(int $id)
    {
        try {
            $colecao = ColecaoReceita::with('usuario:id,name', 'receitas')->findOrFail($id);
            $user = Auth::user();

            if ($colecao->id_usuario !== $user->id) {
                return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
            }
            return response()->json($colecao);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coleção de receitas não encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified recipe collection in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $colecao = ColecaoReceita::findOrFail($id);
            $user = Auth::user();

            if ($colecao->id_usuario !== $user->id) {
                return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
            }

            $messages = [
                'nome.required' => 'O nome da coleção é obrigatório.',
                'nome.max' => 'O nome da coleção não pode ter mais que :max caracteres.',
                'receitas.array' => 'As receitas devem ser um array de IDs.',
                'receitas.*.integer' => 'Cada ID de receita deve ser um número inteiro.',
                'receitas.*.exists' => 'Uma ou mais receitas selecionadas são inválidas ou não pertencem a você/não são públicas.',
            ];
            $rules = [
                'nome' => 'sometimes|required|string|max:255',
                'descricao' => 'sometimes|nullable|string',
                'receitas' => 'sometimes|nullable|array',
                'receitas.*' => [
                    'integer',
                    Rule::exists('receitas', 'id')->where(function ($query) use ($user) {
                        $query->where('is_public', true)->orWhere('id_usuario', $user->id);
                    }),
                ],
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validatedData = $validator->validated();

            DB::transaction(function () use ($validatedData, $colecao) {
                $dadosColecao = [];
                if (isset($validatedData['nome'])) $dadosColecao['nome'] = $validatedData['nome'];
                if (array_key_exists('descricao', $validatedData)) $dadosColecao['descricao'] = $validatedData['descricao'];

                if (!empty($dadosColecao)) {
                    $colecao->update($dadosColecao);
                }

                if (array_key_exists('receitas', $validatedData)) {
                    $colecao->receitas()->sync($validatedData['receitas'] ?? []);
                }
            });

            return response()->json($colecao->fresh()->load('receitas:id,titulo'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coleção de receitas não encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified recipe collection from storage.
     */
    public function destroy(int $id)
    {
        try {
            $colecao = ColecaoReceita::findOrFail($id);
            $user = Auth::user();

            if ($colecao->id_usuario !== $user->id) {
                return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
            }

            DB::transaction(function () use ($colecao) {
                $colecao->receitas()->detach(); // Remove as associações na tabela pivot
                $colecao->delete();
            });

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coleção de receitas não encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Add a specific recipe to a collection.
     */
    public function addReceitaToColecao(Request $request, int $idColecao, int $idReceita)
    {
        try {
            $colecao = ColecaoReceita::findOrFail($idColecao);
            $user = Auth::user();

            if ($colecao->id_usuario !== $user->id) {
                return response()->json(['message' => 'Acesso não autorizado à coleção.'], Response::HTTP_FORBIDDEN);
            }

            $receita = Receita::where('id', $idReceita)
                ->where(function ($query) use ($user) {
                    $query->where('is_public', true)
                        ->orWhere('id_usuario', $user->id);
                })
                ->firstOrFail();

            // Adiciona a receita à coleção se ainda não estiver lá
            $colecao->receitas()->syncWithoutDetaching([$receita->id]);

            return response()->json(['message' => 'Receita adicionada à coleção com sucesso.', 'colecao' => $colecao->fresh()->load('receitas:id,titulo')]);
        } catch (ModelNotFoundException $e) {
            if ($e->getModel() === ColecaoReceita::class) {
                return response()->json(['message' => 'Coleção não encontrada.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json(['message' => 'Receita não encontrada, não é pública ou não pertence a você.'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove a specific recipe from a collection.
     */
    public function removeReceitaFromColecao(Request $request, int $idColecao, int $idReceita)
    {
        try {
            $colecao = ColecaoReceita::findOrFail($idColecao);
            $user = Auth::user();

            if ($colecao->id_usuario !== $user->id) {
                return response()->json(['message' => 'Acesso não autorizado à coleção.'], Response::HTTP_FORBIDDEN);
            }

            // Verifica se a receita existe, não precisa checar public/owner aqui, apenas se está na coleção
            if (!$colecao->receitas()->where('receitas.id', $idReceita)->exists()) {
                return response()->json(['message' => 'Receita não encontrada nesta coleção.'], Response::HTTP_NOT_FOUND);
            }

            $colecao->receitas()->detach($idReceita);

            return response()->json(['message' => 'Receita removida da coleção com sucesso.', 'colecao' => $colecao->fresh()->load('receitas:id,titulo')]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coleção não encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }
}

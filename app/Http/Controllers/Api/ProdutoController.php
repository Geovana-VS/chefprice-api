<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    public function index()
    {
        $produtos = Produto::with(['categoria', 'imagens'])->latest()->get();
        return response()->json($produtos);
    }

    public function store(Request $request)
    {
        $messages = [
            'codigo_barra.unique' => 'Este código de barra já está em uso.',
            'codigo_barra.max' => 'O código de barra não pode ter mais que :max caracteres.',
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais que :max caracteres.',
            'id_categoria.required' => 'A categoria é obrigatória.',
            'id_categoria.exists' => 'A categoria selecionada é inválida.',
            'unidade_medida.max' => 'A unidade de medida não pode ter mais que :max caracteres.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.',
        ];

        $rules = [
            'codigo_barra' => 'nullable|string|max:100|unique:produtos,codigo_barra',
            'nome' => 'required|string|max:200',
            'id_categoria' => 'required|integer|exists:categorias,id',
            'unidade_medida' => 'nullable|string|max:50',
            'imagens' => 'nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $produto = null;

        DB::transaction(function () use ($validatedData, &$produto) {

            $produtoData = [
                'nome' => $validatedData['nome'],
                'id_categoria' => $validatedData['id_categoria'],
                'codigo_barra' => $validatedData['codigo_barra'] ?? null,
                'unidade_medida' => $validatedData['unidade_medida'] ?? null,
            ];

            $produto = Produto::create($produtoData);

            if (!empty($validatedData['imagens'])) {
                $produto->imagens()->attach($validatedData['imagens']);
            }
        });

        if ($produto === null) {
            return response()->json(['message' => 'Produto não criado.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json($produto->load(['categoria', 'imagens']), Response::HTTP_CREATED);
    }

    public function show(Produto $produto)
    {
        // Load relationships needed for detailed view
        return response()->json($produto->load(['categoria', 'imagens', 'historico', 'usuario'])); // Assuming usuario relationship exists
    }

    public function update(Request $request, Produto $produto)
    {

        $messages = [
            'codigo_barra.unique' => 'Este código de barra já está em uso.',
            'codigo_barra.max' => 'O código de barra não pode ter mais que :max caracteres.',
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais que :max caracteres.',
            'id_categoria.required' => 'A categoria é obrigatória.',
            'id_categoria.exists' => 'A categoria selecionada é inválida.',
            'unidade_medida.max' => 'A unidade de medida não pode ter mais que :max caracteres.',
            'imagens.array' => 'As imagens devem ser um array.',
            'imagens.*.exists' => 'Uma ou mais imagens selecionadas são inválidas.',
        ];

        $rules = [
            'codigo_barra' => 'sometimes|nullable|string|max:100|unique:produtos,codigo_barra,' . $produto->id,
            'nome' => 'sometimes|required|string|max:200',
            'id_categoria' => 'sometimes|required|integer|exists:categorias,id',
            'unidade_medida' => 'sometimes|nullable|string|max:50',
            'imagens' => 'sometimes|nullable|array',
            'imagens.*' => 'integer|exists:imagens,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $produto) {
            $produtoData = [];
            if (array_key_exists('nome', $validatedData)) $produtoData['nome'] = $validatedData['nome'];
            if (array_key_exists('id_categoria', $validatedData)) $produtoData['id_categoria'] = $validatedData['id_categoria'];
            if (array_key_exists('codigo_barra', $validatedData)) $produtoData['codigo_barra'] = $validatedData['codigo_barra'];
            if (array_key_exists('unidade_medida', $validatedData)) $produtoData['unidade_medida'] = $validatedData['unidade_medida'];

            if (!empty($produtoData)) {
                $produto->update($produtoData);
            }

            if (array_key_exists('imagens', $validatedData)) {
                $produto->imagens()->sync($validatedData['imagens'] ?? []);
            }
        });


        return response()->json($produto->fresh()->load(['categoria', 'imagens']));
    }

    public function destroy(Produto $produto)
    {
        if ($produto->ingredienteEmReceitas()->exists()) {
            return response()->json(['message' => 'Não pode deletar produto usado como ingrediente em receitas.'], Response::HTTP_CONFLICT); // 409 Conflict
        }

        DB::transaction(function () use ($produto) {
            $produto->imagens()->detach();

            $produto->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT); // 204 No Content
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ImagemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $imagens = Imagem::with(['usuario', 'tipoImagem'])->latest()->get();
        return response()->json($imagens);
    }

    /**
     * Store a newly created resource in storage.
     * Handles file upload and attaching relationships.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $messages = [
            'id_tipo_imagem.required' => 'O tipo da imagem é obrigatório.',
            'id_tipo_imagem.exists' => 'O tipo da imagem selecionado é inválido.',
            'is_publico.boolean' => 'O campo is_publico deve ser verdadeiro ou falso.',
            'image_file.required' => 'O arquivo de imagem é obrigatório.',
            'image_file.image' => 'O arquivo deve ser uma imagem válida (jpeg, png, jpg, gif, webp).',
            'image_file.mimes' => 'A imagem deve ser do tipo: :values.',
            'image_file.max' => 'A imagem não pode ser maior que :max kilobytes.',
            'produtos.array' => 'Os produtos devem ser um array de IDs.',
            'produtos.*.integer' => 'Cada ID de produto deve ser um número inteiro.',
            'produtos.*.exists' => 'Um ou mais produtos selecionados são inválidos.',
            'receitas.array' => 'As receitas devem ser um array de IDs.',
            'receitas.*.integer' => 'Cada ID de receita deve ser um número inteiro.',
            'receitas.*.exists' => 'Uma ou mais receitas selecionadas são inválidas.',
        ];

        $rules = [
            'id_tipo_imagem' => 'required|integer|exists:tipo_imagens,id',
            'is_publico' => 'sometimes|boolean',
            'nome_arquivo' => 'nullable|string|max:255',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'produtos' => 'nullable|array',
            'produtos.*' => 'integer|exists:produtos,id',
            'receitas' => 'nullable|array',
            'receitas.*' => 'integer|exists:receitas,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $imagem = null;

        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $file = $request->file('image_file');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();

            // Store file in storage/app/public/images
            $storedPath = $file->store('images', 'public');

            if (!$storedPath) {
                return response()->json(['message' => 'Erro ao salvar o arquivo de imagem.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $imagemData = [
                'id_usuario' => Auth::id(),
                'id_tipo_imagem' => $validatedData['id_tipo_imagem'],
                'nome_arquivo' => $validatedData['nome_arquivo'] ?? $originalFilename,
                'nome_arquivo_storage' => $storedPath,
                'mime_type' => $mimeType,
                'is_publico' => $validatedData['is_publico'] ?? false,
            ];

            // Use transaction to ensure database operations succeed together
            DB::transaction(function () use ($imagemData, $validatedData, &$imagem) {
                $imagem = Imagem::create($imagemData);

                // Attach Relationships
                if (!empty($validatedData['produtos'])) {
                    $imagem->produtos()->attach($validatedData['produtos']);
                }
                if (!empty($validatedData['receitas'])) {
                    $imagem->receitas()->attach($validatedData['receitas']);
                }
            });

            if ($imagem === null) {
                return response()->json(['message' => 'Imagem não criada.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json($imagem->load(['usuario', 'tipoImagem', 'produtos', 'receitas']), Response::HTTP_CREATED);

        } else {
             return response()->json(['message' => 'Arquivo de imagem inválido ou não enviado.'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Imagem  $imagem
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Imagem $imagem)
    {
        // Eager load necessary relationships
        return response()->json($imagem->load(['usuario', 'tipoImagem', 'produtos', 'receitas']));
    }

    /**
     * Update the specified resource in storage.
     * Updates metadata and syncs relationships. Does not handle file replacement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Imagem  $imagem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Imagem $imagem)
    {
        // Optional: Add Authorization check here if needed
        // if ($imagem->id_usuario !== Auth::id()) { ... }

        $messages = [
            'id_tipo_imagem.required' => 'O tipo da imagem é obrigatório.',
            'id_tipo_imagem.exists' => 'O tipo da imagem selecionado é inválido.',
            'is_publico.boolean' => 'O campo is_publico deve ser verdadeiro ou falso.',
            'nome_arquivo.max' => 'O nome do arquivo não pode ter mais que :max caracteres.',
            'produtos.array' => 'Os produtos devem ser um array de IDs.',
            'produtos.*.integer' => 'Cada ID de produto deve ser um número inteiro.',
            'produtos.*.exists' => 'Um ou mais produtos selecionados são inválidos.',
            'receitas.array' => 'As receitas devem ser um array de IDs.',
            'receitas.*.integer' => 'Cada ID de receita deve ser um número inteiro.',
            'receitas.*.exists' => 'Uma ou mais receitas selecionadas são inválidas.',
        ];

        // Use 'sometimes' for fields that are optional during update
        $rules = [
            'id_tipo_imagem' => 'sometimes|required|integer|exists:tipo_imagens,id',
            'is_publico' => 'sometimes|boolean',
            'nome_arquivo' => 'sometimes|nullable|string|max:255', // Allow updating display name
            'produtos' => 'sometimes|nullable|array',
            'produtos.*' => 'integer|exists:produtos,id',
            'receitas' => 'sometimes|nullable|array',
            'receitas.*' => 'integer|exists:receitas,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $imagem) {
             // 1. Prepare metadata for update
             $imagemData = [];
             if (array_key_exists('id_tipo_imagem', $validatedData)) $imagemData['id_tipo_imagem'] = $validatedData['id_tipo_imagem'];
             if (array_key_exists('is_publico', $validatedData)) $imagemData['is_publico'] = $validatedData['is_publico'];
             if (array_key_exists('nome_arquivo', $validatedData)) $imagemData['nome_arquivo'] = $validatedData['nome_arquivo'];

             // 2. Update metadata if provided
             if (!empty($imagemData)) {
                 $imagem->update($imagemData);
             }

            // 3. Sync Products (if key was present in request)
            if (array_key_exists('produtos', $validatedData)) {
                $imagem->produtos()->sync($validatedData['produtos'] ?? []);
            }

            // 4. Sync Recipes (if key was present in request)
            if (array_key_exists('receitas', $validatedData)) {
                $imagem->receitas()->sync($validatedData['receitas'] ?? []);
            }
        }); // End transaction

        // Return fresh model data with updated relationships
        return response()->json($imagem->fresh()->load(['usuario', 'tipoImagem', 'produtos', 'receitas']));
    }

    /**
     * Remove the specified resource from storage.
     * Deletes the file and database record.
     *
     * @param  \App\Models\Imagem  $imagem
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Imagem $imagem)
    {
        // Optional: Add Authorization check here if needed
        // if ($imagem->id_usuario !== Auth::id()) { ... }

        // Optional: Add check if image is still linked somewhere critical, if needed

        DB::transaction(function () use ($imagem) {
            // 1. Delete the file from storage
            if ($imagem->nome_arquivo_storage) {
                Storage::disk('public')->delete($imagem->nome_arquivo_storage);
            }

            // 2. Detach from all related models to clean up pivot tables
            $imagem->produtos()->detach();
            $imagem->receitas()->detach();

            // 3. Delete the database record
            $imagem->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT); // 204
    }
}
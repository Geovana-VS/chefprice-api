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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\TipoImagem;
use App\Services\ReceiptProcessingService;

class ImagemController extends Controller
{
    protected ReceiptProcessingService $receiptService;
    public function __construct(ReceiptProcessingService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

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
            'receitas.integer' => 'O ID de receita deve ser um número inteiro.',
            'receitas.exists' => 'Uma ou mais receitas selecionadas são inválidas.',
        ];

        $rules = [
            'id_tipo_imagem' => 'required|integer|exists:tipo_imagens,id',
            'is_publico' => 'sometimes|boolean',
            'nome_arquivo' => 'nullable|string|max:255',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'produtos' => 'nullable|array',
            'produtos.*' => 'integer|exists:produtos,id',
            'receitas' => 'nullable|integer|exists:receitas,id',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $imagem = null;
        $receiptProcessingResult = null;

        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $file = $request->file('image_file');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();

            // Store file in storage/app/public/images
            $storedPath = $file->store('images', 'public');

            if (!$storedPath) {
                return response()->json(['message' => 'Erro ao salvar o arquivo de imagem.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $publicUrl = Storage::disk('public')->url($storedPath);

            $imagemData = [
                'id_usuario' => Auth::id(),
                'id_tipo_imagem' => $validatedData['id_tipo_imagem'],
                'nome_arquivo' => $validatedData['nome_arquivo'] ?? $originalFilename,
                'caminho_storage' => $storedPath,
                'mime_type' => $mimeType,
                'is_publico' => $validatedData['is_publico'] ?? false,
                'url' => $publicUrl,
            ];
            $tipoImagemModel = TipoImagem::find($validatedData['id_tipo_imagem']);
            $tipoNomeLower = strtolower($tipoImagemModel->nome);
            // Use transaction to ensure database operations succeed together
            $transaction = DB::transaction(
                function () use ($imagemData, $validatedData, &$imagem, $tipoImagemModel, $tipoNomeLower) {
                    $imagem = Imagem::create($imagemData);

                    // Attach Relationships
                    if (!empty($validatedData['produtos'])) {
                        $imagem->produtos()->attach($validatedData['produtos']);
                    }
                    if (!empty($validatedData['receitas'])) {
                        $imagem->receitas()->attach($validatedData['receitas']);
                    }

                    // --- Receipt Processing Logic ---

                    if ($tipoImagemModel) {
                        $recipeIdForReceipt = null;

                        if ($tipoNomeLower === 'cupom fiscal genérico' || $tipoNomeLower === 'cupom fiscal receita') {
                            if ($tipoNomeLower === 'cupom fiscal receita') {
                                if (!empty($validatedData['receitas'])) {
                                    $receiptProcessingResult = $this->receiptService->processReceipt($imagem, Auth::id(), $validatedData['receitas']);
                                } else {
                                    return response()->json(['message' => 'Para cupons fiscais do tipo "Cupom Fiscal Receita", deve haver exatamente uma receita associada.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                                }
                            } else if ($tipoNomeLower === 'cupom fiscal genérico') {
                                $receiptProcessingResult = $this->receiptService->processReceipt($imagem, Auth::id());
                            }
                        }
                    }
                    return $receiptProcessingResult;
                }
            ); // End transaction

            if ($imagem === null) {
                return response()->json(['message' => 'Imagem não criada.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            Log::info($transaction);
            if ($tipoNomeLower === 'cupom fiscal receita' && $transaction['success'] != true) {
                return response()->json(['message' => 'Imagem criada, mas não foi possível processar o recibo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json([
                'imagem' => $imagem->load(['usuario', 'tipoImagem']),
                'receiptProcessingResult' => $transaction,
            ], Response::HTTP_CREATED);
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
    public function show(int $idImagem)
    {
        $imagem = Imagem::findOrFail($idImagem);

        // Eager load necessary relationships
        return response()->json($imagem->load(['usuario', 'tipoImagem', 'produtos', 'receitas']));
    }

    public function view(int $idImagem)
    {
        try {
            $imagem = Imagem::findOrFail($idImagem);

            // Authorization check:
            // If image is not public, only the owner can view it.
            if (!$imagem->is_publico) {
                if (!Auth::check() || Auth::id() !== $imagem->id_usuario) {
                    return response()->json(['message' => 'Acesso não autorizado a esta imagem.'], Response::HTTP_FORBIDDEN);
                }
            }

            if (!Storage::disk('public')->exists($imagem->caminho_storage)) {
                return response()->json(['message' => 'Arquivo de imagem não encontrado no servidor.'], Response::HTTP_NOT_FOUND);
            }

            // Get the full path to the file on the server
            $path = Storage::disk('public')->path($imagem->caminho_storage);

            // Determine the MIME type from the model, default if not set
            $mimeType = $imagem->mime_type ?: 'application/octet-stream';

            $headers = [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($imagem->caminho_storage) . '"', // Suggests browser to display inline
            ];

            return response()->file($path, $headers);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Imagem não encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno ao processar a requisição da imagem.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     * Updates metadata and syncs relationships. Does not handle file replacement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Imagem  $imagem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $idImagem)
    {
        $imagem = Imagem::findOrFail($idImagem);


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
    public function destroy(int $idImagem)
    {
        try {
            $imagem = Imagem::findOrFail($idImagem);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
            }

            if ($imagem->id_usuario !== $user->id && !$user->is_admin) {
                return response()->json(['message' => 'Acesso não autorizado. Você não é o proprietário desta imagem nem um administrador.'], Response::HTTP_FORBIDDEN);
            }

            $caminhoStorage = $imagem->caminho_storage;
            $logImagemId = $imagem->id;

            $deletedInDb = false;
            DB::transaction(function () use ($imagem, &$deletedInDb) {
                $imagem->produtos()->detach();
                $imagem->receitas()->detach();

                $deletedInDb = $imagem->delete();
            });

            if ($deletedInDb) {
                if ($caminhoStorage) {
                    if (Storage::disk('public')->exists($caminhoStorage)) {
                        if (Storage::disk('public')->delete($caminhoStorage)) {
                            Log::info("Arquivo deletado do storage: {$caminhoStorage} para Imagem ID: {$logImagemId}");
                        } else {
                            Log::error("Falha ao deletar o arquivo do storage: {$caminhoStorage} para Imagem ID: {$logImagemId}. O registro no banco foi deletado.");
                        }
                    } else {
                        Log::warning("Arquivo não encontrado no storage para Imagem ID {$logImagemId}: {$caminhoStorage}. O registro no banco foi deletado.");
                    }
                } else {
                    Log::info("Imagem ID {$logImagemId} não possuía caminho_storage. Apenas o registro no banco foi deletado.");
                }
                return response()->json(null, Response::HTTP_NO_CONTENT); // 204
            } else {
                Log::error("Falha ao deletar o registro da Imagem ID {$idImagem} do banco de dados.");
                return response()->json(['message' => 'A imagem não pôde ser deletada do banco de dados.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Imagem com ID ' . $idImagem . ' não encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("Erro ao tentar deletar imagem ID {$idImagem}: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno ao processar a requisição para deletar a imagem.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

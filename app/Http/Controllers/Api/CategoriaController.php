<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::all();
        return response()->json($categorias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'nome.required' => 'O campo nome é obrigatório.', 
            'nome.unique'   => 'Este nome de categoria já existe.',
            'nome.max'      => 'O campo nome não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:100|unique:categorias,nome',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        $categoria = Categoria::create($validator->validated());

        return response()->json($categoria, Response::HTTP_CREATED); // 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria)
    {
        return response()->json($categoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        // Optional: Custom messages for consistency
        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.unique'   => 'Este nome de categoria já existe.',
            'nome.max'      => 'O campo nome não pode ter mais que :max caracteres.',
        ];

        $rules = [
            'nome' => 'required|string|max:100|unique:categorias,nome,' . $categoria->id,
        ];
        
        $validator = Validator::make($request->all(), $rules, $messages); // Pass messages

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        $categoria->update($validator->validated());

        return response()->json($categoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->produtos()->exists()) {
            return response()->json(['message' => 'Cannot delete category with associated products.'], Response::HTTP_CONFLICT); // 409 Conflict
        } else {
            $categoria->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT); // 204 No Content
        }
    }
}

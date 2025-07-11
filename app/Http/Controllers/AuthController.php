<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registro de um novo usuário.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso.',
            'user' => $user,
            'token' => $token
        ], 201); // 201 Created status
    }

    /**
     * Login do usuário.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);


        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $user = Auth::user();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Logout do usuário.
     */
    public function logout(Request $request)
    {
        // Pega o usuário autenticado
        $user = $request->user();

        // Revoca o o token atual
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Usuário deslogado com sucesso.']);
    }

    public function getUsers(Request $request)
    {
        // Busca todos os usuários com email verificado
        $users = User::where('email_verified_at', '!=', NULL)->get();

        return response()->json($users);
    }

    public function grantAdmin(Request $request, $id)
    {
        // Busca o usuário pelo ID
        $user = User::findOrFail($id);

        // Verifica se o usuário já é admin
        if ($user->is_admin) {
            return response()->json(['message' => 'Usuário já é um administrador.'], 400);
        }

        // Concede privilégios de administrador
        $user->is_admin = true;
        $user->save();

        return response()->json(['message' => 'Usuário promovido a administrador com sucesso.']);
    }
    public function revokeAdmin(Request $request, $id)
    {
        // Busca o usuário pelo ID
        $user = User::findOrFail($id);

        // Verifica se o usuário não é admin
        if (!$user->is_admin) {
            return response()->json(['message' => 'Usuário não é um administrador.'], 400);
        }

        // Revoga privilégios de administrador
        $user->is_admin = false;
        $user->save();

        return response()->json(['message' => 'Privilégios de administrador revogados com sucesso.']);
    }
}

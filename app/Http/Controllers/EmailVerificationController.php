<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Http\Requests\ApiEmailVerificationRequest;


class EmailVerificationController extends Controller
{
    /**
     * Marca o email do usuário autenticado como verificado.
     *
     * 1. Checa se a assinatura do link é válida.
     * 2. Encontra o usuário via ID na URL (parametros de rota).
     * 3. Checa se o hash do email na URL bate com o hash do email do usuário.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(ApiEmailVerificationRequest $request): JsonResponse
    {
        $userId = $request->route('id');
        $user = User::findOrFail($userId);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return response()->json(['message' => 'Hash incorreta de email.'], 403); // Forbidden
        }
        if ($user->hasVerifiedEmail()) {
             return response()->json(['message' => 'Email já verificado.'], 200);
        }

        if ($user->markEmailAsVerified()) {
            // Optionally, fire the Verified event with the correct user object
            // event(new \Illuminate\Auth\Events\Verified($user));
        }

        return response()->json(['message' => 'Email verificado com sucesso.'], 200);
    }

    /**
     * Reenvia o email de verificação.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        // User must be authenticated via Sanctum token to call this
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email já verificado.'], 400); // Bad request
        }

        // Send the notification
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de verificação enviado.'], 200);
    }
}
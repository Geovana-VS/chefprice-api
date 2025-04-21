<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Http\Requests\ApiEmailVerificationRequest;
use Illuminate\Http\RedirectResponse;


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
    public function verify(ApiEmailVerificationRequest $request): RedirectResponse // <-- Change return type hint
    {
        $userId = $request->route('id');
        $user = User::findOrFail($userId); // Or Usuario::findOrFail($userId);

        // Construct base URLs from config
        $successUrl = config('app.frontend_url') . config('app.frontend_email_verify_success_path');
        $failedUrl = config('app.frontend_url') . config('app.frontend_email_verify_failed_path');

        // --- Manually perform the hash check ---
        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect()->away($failedUrl . '?error=invalid_hash');
        }

        if ($user->hasVerifiedEmail()) {

            return redirect()->away($successUrl . '?status=already_verified');
        }

        if ($user->markEmailAsVerified()) {
            return redirect()->away($successUrl . '?status=success');
        } else {
            return redirect()->away($failedUrl . '?error=server_error');
        }
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
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiEmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by the 'signed' middleware on the route itself
     * for this specific email verification flow.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
        ];
    }
}
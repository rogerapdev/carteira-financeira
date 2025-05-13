<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|min:10|max:15',
            'document' => 'required|string|min:11|max:18|unique:users',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Formato de email inválido',
            'email.unique' => 'Este email já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres',
            'password.confirmed' => 'A confirmação de senha não coincide',
            'phone.required' => 'O telefone é obrigatório',
            'phone.min' => 'O telefone deve ter pelo menos 10 dígitos',
            'document.required' => 'O documento (CPF/CNPJ) é obrigatório',
            'document.min' => 'O documento deve ter pelo menos 11 dígitos (CPF)',
            'document.unique' => 'Este documento já está em uso',
        ];
    }
} 
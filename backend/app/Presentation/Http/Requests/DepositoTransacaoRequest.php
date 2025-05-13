<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DepositoTransacaoRequest extends FormRequest
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
            'to_account_id' => 'required|string|exists:accounts,public_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_key' => 'nullable|uuid',
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
            'to_account_id.required' => 'O ID da conta do destinatário é obrigatório',
            'to_account_id.string' => 'O ID da conta do destinatário deve ser um UUID válido',
            'to_account_id.exists' => 'Conta do destinatário não encontrada',
            'amount.required' => 'O valor do depósito é obrigatório',
            'amount.numeric' => 'O valor deve ser um número',
            'amount.min' => 'O valor mínimo para depósito é R$ 0,01',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres',
            'transaction_key.uuid' => 'A chave de transação deve ser um UUID válido',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Gera uma chave de transação se não fornecida
        if (!$this->has('transaction_key')) {
            $this->merge([
                'transaction_key' => Str::uuid()->toString()
            ]);
        }
    }
} 
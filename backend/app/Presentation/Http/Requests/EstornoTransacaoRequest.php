<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstornoTransacaoRequest extends FormRequest
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
            'reason' => 'required|string|max:255',
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
            'reason.required' => 'O motivo é obrigatório',
            'reason.max' => 'O motivo do estorno deve ter no máximo 255 caracteres',
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
        // Compatibilidade com código que possa usar 'description' em vez de 'reason'
        if ($this->has('description') && !$this->has('reason')) {
            $this->merge([
                'reason' => $this->input('description')
            ]);
        }
        
        // Gera uma chave de transação se não fornecida
        if (!$this->has('transaction_key')) {
            $this->merge([
                'transaction_key' => \Illuminate\Support\Str::uuid()
            ]);
        }
    }
} 
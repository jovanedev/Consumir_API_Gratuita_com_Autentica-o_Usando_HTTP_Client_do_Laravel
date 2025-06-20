<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTarefaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['pendente', 'em_andamento', 'concluida'])],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.max' => 'O título não pode ter mais de 255 caracteres.',
            'status.in' => 'O status deve ser pendente, em_andamento ou concluída.',
        ];
    }
}

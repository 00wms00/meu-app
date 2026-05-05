<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limite_alerta' => ['required', 'numeric', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'limite_alerta.required' => 'Informe o percentual de variação para o alerta.',
            'limite_alerta.min'      => 'O percentual mínimo é 1%.',
            'limite_alerta.max'      => 'O percentual máximo é 100%.',
        ];
    }
}

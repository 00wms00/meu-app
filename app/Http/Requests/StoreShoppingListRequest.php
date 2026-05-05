<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShoppingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:255'],
            'descricao'   => ['nullable', 'string', 'max:1000'],
            'data_compra' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe um nome para a lista.',
        ];
    }
}

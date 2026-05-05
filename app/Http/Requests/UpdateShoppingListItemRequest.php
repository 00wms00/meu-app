<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShoppingListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao'      => ['nullable', 'string', 'max:255'],
            'quantidade'     => ['required', 'numeric', 'min:0.001'],
            'unidade'        => ['nullable', 'string', 'max:10'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

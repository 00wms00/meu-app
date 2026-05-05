<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShoppingListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'     => ['nullable', 'exists:products,id'],
            'descricao'      => ['required_without:product_id', 'nullable', 'string', 'max:255'],
            'quantidade'     => ['required', 'numeric', 'min:0.001'],
            'unidade'        => ['nullable', 'string', 'max:10'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantidade.required'        => 'Informe a quantidade.',
            'descricao.required_without' => 'Informe a descrição ou selecione um produto.',
        ];
    }
}

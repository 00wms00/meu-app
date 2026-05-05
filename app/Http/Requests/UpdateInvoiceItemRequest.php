<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao'      => ['required', 'string', 'max:255'],
            'unidade'        => ['nullable', 'string', 'max:10'],
            'quantidade'     => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total'    => ['required', 'numeric', 'min:0'],
        ];
    }
}

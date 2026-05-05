<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'       => ['nullable', 'string', 'max:50'],
            'serie'        => ['nullable', 'string', 'max:10'],
            'data_emissao' => ['required', 'date'],
            'emitente'     => ['nullable', 'string', 'max:255'],
            'cnpj'         => ['nullable', 'string', 'max:18'],
            'valor_total'  => ['required', 'numeric', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização por middleware auth já garantida
    }

    public function rules(): array
    {
        return [
            'xml' => [
                'required',
                'file',
                'mimes:xml,text',
                'max:2048', // 2 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'xml.required' => 'Selecione um arquivo XML de NF-e.',
            'xml.mimes'    => 'O arquivo deve ser um XML válido.',
            'xml.max'      => 'O arquivo não pode ultrapassar 2 MB.',
        ];
    }
}

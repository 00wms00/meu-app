<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfirmarAgrupamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'produto_id' => [
                'required',
                // Garante que o produto pertence ao usuário autenticado.
                // Sem isso, qualquer produto_id válido no banco seria aceito.
                Rule::exists('products', 'id')->where('user_id', $userId),
            ],
            'canonico_id' => [
                'nullable',
                // Mesma garantia de ownership para o produto canônico.
                Rule::exists('products', 'id')->where('user_id', $userId),
            ],
            'acao' => ['required', 'in:agrupar,pular,ignorar'],
        ];
    }

    public function messages(): array
    {
        return [
            'produto_id.exists'  => 'Produto não encontrado ou sem permissão.',
            'canonico_id.exists' => 'Produto canônico não encontrado ou sem permissão.',
            'acao.in'            => 'Ação inválida.',
        ];
    }
}

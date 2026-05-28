@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📦 Produtos</h1>
            <p class="mt-1 text-gray-600">{{ $total }} produtos cadastrados</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.normalizacao') }}" class="btn-outline-secondary text-sm">🏷️ Normalizar</a>
            <a href="{{ route('products.categorias') }}" class="btn-outline-secondary text-sm">📂 Categorizar</a>
            <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary text-sm">🔗 Agrupamentos</a>
        </div>
    </div>
</div>

{{-- Busca --}}
<form method="GET" class="mb-4 flex gap-2">
    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Buscar produto..."
        class="form-control flex-1"
    >
    <button type="submit" class="btn-primary">🔍</button>
    @if(request('search'))
    <a href="{{ route('products.index') }}" class="btn-outline-secondary">✕</a>
    @endif
</form>

{{-- Botões expandir / recolher todos --}}
@if($grouped->count() > 1)
<div class="flex gap-2 mb-3">
    <button
        type="button"
        onclick="document.querySelectorAll('[data-accordion-body]').forEach(el => el.classList.remove('hidden')); document.querySelectorAll('[data-accordion-icon]').forEach(el => el.style.transform = 'rotate(180deg)')"
        class="text-xs text-blue-600 hover:underline"
    >Expandir tudo</button>
    <span class="text-gray-300">|</span>
    <button
        type="button"
        onclick="document.querySelectorAll('[data-accordion-body]').forEach(el => el.classList.add('hidden')); document.querySelectorAll('[data-accordion-icon]').forEach(el => el.style.transform = 'rotate(0deg)')"
        class="text-xs text-gray-500 hover:underline"
    >Recolher tudo</button>
</div>
@endif

@if($grouped->isEmpty())
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">
        Nenhum produto encontrado.
    </div>
@else

{{-- Acordeão --}}
<div class="space-y-2" id="accordion-produtos">
    @foreach($grouped as $categoria => $produtos)
    @php $slug = Str::slug($categoria); $primeiro = $loop->first; @endphp

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">

        {{-- Cabeçalho do grupo --}}
        <button
            type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            onclick="toggleAccordion('{{ $slug }}')"
            aria-expanded="{{ $primeiro ? 'true' : 'false' }}"
            aria-controls="accordion-body-{{ $slug }}"
        >
            <div class="flex items-center gap-2">
                <span class="font-semibold text-gray-800 text-sm">{{ $categoria }}</span>
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">{{ $produtos->count() }}</span>
            </div>
            <svg
                data-accordion-icon
                id="icon-{{ $slug }}"
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-gray-400 transition-transform duration-200 {{ $primeiro ? 'rotate-180' : '' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Corpo do grupo --}}
        <div
            data-accordion-body
            id="accordion-body-{{ $slug }}"
            class="{{ $primeiro ? '' : 'hidden' }} border-t border-gray-100"
        >
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wide">Produto</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wide hidden sm:table-cell">Nome original</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-400 uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($produtos as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2">
                            <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                {{ \App\Helpers\ProductHelper::displayName($product) }}
                            </a>
                            @if($product->nome_exibicao && $product->normalizacao_status === 'aprovado')
                                <span class="text-xs text-green-500 ml-1" title="Normalizado">✓</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center hidden sm:table-cell">
                            @if($product->nome_exibicao && $product->nome !== $product->nome_exibicao)
                                <span class="text-xs text-gray-400" title="{{ $product->nome }}">{{ Str::limit($product->nome, 40) }}</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('products.show', $product) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Ver histórico">📈</a>
                            <a href="{{ route('products.edit', $product) }}" class="text-gray-400 hover:text-gray-600 text-sm ml-2" title="Editar">✏️</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
    @endforeach
</div>

@endif

<script>
function toggleAccordion(slug) {
    const body = document.getElementById('accordion-body-' + slug);
    const icon = document.getElementById('icon-' + slug);
    const isOpen = !body.classList.contains('hidden');
    body.classList.toggle('hidden', isOpen);
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    const btn = body.previousElementSibling;
    btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
}
</script>
@endsection

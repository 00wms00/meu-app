@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📦 Produtos</h1>
            <p class="mt-1 text-gray-600">{{ $total }} produto{{ $total !== 1 ? 's' : '' }} cadastrado{{ $total !== 1 ? 's' : '' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.normalizacao') }}" class="btn-outline-secondary text-sm">🏷️ Normalizar</a>
            <a href="{{ route('products.categorias') }}" class="btn-outline-secondary text-sm">📂 Categorizar</a>
            <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary text-sm">🔗 Agrupamentos</a>
        </div>
    </div>
</div>

{{-- ===== BUSCA COM AUTOCOMPLETE ===== --}}
<div class="relative mb-4" id="search-wrapper">
    <form method="GET" id="search-form" autocomplete="off" class="flex gap-2">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </span>
            <input
                type="text"
                id="search-input"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar produto..."
                class="form-control pl-9 w-full"
            >
            {{-- Dropdown autocomplete --}}
            <ul
                id="autocomplete-list"
                class="hidden absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg divide-y divide-gray-100 overflow-hidden"
                role="listbox"
            ></ul>
        </div>
        <button type="submit" class="btn-primary px-4">Buscar</button>
        @if(request('search'))
        <a href="{{ route('products.index') }}" class="btn-outline-secondary px-3" title="Limpar busca">✕</a>
        @endif
    </form>
</div>

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
            <svg data-accordion-icon id="icon-{{ $slug }}"
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-gray-400 transition-transform duration-200 {{ $primeiro ? 'rotate-180' : '' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div data-accordion-body id="accordion-body-{{ $slug }}"
            class="{{ $primeiro ? '' : 'hidden' }} border-t border-gray-100">
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
// ===== Acordeão =====
function toggleAccordion(slug) {
    const body = document.getElementById('accordion-body-' + slug);
    const icon = document.getElementById('icon-' + slug);
    const isOpen = !body.classList.contains('hidden');
    body.classList.toggle('hidden', isOpen);
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    body.previousElementSibling.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
}

// ===== Autocomplete =====
(function () {
    const input   = document.getElementById('search-input');
    const list    = document.getElementById('autocomplete-list');
    const baseUrl = '{{ route('products.autocomplete') }}';
    let timer, activeIdx = -1;

    function highlight(text, q) {
        if (!q) return text;
        return text.replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'),
            '<mark class="bg-yellow-100 text-yellow-800 rounded px-0.5">$1</mark>');
    }

    function renderResults(items, q) {
        list.innerHTML = '';
        activeIdx = -1;
        if (!items.length) {
            list.innerHTML = '<li class="px-4 py-3 text-sm text-gray-400 text-center">Nenhum resultado encontrado</li>';
            list.classList.remove('hidden');
            return;
        }
        items.forEach(function (item, i) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.setAttribute('data-url', item.url);
            li.className = 'flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-blue-50 transition-colors';
            li.innerHTML =
                '<div>' +
                    '<span class="text-sm font-medium text-gray-800">' + highlight(item.nome, q) + '</span>' +
                    '<span class="block text-xs text-gray-400 mt-0.5">' + item.categoria + '</span>' +
                '</div>' +
                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                window.location.href = item.url;
            });
            list.appendChild(li);
        });

        // Rodapé: "Ver todos os resultados"
        const footer = document.createElement('li');
        footer.className = 'px-4 py-2 text-center border-t border-gray-100';
        footer.innerHTML = '<a href="{{ route('products.index') }}?search=' + encodeURIComponent(input.value) + '" class="text-xs text-blue-600 hover:underline">Ver todos os resultados →</a>';
        list.appendChild(footer);

        list.classList.remove('hidden');
    }

    function closeList() {
        list.classList.add('hidden');
        activeIdx = -1;
    }

    function setActive(idx) {
        const items = list.querySelectorAll('[role=option]');
        items.forEach(el => el.classList.remove('bg-blue-50'));
        if (idx >= 0 && idx < items.length) {
            items[idx].classList.add('bg-blue-50');
            items[idx].scrollIntoView({ block: 'nearest' });
            activeIdx = idx;
        } else {
            activeIdx = -1;
        }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { closeList(); return; }
        timer = setTimeout(function () {
            fetch(baseUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => renderResults(data, q))
            .catch(() => closeList());
        }, 220);
    });

    input.addEventListener('keydown', function (e) {
        const items = list.querySelectorAll('[role=option]');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIdx + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIdx - 1, 0));
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            const url = items[activeIdx].getAttribute('data-url');
            if (url) window.location.href = url;
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    input.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && list.children.length) {
            list.classList.remove('hidden');
        }
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('search-wrapper').contains(e.target)) {
            closeList();
        }
    });
}());
</script>
@endsection

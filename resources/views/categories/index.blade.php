@extends('layouts.app')

@section('title', 'Gerenciar Categorias')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Categorias</h1>
            <p class="mt-1 text-gray-600">Gerencie suas categorias personalizadas</p>
        </div>
        <button type="button" id="btnNovaCat"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
            ➕ Nova Categoria
        </button>
    </div>
</div>

{{-- Lista de Categorias --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($categorias as $cat)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl" aria-hidden="true">{{ $cat->emoji ?? '📁' }}</span>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $cat->nome }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $cat->products_count }} produto(s)
                            @if($cat->descricao)
                                <br><span class="text-gray-400">{{ Str::limit($cat->descricao, 50) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex gap-1">
                    <button type="button"
                            class="text-gray-400 hover:text-blue-600 p-1"
                            aria-label="Editar categoria {{ $cat->nome }}"
                            onclick="editarCategoria(
                                @json($cat->id),
                                @json($cat->nome),
                                @json($cat->emoji),
                                @json($cat->cor),
                                @json($cat->descricao ?? ''),
                                @json($cat->ordem)
                            )">✏️</button>

                    <form action="{{ route('categories.destroy', $cat) }}" method="POST" class="inline"
                          data-confirm="Excluir a categoria '{{ $cat->nome }}'? Os produtos permanecerão, mas perderão esta categoria.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 p-1"
                                aria-label="Excluir categoria {{ $cat->nome }}">🗑️</button>
                    </form>
                </div>
            </div>
            <div class="h-1 rounded-b-lg" style="background-color: {{ $cat->cor ?? '#3b82f6' }}" aria-hidden="true"></div>
        </div>
    @empty
        <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <span class="text-4xl" aria-hidden="true">🏷️</span>
            <p class="text-gray-500 mt-2">Nenhuma categoria criada ainda.</p>
            <button type="button" id="btnNovaCatEmpty"
                    class="inline-flex items-center mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                ➕ Criar Primeira Categoria
            </button>
        </div>
    @endforelse
</div>

{{-- Modal Criar/Editar Categoria --}}
<div id="modalCategoria"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center"
     style="padding-top: 5vh;"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalCategoriaTitulo">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4" style="max-height: 90vh; overflow-y: auto;">

        <div class="sticky top-0 bg-white rounded-t-lg px-6 py-4 border-b z-10">
            <h3 id="modalCategoriaTitulo" class="text-lg font-semibold text-gray-800">➕ Nova Categoria</h3>
        </div>

        <div class="px-6 py-4">
            <form id="formCategoria" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="space-y-4">
                    <div>
                        <label for="inputNome" class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" name="nome" id="inputNome" required placeholder="Ex: Hortifruti"
                               class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emoji</label>
                        <div class="flex items-center gap-3">
                            <div id="emojiPreview"
                                 class="text-3xl w-14 h-14 flex items-center justify-center border-2 border-gray-300 rounded-lg bg-gray-50 cursor-pointer hover:border-blue-400 transition"
                                 role="button"
                                 tabindex="0"
                                 aria-label="Escolher emoji"
                                 onclick="toggleEmojiPicker()"
                                 onkeydown="if(event.key==='Enter'||event.key===' ')toggleEmojiPicker()">📁</div>
                            <input type="text" name="emoji" id="inputEmoji"
                                   placeholder="Ou digite o emoji" maxlength="10"
                                   class="flex-1 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
                                   oninput="document.getElementById('emojiPreview').textContent = this.value || '📁'">
                        </div>
                        <div id="emojiPicker" class="hidden mt-2 p-3 border border-gray-200 rounded-lg bg-white shadow-inner max-h-40 overflow-y-auto">
                            <p class="text-xs text-gray-500 mb-2 sticky top-0 bg-white pb-1">Clique em um emoji:</p>
                            <div class="grid grid-cols-10 gap-0.5" id="emojiGrid"></div>
                        </div>
                    </div>

                    <div>
                        <label for="inputCor" class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="color" name="cor" id="inputCor"
                                   class="w-10 h-10 rounded border cursor-pointer" value="#3b82f6"
                                   oninput="document.getElementById('inputCorTexto').value = this.value">
                            <input type="text" id="inputCorTexto" value="#3b82f6"
                                   class="w-28 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
                                   oninput="document.getElementById('inputCor').value = this.value">
                            <div class="flex gap-1" aria-label="Cores rápidas">
                                @foreach(['#ef4444'=>'Vermelho','#f59e0b'=>'Laranja','#22c55e'=>'Verde','#06b6d4'=>'Ciano','#3b82f6'=>'Azul','#8b5cf6'=>'Roxo','#ec4899'=>'Rosa'] as $hex => $label)
                                    <button type="button"
                                            class="w-5 h-5 rounded-full border border-gray-300"
                                            style="background:{{ $hex }}"
                                            onclick="setCor('{{ $hex }}')"
                                            title="{{ $label }}"
                                            aria-label="{{ $label }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="inputDescricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea name="descricao" id="inputDescricao" rows="2"
                                  placeholder="Descrição opcional..."
                                  class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"></textarea>
                    </div>

                    <div>
                        <label for="inputOrdem" class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                        <input type="number" name="ordem" id="inputOrdem" value="0" min="0"
                               class="w-24 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                    </div>
                </div>
            </form>
        </div>

        <div class="sticky bottom-0 bg-white rounded-b-lg px-6 py-4 border-t">
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnCancelarCat"
                        class="inline-flex items-center px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="button" onclick="document.getElementById('formCategoria').submit()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                    💾 Salvar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const EMOJIS = [
        '🥬','🥩','🧀','🍚','🍞','🥤','🧴','🧹','📦',
        '🍎','🍌','🍇','🦕','🥦','🌽','🍅','🥑','🍊',
        '🐄','🐔','🐟','🍗','🥓','🍖','🦐','🐑','🦃',
        '🥛','🍦','🍰','🥃','🍺','🍷','☕','🍵',
        '🍩','🍪','🎂','🍫','🍬','🍯','🧂','🎥','🍕',
        '🥜','🥖','🧅','🧄','🫙','🥫','🧻','🪬','🧼',
        '🛋️','📄','🛒','💰','⭐','🔥','💧','🎯','🏠',
    ];

    const grid = document.getElementById('emojiGrid');
    EMOJIS.forEach(e => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = e;
        btn.className = 'text-xl hover:bg-gray-100 rounded p-0.5 cursor-pointer';
        btn.setAttribute('aria-label', 'Emoji ' + e);
        btn.addEventListener('click', () => {
            document.getElementById('inputEmoji').value = e;
            document.getElementById('emojiPreview').textContent = e;
            document.getElementById('emojiPicker').classList.add('hidden');
        });
        grid.appendChild(btn);
    });

    window.toggleEmojiPicker = function() {
        document.getElementById('emojiPicker').classList.toggle('hidden');
    };

    window.setCor = function(hex) {
        document.getElementById('inputCor').value      = hex;
        document.getElementById('inputCorTexto').value = hex;
    };

    const modal           = document.getElementById('modalCategoria');
    const titulo          = document.getElementById('modalCategoriaTitulo');
    const form            = document.getElementById('formCategoria');
    const inputMethod     = document.getElementById('formMethod');
    const btnNovaCat      = document.getElementById('btnNovaCat');
    const btnNovaCatEmpty = document.getElementById('btnNovaCatEmpty');
    const btnCancelar     = document.getElementById('btnCancelarCat');

    function abrirModal() {
        modal.classList.remove('hidden');
        document.getElementById('inputNome').focus();
    }

    function fecharModal() {
        modal.classList.add('hidden');
        form.reset();
        form.action       = '{{ route('categories.store') }}';
        inputMethod.value = 'POST';
        titulo.textContent = '➕ Nova Categoria';
        document.getElementById('emojiPreview').textContent = '📁';
    }

    btnNovaCat?.addEventListener('click', abrirModal);
    btnNovaCatEmpty?.addEventListener('click', abrirModal);
    btnCancelar.addEventListener('click', fecharModal);
    modal.addEventListener('click', e => { if (e.target === modal) fecharModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });

    window.editarCategoria = function(id, nome, emoji, cor, descricao, ordem) {
        form.action       = `/categories/${id}`;
        inputMethod.value = 'PUT';
        titulo.textContent = '✏️ Editar Categoria';

        document.getElementById('inputNome').value        = nome;
        document.getElementById('inputEmoji').value       = emoji ?? '';
        document.getElementById('inputCor').value         = cor ?? '#3b82f6';
        document.getElementById('inputCorTexto').value    = cor ?? '#3b82f6';
        document.getElementById('inputDescricao').value   = descricao ?? '';
        document.getElementById('inputOrdem').value       = ordem ?? 0;
        document.getElementById('emojiPreview').textContent = emoji || '📁';

        abrirModal();
    };

    // Confirm antes de deletar
    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });
});
</script>
@endpush

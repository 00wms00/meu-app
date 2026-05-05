@extends('layouts.app')

@section('title', 'Gerenciar Categorias')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Categorias</h1>
            <p class="mt-1 text-gray-600">Gerencie suas categorias personalizadas</p>
        </div>
        <button id="btnNovaCat" class="btn-primary">➕ Nova Categoria</button>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4" role="status">
        ✅ {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        @foreach($errors->all() as $error)❌ {{ $error }}<br>@endforeach
    </div>
@endif

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
                    {{--
                        @json() garante escape seguro de qualquer string em contexto JS.
                        addslashes() não é suficiente: uma categoria com nome contendo
                        </script> ou aspas duplas quebraria o onclick.
                    --}}
                    <button class="text-gray-400 hover:text-blue-600 p-1"
                            aria-label="Editar categoria {{ $cat->nome }}"
                            onclick="editarCategoria(
                                @json($cat->id),
                                @json($cat->nome),
                                @json($cat->emoji),
                                @json($cat->cor),
                                @json($cat->descricao ?? ''),
                                @json($cat->ordem)
                            )">✏️</button>

                    {{-- data-confirm interceptado pelo globalConfirmBanner no layout --}}
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
            <button id="btnNovaCatEmpty" class="btn-primary mt-3">➕ Criar Primeira Categoria</button>
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

        {{-- Cabeçalho fixo --}}
        <div class="sticky top-0 bg-white rounded-t-lg px-6 py-4 border-b z-10">
            <h3 id="modalCategoriaTitulo" class="text-lg font-semibold text-gray-800">➕ Nova Categoria</h3>
        </div>

        {{-- Corpo --}}
        <div class="px-6 py-4">
            <form id="formCategoria" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="space-y-4">
                    <div>
                        <label for="inputNome" class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" name="nome" id="inputNome" class="form-control" required placeholder="Ex: Hortifrúti">
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
                            <input type="text" name="emoji" id="inputEmoji" class="form-control text-sm flex-1"
                                   placeholder="Ou digite o emoji" maxlength="10"
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
                            <input type="text" id="inputCorTexto" class="form-control text-sm w-28" value="#3b82f6"
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
                        <textarea name="descricao" id="inputDescricao" rows="2" class="form-control" placeholder="Descrição opcional..."></textarea>
                    </div>

                    <div>
                        <label for="inputOrdem" class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                        <input type="number" name="ordem" id="inputOrdem" class="form-control w-24" value="0" min="0">
                    </div>
                </div>
            </form>
        </div>

        {{-- Rodapé fixo --}}
        <div class="sticky bottom-0 bg-white rounded-b-lg px-6 py-4 border-t">
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnCancelarCat" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="button" onclick="document.getElementById('formCategoria').submit()" class="btn-primary text-sm">💾 Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Emoji grid ──────────────────────────────────────────────────────
    const EMOJIS = [
        '🥬','🥩','🧀','🍚','🍞','🥤','🧴','🧹','📦',
        '🍎','🍌','🍇','🥕','🥦','🌽','🍅','🥑','🍊',
        '🐄','🐔','🐟','🍗','🥓','🍖','🦐','🐑','🦃',
        '🥛','🧨','🍦','🍰','🧃','🍺','🍷','☕','🍵',
        '🍩','🍪','🎂','🍫','🍬','🍯','🧂','🎝️','🍕',
        '🥜','🥖','🧅','🧄','�abe8','🥫','🧻','🪥','🧼',
        '🪢','💄','�abe7','🧻a','🦽','�aaxa3','🧯','🛒','💰',
        '⭐','🔥','💧','🎯','🏠','🚗','📱','💡','🎵',
    ];

    const grid = document.getElementById('emojiGrid');
    EMOJIS.forEach(function (emoji) {
        const btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'text-lg w-8 h-8 flex items-center justify-center hover:bg-blue-100 rounded cursor-pointer transition';
        btn.textContent = emoji;
        btn.title     = emoji;
        btn.addEventListener('click', function (e) { e.stopPropagation(); setEmoji(emoji); });
        grid.appendChild(btn);
    });

    // ── Emoji picker ────────────────────────────────────────────────
    window.setEmoji = function (emoji) {
        document.getElementById('inputEmoji').value = emoji;
        document.getElementById('emojiPreview').textContent = emoji;
        document.getElementById('emojiPicker').classList.add('hidden');
    };
    window.toggleEmojiPicker = function () {
        document.getElementById('emojiPicker').classList.toggle('hidden');
    };

    document.addEventListener('click', function (e) {
        const picker  = document.getElementById('emojiPicker');
        const preview = document.getElementById('emojiPreview');
        if (picker && !picker.classList.contains('hidden')
            && !picker.contains(e.target) && e.target !== preview) {
            picker.classList.add('hidden');
        }
    });

    // ── Cor rápida ───────────────────────────────────────────────────
    window.setCor = function (cor) {
        document.getElementById('inputCor').value = cor;
        document.getElementById('inputCorTexto').value = cor;
    };

    // ── Modal ─────────────────────────────────────────────────────────
    const modal    = document.getElementById('modalCategoria');
    const titulo   = document.getElementById('modalCategoriaTitulo');
    const form     = document.getElementById('formCategoria');
    const btnNova  = document.getElementById('btnNovaCat');
    const btnEmpty = document.getElementById('btnNovaCatEmpty');
    const btnFechar= document.getElementById('btnCancelarCat');
    let   ultimoFoco = null;

    function abrirModal() {
        ultimoFoco = document.activeElement;
        modal.classList.remove('hidden');
        // Foca no primeiro campo ao abrir
        setTimeout(function () {
            document.getElementById('inputNome').focus();
        }, 50);
    }

    function fecharModal() {
        modal.classList.add('hidden');
        // Devolve foco a quem abriu o modal
        if (ultimoFoco) ultimoFoco.focus();
    }

    function resetForm() {
        titulo.textContent = '➕ Nova Categoria';
        form.action = @json(route('categories.store'));
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('inputNome').value  = '';
        document.getElementById('inputEmoji').value = '';
        document.getElementById('emojiPreview').textContent = '📁';
        document.getElementById('inputCor').value       = '#3b82f6';
        document.getElementById('inputCorTexto').value  = '#3b82f6';
        document.getElementById('inputDescricao').value = '';
        document.getElementById('inputOrdem').value     = '0';
        document.getElementById('emojiPicker').classList.add('hidden');
    }

    window.mostrarModalCriar = function () { resetForm(); abrirModal(); };

    window.editarCategoria = function (id, nome, emoji, cor, descricao, ordem) {
        titulo.textContent = '✏️ Editar Categoria';
        form.action = '/categories/' + id;
        document.getElementById('formMethod').value     = 'PUT';
        document.getElementById('inputNome').value      = nome;
        document.getElementById('inputEmoji').value     = emoji  || '';
        document.getElementById('emojiPreview').textContent = emoji || '📁';
        document.getElementById('inputCor').value       = cor    || '#3b82f6';
        document.getElementById('inputCorTexto').value  = cor    || '#3b82f6';
        document.getElementById('inputDescricao').value = descricao || '';
        document.getElementById('inputOrdem').value     = ordem  || 0;
        document.getElementById('emojiPicker').classList.add('hidden');
        abrirModal();
    };

    if (btnNova)  btnNova.addEventListener('click', window.mostrarModalCriar);
    if (btnEmpty) btnEmpty.addEventListener('click', window.mostrarModalCriar);
    btnFechar.addEventListener('click', fecharModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) fecharModal(); });

    // Escape fecha modal (sem interferir com o banner global do layout)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) fecharModal();
    });

    // Trap de foco dentro do modal (Tab / Shift+Tab)
    modal.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        const focusables = modal.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusables[0];
        const last  = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

});
</script>
@endpush

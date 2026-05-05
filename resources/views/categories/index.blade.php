@extends('layouts.app')

@section('title', 'Gerenciar Categorias')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Categorias</h1>
            <p class="mt-1 text-gray-600">Gerencie suas categorias personalizadas</p>
        </div>
        <button onclick="mostrarModalCriar()" class="btn-primary">
            ➕ Nova Categoria
        </button>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
    @foreach($errors->all() as $error) ❌ {{ $error }}<br> @endforeach
</div>
@endif

<!-- Lista de Categorias -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($categorias as $cat)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
        <div class="px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ $cat->emoji ?? '📁' }}</span>
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
                <button onclick="editarCategoria({{ $cat->id }}, '{{ addslashes($cat->nome) }}', '{{ $cat->emoji }}', '{{ $cat->cor }}', '{{ addslashes($cat->descricao ?? '') }}', {{ $cat->ordem }})" 
                        class="text-gray-400 hover:text-blue-600 p-1" title="Editar">✏️</button>
                <form action="{{ route('categories.destroy', $cat) }}" method="POST" class="inline"
                      onsubmit="return confirm('Excluir categoria?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-600 p-1" title="Excluir">🗑️</button>
                </form>
            </div>
        </div>
        <div class="h-1 rounded-b-lg" style="background-color: {{ $cat->cor ?? '#3b82f6' }}"></div>
    </div>
    @empty
    <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <span class="text-4xl">🏷️</span>
        <p class="text-gray-500 mt-2">Nenhuma categoria criada ainda.</p>
        <button onclick="mostrarModalCriar()" class="btn-primary mt-3">➕ Criar Primeira Categoria</button>
    </div>
    @endforelse
</div>

<!-- Modal Criar/Editar Categoria -->
<div id="modalCategoria" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center" style="padding-top: 5vh;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4" style="max-height: 90vh; overflow-y: auto;">
        <!-- Cabeçalho do Modal (fixo) -->
        <div class="sticky top-0 bg-white rounded-t-lg px-6 py-4 border-b z-10">
            <h3 class="text-lg font-semibold text-gray-800" id="modalTitulo">➕ Nova Categoria</h3>
        </div>
        
        <!-- Corpo do Modal -->
        <div class="px-6 py-4">
            <form id="formCategoria" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="space-y-4">
                    <!-- Nome -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" name="nome" id="inputNome" class="form-control" required placeholder="Ex: Hortifrúti">
                    </div>
                    
                    <!-- Emoji -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emoji</label>
                        <div class="flex items-center gap-3">
                            <div id="emojiPreview" class="text-3xl w-14 h-14 flex items-center justify-center border-2 border-gray-300 rounded-lg bg-gray-50 cursor-pointer hover:border-blue-400 transition" 
                                 onclick="toggleEmojiPicker()" title="Clique para escolher">📁</div>
                            <input type="text" name="emoji" id="inputEmoji" class="form-control text-sm flex-1" placeholder="Ou digite o emoji" maxlength="10" 
                                   oninput="document.getElementById('emojiPreview').textContent = this.value || '📁'">
                        </div>
                        
                        <!-- Grid de emojis -->
                        <div id="emojiPicker" class="hidden mt-2 p-3 border border-gray-200 rounded-lg bg-white shadow-inner max-h-40 overflow-y-auto">
                            <p class="text-xs text-gray-500 mb-2 sticky top-0 bg-white pb-1">Clique em um emoji:</p>
                            <div class="grid grid-cols-10 gap-0.5" id="emojiGrid">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="color" name="cor" id="inputCor" class="w-10 h-10 rounded border cursor-pointer" value="#3b82f6" 
                                   oninput="document.getElementById('inputCorTexto').value = this.value">
                            <input type="text" id="inputCorTexto" class="form-control text-sm w-28" value="#3b82f6" 
                                   oninput="document.getElementById('inputCor').value = this.value">
                            <div class="flex gap-1">
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#ef4444" onclick="setCor('#ef4444')" title="Vermelho"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#f59e0b" onclick="setCor('#f59e0b')" title="Laranja"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#22c55e" onclick="setCor('#22c55e')" title="Verde"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#06b6d4" onclick="setCor('#06b6d4')" title="Ciano"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#3b82f6" onclick="setCor('#3b82f6')" title="Azul"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#8b5cf6" onclick="setCor('#8b5cf6')" title="Roxo"></button>
                                <button type="button" class="w-5 h-5 rounded-full border border-gray-300" style="background:#ec4899" onclick="setCor('#ec4899')" title="Rosa"></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Descrição -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea name="descricao" id="inputDescricao" rows="2" class="form-control" placeholder="Descrição opcional..."></textarea>
                    </div>
                    
                    <!-- Ordem -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                        <input type="number" name="ordem" id="inputOrdem" class="form-control w-24" value="0" min="0">
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Rodapé do Modal (fixo) -->
        <div class="sticky bottom-0 bg-white rounded-b-lg px-6 py-4 border-t">
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="fecharModal()" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="button" onclick="document.getElementById('formCategoria').submit()" class="btn-primary text-sm">💾 Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const EMOJIS_CATEGORIAS = [
    '🥬','🥩','🧀','🍚','🍞','🥤','🧴','🧹','📦',
    '🍎','🍌','🍇','🥕','🥦','🌽','🍅','🥑','🍊',
    '🐄','🐔','🐟','🍗','🥓','🍖','🦐','🐑','🦃',
    '🥛','🧈','🍦','🍰','🧃','🍺','🍷','☕','🍵',
    '🍩','🍪','🎂','🍫','🍬','🍯','🧂','🍝','🍕',
    '🥜','🥖','🧅','🧄','🫘','🥫','🧻','🪥','🧼',
    '🪒','💄','🫧','🧺','🧽','🪣','🧯','🛒','💰',
    '⭐','🔥','💧','🎯','🏠','🚗','📱','💡','🎵',
];

function criarEmojiGrid() {
    const grid = document.getElementById('emojiGrid');
    if (!grid) return;
    grid.innerHTML = '';
    EMOJIS_CATEGORIAS.forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'text-lg w-8 h-8 flex items-center justify-center hover:bg-blue-100 rounded cursor-pointer transition';
        btn.textContent = emoji;
        btn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            setEmoji(emoji);
        };
        btn.title = emoji;
        grid.appendChild(btn);
    });
}

function setEmoji(emoji) {
    document.getElementById('inputEmoji').value = emoji;
    document.getElementById('emojiPreview').textContent = emoji;
    document.getElementById('emojiPicker').classList.add('hidden');
}

function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.classList.toggle('hidden');
}

function setCor(cor) {
    document.getElementById('inputCor').value = cor;
    document.getElementById('inputCorTexto').value = cor;
}

function mostrarModalCriar() {
    document.getElementById('modalTitulo').textContent = '➕ Nova Categoria';
    document.getElementById('formCategoria').action = '{{ route('categories.store') }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('inputNome').value = '';
    document.getElementById('inputEmoji').value = '';
    document.getElementById('emojiPreview').textContent = '📁';
    document.getElementById('inputCor').value = '#3b82f6';
    document.getElementById('inputCorTexto').value = '#3b82f6';
    document.getElementById('inputDescricao').value = '';
    document.getElementById('inputOrdem').value = '0';
    document.getElementById('emojiPicker').classList.add('hidden');
    document.getElementById('modalCategoria').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function editarCategoria(id, nome, emoji, cor, descricao, ordem) {
    document.getElementById('modalTitulo').textContent = '✏️ Editar Categoria';
    document.getElementById('formCategoria').action = '/categories/' + id;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('inputNome').value = nome;
    document.getElementById('inputEmoji').value = emoji || '';
    document.getElementById('emojiPreview').textContent = emoji || '📁';
    document.getElementById('inputCor').value = cor || '#3b82f6';
    document.getElementById('inputCorTexto').value = cor || '#3b82f6';
    document.getElementById('inputDescricao').value = descricao || '';
    document.getElementById('inputOrdem').value = ordem || 0;
    document.getElementById('emojiPicker').classList.add('hidden');
    document.getElementById('modalCategoria').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function fecharModal() {
    document.getElementById('modalCategoria').classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar modal ao clicar fora
document.getElementById('modalCategoria').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});

// Fechar picker ao clicar fora
document.addEventListener('click', function(e) {
    const picker = document.getElementById('emojiPicker');
    const preview = document.getElementById('emojiPreview');
    if (picker && !picker.classList.contains('hidden') && !picker.contains(e.target) && e.target !== preview) {
        picker.classList.add('hidden');
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    criarEmojiGrid();
});
</script>
@endpush

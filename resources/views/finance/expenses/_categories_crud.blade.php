{{--
  Modal CRUD de Categorias de Despesas
  Ativado via Alpine.js:  $dispatch('open-categories')
--}}
<div
  x-data="expenseCategoriesCrud()"
  x-init="init()"
  @open-categories.window="open = true"
  x-show="open"
  x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="background:rgba(0,0,0,.45);"
  @keydown.escape.window="open = false"
>
  <div
    class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"
    @click.stop
  >
    {{-- Cabeçalho --}}
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h2 class="font-semibold text-gray-800 text-base">🏷️ Gerenciar Categorias</h2>
      <button @click="open = false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto px-5 py-3 space-y-1.5">
      <template x-for="cat in categories" :key="cat.id">
        <div class="rounded-lg border border-transparent hover:border-gray-100 hover:bg-gray-50 transition group">

          {{-- Modo visualização --}}
          <template x-if="editingId !== cat.id">
            <div class="flex items-center gap-2 px-2 py-1.5">
              <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 border border-black/10"
                    :style="'background:#' + cat.cor.replace('#','')"></span>
              <span class="text-base leading-none" x-text="cat.emoji || '•'"></span>
              <span class="flex-1 text-sm text-gray-800" x-text="cat.nome"></span>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="startEdit(cat)"
                        class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-700"
                        title="Editar">✏️</button>
                <button @click="remove(cat)"
                        class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-700"
                        title="Excluir">🗑️</button>
              </div>
            </div>
          </template>

          {{-- Modo edição inline --}}
          <template x-if="editingId === cat.id">
            <div class="px-2 py-2 space-y-2">
              {{-- Linha 1: emoji picker + cor --}}
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 w-10 text-right shrink-0">Emoji</span>
                <div class="flex flex-wrap gap-1 flex-1">
                  <template x-for="e in emojiSuggestions" :key="e">
                    <button type="button"
                            @click="editEmoji = e"
                            :class="editEmoji === e ? 'ring-2 ring-blue-400 bg-blue-50' : 'bg-gray-50 hover:bg-gray-100'"
                            class="text-base w-8 h-8 rounded flex items-center justify-center transition"
                            x-text="e"></button>
                  </template>
                  <input x-model="editEmoji" type="text" maxlength="4"
                         placeholder="✏️"
                         class="form-control text-sm w-10 text-center px-1 py-0.5"
                         title="Ou digite o emoji">
                </div>
                <label class="text-xs text-gray-500 shrink-0">Cor</label>
                <input x-model="editCorHex" type="color"
                       class="w-8 h-8 rounded cursor-pointer border border-gray-200 shrink-0"
                       style="padding:2px">
              </div>
              {{-- Linha 2: nome --}}
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 w-10 text-right shrink-0">Nome</span>
                <input x-model="editNome" type="text" maxlength="80"
                       class="form-control text-sm flex-1"
                       @keydown.enter="saveEdit(cat)" @keydown.escape="cancelEdit()">
                <button @click="saveEdit(cat)"
                        class="text-xs px-3 py-1.5 rounded bg-green-600 text-white hover:bg-green-700 shrink-0">Salvar</button>
                <button @click="cancelEdit()"
                        class="text-xs px-2 py-1.5 rounded bg-gray-100 text-gray-600 hover:bg-gray-200 shrink-0">✕</button>
              </div>
            </div>
          </template>

        </div>
      </template>

      <p x-show="categories.length === 0" class="text-sm text-gray-400 py-4 text-center">
        Nenhuma categoria cadastrada.
      </p>
    </div>

    {{-- Formulário: nova categoria --}}
    <div class="border-t px-5 py-4 space-y-2">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nova Categoria</p>

      {{-- Emoji picker para novo --}}
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500 w-10 text-right shrink-0">Emoji</span>
        <div class="flex flex-wrap gap-1 flex-1">
          <template x-for="e in emojiSuggestions" :key="e">
            <button type="button"
                    @click="newEmoji = e"
                    :class="newEmoji === e ? 'ring-2 ring-blue-400 bg-blue-50' : 'bg-gray-50 hover:bg-gray-100'"
                    class="text-base w-8 h-8 rounded flex items-center justify-center transition"
                    x-text="e"></button>
          </template>
          <input x-model="newEmoji" type="text" maxlength="4"
                 placeholder="✏️"
                 class="form-control text-sm w-10 text-center px-1"
                 title="Ou digite o emoji">
        </div>
        <label class="text-xs text-gray-500 shrink-0">Cor</label>
        <input x-model="newCorHex" type="color"
               class="w-9 h-9 rounded cursor-pointer border border-gray-200 shrink-0"
               style="padding:2px">
      </div>

      {{-- Nome + botão --}}
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500 w-10 text-right shrink-0">Nome</span>
        <input x-model="newNome" type="text" maxlength="80"
               placeholder="Nome da categoria"
               class="form-control text-sm flex-1"
               @keydown.enter="addCategory()">
        <button @click="addCategory()"
                :disabled="!newNome.trim()"
                class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium shrink-0
                       hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
          + Adicionar
        </button>
      </div>

      <p x-show="errorMsg" x-text="errorMsg" class="text-xs text-red-600"></p>
    </div>
  </div>
</div>

@push('scripts')
<script>
function expenseCategoriesCrud() {
  // Normaliza cor: garante que fica SEM # para armazenar, COM # para o input[type=color]
  function stripHash(c)  { return c ? c.replace('#','') : '6366f1'; }
  function addHash(c)    { const s = stripHash(c); return s.startsWith('#') ? s : '#' + s; }

  return {
    open: false,
    categories: [],

    // novo
    newNome:   '',
    newCorHex: '#6366f1',   // com # para o input[type=color]
    newEmoji:  '',

    // edição
    editingId:  null,
    editNome:   '',
    editCorHex: '#6b7280',  // com # para o input[type=color]
    editEmoji:  '',

    errorMsg: '',

    emojiSuggestions: [
      '🏠','🍽️','🚗','🚌','💊','📚','🎮','👗','📺','📦',
      '💡','🛒','🐾','✈️','🎵','💻','🏋️','🍺','💈','🎁',
    ],

    init() {
      this.$watch('open', v => { if (v) this.load(); });
    },

    async load() {
      const r = await fetch('{{ route("finance.expense_categories.index") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      this.categories = await r.json();
    },

    async addCategory() {
      this.errorMsg = '';
      const nome = this.newNome.trim();
      if (!nome) return;

      const r = await fetch('{{ route("finance.expense_categories.store") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          nome,
          cor:   stripHash(this.newCorHex),
          emoji: this.newEmoji,
        }),
      });

      if (r.ok) {
        const cat = await r.json();
        this.categories.push(cat);
        this.newNome   = '';
        this.newEmoji  = '';
        this.newCorHex = '#6366f1';
        window.__expenseCats = null; // invalida cache das linhas
        window.dispatchEvent(new CustomEvent('categories-updated'));
      } else {
        const e = await r.json().catch(() => ({}));
        this.errorMsg = e.message || 'Erro ao salvar.';
      }
    },

    startEdit(cat) {
      this.editingId  = cat.id;
      this.editNome   = cat.nome;
      this.editCorHex = addHash(cat.cor);
      this.editEmoji  = cat.emoji || '';
    },

    cancelEdit() { this.editingId = null; },

    async saveEdit(cat) {
      const r = await fetch(`/financas/expense-categories/${cat.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          nome:  this.editNome,
          cor:   stripHash(this.editCorHex),
          emoji: this.editEmoji,
        }),
      });

      if (r.ok) {
        const updated = await r.json();
        const idx = this.categories.findIndex(c => c.id === cat.id);
        if (idx !== -1) this.categories[idx] = updated;
        this.editingId = null;
        window.__expenseCats = null;
        window.dispatchEvent(new CustomEvent('categories-updated'));
      }
    },

    async remove(cat) {
      if (!confirm(`Excluir a categoria "${cat.nome}"?\nAs despesas vinculadas não serão excluídas.`)) return;

      const r = await fetch(`/financas/expense-categories/${cat.id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (r.ok) {
        this.categories = this.categories.filter(c => c.id !== cat.id);
        window.__expenseCats = null;
        window.dispatchEvent(new CustomEvent('categories-updated'));
      }
    },
  };

  function stripHash(c)  { return (c || '6366f1').replace('#',''); }
  function addHash(c)    { const s = stripHash(c); return '#' + s; }
}
</script>
@endpush

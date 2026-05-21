{{--
  Modal CRUD de Categorias de Despesas
  Ativado via Alpine.js:  $dispatch('open-categories')
  ou clicando no botão "Gerenciar categorias"
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
        <div class="flex items-center gap-2 group">
          {{-- cor --}}
          <span class="w-4 h-4 rounded-full flex-shrink-0 border border-black/10"
                :style="'background:' + cat.cor"></span>

          {{-- emoji --}}
          <template x-if="editingId !== cat.id">
            <span class="text-base" x-text="cat.emoji || '•'"></span>
          </template>

          {{-- nome (modo visualização) --}}
          <template x-if="editingId !== cat.id">
            <span class="flex-1 text-sm text-gray-800" x-text="cat.nome"></span>
          </template>

          {{-- modo edição inline --}}
          <template x-if="editingId === cat.id">
            <div class="flex items-center gap-1.5 flex-1">
              <input x-model="editEmoji" type="text" maxlength="4"
                     placeholder="📦" class="form-control text-sm w-12 text-center px-1 py-0.5">
              <input x-model="editNome" type="text" maxlength="80"
                     class="form-control text-sm flex-1 py-0.5"
                     @keydown.enter="saveEdit(cat)" @keydown.escape="cancelEdit()">
              <input x-model="editCor" type="color" class="w-8 h-8 rounded cursor-pointer border-0">
              <button @click="saveEdit(cat)"
                      class="text-xs px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700">OK</button>
              <button @click="cancelEdit()"
                      class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 hover:bg-gray-200">✕</button>
            </div>
          </template>

          {{-- Ações --}}
          <template x-if="editingId !== cat.id">
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button @click="startEdit(cat)"
                      class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-700"
                      title="Editar">✏️</button>
              <button @click="remove(cat)"
                      class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-700"
                      title="Excluir">🗑️</button>
            </div>
          </template>
        </div>
      </template>

      <p x-show="categories.length === 0" class="text-sm text-gray-400 py-4 text-center">
        Nenhuma categoria cadastrada.
      </p>
    </div>

    {{-- Formulário: nova categoria --}}
    <div class="border-t px-5 py-4">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Nova Categoria</p>
      <div class="flex items-center gap-2">
        <input x-model="newEmoji" type="text" maxlength="4"
               placeholder="📦" class="form-control text-sm w-12 text-center px-1">
        <input x-model="newNome" type="text" maxlength="80"
               placeholder="Nome da categoria"
               class="form-control text-sm flex-1"
               @keydown.enter="addCategory()">
        <div class="relative" title="Escolha a cor">
          <input x-model="newCor" type="color"
                 class="w-9 h-9 rounded cursor-pointer border border-gray-200"
                 style="padding:2px">
        </div>
        <button @click="addCategory()"
                :disabled="!newNome.trim()"
                class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium
                       hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
          + Adicionar
        </button>
      </div>
      <p x-show="errorMsg" x-text="errorMsg" class="text-xs text-red-600 mt-1.5"></p>
    </div>
  </div>
</div>

@push('scripts')
<script>
function expenseCategoriesCrud() {
  return {
    open: false,
    categories: [],
    newNome: '',
    newCor: '#6366f1',
    newEmoji: '',
    editingId: null,
    editNome: '',
    editCor: '#6b7280',
    editEmoji: '',
    errorMsg: '',

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
        body: JSON.stringify({ nome, cor: this.newCor, emoji: this.newEmoji }),
      });

      if (r.ok) {
        const cat = await r.json();
        this.categories.push(cat);
        this.newNome = '';
        this.newEmoji = '';
        this.newCor = '#6366f1';
        // dispara evento para que o select do formulário recarregue
        window.dispatchEvent(new CustomEvent('categories-updated'));
      } else {
        const e = await r.json();
        this.errorMsg = e.message || 'Erro ao salvar.';
      }
    },

    startEdit(cat) {
      this.editingId = cat.id;
      this.editNome  = cat.nome;
      this.editCor   = cat.cor;
      this.editEmoji = cat.emoji || '';
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
        body: JSON.stringify({ nome: this.editNome, cor: this.editCor, emoji: this.editEmoji }),
      });

      if (r.ok) {
        const updated = await r.json();
        const idx = this.categories.findIndex(c => c.id === cat.id);
        if (idx !== -1) this.categories[idx] = updated;
        this.editingId = null;
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
        window.dispatchEvent(new CustomEvent('categories-updated'));
      }
    },
  };
}
</script>
@endpush

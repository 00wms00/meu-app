<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mercado e Finanças')</title>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded focus:shadow-lg focus:text-sm">
        Ir para o conteúdo principal
    </a>

    <nav class="bg-gray-800 shadow-lg" x-data="{ openDropdown: null, mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">

                <a href="{{ route('dashboard') }}" class="text-white font-bold text-xl flex-shrink-0">
                    📊 Mercado e Finanças
                </a>

                {{-- MENU DESKTOP --}}
                <div class="hidden md:flex items-center space-x-1">
                    @auth

                        {{-- Dashboard --}}
                        <a href="{{ route('dashboard') }}"
                           class="px-3 py-2 rounded text-sm {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            📈 Dashboard
                        </a>

                        {{-- Dropdown: Notas --}}
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'notas' ? null : 'notas'"
                                    :aria-expanded="openDropdown === 'notas'"
                                    aria-haspopup="true"
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->routeIs('invoices.*','import.*','relatorio.*','lancamento.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                📄 Notas
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'notas' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="openDropdown === 'notas'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="openDropdown = null"
                                 @keydown.escape.window="openDropdown = null"
                                 class="absolute left-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('import.create') }}"    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📥 Importar NFC-e</a>
                                <a href="{{ route('lancamento.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">✍️ Lançamento Manual</a>
                                <hr class="my-1 border-gray-100">
                                <a href="{{ route('invoices.index') }}"    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📋 Ver Notas</a>
                                <hr class="my-1 border-gray-100">
                                <a href="{{ route('relatorio.mensal') }}"  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📊 Relatório Mensal</a>
                                <a href="{{ route('relatorio.periodo') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📅 Relatório por Período</a>
                            </div>
                        </div>

                        {{-- Dropdown: Produtos --}}
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'produtos' ? null : 'produtos'"
                                    :aria-expanded="openDropdown === 'produtos'"
                                    aria-haspopup="true"
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->routeIs('products.*','categories.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                📦 Produtos
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'produtos' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="openDropdown === 'produtos'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="openDropdown = null"
                                 @keydown.escape.window="openDropdown = null"
                                 class="absolute left-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('products.index') }}"        class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📋 Todos os Produtos</a>
                                <hr class="my-1 border-gray-100">
                                <a href="{{ route('products.categorias') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🏷️ Categorizar</a>
                                <a href="{{ route('categories.index') }}"      class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">⚙️ Gerenciar Categorias</a>
                                <hr class="my-1 border-gray-100">
                                <a href="{{ route('products.agrupamentos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🔗 Agrupamentos</a>
                                <a href="{{ route('products.ml-interativo') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🤖 ML Interativo</a>
                            </div>
                        </div>

                        {{-- Dropdown: Compras --}}
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'compras' ? null : 'compras'"
                                    :aria-expanded="openDropdown === 'compras'"
                                    aria-haspopup="true"
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->routeIs('shopping-lists.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                🛒 Compras
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'compras' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="openDropdown === 'compras'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="openDropdown = null"
                                 @keydown.escape.window="openDropdown = null"
                                 class="absolute left-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('shopping-lists.index') }}"       class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📋 Listas de Compras</a>
                                <a href="{{ route('shopping-lists.planejamento') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🗓️ Planejamento</a>
                            </div>
                        </div>

                        {{-- Dropdown: Veículos (NOVO) --}}
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'veiculos' ? null : 'veiculos'"
                                    :aria-expanded="openDropdown === 'veiculos'"
                                    aria-haspopup="true"
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->routeIs('vehicles.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                🚗 Veículos
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'veiculos' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="openDropdown === 'veiculos'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="openDropdown = null"
                                 @keydown.escape.window="openDropdown = null"
                                 class="absolute left-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('vehicles.index') }}"              class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🚗 Meus Veículos</a>
                                <a href="{{ route('vehicles.create') }}"             class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">➕ Novo Veículo</a>
                                <hr class="my-1 border-gray-100">
                                <a href="{{ route('vehicles.report.monthly') }}"     class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📊 Relatório Mensal</a>
                                <a href="{{ route('vehicles.report.fuel-stations') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">⛽ Comparativo de Postos</a>
                            </div>
                        </div>

                        {{-- Dropdown: Finanças --}}
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'financas' ? null : 'financas'"
                                    :aria-expanded="openDropdown === 'financas'"
                                    aria-haspopup="true"
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->routeIs('finance.*','budgets.*','alertas.*','offers.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                💰 Finanças
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'financas' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="openDropdown === 'financas'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="openDropdown = null"
                                 @keydown.escape.window="openDropdown = null"
                                 class="absolute left-0 mt-1 w-56 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                {{-- Seção: Fluxo de Caixa --}}
                                <p class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Fluxo de Caixa</p>
                                <a href="{{ route('finance.incomes.index') }}"  class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700" role="menuitem">💰 Receitas</a>
                                <a href="{{ route('finance.expenses.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700" role="menuitem">📋 Despesas</a>

                                {{-- Seção: Cartões --}}
                                <hr class="my-1 border-gray-100">
                                <p class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Cartões de Crédito</p>
                                <a href="{{ route('finance.credit_cards.index') }}"     class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">💳 Gerenciar Cartões</a>
                                <a href="{{ route('finance.faturas.index') }}"          class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📄 Faturas</a>

                                {{-- Seção: Planejamento --}}
                                <hr class="my-1 border-gray-100">
                                <p class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Planejamento</p>
                                <a href="{{ route('budgets.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🎯 Orçamento</a>
                                <a href="{{ route('alertas.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🔔 Alertas de Preço</a>
                                <a href="{{ route('offers.index') }}"  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🏷️ Ofertas / Encartes</a>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded text-sm text-gray-400 hover:bg-red-600 hover:text-white transition" title="Sair" aria-label="Sair da conta">
                                🚶
                            </button>
                        </form>

                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded text-sm">
                            🔑 Login
                        </a>
                    @endauth
                </div>

                {{-- Botão hamburguer mobile --}}
                <button @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen"
                        aria-controls="mobile-menu"
                        aria-label="Abrir menu de navegação"
                        class="md:hidden text-gray-300 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            {{-- MENU MOBILE --}}
            <div id="mobile-menu" x-show="mobileOpen" class="md:hidden pb-3" x-transition>
                @auth
                    {{-- Dashboard --}}
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📈 Dashboard</a>

                    {{-- Notas --}}
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Notas</p>
                    <a href="{{ route('import.create') }}"    class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📥 Importar NFC-e</a>
                    <a href="{{ route('lancamento.create') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">✍️ Lançamento Manual</a>
                    <a href="{{ route('invoices.index') }}"    class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📋 Ver Notas</a>
                    <a href="{{ route('relatorio.mensal') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📊 Relatório Mensal</a>
                    <a href="{{ route('relatorio.periodo') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📅 Relatório por Período</a>

                    {{-- Produtos --}}
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Produtos</p>
                    <a href="{{ route('products.index') }}"         class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📦 Todos os Produtos</a>
                    <a href="{{ route('products.categorias') }}"    class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Categorizar</a>
                    <a href="{{ route('categories.index') }}"       class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">⚙️ Gerenciar Categorias</a>
                    <a href="{{ route('products.agrupamentos') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🔗 Agrupamentos</a>
                    <a href="{{ route('products.ml-interativo') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🤖 ML Interativo</a>

                    {{-- Compras --}}
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Compras</p>
                    <a href="{{ route('shopping-lists.index') }}"       class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📋 Listas de Compras</a>
                    <a href="{{ route('shopping-lists.planejamento') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🗓️ Planejamento</a>

                    {{-- Veículos (NOVO) --}}
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Veículos</p>
                    <a href="{{ route('vehicles.index') }}"                 class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🚗 Meus Veículos</a>
                    <a href="{{ route('vehicles.create') }}"                class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">➕ Novo Veículo</a>
                    <a href="{{ route('vehicles.report.monthly') }}"        class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📊 Relatório Mensal</a>
                    <a href="{{ route('vehicles.report.fuel-stations') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">⛽ Comparativo de Postos</a>

                    {{-- Finanças --}}
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Finanças</p>

                    <p class="px-3 pt-1 pb-1 text-xs font-medium text-gray-500">Fluxo de Caixa</p>
                    <a href="{{ route('finance.incomes.index') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">💰 Receitas</a>
                    <a href="{{ route('finance.expenses.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">📋 Despesas</a>

                    <p class="px-3 pt-1 pb-1 text-xs font-medium text-gray-500">Cartões de Crédito</p>
                    <a href="{{ route('finance.credit_cards.index') }}"     class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">💳 Gerenciar Cartões</a>
                    <a href="{{ route('finance.faturas.index') }}"          class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">📄 Faturas</a>

                    <p class="px-3 pt-1 pb-1 text-xs font-medium text-gray-500">Planejamento</p>
                    <a href="{{ route('budgets.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">🎯 Orçamento</a>
                    <a href="{{ route('alertas.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">🔔 Alertas de Preço</a>
                    <a href="{{ route('offers.index') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white pl-6">🏷️ Ofertas / Encartes</a>

                    {{-- Sair --}}
                    <div class="pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-3 py-2 rounded text-sm text-red-400 hover:bg-red-600 hover:text-white">🚶 Sair</button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <main id="main-content" class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <x-flash-messages />
        @yield('content')
    </main>

    @stack('scripts')

    <div id="globalConfirmBanner"
         class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-[200] bg-white border border-gray-300 shadow-xl rounded-lg px-5 py-4 flex items-center gap-4 w-full max-w-sm"
         role="alertdialog"
         aria-modal="true"
         aria-labelledby="globalConfirmMsg">
        <p id="globalConfirmMsg" class="text-sm text-gray-700 flex-1"></p>
        <div class="flex gap-2 flex-shrink-0">
            <button id="globalConfirmCancel"
                    class="px-3 py-1.5 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button id="globalConfirmOk"
                    class="px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition">
                Confirmar
            </button>
        </div>
    </div>

    <script>
    (function () {
        const banner    = document.getElementById('globalConfirmBanner');
        const msg       = document.getElementById('globalConfirmMsg');
        const btnOk     = document.getElementById('globalConfirmOk');
        const btnCancel = document.getElementById('globalConfirmCancel');
        let pending     = null;

        function mostrar(texto, form) {
            msg.textContent = texto;
            pending = form;
            banner.classList.remove('hidden');
            btnOk.focus();
        }

        function fechar() {
            banner.classList.add('hidden');
            pending = null;
        }

        btnOk.addEventListener('click', function () {
            if (pending) pending.submit();
            fechar();
        });
        btnCancel.addEventListener('click', fechar);
        banner.addEventListener('click', function (e) { if (e.target === banner) fechar(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !banner.classList.contains('hidden')) fechar();
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    mostrar(form.dataset.confirm, form);
                });
            });
        });
    })();
    </script>
</body>
</html>
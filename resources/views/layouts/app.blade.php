<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- csrf-token é necessário para requisicões AJAX/fetch (ex: axios, fetch nativo) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mercado e Finanças')</title>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine carregado via npm/vite no app.js — CDN removido para evitar conflito de versão --}}
</head>
<body class="h-full">

    {{-- Skip link: primeiro elemento focavel, obrigatório para acessibilidade (WCAG 2.4.1) --}}
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

                {{-- Menu Desktop --}}
                <div class="hidden md:flex items-center space-x-1">
                    @auth
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
                                 class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('import.create') }}"    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📥 Importar NFC-e</a>
                                <a href="{{ route('lancamento.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">✍️ Lançamento Manual</a>
                                <a href="{{ route('invoices.index') }}"    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📋 Ver Notas</a>
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
                                           {{ request()->routeIs('products.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
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
                                 class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 role="menu">
                                <a href="{{ route('products.index') }}"        class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">📋 Todos os Produtos</a>
                                <a href="{{ route('products.categorias') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🏷️ Categorizar</a>
                                <a href="{{ route('products.agrupamentos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700" role="menuitem">🔗 Agrupamentos</a>
                            </div>
                        </div>

                        {{-- Links diretos (desktop) --}}
                        <a href="{{ route('budgets.index') }}"
                           class="px-3 py-2 rounded text-sm {{ request()->routeIs('budgets.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            💰 Orçamento
                        </a>
                        <a href="{{ route('alertas.index') }}"
                           class="px-3 py-2 rounded text-sm {{ request()->routeIs('alertas.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            🔔 Alertas
                        </a>
                        <a href="{{ route('shopping-lists.index') }}"
                           class="px-3 py-2 rounded text-sm {{ request()->routeIs('shopping-lists.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            🛒 Compras
                        </a>
                        <a href="{{ route('offers.index') }}"
                           class="px-3 py-2 rounded text-sm {{ request()->routeIs('offers.*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            🏷️ Ofertas
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded text-sm text-gray-400 hover:bg-red-600 hover:text-white transition" title="Sair" aria-label="Sair da conta">
                                🚪
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

            {{-- Menu Mobile --}}
            <div id="mobile-menu" x-show="mobileOpen" class="md:hidden pb-3" x-transition>
                @auth
                    <a href="{{ route('dashboard') }}"            class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📈 Dashboard</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('import.create') }}"         class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📥 Importar NFC-e</a>
                    <a href="{{ route('lancamento.create') }}"     class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">✍️ Lançamento Manual</a>
                    <a href="{{ route('invoices.index') }}"        class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📋 Ver Notas</a>
                    <a href="{{ route('relatorio.mensal') }}"      class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📊 Relatório Mensal</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('products.index') }}"        class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📦 Produtos</a>
                    <a href="{{ route('products.categorias') }}"   class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Categorizar</a>
                    <a href="{{ route('products.agrupamentos') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🔗 Agrupamentos</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('categories.index') }}"      class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Categorias</a>
                    <a href="{{ route('budgets.index') }}"         class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">💰 Orçamento</a>
                    <a href="{{ route('alertas.index') }}"         class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🔔 Alertas</a>
                    <a href="{{ route('shopping-lists.index') }}"  class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🛒 Lista de Compras</a>
                    <a href="{{ route('offers.index') }}"          class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Ofertas</a>
                    <hr class="my-1 border-gray-700">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 rounded text-sm text-red-400 hover:bg-red-600 hover:text-white">🚪 Sair</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main id="main-content" class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <x-flash-messages />
        @yield('content')
    </main>

    @stack('scripts')

    {{--
        Utilitário global data-confirm.
        Qualquer <form data-confirm="mensagem"> no projeto terá submit interceptado
        e exibirá este banner em vez do confirm() nativo do browser.
        Não é necessário duplicar este JS em cada view.
    --}}
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

<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NFC-e Manager')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full">
    <!-- Navbar -->
    <nav class="bg-gray-800 shadow-lg" x-data="{ openDropdown: null, mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ url('/dashboard') }}" class="text-white font-bold text-xl flex-shrink-0">
                    📊 Mercado e Finanças
                </a>

                <!-- Menu Desktop -->
                <div class="hidden md:flex items-center space-x-1">
                    @auth
                        <!-- Dashboard -->
                        <a href="{{ url('/dashboard') }}" 
                           class="px-3 py-2 rounded text-sm {{ request()->is('dashboard') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            📈 Dashboard
                        </a>

                        <!-- Dropdown: Notas -->
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'notas' ? null : 'notas'" 
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->is('invoices*') || request()->is('import*') || request()->is('relatorio*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                📄 Notas
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'notas' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                 class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                                <a href="{{ route('import.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">📥 Importar NFC-e</a>
                                <a href="{{ route('lancamento.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">✍️ Lançamento Manual</a>
                                <a href="{{ route('invoices.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">📋 Ver Notas</a>
                                <a href="{{ route('relatorio.mensal') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">📊 Relatório Mensal</a>
                                <a href="{{ route('relatorio.periodo') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">📅 Relatório por Período</a>
                            </div>
                        </div>

                        <!-- Dropdown: Produtos -->
                        <div class="relative">
                            <button @click="openDropdown = openDropdown === 'produtos' ? null : 'produtos'" 
                                    class="px-3 py-2 rounded text-sm inline-flex items-center gap-1 transition
                                           {{ request()->is('products*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                📦 Produtos
                                <svg class="w-4 h-4 transition-transform" :class="openDropdown === 'produtos' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                 class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                                <a href="{{ route('products.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">📋 Todos os Produtos</a>
                                <a href="{{ route('products.categorias') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">🏷️ Categorizar</a>
                                <a href="{{ route('products.agrupamentos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">🔗 Agrupamentos</a>
                            </div>
                        </div>

                        <!-- Categorias 
                        <a href="{{ route('categories.index') }}" 
                           class="px-3 py-2 rounded text-sm {{ request()->is('categories*') ? 'bg-gray-900 text-white font-medium' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            🏷️ Categorias
                        </a>
-->
                        <a href="{{ route('budgets.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">💰 Orçamento</a>
<a href="{{ route('alertas.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">🔔 Alertas de Preço</a>

<a href="{{ route('shopping-lists.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">🛒 Lista de Compras</a>
<a href="{{ route('shopping-lists.planejamento') }}" 
   class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
    📅 Planejamento
</a>
<a href="{{ route('offers.index') }}" class="...">🏷️ Ofertas</a>

                        <!-- Sair -->
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded text-sm text-gray-400 hover:bg-red-600 hover:text-white transition">
                                🚪
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded text-sm">
                            🔑 Login
                        </a>
                    @endauth
                </div>

                <!-- Botão Mobile -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden text-gray-300 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Menu Mobile -->
            <div x-show="mobileOpen" class="md:hidden pb-3" x-transition>
                @auth
                    <a href="{{ url('/dashboard') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📈 Dashboard</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('import.create') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📥 Importar NFC-e</a>
                    <a href="{{ route('invoices.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📋 Ver Notas</a>
                    <a href="{{ route('relatorio.mensal') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📊 Relatório Mensal</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('products.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">📦 Produtos</a>
                    <a href="{{ route('products.categorias') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Categorizar</a>
                    <a href="{{ route('products.agrupamentos') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🔗 Agrupamentos</a>
                    <hr class="my-1 border-gray-700">
                    <a href="{{ route('categories.index') }}" class="block px-3 py-2 rounded text-sm text-gray-300 hover:bg-gray-700 hover:text-white">🏷️ Categorias</a>
                    <hr class="my-1 border-gray-700">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 rounded text-sm text-red-400 hover:bg-red-600 hover:text-white">🚪 Sair</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Conteúdo -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                @foreach ($errors->all() as $error) <p>❌ {{ $error }}</p> @endforeach
            </div>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>

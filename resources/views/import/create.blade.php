@extends('layouts.app')

@section('title', 'Importar NFC-e')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📄 Importar NFC-e</h1>
        <p class="mt-1 text-gray-500 text-sm">Importe suas notas fiscais eletrônicas de mercado ou abastecimento</p>
    </div>
    <a href="{{ route('invoices.index') }}" class="btn-outline-secondary text-sm">
        🗂️ Notas importadas
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Coluna principal: upload --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-base font-semibold text-gray-800">📤 Upload do Arquivo HTML</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('import.parse') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf

                    {{-- Zona de Drag & Drop --}}
                    <div id="dropZone"
                         class="relative flex flex-col items-center justify-center gap-3 border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer transition-all duration-200 hover:border-blue-400 hover:bg-blue-50">

                        <input type="file" name="arquivo" id="arquivo" accept=".html,.htm"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <div id="dropIcon" class="text-5xl select-none">📂</div>

                        <div id="dropText">
                            <p class="text-base font-semibold text-gray-700">Arraste o arquivo HTML aqui</p>
                            <p class="text-sm text-gray-400 mt-1">ou clique para selecionar &mdash; <span class="font-medium text-blue-600">.html / .htm</span></p>
                        </div>

                        <div id="fileSelected" class="hidden w-full">
                            <div class="inline-flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 max-w-sm mx-auto">
                                <span class="text-2xl">📄</span>
                                <div class="text-left">
                                    <p id="fileName" class="text-sm font-semibold text-blue-800 truncate max-w-[220px]"></p>
                                    <p id="fileSize" class="text-xs text-blue-500"></p>
                                </div>
                                <button type="button" id="btnRemoveFile"
                                        class="ml-auto text-blue-400 hover:text-red-500 transition text-lg leading-none"
                                        aria-label="Remover arquivo">&times;</button>
                            </div>
                        </div>
                    </div>

                    @error('arquivo')
                        <p class="text-red-500 text-sm mt-2">⚠️ {{ $message }}</p>
                    @enderror

                    {{-- Botão com estado de loading --}}
                    <button type="submit" id="btnSubmit" disabled
                            class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-md transition">
                        <span id="btnIcon">🔍</span>
                        <span id="btnLabel">Processar Nota Fiscal</span>
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Coluna lateral: instruções + histórico --}}
    <div class="space-y-6">

        {{-- Instruções --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-base font-semibold text-gray-800">ℹ️ Como importar</h2>
            </div>
            <div class="p-5 text-sm text-gray-600 space-y-4">
                <div>
                    <p class="font-semibold text-gray-700 mb-1">🛒 Compra no mercado</p>
                    <ol class="list-decimal list-inside space-y-1 text-gray-500">
                        <li>Escaneie o QR Code da NFC-e impressa</li>
                        <li>Ou acesse o portal da SEFAZ do seu estado</li>
                        <li>Pressione <kbd class="bg-gray-100 border border-gray-300 rounded px-1 py-0.5 text-xs font-mono">Ctrl+S</kbd> para salvar a página</li>
                        <li>Arraste ou selecione o <code class="bg-gray-100 rounded px-1">.html</code> gerado</li>
                    </ol>
                </div>
                <div>
                    <p class="font-semibold text-gray-700 mb-1">⛽ Posto de combustível</p>
                    <ol class="list-decimal list-inside space-y-1 text-gray-500">
                        <li>Siga os mesmos passos acima</li>
                        <li>Na próxima tela, selecione o veículo e informe o KM</li>
                    </ol>
                </div>
                <div class="pt-2 border-t border-gray-100 text-xs text-gray-400">
                    💡 O arquivo não é enviado para nenhum servidor externo &mdash; apenas lido localmente.
                </div>
            </div>
        </div>

        {{-- Link rápido para histórico --}}
        <a href="{{ route('invoices.index') }}"
           class="flex items-center gap-3 bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:bg-gray-50 transition group">
            <span class="text-2xl">🗂️</span>
            <div>
                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition">Ver notas importadas</p>
                <p class="text-xs text-gray-400">Histórico de todas as NFC-e importadas</p>
            </div>
            <svg class="ml-auto w-4 h-4 text-gray-300 group-hover:text-blue-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

    </div>
</div>

<script>
(function () {
    const dropZone     = document.getElementById('dropZone');
    const fileInput    = document.getElementById('arquivo');
    const dropIcon     = document.getElementById('dropIcon');
    const dropText     = document.getElementById('dropText');
    const fileSelected = document.getElementById('fileSelected');
    const fileName     = document.getElementById('fileName');
    const fileSize     = document.getElementById('fileSize');
    const btnRemove    = document.getElementById('btnRemoveFile');
    const btnSubmit    = document.getElementById('btnSubmit');
    const btnIcon      = document.getElementById('btnIcon');
    const btnLabel     = document.getElementById('btnLabel');
    const btnSpinner   = document.getElementById('btnSpinner');
    const form         = document.getElementById('importForm');

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function showFile(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        dropText.classList.add('hidden');
        dropIcon.classList.add('hidden');
        fileSelected.classList.remove('hidden');
        btnSubmit.disabled = false;
        dropZone.classList.remove('border-gray-300', 'hover:border-blue-400', 'hover:bg-blue-50');
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    }

    function clearFile() {
        fileInput.value = '';
        dropText.classList.remove('hidden');
        dropIcon.classList.remove('hidden');
        fileSelected.classList.add('hidden');
        btnSubmit.disabled = true;
        dropZone.classList.add('border-gray-300', 'hover:border-blue-400', 'hover:bg-blue-50');
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) showFile(fileInput.files[0]);
    });

    btnRemove.addEventListener('click', (e) => {
        e.stopPropagation();
        clearFile();
    });

    // Loading ao submeter
    form.addEventListener('submit', () => {
        btnSubmit.disabled = true;
        btnIcon.classList.add('hidden');
        btnSpinner.classList.remove('hidden');
        btnLabel.textContent = 'Processando...';
    });

    // Drag events
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50', 'scale-[1.01]');
        });
    });

    ['dragleave', 'dragend'].forEach(evt => {
        dropZone.addEventListener(evt, () => {
            dropZone.classList.remove('border-blue-500', 'scale-[1.01]');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'scale-[1.01]');
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['html', 'htm'].includes(ext)) {
            alert('Formato inválido. Selecione um arquivo .html ou .htm.');
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFile(file);
    });
})();
</script>
@endsection

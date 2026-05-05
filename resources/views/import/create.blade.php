@extends('layouts.app')

@section('title', 'Importar NFC-e')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">📄 Importar NFC-e</h1>
    <p class="mt-2 text-gray-600">Importe suas notas fiscais eletrônicas</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">📤 Upload do Arquivo HTML</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('import.parse') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf

                    {{-- Zona de Drag & Drop --}}
                    <div id="dropZone"
                         class="relative flex flex-col items-center justify-center gap-3 border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer transition-all duration-200
                                hover:border-blue-400 hover:bg-blue-50">

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

                    <button type="submit" id="btnSubmit" disabled
                            class="mt-6 inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-md transition">
                        🔍 Processar Nota Fiscal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">ℹ️ Instruções</h2>
            </div>
            <div class="p-6 text-sm text-gray-600 space-y-3">
                <p><strong>Como obter o HTML:</strong></p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Acesse o site da SEFAZ-MS</li>
                    <li>Faça a consulta da NFC-e</li>
                    <li>Pressione <kbd class="bg-gray-100 border border-gray-300 rounded px-1 py-0.5 text-xs font-mono">Ctrl+S</kbd> para salvar</li>
                    <li>Arraste ou selecione o arquivo <code class="bg-gray-100 rounded px-1">.html</code> gerado</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const dropZone    = document.getElementById('dropZone');
    const fileInput   = document.getElementById('arquivo');
    const dropIcon    = document.getElementById('dropIcon');
    const dropText    = document.getElementById('dropText');
    const fileSelected = document.getElementById('fileSelected');
    const fileName    = document.getElementById('fileName');
    const fileSize    = document.getElementById('fileSize');
    const btnRemove   = document.getElementById('btnRemoveFile');
    const btnSubmit   = document.getElementById('btnSubmit');

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
        // Atribui o arquivo ao input via DataTransfer
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFile(file);
    });
})();
</script>
@endsection

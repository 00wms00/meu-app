@extends('layouts.app')

@section('title', 'Importar NFC-e')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">📄 Importar NFC-e</h1>
    <p class="mt-2 text-gray-600">Importe suas notas fiscais eletrônicas</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg">HTML da NFC-e</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('import.parse') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="html" class="block text-sm font-medium text-gray-700 mb-2">
                            Cole o HTML da consulta
                        </label>
                        <textarea 
                            name="html" 
                            id="html" 
                            rows="12" 
                            class="form-control @error('html') border-red-500 @enderror" 
                            placeholder="Cole aqui o HTML salvo do site da SEFAZ-MS..."
                        >{{ old('html') }}</textarea>
                        @error('html')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-2">
                            Ou faça upload do arquivo HTML
                        </label>
                        <input 
                            type="file" 
                            name="arquivo" 
                            id="arquivo" 
                            class="form-control" 
                            accept=".html,.htm"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">
                        🔍 Processar Nota Fiscal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg">ℹ️ Instruções</h2>
            </div>
            <div class="card-body text-sm text-gray-600 space-y-3">
                <p><strong>Como obter o HTML:</strong></p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Acesse o site da SEFAZ-MS</li>
                    <li>Faça a consulta da NFC-e</li>
                    <li>Pressione Ctrl+S para salvar</li>
                    <li>Faça upload do arquivo .html</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

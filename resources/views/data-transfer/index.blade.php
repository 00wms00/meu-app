@extends('layouts.app')

@section('title', 'Transferir dados')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Transferir dados</h1>
            <p class="mt-1 text-sm text-gray-600">Leve seus dados e fotos para outra instalação local do aplicativo.</p>
        </div>

        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900">Exportar backup</h2>
            <p class="mt-2 text-sm text-gray-600">Baixe um ZIP com todos os registros do banco e os arquivos armazenados pelo aplicativo.</p>
            <a href="{{ route('data-transfer.export') }}"
               class="mt-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Baixar backup completo
            </a>
        </section>

        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900">Importar backup</h2>
            <p class="mt-2 text-sm text-gray-600">Selecione um ZIP gerado pelo aplicativo. Registros com o mesmo ID serão atualizados; os demais serão adicionados.</p>

            <form method="POST" action="{{ route('data-transfer.import') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="arquivo" class="block text-sm font-medium text-gray-700">Arquivo ZIP</label>
                    <input id="arquivo" name="arquivo" type="file" accept=".zip,application/zip" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('arquivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input name="confirmacao" type="checkbox" value="1" required class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>Confirmo que quero importar este backup e atualizar registros existentes pelos seus IDs.</span>
                </label>
                @error('confirmacao') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Importar backup
                </button>
            </form>
        </section>

        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Faça um backup antes de importar. A importação não apaga registros, mas pode substituir dados dos mesmos IDs.
        </div>
    </div>
@endsection
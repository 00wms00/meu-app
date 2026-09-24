<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use ZipArchive;

class DataTransferController extends Controller
{
    private const TABLES = [
        'users',
        'finance_incomes',
        'products',
        'invoices',
        'invoice_items',
        'categories',
        'budgets',
        'budget_categories',
        'price_alerts',
        'shopping_lists',
        'shopping_list_items',
        'offers',
        'vehicles',
        'vehicle_expenses',
        'fuel_entries',
        'maintenance_reminders',
        'credit_cards',
        'finance_nfes',
        'finance_expenses',
        'finance_credit_cards',
        'finance_credit_purchases',
        'finance_installments',
        'expense_categories',
    ];

    public function index(): View
    {
        return view('data-transfer.index');
    }

    public function export()
    {
        $payload = [
            'format' => 'meu-app-backup',
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'database' => config('database.default'),
            'tables' => [],
        ];

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $payload['tables'][$table] = DB::table($table)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'meu-app-backup-');
        $zip = new ZipArchive();

        if ($zip->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Não foi possível criar o arquivo de backup.');
        }

        $zip->addFromString(
            'backup.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        $publicPath = storage_path('app/public');
        if (File::isDirectory($publicPath)) {
            foreach (File::allFiles($publicPath) as $file) {
                $relativePath = ltrim(str_replace($publicPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $zip->addFile($file->getPathname(), 'storage/app/public/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
            }
        }

        $zip->close();

        return response()->download(
            $temporaryFile,
            'meu-app-backup-' . now()->format('Y-m-d-His') . '.zip',
            ['Content-Type' => 'application/zip'],
        )->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'confirmacao' => ['accepted'],
        ], [
            'arquivo.mimes' => 'Selecione um backup ZIP exportado pelo aplicativo.',
            'arquivo.max' => 'O backup não pode ultrapassar 50 MB.',
            'confirmacao.accepted' => 'Confirme que deseja importar os dados.',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($request->file('arquivo')->getRealPath()) !== true) {
            return back()->withErrors(['arquivo' => 'O arquivo ZIP não pôde ser aberto.']);
        }

        $json = $zip->getFromName('backup.json');
        if ($json === false) {
            $zip->close();
            return back()->withErrors(['arquivo' => 'O arquivo não contém um backup válido.']);
        }

        try {
            $backup = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->validateBackup($backup);
            $this->importTables($backup['tables']);
            $this->restoreFiles($zip);
        } catch (\Throwable $exception) {
            $zip->close();
            report($exception);

            return back()->withErrors(['arquivo' => 'Não foi possível importar o backup: ' . $exception->getMessage()]);
        }

        $zip->close();

        return back()->with('success', 'Backup importado com sucesso. Os dados existentes foram atualizados por ID.');
    }

    private function validateBackup(mixed $backup): void
    {
        if (!is_array($backup) || ($backup['format'] ?? null) !== 'meu-app-backup' || ($backup['version'] ?? null) !== 1) {
            throw new \InvalidArgumentException('Formato de backup incompatível.');
        }

        if (!isset($backup['tables']) || !is_array($backup['tables'])) {
            throw new \InvalidArgumentException('O backup não contém tabelas.');
        }
    }

    private function importTables(array $tables): void
    {
        DB::transaction(function () use ($tables): void {
            DB::statement("SET LOCAL session_replication_role = 'replica'");

            foreach (self::TABLES as $table) {
                if (!isset($tables[$table]) || !Schema::hasTable($table)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                foreach ($tables[$table] as $record) {
                    if (!is_array($record) || !array_key_exists('id', $record)) {
                        continue;
                    }

                    $record = array_intersect_key($record, array_flip($columns));
                    if ($table === 'fuel_entries') {
                        unset($record['preco_por_litro']);
                    }
                    if ($table === 'maintenance_reminders') {
                        unset($record['km_alerta']);
                    }

                    $values = $record;
                    unset($values['id']);

                    DB::table($table)->updateOrInsert(['id' => $record['id']], $values);
                }
            }

            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), true)");
                }
            }
        });
    }

    private function restoreFiles(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $prefix = 'storage/app/public/';

            if (!str_starts_with($name, $prefix) || str_contains($name, '..')) {
                continue;
            }

            $relativePath = substr($name, strlen($prefix));
            if ($relativePath === '' || str_ends_with($name, '/')) {
                continue;
            }

            $destination = storage_path('app/public/' . $relativePath);
            File::ensureDirectoryExists(dirname($destination));
            File::put($destination, $zip->getFromIndex($index));
        }
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class MarketplaceBackup extends Command
{
    protected $signature = 'marketplace:backup {--files : Include private product files in the archive}';

    protected $description = 'Create a zip backup of the database (and optionally private product files), pruning old backups';

    public function handle(): int
    {
        $dir = config('marketplace.backup_path') ?: storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.'backup-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            $this->error('Could not create archive at '.$path);
            return self::FAILURE;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $database = DB::connection()->getConfig('database');
            if ($database !== ':memory:' && is_file($database)) {
                $zip->addFile($database, 'database/database.sqlite');
            } else {
                $zip->addFromString('database/EXPORT-NOTE.txt', 'In-memory sqlite database: nothing to copy.');
            }
        } else {
            $dump = $this->mysqlDump();
            if ($dump === null) {
                $zip->addFromString('database/EXPORT-NOTE.txt', 'mysqldump was not available; back up the database manually.');
                $this->warn('mysqldump unavailable — archive contains no database dump.');
            } else {
                $zip->addFromString('database/dump.sql', $dump);
            }
        }

        if ($this->option('files')) {
            $root = storage_path('app'.DIRECTORY_SEPARATOR.'private');
            if (is_dir($root)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    $zip->addFile($file->getPathname(), 'files/'.str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)));
                }
            }
        }

        $zip->addFromString('manifest.json', json_encode(['created_at' => now()->toIso8601String(), 'driver' => $driver, 'includes_files' => (bool) $this->option('files'), 'app_version' => app()->version()], JSON_PRETTY_PRINT));
        $zip->close();

        $pruned = $this->prune($dir, (int) config('marketplace.backup_keep', 7));
        $this->info('Backup written to '.$path.($pruned ? " (pruned {$pruned} old backup(s))" : ''));
        return self::SUCCESS;
    }

    private function mysqlDump(): ?string
    {
        $config = DB::connection()->getConfig();
        $command = sprintf('mysqldump --host=%s --port=%s --user=%s %s %s 2>&1',
            escapeshellarg($config['host'] ?? '127.0.0.1'), escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? ''),
            ($config['password'] ?? '') !== '' ? '--password='.escapeshellarg($config['password']) : '',
            escapeshellarg($config['database'] ?? ''));
        $output = [];
        $status = 1;
        @exec($command, $output, $status);
        return $status === 0 ? implode("\n", $output) : null;
    }

    private function prune(string $dir, int $keep): int
    {
        $backups = collect(glob($dir.DIRECTORY_SEPARATOR.'backup-*.zip'))->sortDesc()->values();
        $stale = $backups->slice(max(1, $keep));
        $stale->each(fn (string $file) => @unlink($file));
        return $stale->count();
    }
}

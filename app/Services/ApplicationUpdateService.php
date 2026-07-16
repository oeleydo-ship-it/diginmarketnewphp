<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use ZipArchive;

/**
 * Applies an uploaded release zip over the codebase, CodeCanyon-style. The zip is validated
 * (real archive, no traversal entries, only whitelisted top-level paths), a backup is taken,
 * the app goes into maintenance mode for the copy + migrations, and every step is audited.
 */
class ApplicationUpdateService
{
    /** Top-level paths a release may replace. Nothing else — never .env, storage/, or uploads. */
    private const ALLOWED_ROOTS = ['app', 'bootstrap', 'config', 'database', 'lang', 'public', 'resources', 'routes', 'vendor', 'composer.json', 'composer.lock', 'artisan', 'package.json', 'vite.config.js', 'update-manifest.json'];

    /** Paths inside allowed roots that must survive an update untouched. */
    private const PROTECTED = ['public/storage', 'bootstrap/cache'];

    public function apply(UploadedFile $zip, User $admin): array
    {
        $target = rtrim((string) (config('marketplace.update_target') ?: base_path()), '/\\');
        $archive = new ZipArchive;
        if ($archive->open($zip->getRealPath()) !== true) {
            throw new RuntimeException('The uploaded file is not a readable zip archive.');
        }
        [$entries, $prefix] = $this->validated($archive);
        $manifest = $this->manifest($archive, $prefix);

        // Backup + maintenance mode guard the real deployment; the test suite copies into a
        // scratch target and must not toggle the developer's live storage/framework/down.
        $guarded = ! app()->runningUnitTests();
        if ($guarded) {
            Artisan::call('marketplace:backup');
            Artisan::call('down', ['--retry' => 30]);
        }
        $copied = 0;
        try {
            foreach ($entries as $entry) {
                $relative = substr($entry, strlen($prefix));
                if ($relative === '' || str_ends_with($entry, '/') || $this->isProtected($relative)) {
                    continue;
                }
                $destination = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (! is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0755, true);
                }
                $stream = $archive->getStream($entry);
                if ($stream === false) {
                    continue;
                }
                file_put_contents($destination, $stream);
                fclose($stream);
                $copied++;
            }
            $archive->close();
            Artisan::call('migrate', ['--force' => true]);
            if ($guarded) {
                foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
                    rescue(fn () => Artisan::call($command), report: false);
                }
            }
        } finally {
            if ($guarded) {
                Artisan::call('up');
            }
        }

        $version = (string) ($manifest['version'] ?? 'unversioned');
        Setting::updateOrCreate(['key' => 'system.version'], ['group' => 'system', 'value' => $version]);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'system.update_applied', 'entity_type' => null, 'entity_id' => null, 'new_values' => ['version' => $version, 'files' => $copied, 'archive' => $zip->getClientOriginalName()], 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

        return ['version' => $version, 'files' => $copied];
    }

    /**
     * Reject traversal/absolute entries outright, require every file to live under an allowed
     * root, and detect a single wrapping directory (GitHub-style archives) to strip as a prefix.
     *
     * @return array{0:list<string>,1:string}
     */
    private function validated(ZipArchive $archive): array
    {
        $entries = [];
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $archive->getNameIndex($i));
            if ($name === '') {
                continue;
            }
            if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('/^[a-zA-Z]:/', $name)) {
                throw new RuntimeException('The archive contains an unsafe path: '.$name);
            }
            $entries[] = $name;
        }
        if ($entries === []) {
            throw new RuntimeException('The archive is empty.');
        }
        $prefix = $this->wrapperPrefix($entries);
        foreach ($entries as $entry) {
            $relative = substr($entry, strlen($prefix));
            if ($relative === '' || str_ends_with($entry, '/')) {
                continue;
            }
            $root = explode('/', $relative, 2)[0];
            if (! in_array($root, self::ALLOWED_ROOTS, true)) {
                throw new RuntimeException('The archive touches a path updates may not modify: '.$relative);
            }
        }

        return [$entries, $prefix];
    }

    /** "release-1.2/" when every entry lives under one folder; empty string otherwise. */
    private function wrapperPrefix(array $entries): string
    {
        $first = explode('/', $entries[0], 2)[0].'/';
        foreach ($entries as $entry) {
            if (! str_starts_with($entry, $first)) {
                return '';
            }
        }

        // A wrapper only counts if it is not itself an app directory like "app/".
        return in_array(rtrim($first, '/'), self::ALLOWED_ROOTS, true) ? '' : $first;
    }

    /** @return array<string,mixed> */
    private function manifest(ZipArchive $archive, string $prefix): array
    {
        $raw = $archive->getFromName($prefix.'update-manifest.json');

        return $raw === false ? [] : (array) json_decode($raw, true);
    }

    private function isProtected(string $relative): bool
    {
        foreach (self::PROTECTED as $protected) {
            if ($relative === $protected || str_starts_with($relative, $protected.'/')) {
                return true;
            }
        }

        return false;
    }
}

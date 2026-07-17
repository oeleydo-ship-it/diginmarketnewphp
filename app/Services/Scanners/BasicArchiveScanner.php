<?php

namespace App\Services\Scanners;

use App\Contracts\FileScanner;
use ZipArchive;

/**
 * Default scanner when no ClamAV binary is configured. It cannot detect malware — it
 * verifies the archive is a readable zip whose entries carry no traversal paths, and
 * flags anything else as 'error' for an administrator to inspect (never 'infected',
 * which would block downloads on the word of a heuristic).
 */
class BasicArchiveScanner implements FileScanner
{
    public function scan(string $absolutePath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            return 'error';
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $zip->close();

                return 'error';
            }
        }
        $zip->close();

        return 'clean';
    }
}

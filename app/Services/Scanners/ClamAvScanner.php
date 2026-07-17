<?php

namespace App\Services\Scanners;

use App\Contracts\FileScanner;
use Symfony\Component\Process\Process;

/**
 * Shells out to a local ClamAV binary (clamscan/clamdscan) configured via CLAMAV_PATH.
 * Exit codes per ClamAV docs: 0 = clean, 1 = virus found, anything else = scanner error.
 */
class ClamAvScanner implements FileScanner
{
    public function scan(string $absolutePath): string
    {
        $process = new Process([(string) config('marketplace.clamav_path'), '--no-summary', $absolutePath]);
        $process->setTimeout(300);
        $process->run();

        return match ($process->getExitCode()) {
            0 => 'clean',
            1 => 'infected',
            default => 'error',
        };
    }
}

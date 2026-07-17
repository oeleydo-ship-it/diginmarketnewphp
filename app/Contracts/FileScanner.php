<?php

namespace App\Contracts;

interface FileScanner
{
    /**
     * Scan an uploaded product archive.
     *
     * @return string 'clean', 'infected' (blocks downloads), or 'error' (flagged for admin review).
     */
    public function scan(string $absolutePath): string;
}

<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Upload size limits shared by validation rules, form hints and the client-side size check.
 * Each limit is the configured value, capped by what PHP will actually accept, so the
 * form never promises more than the server can receive.
 */
class UploadLimits
{
    public static function archiveKb(): int
    {
        return self::capped((int) config('marketplace.max_archive_mb', 500));
    }

    public static function imageKb(): int
    {
        return self::capped((int) config('marketplace.max_image_mb', 10));
    }

    public static function archiveMb(): int
    {
        return intdiv(self::archiveKb(), 1024);
    }

    public static function imageMb(): int
    {
        return intdiv(self::imageKb(), 1024);
    }

    /** PHP's post_max_size in bytes (the whole request body); 0 means unlimited. */
    public static function postMaxBytes(): int
    {
        return self::iniBytes((string) ini_get('post_max_size'));
    }

    public static function postMaxMb(): int
    {
        return intdiv(self::postMaxBytes(), 1024 * 1024);
    }

    private static function capped(int $configuredMb): int
    {
        $kb = $configuredMb * 1024;
        $phpKb = (int) floor(UploadedFile::getMaxFilesize() / 1024);

        return $phpKb > 0 ? min($kb, $phpKb) : $kb;
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}

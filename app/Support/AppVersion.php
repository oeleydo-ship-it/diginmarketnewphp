<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Application versioning. The codebase ships a baseline in config/marketplace.php; applied
 * update packages record their manifest version in settings, which then takes precedence.
 */
class AppVersion
{
    public static function current(): string
    {
        return rescue(fn () => Setting::get('system.version'), null, false) ?: (string) config('marketplace.version', '1.0.0');
    }

    /** Strictly newer than the running version, per semantic comparison. */
    public static function isNewer(string $candidate): bool
    {
        return version_compare($candidate, self::current(), '>');
    }

    public static function isValid(string $candidate): bool
    {
        return (bool) preg_match('/^\d+(\.\d+){1,3}$/', $candidate);
    }

    /** @return array{version:?string,at:?string,previous:?string} */
    public static function lastUpdate(): array
    {
        return [
            'version' => Setting::get('system.version'),
            'at' => Setting::get('system.updated_at'),
            'previous' => Setting::get('system.previous_version'),
        ];
    }
}

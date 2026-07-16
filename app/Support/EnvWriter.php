<?php

namespace App\Support;

use RuntimeException;

/**
 * Minimal .env editor for the installer: replaces `KEY=value` lines in place (commented
 * `# KEY=` lines included) or appends missing keys. Values with spaces or #/quotes are quoted.
 */
class EnvWriter
{
    public function __construct(private ?string $path = null)
    {
        // marketplace.env_path lets tests point writes at a scratch file instead of the real .env.
        $this->path = $path ?: (config('marketplace.env_path') ?: base_path('.env'));
    }

    /** @param array<string,string|int|null> $pairs */
    public function set(array $pairs): void
    {
        if (! is_file($this->path) || ! is_writable($this->path)) {
            throw new RuntimeException('The .env file is missing or not writable.');
        }
        $contents = (string) file_get_contents($this->path);
        foreach ($pairs as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';
            $contents = preg_match($pattern, $contents)
             ? (string) preg_replace($pattern, $line, $contents, 1)
             : rtrim($contents, "\r\n").PHP_EOL.$line.PHP_EOL;
        }
        file_put_contents($this->path, $contents);
    }

    private function format(string|int|null $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}

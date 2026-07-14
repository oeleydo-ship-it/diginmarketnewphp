<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_encrypted', 'is_public'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean', 'is_public' => 'boolean'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember('setting:'.$key, 300, function () use ($key) {
            $setting = static::where('key', $key)->first();
            if (! $setting) return null;
            return $setting->is_encrypted ? rescue(fn () => decrypt((string) $setting->value), null, false) : $setting->value;
        });

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(string $key, ?string $value, string $group, bool $encrypted = false): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->fill(['group' => $group, 'is_encrypted' => $encrypted, 'value' => $encrypted && $value !== null && $value !== '' ? encrypt($value) : ($value ?? '')])->save();
        Cache::forget('setting:'.$key);

        return $setting;
    }
}

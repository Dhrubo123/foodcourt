<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        $decoded = json_decode((string) $setting->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    public static function putValue(string $key, mixed $value): void
    {
        $storedValue = is_string($value) ? $value : json_encode($value);

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue]
        );
    }
}

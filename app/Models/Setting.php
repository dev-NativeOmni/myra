<?php

namespace App\Models;

use App\Services\TenantContext;
use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'key', 'value'];

    protected static array $holidaysCache = [];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::cacheKey($key));
        self::$holidaysCache = [];

        return $setting;
    }

    public static function getGlobal(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('setting:global:'.$key, function () use ($key, $default) {
            $setting = self::withoutGlobalScope('institution')
                ->whereNull('institution_id')
                ->where('key', $key)
                ->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function setGlobal(string $key, ?string $value): ?self
    {
        Cache::forget('setting:global:'.$key);

        if ($value === null) {
            self::withoutGlobalScope('institution')
                ->whereNull('institution_id')
                ->where('key', $key)
                ->delete();

            return null;
        }

        $setting = self::withoutGlobalScope('institution')
            ->whereNull('institution_id')
            ->where('key', $key)
            ->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            $setting = new self;
            $setting->institution_id = null;
            $setting->key = $key;
            $setting->value = $value;
            $setting->saveQuietly();
        }

        return $setting;
    }

    /**
     * Get the public URL of the custom general platform Myra logo, or null if none is set.
     */
    public static function platformLogoUrl(): ?string
    {
        $path = self::getGlobal('platform_logo_path');
        if (! $path) {
            return null;
        }

        return Storage::disk(config('filesystems.uploads'))->url($path);
    }

    /**
     * Get list of national/institution holidays for a given year.
     * Checks database setting 'national_holidays_{year}' first, then falls back to defaults.
     */
    public static function getNationalHolidays(int $year): array
    {
        $cacheSlot = (TenantContext::getTenantId() ?? 'none').':'.$year;

        if (isset(self::$holidaysCache[$cacheSlot])) {
            return self::$holidaysCache[$cacheSlot];
        }

        $custom = self::get("national_holidays_{$year}");
        if ($custom) {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                return self::$holidaysCache[$cacheSlot] = $decoded;
            }
        }

        // Default Indonesian national holidays (fixed dates)
        return self::$holidaysCache[$cacheSlot] = [
            "{$year}-01-01", // Tahun Baru Masehi
            "{$year}-05-01", // Hari Buruh
            "{$year}-06-01", // Hari Lahir Pancasila
            "{$year}-08-17", // Hari Kemerdekaan RI
            "{$year}-12-25", // Hari Natal
        ];
    }

    /**
     * Settings are per institution, so the cache key must be too.
     */
    protected static function cacheKey(string $key): string
    {
        return 'setting:'.(TenantContext::getTenantId() ?? 'none').':'.$key;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $holidaysCache = [];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
        self::$holidaysCache = [];

        return $setting;
    }

    /**
     * Get list of national/institution holidays for a given year.
     * Checks database setting 'national_holidays_{year}' first, then falls back to defaults.
     */
    public static function getNationalHolidays(int $year): array
    {
        if (isset(self::$holidaysCache[$year])) {
            return self::$holidaysCache[$year];
        }

        $custom = self::get("national_holidays_{$year}");
        if ($custom) {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                return self::$holidaysCache[$year] = $decoded;
            }
        }

        // Default Indonesian national holidays (fixed dates)
        return self::$holidaysCache[$year] = [
            "{$year}-01-01", // Tahun Baru Masehi
            "{$year}-05-01", // Hari Buruh
            "{$year}-06-01", // Hari Lahir Pancasila
            "{$year}-08-17", // Hari Kemerdekaan RI
            "{$year}-12-25", // Hari Natal
        ];
    }
}

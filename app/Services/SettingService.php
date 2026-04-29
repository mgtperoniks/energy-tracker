<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Get setting value with inheritance chain: device -> machine -> department -> global
     */
    public static function get(string $key, $deviceId = null, $default = null)
    {
        // 1. Cek Device Scope
        if ($deviceId) {
            $cacheKeyDevice = "setting.{$key}.device.{$deviceId}";
            $deviceValue = Cache::rememberForever($cacheKeyDevice, function () use ($key, $deviceId) {
                $setting = Setting::where('key', $key)
                    ->where('scope_type', 'device')
                    ->where('scope_id', $deviceId)
                    ->first();
                return $setting ? $setting->value : null;
            });

            if ($deviceValue !== null) {
                return $deviceValue;
            }
        }

        // Future: 2. Cek Machine Scope (placeholder)
        // Future: 3. Cek Department Scope (placeholder)

        // 4. Fallback ke Global Scope
        $cacheKeyGlobal = "setting.{$key}.global";
        $globalValue = Cache::rememberForever($cacheKeyGlobal, function () use ($key) {
            $setting = Setting::where('key', $key)
                ->where('scope_type', 'global')
                ->whereNull('scope_id')
                ->first();
            return $setting ? $setting->value : null;
        });

        if ($globalValue !== null) {
            return $globalValue;
        }

        // 5. Hard Fallback to config file (Backward compatibility)
        return config("energy.{$key}", $default);
    }

    /**
     * Set setting value and flush cache
     */
    public static function set(string $key, $value, string $dataType, string $scopeType = 'global', $scopeId = null, $description = null): void
    {
        Setting::updateOrCreate(
            ['key' => $key, 'scope_type' => $scopeType, 'scope_id' => $scopeId],
            ['value' => $value, 'data_type' => $dataType, 'description' => $description]
        );

        // Flush Cache
        if ($scopeType === 'global') {
            Cache::forget("setting.{$key}.global");
        } else {
            Cache::forget("setting.{$key}.{$scopeType}.{$scopeId}");
        }
    }

    /**
     * Remove an override setting and flush cache
     */
    public static function forget(string $key, string $scopeType, $scopeId): void
    {
        Setting::where('key', $key)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        Cache::forget("setting.{$key}.{$scopeType}.{$scopeId}");
    }
}

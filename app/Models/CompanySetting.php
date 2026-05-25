<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'option', 'value'];

    protected static array $settingsCache = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('company_id', $company_id);
    }

    public static function setSettings($settings, $company_id)
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                [
                    'option' => $key,
                    'company_id' => $company_id,
                ],
                [
                    'option' => $key,
                    'company_id' => $company_id,
                    'value' => $value,
                ]
            );
        }

        unset(static::$settingsCache[$company_id]);
    }

    public static function getAllSettings($company_id)
    {
        $settings = static::whereCompany($company_id)->get()->mapWithKeys(function ($item) {
            return [$item['option'] => $item['value']];
        });

        static::$settingsCache[$company_id] = $settings->all();

        return $settings;
    }

    public static function getSettings($settings, $company_id)
    {
        return static::whereIn('option', $settings)->whereCompany($company_id)
            ->get()->mapWithKeys(function ($item) {
                return [$item['option'] => $item['value']];
            });
    }

    public static function getSetting($key, $company_id)
    {
        if (array_key_exists($key, static::$settingsCache[$company_id] ?? [])) {
            return static::$settingsCache[$company_id][$key];
        }

        $setting = static::whereOption($key)->whereCompany($company_id)->first();

        if ($setting) {
            static::$settingsCache[$company_id][$key] = $setting->value;

            return $setting->value;
        }

        static::$settingsCache[$company_id][$key] = null;

        return null;
    }
}

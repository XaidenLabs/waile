<?php

use App\Models\SettingModel;

/**
 * Branding / white-label helpers. Values are stored in the `settings` table
 * (group = 'branding') and edited under Admin → Branding.
 *
 * The whole group is loaded once per request and cached statically.
 */

if (! function_exists('branding')) {
    /**
     * Get a single branding value (or $default if unset/empty).
     */
    function branding(string $key, ?string $default = null): ?string
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $cache = (new SettingModel())->getGroup('branding');
            } catch (\Throwable) {
                $cache = []; // table missing (e.g. pre-migration) — degrade gracefully
            }
        }

        $value = $cache[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('app_name')) {
    /**
     * The application/brand name, with sensible fallbacks.
     */
    function app_name(): string
    {
        return branding('brand_name') ?? (env('APP_NAME') ?: 'WALE');
    }
}

if (! function_exists('available_locales')) {
    /**
     * Installed UI languages, as [code => display name].
     *
     * Auto-discovered by scanning app/Language/<code>/ — drop in a new
     * translated folder (e.g. app/Language/es/) and it appears here and in
     * the Branding → Application Language dropdown with no code changes.
     */
    function available_locales(): array
    {
        // Friendly names for common codes; unknown codes fall back to the code itself.
        $names = [
            'en' => 'English',    'es' => 'Español',   'fr' => 'Français',
            'de' => 'Deutsch',    'pt' => 'Português', 'it' => 'Italiano',
            'nl' => 'Nederlands', 'ru' => 'Русский',   'tr' => 'Türkçe',
            'ar' => 'العربية',    'hi' => 'हिन्दी',       'id' => 'Bahasa Indonesia',
            'zh' => '中文',        'ja' => '日本語',      'ko' => '한국어',
            'pl' => 'Polski',     'vi' => 'Tiếng Việt', 'th' => 'ไทย',
        ];

        $out = [];
        foreach (glob(APPPATH . 'Language/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $code       = basename($dir);
            $out[$code] = $names[$code] ?? strtoupper($code);
        }

        return $out ?: ['en' => 'English'];
    }
}

if (! function_exists('app_locale')) {
    /**
     * The active UI locale: the admin-selected Branding language if set and
     * installed, otherwise the framework default.
     */
    function app_locale(): string
    {
        $locale = branding('app_locale') ?: config('App')->defaultLocale;

        return is_dir(APPPATH . 'Language/' . $locale) ? $locale : config('App')->defaultLocale;
    }
}

if (! function_exists('demo_mode')) {
    /**
     * Read-only "review demo" mode. Enable by setting DEMO_MODE = true in .env
     * on the public demo instance. Shows a notice banner and blocks all
     * state-changing actions so buyers can browse every screen safely.
     */
    function demo_mode(): bool
    {
        return filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('locale_attrs')) {
    /**
     * `lang="…"` (plus `dir="rtl"` for right-to-left languages) for the <html> tag.
     */
    function locale_attrs(): string
    {
        $locale = app_locale();
        $rtl    = in_array($locale, ['ar', 'he', 'fa', 'ur', 'ps', 'sd'], true);

        return 'lang="' . esc($locale, 'attr') . '"' . ($rtl ? ' dir="rtl"' : '');
    }
}

if (! function_exists('brand_asset')) {
    /**
     * Full URL to a stored branding asset (logo/favicon), or null if unset
     * or the stored file no longer exists (prevents broken images).
     */
    function brand_asset(string $key): ?string
    {
        $path = branding($key);

        if (! $path) {
            return null;
        }

        return is_file(FCPATH . ltrim($path, '/')) ? base_url($path) : null;
    }
}

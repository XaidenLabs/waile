<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Applies the admin-selected UI language (Branding → Application Language) to
 * every request, so lang() resolves against the right app/Language/<locale>/ files.
 *
 * The chosen locale must also be listed in Config\App::$supportedLocales — that
 * list is auto-populated from the installed language folders in App's constructor.
 */
class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('setting');

        $locale = app_locale();

        // Only switch when the translation folder actually exists (app_locale()
        // already guarantees this, but keep the guard for safety on fresh installs).
        if (is_dir(APPPATH . 'Language/' . $locale)) {
            $request->setLocale($locale);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

/**
 * Super Admin: white-label / branding settings.
 * Stored in the `settings` table (group = 'branding'); read via the branding() helper.
 */
class BrandingController extends BaseController
{
    protected SettingModel $settings;

    /** Where uploaded logo/favicon files live (web-accessible). */
    private string $uploadDir = 'uploads/branding';

    public function __construct()
    {
        $this->settings = new SettingModel();

        if (session()->get('user_role') !== 'superadmin') {
            redirect()->to('/panel/dashboard')->with('error', lang('Messages.accessDenied'))->send();
            exit;
        }
    }

    public function index()
    {
        return view('admin/settings/branding', [
            'title' => lang('Titles.branding'),
            'branding' => $this->settings->getGroup('branding'),
        ]);
    }

    public function save()
    {
        $rules = [
            'brand_name'     => 'required|max_length[100]',
            'primary_color'  => 'permit_empty|regex_match[/^#[0-9a-fA-F]{6}$/]',
            'login_heading'  => 'permit_empty|max_length[120]',
            'login_subtitle' => 'permit_empty|max_length[160]',
            'footer_text'    => 'permit_empty|max_length[200]',
            'logo'           => 'permit_empty|is_image[logo]|max_size[logo,1024]|ext_in[logo,png,jpg,jpeg,svg,webp]',
            'favicon'        => 'permit_empty|is_image[favicon]|max_size[favicon,256]|ext_in[favicon,png,ico,svg]',
        ];

        $messages = [
            'primary_color' => ['regex_match' => lang('Messages.primaryColorHex')],
            'logo'          => ['max_size' => lang('Messages.logoMax1mb')],
            'favicon'       => ['max_size' => lang('Messages.faviconMax256')],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Only accept a locale we actually ship a translation folder for.
        helper('setting');
        $locale = (string) $this->request->getPost('app_locale');
        if (! array_key_exists($locale, available_locales())) {
            $locale = config('App')->defaultLocale;
        }

        $values = [
            'brand_name'     => trim($this->request->getPost('brand_name')),
            'primary_color'  => trim($this->request->getPost('primary_color') ?? ''),
            'login_heading'  => trim($this->request->getPost('login_heading') ?? ''),
            'login_subtitle' => trim($this->request->getPost('login_subtitle') ?? ''),
            'footer_text'    => trim($this->request->getPost('footer_text') ?? ''),
            'app_locale'     => $locale,
        ];

        // Handle optional image uploads
        if ($path = $this->storeUpload('logo')) {
            $values['logo_path'] = $path;
        }
        if ($path = $this->storeUpload('favicon')) {
            $values['favicon_path'] = $path;
        }

        $this->settings->setGroup('branding', $values);

        return redirect()->to('/panel/settings/branding')
            ->with('success', lang('Messages.brandingUpdated'));
    }

    /**
     * Remove the logo or favicon.
     */
    public function removeAsset(string $which)
    {
        $key = $which === 'favicon' ? 'favicon_path' : 'logo_path';

        $current = $this->settings->getValue($key, '', 'branding');
        if ($current && is_file(FCPATH . $current)) {
            @unlink(FCPATH . $current);
        }
        $this->settings->setValue($key, '', 'branding');

        return redirect()->to('/panel/settings/branding')
            ->with('success', $which === 'favicon' ? lang('Messages.faviconRemoved') : lang('Messages.logoRemoved'));
    }

    // ------------------------------------------------------------------

    /**
     * Validate, move an uploaded file into the public upload dir, and return its
     * web-relative path (or null if no valid file was uploaded).
     */
    private function storeUpload(string $field): ?string
    {
        $file = $this->request->getFile($field);

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $absDir = FCPATH . $this->uploadDir;
        if (! is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $newName = $field . '_' . bin2hex(random_bytes(6)) . '.' . $file->getExtension();
        $file->move($absDir, $newName);

        return $this->uploadDir . '/' . $newName;
    }
}

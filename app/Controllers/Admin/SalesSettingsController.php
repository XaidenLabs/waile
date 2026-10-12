<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\EncryptionService;

/**
 * Super Admin: configure the public sales page copy (group 'sales') and the
 * payment gateway (group 'payment'). Read by the public SalesController and
 * the PaymentManager.
 */
class SalesSettingsController extends BaseController
{
    protected SettingModel $settings;

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
        $payment = $this->settings->getGroup('payment');
        if (! empty($payment['paypal_secret'])) {
            $payment['secret_set'] = true;
        }

        return view('admin/settings/sales', [
            'title' => lang('Titles.salesPayments'),
            'sales'    => $this->settings->getGroup('sales'),
            'payment'  => $payment,
            'pageUrl'  => site_url('pricing'),
        ]);
    }

    public function save()
    {
        $rules = [
            'active_gateway' => 'required|in_list[paypal]',
            'currency'       => 'required|alpha|exact_length[3]',
            'paypal_mode'    => 'required|in_list[sandbox,live]',
            'hero_headline'  => 'permit_empty|max_length[150]',
            'hero_subheadline' => 'permit_empty|max_length[300]',
            'hero_cta'       => 'permit_empty|max_length[40]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // --- Sales page content ---
        $this->settings->setGroup('sales', [
            'hero_headline'    => trim($this->request->getPost('hero_headline') ?? ''),
            'hero_subheadline' => trim($this->request->getPost('hero_subheadline') ?? ''),
            'hero_cta'         => trim($this->request->getPost('hero_cta') ?? ''),
            'features'         => trim($this->request->getPost('features') ?? ''),
        ]);

        // --- Payment gateway ---
        $payment = [
            'active_gateway'   => $this->request->getPost('active_gateway'),
            'currency'         => strtoupper(trim($this->request->getPost('currency'))),
            'paypal_mode'      => $this->request->getPost('paypal_mode'),
            'paypal_client_id' => trim($this->request->getPost('paypal_client_id') ?? ''),
        ];

        // Only update the secret when a new one is entered (encrypt it).
        $newSecret = (string) $this->request->getPost('paypal_secret');
        if ($newSecret !== '') {
            $payment['paypal_secret'] = (new EncryptionService())->encrypt($newSecret);
        }

        $this->settings->setGroup('payment', $payment);

        return redirect()->to('/panel/settings/sales')
            ->with('success', lang('Messages.salesSettingsSaved'));
    }
}

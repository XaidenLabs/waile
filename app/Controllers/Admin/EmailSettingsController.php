<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\EmailService;
use App\Services\EncryptionService;

/**
 * Super Admin: manage the platform's global SMTP / email settings.
 * Stored in the `settings` table (group = 'smtp'); used by EmailService.
 */
class EmailSettingsController extends BaseController
{
    protected SettingModel $settings;

    public function __construct()
    {
        $this->settings = new SettingModel();

        // Platform-level config — superadmin only.
        if (session()->get('user_role') !== 'superadmin') {
            redirect()->to('/panel/dashboard')->with('error', lang('Messages.accessDenied'))->send();
            exit;
        }
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $smtp = $this->settings->getGroup('smtp');

        // Mask the password for display
        if (! empty($smtp['password'])) {
            $smtp['password_set'] = true;
        }

        return view('admin/settings/email', [
            'title' => lang('Titles.emailSettings'),
            'smtp'  => $smtp,
        ]);
    }

    public function save()
    {
        $rules = [
            'protocol'   => 'required|in_list[smtp,mail]',
            'host'       => 'permit_empty|max_length[255]', // only needed for SMTP; UI hides it for mail()
            'port'       => 'permit_empty|integer',
            'encryption' => 'permit_empty|in_list[,ssl,tls]',
            'username'   => 'permit_empty|max_length[255]',
            'from_email' => 'required|valid_email|max_length[255]',
            'from_name'  => 'permit_empty|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $values = [
            'protocol'   => $this->request->getPost('protocol'),
            'host'       => trim($this->request->getPost('host') ?? ''),
            'port'       => trim($this->request->getPost('port') ?? ''),
            'encryption' => $this->request->getPost('encryption') ?? '',
            'username'   => trim($this->request->getPost('username') ?? ''),
            'from_email' => trim($this->request->getPost('from_email')),
            'from_name'  => trim($this->request->getPost('from_name') ?? 'WALE'),
        ];

        // Only update the password when a new one is entered (encrypt it)
        $newPassword = (string) $this->request->getPost('password');
        if ($newPassword !== '') {
            $enc = new EncryptionService();
            $values['password'] = $enc->encrypt($newPassword);
        }

        $this->settings->setGroup('smtp', $values);

        return redirect()->to('/panel/settings/email')
            ->with('success', lang('Messages.emailSettingsSaved'));
    }

    /**
     * AJAX: send a test email to verify the configuration.
     */
    public function sendTest()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        $to = trim((string) $this->request->getPost('test_email'));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'success'   => false,
                'message'   => lang('Messages.enterValidTestEmail'),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        try {
            $result = (new EmailService())->sendTest($to);
            $result['csrf_hash'] = csrf_hash(); // refresh token for repeat clicks
            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', 'SMTP test error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success'   => false,
                'message'   => $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ]);
        }
    }
}

<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\EmailService;

/**
 * Handles web (session-based) authentication for the admin panel.
 * Routes: /login, /register, /logout
 */
class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function loginForm()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/panel/dashboard');
        }

        return view('auth/login');
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email|max_length[255]',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $user     = $this->userModel->findByEmail($email);

        if (! $user || ! $this->userModel->verifyPassword($password, $user['password'])) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.invalidCredentials'));
        }

        if ($user['status'] !== 'active') {
            return redirect()->back()
                ->with('error', lang('Messages.accountStatusBlocked', [$user['status']]));
        }

        // Persist tenant context in session
        session()->set([
            'logged_in'    => true,
            'user_id'      => (int) $user['id'],
            'user_name'    => $user['name'],
            'user_email'   => $user['email'],
            'user_role'    => $user['role'],
            'company_name' => $user['company_name'] ?? '',
        ]);

        $this->userModel->updateLastLogin((int) $user['id']);

        // Force a password change if this account still uses a default/seeded one.
        if (! empty($user['force_password_change'])) {
            session()->set('force_pw', true);
            return redirect()->to('/panel/security/password')
                ->with('error', lang('Messages.setNewPasswordFirst'));
        }

        return redirect()->to('/panel/dashboard')->with('success', lang('Messages.welcomeBack', [$user['name']]));
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function registerForm()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/panel/dashboard');
        }

        return view('auth/register');
    }

    public function register()
    {
        $rules = [
            'name'             => 'required|min_length[2]|max_length[150]',
            'company_name'     => 'permit_empty|max_length[255]',
            'email'            => 'required|valid_email|max_length[255]|is_unique[users.email]',
            'password'         => 'required|min_length[8]|max_length[72]',
            'confirm_password' => 'required|matches[password]',
        ];

        $messages = [
            'email'    => ['is_unique' => lang('Messages.emailAlreadyRegistered')],
            'password' => ['min_length' => lang('Messages.passwordMin8')],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $userId = $this->userModel->insert([
            'name'         => trim($this->request->getPost('name')),
            'company_name' => trim($this->request->getPost('company_name') ?? ''),
            'email'        => strtolower(trim($this->request->getPost('email'))),
            'password'     => $this->request->getPost('password'), // hashed by model hook
            'role'         => 'user',
            'status'       => 'active',
        ]);

        if (! $userId) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.registrationFailed'));
        }

        return redirect()->to('/login')
            ->with('success', lang('Messages.accountCreated'));
    }

    // ------------------------------------------------------------------
    // Forgot password — request a reset link
    // ------------------------------------------------------------------

    public function forgotPasswordForm()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/panel/dashboard');
        }

        return view('auth/forgot_password');
    }

    public function forgotPassword()
    {
        if (! $this->validate(['email' => 'required|valid_email'])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = strtolower(trim($this->request->getPost('email')));
        $user  = $this->userModel->findByEmail($email);

        // Only act for real, active accounts — but always show the same message
        // afterwards so we never reveal whether an email is registered.
        if ($user && $user['status'] === 'active') {
            $token = bin2hex(random_bytes(32));

            $this->userModel->skipValidation(true)->update($user['id'], [
                'reset_token'   => $token,
                'reset_expires' => date('Y-m-d H:i:s', time() + 3600), // 1 hour
            ]);

            $link = site_url('reset-password/' . $token);
            $name = esc($user['name']);
            $app  = esc(app_name());
            $body = '<div style="font-family:sans-serif;font-size:15px;color:#2d3748">'
                  . '<p>' . lang('Emails.hiName', [$name]) . '</p>'
                  . '<p>' . lang('Emails.resetBody', [$app]) . '</p>'
                  . '<p style="margin:24px 0"><a href="' . $link . '" '
                  . 'style="background:#128C7E;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:600">'
                  . lang('Emails.resetButton') . '</a></p>'
                  . '<p style="font-size:13px;color:#718096">' . lang('Emails.resetPasteLink') . '<br>'
                  . '<a href="' . $link . '">' . $link . '</a></p>'
                  . '<hr><p style="font-size:12px;color:#718096">' . lang('Emails.resetIgnore') . '</p></div>';

            try {
                (new EmailService())->send($user['email'], lang('Emails.resetSubject', [app_name()]), $body);
            } catch (\Throwable $e) {
                log_message('error', 'Forgot-password email failed: ' . $e->getMessage());
            }
        }

        return redirect()->to('/login')->with('success',
            lang('Messages.resetLinkSent'));
    }

    // ------------------------------------------------------------------
    // Reset password — set a new password from the emailed link
    // ------------------------------------------------------------------

    public function resetPasswordForm(string $token)
    {
        if (! $this->findValidResetUser($token)) {
            return redirect()->to('/login')
                ->with('error', lang('Messages.resetLinkInvalid'));
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword(string $token)
    {
        $rules = [
            'password'         => 'required|min_length[8]|max_length[72]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $user = $this->findValidResetUser($token);
        if (! $user) {
            return redirect()->to('/login')
                ->with('error', lang('Messages.resetLinkInvalid'));
        }

        $this->userModel->skipValidation(true)->update($user['id'], [
            'password'      => $this->request->getPost('password'), // hashed by model hook
            'reset_token'   => null,
            'reset_expires' => null,
        ]);

        return redirect()->to('/login')
            ->with('success', lang('Messages.passwordResetDone'));
    }

    /**
     * Find a user by a non-expired reset token, or null.
     */
    private function findValidResetUser(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        return $this->userModel
            ->where('reset_token', $token)
            ->where('reset_expires >=', date('Y-m-d H:i:s'))
            ->first();
    }

    // ------------------------------------------------------------------
    // Logout
    // ------------------------------------------------------------------

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', lang('Messages.loggedOut'));
    }
}

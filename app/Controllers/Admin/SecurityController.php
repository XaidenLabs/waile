<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

/**
 * Forced password change for accounts flagged force_password_change (the seeded
 * default super-admin / demo user). The auth filter keeps the user on this page
 * until they set a new password.
 */
class SecurityController extends BaseController
{
    public function passwordForm()
    {
        return view('admin/security/force_password');
    }

    public function updatePassword()
    {
        $rules = [
            'password'         => 'required|min_length[8]|max_length[72]',
            'confirm_password' => 'required|matches[password]',
        ];
        $messages = [
            'password' => ['min_length' => lang('Messages.passwordMin8')],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userId   = (int) session()->get('user_id');
        $password = (string) $this->request->getPost('password');

        // Reject reusing the shipped default password.
        if (in_array($password, ['Admin@1234', 'Demo@1234'], true)) {
            return redirect()->back()->with('error', lang('Messages.chooseDifferentPassword'));
        }

        (new UserModel())->update($userId, [
            'password'              => $password, // hashed by model hook
            'force_password_change' => 0,
        ]);

        session()->remove('force_pw');

        return redirect()->to('/panel/dashboard')->with('success', lang('Messages.passwordUpdatedWelcome', [app_name()]));
    }
}

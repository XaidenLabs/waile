<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\SubscriptionModel;

/**
 * Creates / restores a tenant account + subscription and emails the login
 * details. Shared by the PayPal checkout flow and the manual "mark as paid"
 * admin action so provisioning behaves identically everywhere.
 *
 * Idempotent and safe against the unique-email constraint: it checks for the
 * email INCLUDING soft-deleted rows, so a repeat purchase, a parallel checkout,
 * or a previously-deleted account never triggers a duplicate-key crash.
 */
class TenantProvisioner
{
    /**
     * @return array{user_id:int, created:bool}
     */
    public function provisionOrExtend(string $name, string $email, int $planId): array
    {
        $userModel = new UserModel();
        $existing  = $userModel->withDeleted()->where('email', $email)->first();

        if ($existing) {
            $userId = (int) $existing['id'];

            if (! empty($existing['deleted_at'])) {
                // Account was removed — restore it with fresh credentials.
                $password = $this->tempPassword();
                $userModel->builder()->where('id', $userId)->update(['deleted_at' => null]); // un-delete first
                $userModel->update($userId, ['name' => $name, 'status' => 'active', 'password' => $password]);
                (new SubscriptionModel())->grantOrExtend($userId, $planId);
                $this->sendWelcomeEmail($name, $email, $password);

                return ['user_id' => $userId, 'created' => true];
            }

            // Active account — extend its subscription (renewal / upgrade / repurchase).
            (new SubscriptionModel())->grantOrExtend($userId, $planId);

            return ['user_id' => $userId, 'created' => false];
        }

        return ['user_id' => $this->provision($name, $email, $planId), 'created' => true];
    }

    /**
     * Create a brand-new account. Caller must ensure the email is unique
     * (prefer provisionOrExtend(), which handles existing emails safely).
     */
    public function provision(string $name, string $email, int $planId): int
    {
        $password = $this->tempPassword();

        $userId = (new UserModel())->insert([
            'name'     => $name,
            'email'    => $email,
            'password' => $password, // hashed by model hook
            'role'     => 'user',
            'status'   => 'active',
        ]);

        (new SubscriptionModel())->activate((int) $userId, $planId);
        $this->sendWelcomeEmail($name, $email, $password);

        return (int) $userId;
    }

    // ------------------------------------------------------------------

    private function tempPassword(): string
    {
        return 'Wale-' . bin2hex(random_bytes(4)); // >8 chars
    }

    private function sendWelcomeEmail(string $name, string $email, string $password): void
    {
        helper('setting'); // app_name() for the email

        try {
            $html = view('emails/tenant_welcome', [
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
                'appName'  => app_name(),
                'loginUrl' => site_url('login'),
            ]);
            (new EmailService())->send($email, lang('Emails.subjWelcome', [app_name()]), $html);
        } catch (\Throwable $e) {
            log_message('error', 'Provisioning welcome email failed: ' . $e->getMessage());
        }
    }
}

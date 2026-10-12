<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds a default superadmin and a demo user account.
 *
 * Superadmin  — admin@wale.dev  / Admin@1234
 * Demo user   — demo@wale.dev   / Demo@1234
 *
 * Both are flagged force_password_change, so the well-known install password
 * must be replaced on first login.
 *
 * Run: php spark db:seed AdminUserSeeder
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Fetch free plan id
        $freePlan = $this->db->table('plans')->where('slug', 'free')->get()->getRowArray();
        $planId   = $freePlan['id'] ?? null;

        $users = [
            [
                'name'           => 'Super Admin',
                'company_name'   => 'WALE HQ',
                'email'          => 'admin@wale.dev',
                'password'       => password_hash('Admin@1234', PASSWORD_BCRYPT),
                'role'           => 'superadmin',
                'status'         => 'active',
                'email_verified' => 1,
                'force_password_change' => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'name'           => 'Demo User',
                'company_name'   => 'Demo Corp',
                'email'          => 'demo@wale.dev',
                'password'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
                'role'           => 'user',
                'status'         => 'active',
                'email_verified' => 1,
                'force_password_change' => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];

        foreach ($users as $userData) {
            // Skip if email already exists
            $exists = $this->db->table('users')->where('email', $userData['email'])->get()->getRowArray();
            if ($exists) {
                continue;
            }

            $this->db->table('users')->insert($userData);
            $userId = $this->db->insertID();

            // Attach free plan subscription
            if ($planId && $userId) {
                $this->db->table('subscriptions')->insert([
                    'user_id'    => $userId,
                    'plan_id'    => $planId,
                    'status'     => 'active',
                    'starts_at'  => $now,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

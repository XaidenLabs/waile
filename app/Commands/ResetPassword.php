<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

/**
 * Usage:
 *   php spark wale:reset-password admin@wale.dev NewPass@123
 */
class ResetPassword extends BaseCommand
{
    protected $group       = 'WALE';
    protected $name        = 'wale:reset-password';
    protected $description = 'Reset a user password. Args: <email> <new_password>';

    public function run(array $params): void
    {
        $email    = $params[0] ?? CLI::prompt('Email');
        $password = $params[1] ?? CLI::prompt('New password');

        if (empty($email) || empty($password)) {
            CLI::error('Email and password are required.');
            return;
        }

        $model = new UserModel();
        $user  = $model->findByEmail($email);

        if (! $user) {
            CLI::error("No user found with email: {$email}");
            return;
        }

        // Bypass model hooks — update raw so we control the hash
        $hash = password_hash($password, PASSWORD_BCRYPT);
        db_connect()->table('users')->where('id', $user['id'])->update(['password' => $hash]);

        CLI::write("Password reset for {$email}", 'green');
        CLI::write('Verify: ' . (password_verify($password, $hash) ? 'OK' : 'FAIL'), 'green');
    }
}

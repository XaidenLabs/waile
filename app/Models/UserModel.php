<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'name', 'email', 'password', 'company_name', 'phone',
        'timezone', 'role', 'status', 'email_verified', 'force_password_change',
        'verify_token', 'reset_token', 'reset_expires', 'last_login',
    ];

    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[255]',
        'password' => 'required|min_length[8]',
    ];

    protected $validationMessages = [
        'email' => ['required' => 'Email is required', 'valid_email' => 'Please provide a valid email'],
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    // ------------------------------------------------------------------

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }

        return $data;
    }

    public function findByEmail(string $email): array|null
    {
        return $this->where('email', $email)->first();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function updateLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
}

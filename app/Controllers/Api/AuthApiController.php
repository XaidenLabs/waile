<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\JwtLibrary;
use App\Traits\ApiResponseTrait;

/**
 * REST API authentication — no session, JWT only.
 *
 * POST /api/v1/auth/register
 * POST /api/v1/auth/login
 * GET  /api/v1/auth/me        (requires Bearer token)
 * POST /api/v1/auth/logout    (client-side: just discard token; server blacklists if needed)
 * POST /api/v1/auth/refresh   (re-issues token from a valid non-expired one)
 */
class AuthApiController extends BaseController
{
    use ApiResponseTrait;

    protected UserModel  $userModel;
    protected JwtLibrary $jwt;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwt       = new JwtLibrary();
    }

    // ------------------------------------------------------------------
    // POST /api/v1/auth/register
    // ------------------------------------------------------------------

    public function register()
    {
        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'company_name' => 'permit_empty|max_length[255]',
            'email'        => 'required|valid_email|is_unique[users.email]',
            'password'     => 'required|min_length[8]|max_length[72]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $userId = $this->userModel->insert([
            'name'         => trim($this->request->getJSON(true)['name']         ?? $this->request->getPost('name')),
            'company_name' => trim($this->request->getJSON(true)['company_name'] ?? $this->request->getPost('company_name') ?? ''),
            'email'        => strtolower(trim($this->request->getJSON(true)['email'] ?? $this->request->getPost('email'))),
            'password'     => $this->request->getJSON(true)['password'] ?? $this->request->getPost('password'),
            'role'         => 'user',
            'status'       => 'active',
        ]);

        if (! $userId) {
            return $this->fail(lang('Messages.registrationFailed'), 500);
        }

        $user  = $this->userModel->find($userId);
        $token = $this->generateToken($user);

        return $this->success(
            ['token' => $token, 'user' => $this->safeUser($user)],
            'Registration successful',
            201
        );
    }

    // ------------------------------------------------------------------
    // POST /api/v1/auth/login
    // ------------------------------------------------------------------

    public function login()
    {
        $body  = $this->request->getJSON(true) ?? [];
        $email    = trim($body['email']    ?? $this->request->getPost('email')    ?? '');
        $password = trim($body['password'] ?? $this->request->getPost('password') ?? '');

        if (empty($email) || empty($password)) {
            return $this->validationError(['email' => 'Email and password are required.']);
        }

        $user = $this->userModel->findByEmail($email);

        if (! $user || ! $this->userModel->verifyPassword($password, $user['password'])) {
            return $this->fail(lang('Messages.invalidCredentials'), 401);
        }

        if ($user['status'] !== 'active') {
            return $this->fail('Account is ' . $user['status'] . '.', 403);
        }

        $this->userModel->updateLastLogin((int) $user['id']);

        $token = $this->generateToken($user);

        return $this->success(
            ['token' => $token, 'user' => $this->safeUser($user)],
            'Login successful'
        );
    }

    // ------------------------------------------------------------------
    // GET /api/v1/auth/me   (requires ApiAuthFilter)
    // ------------------------------------------------------------------

    public function me()
    {
        $userId = $this->request->userId ?? null;

        if (! $userId) {
            return $this->unauthorized();
        }

        $user = $this->userModel->find($userId);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return $this->success($this->safeUser($user));
    }

    // ------------------------------------------------------------------
    // POST /api/v1/auth/logout
    // ------------------------------------------------------------------

    public function logout()
    {
        // JWT is stateless — instruct client to discard the token.
        // For token blacklisting, store the jti in api_tokens and check in filter.
        return $this->success([], 'Logged out successfully. Discard your token.');
    }

    // ------------------------------------------------------------------
    // POST /api/v1/auth/refresh
    // ------------------------------------------------------------------

    public function refresh()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('No token provided.');
        }

        $token = substr($authHeader, 7);

        try {
            $payload = $this->jwt->decode($token);
        } catch (\Exception $e) {
            return $this->unauthorized($e->getMessage());
        }

        $user = $this->userModel->find($payload->user_id ?? 0);

        if (! $user || $user['status'] !== 'active') {
            return $this->unauthorized('User not found or inactive.');
        }

        return $this->success(
            ['token' => $this->generateToken($user)],
            'Token refreshed'
        );
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function generateToken(array $user): string
    {
        return $this->jwt->encode([
            'user_id' => (int) $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
            'iat'     => time(),
            'exp'     => time() + (60 * 60 * 24), // 24 hours
        ]);
    }

    private function safeUser(array $user): array
    {
        unset($user['password'], $user['verify_token'], $user['reset_token']);
        return $user;
    }
}

<?php

namespace App\Libraries;

/**
 * Lightweight JWT library (HS256) — no external dependency required.
 *
 * Usage:
 *   $jwt   = new JwtLibrary();
 *   $token = $jwt->encode(['user_id' => 1, 'exp' => time() + 3600]);
 *   $data  = $jwt->decode($token);  // stdClass | throws \Exception
 */
class JwtLibrary
{
    private string $secret;
    private string $algo = 'SHA256';

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'change_me_in_production');
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    public function encode(array $payload): string
    {
        $header  = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->base64UrlEncode(json_encode($payload));
        $sig     = $this->sign("{$header}.{$payload}");

        return "{$header}.{$payload}.{$sig}";
    }

    public function decode(string $token): \stdClass
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception('Malformed token');
        }

        [$header, $payload, $signature] = $parts;

        $expectedSig = $this->sign("{$header}.{$payload}");

        if (! hash_equals($expectedSig, $signature)) {
            throw new \Exception('Invalid token signature');
        }

        $data = json_decode($this->base64UrlDecode($payload));

        if (isset($data->exp) && $data->exp < time()) {
            throw new \Exception('Token has expired');
        }

        return $data;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(
            hash_hmac($this->algo, $data, $this->secret, true)
        );
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

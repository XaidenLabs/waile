<?php

namespace App\Services;

/**
 * AES-256-CBC encryption for sensitive tenant data (WhatsApp tokens, etc.)
 *
 * Key is derived from JWT_SECRET in .env so no extra config is needed.
 * Encrypted values are base64-encoded and safe to store in TEXT columns.
 *
 * Usage:
 *   $enc = new EncryptionService();
 *   $cipher  = $enc->encrypt('my_token');
 *   $plain   = $enc->decrypt($cipher);
 *   $masked  = $enc->mask($plain);   // "EAAB***...***xyz"
 */
class EncryptionService
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        // Derive a 32-byte key from the JWT_SECRET
        $secret    = env('JWT_SECRET', 'default_secret_change_me');
        $this->key = hash('sha256', $secret, true); // raw 32 bytes
    }

    // ------------------------------------------------------------------

    public function encrypt(string $plaintext): string
    {
        $iv         = random_bytes(openssl_cipher_iv_length($this->cipher));
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $data      = base64_decode($encoded);
        $ivLength  = openssl_cipher_iv_length($this->cipher);
        $iv        = substr($data, 0, $ivLength);
        $ciphertext= substr($data, $ivLength);

        $result = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($result === false) {
            throw new \RuntimeException('Decryption failed — key mismatch or corrupted ciphertext');
        }

        return $result;
    }

    /**
     * Return a masked version for display — shows first 6 and last 4 chars.
     * e.g.  "EAAB5S***...***a7kc"
     */
    public function mask(string $value, int $showStart = 6, int $showEnd = 4): string
    {
        $len = strlen($value);

        if ($len <= ($showStart + $showEnd)) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, $showStart) . '***...***' . substr($value, -$showEnd);
    }

    /**
     * Returns true if the value looks like an AES-encrypted blob
     * (base64 string longer than a raw token would be).
     */
    public function isEncrypted(string $value): bool
    {
        return base64_decode($value, true) !== false && strlen($value) > 64;
    }
}

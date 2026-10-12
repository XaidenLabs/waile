<?php

namespace App\Payments;

/**
 * PayPal gateway using the Orders v2 REST API (one-time capture).
 *
 * Flow: getAccessToken() → createOrder() → redirect buyer to approveUrl →
 * PayPal returns to return_url → captureOrder().
 *
 * Config keys (from settings group 'payment'): paypal_mode (sandbox|live),
 * paypal_client_id, paypal_secret (already decrypted), currency.
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $secret;
    private string $baseUrl;

    public function __construct(array $cfg)
    {
        $this->clientId = (string) ($cfg['paypal_client_id'] ?? '');
        $this->secret   = (string) ($cfg['paypal_secret'] ?? '');
        $this->baseUrl  = (($cfg['paypal_mode'] ?? 'sandbox') === 'live')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function key(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->secret !== '';
    }

    public function createOrder(float $amount, string $currency, string $description, string $returnUrl, string $cancelUrl): array
    {
        $token = $this->accessToken();

        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'description' => mb_substr($description, 0, 127),
                'amount'      => [
                    'currency_code' => $currency,
                    'value'         => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url'  => $returnUrl,
                'cancel_url'  => $cancelUrl,
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        $res = $this->request('POST', '/v2/checkout/orders', $payload, $token);

        if (empty($res['id'])) {
            throw new \RuntimeException('PayPal: could not create order. ' . ($res['message'] ?? ''));
        }

        $approveUrl = '';
        foreach ($res['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'payer-action' || ($link['rel'] ?? '') === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        if ($approveUrl === '') {
            throw new \RuntimeException('PayPal: no approval URL returned.');
        }

        return ['id' => $res['id'], 'approveUrl' => $approveUrl];
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->accessToken();
        $res   = $this->request('POST', "/v2/checkout/orders/{$orderId}/capture", [], $token);

        $status = $res['status'] ?? 'UNKNOWN';

        return ['paid' => $status === 'COMPLETED', 'status' => $status];
    }

    // ------------------------------------------------------------------

    private function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('PayPal auth network error: ' . $err);
        }

        $decoded = json_decode($body, true) ?? [];
        if (empty($decoded['access_token'])) {
            log_message('error', 'PayPal auth failed: ' . $body);
            throw new \RuntimeException('PayPal authentication failed — check client ID / secret and mode.');
        }

        return $decoded['access_token'];
    }

    private function request(string $method, string $path, array $payload, string $token): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $payload ? json_encode($payload) : '{}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('PayPal network error: ' . $err);
        }

        if ($httpCode >= 400) {
            log_message('error', "PayPal API error [{$httpCode}] {$path}: {$body}");
        }

        return json_decode($body, true) ?? [];
    }
}

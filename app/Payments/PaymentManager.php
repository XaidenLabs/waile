<?php

namespace App\Payments;

use App\Models\SettingModel;
use App\Services\EncryptionService;

/**
 * Resolves the active payment gateway from settings (group 'payment') and
 * exposes a couple of helpers. Register new gateways in the match() below.
 */
class PaymentManager
{
    /** @var array<string,string> */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = (new SettingModel())->getGroup('payment');

        // Decrypt any stored secrets for use by gateways.
        if (! empty($this->cfg['paypal_secret'])) {
            $this->cfg['paypal_secret'] = $this->decrypt($this->cfg['paypal_secret']);
        }
    }

    /** Currency configured for the store (default USD). */
    public function currency(): string
    {
        return strtoupper($this->cfg['currency'] ?? 'USD');
    }

    /** The configured active gateway key (default 'paypal'). */
    public function activeKey(): string
    {
        return $this->cfg['active_gateway'] ?? 'paypal';
    }

    /**
     * The active gateway instance, or null if unknown/unconfigured.
     */
    public function gateway(): ?PaymentGatewayInterface
    {
        $gateway = match ($this->activeKey()) {
            'paypal' => new PayPalGateway($this->cfg),
            // 'stripe' => new StripeGateway($this->cfg),   // future
            default  => null,
        };

        return ($gateway && $gateway->isConfigured()) ? $gateway : null;
    }

    public function isReady(): bool
    {
        return $this->gateway() !== null;
    }

    // ------------------------------------------------------------------

    private function decrypt(string $value): string
    {
        try {
            return (new EncryptionService())->decrypt($value);
        } catch (\Throwable) {
            return $value; // tolerate plaintext
        }
    }
}

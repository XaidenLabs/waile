<?php

namespace App\Payments;

/**
 * Contract every payment gateway must implement. Adding a new provider
 * (Stripe, Razorpay, …) means writing one class that implements this and
 * registering it in PaymentManager — nothing else in the app changes.
 */
interface PaymentGatewayInterface
{
    /** Machine key, e.g. 'paypal'. */
    public function key(): string;

    /** True once the gateway has the credentials it needs. */
    public function isConfigured(): bool;

    /**
     * Create a hosted checkout order.
     *
     * @return array{id: string, approveUrl: string} The provider order id and
     *         the URL to redirect the buyer to for approval/payment.
     *
     * @throws \RuntimeException on failure.
     */
    public function createOrder(float $amount, string $currency, string $description, string $returnUrl, string $cancelUrl): array;

    /**
     * Capture/confirm a previously approved order.
     *
     * @return array{paid: bool, status: string} paid=true when funds captured.
     */
    public function captureOrder(string $orderId): array;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentOrderModel extends Model
{
    protected $table         = 'payment_orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'plan_id', 'gateway', 'gateway_order_id', 'buyer_name', 'buyer_email',
        'amount', 'currency', 'status', 'user_id',
    ];

    public function findByGatewayOrderId(string $orderId): ?array
    {
        return $this->where('gateway_order_id', $orderId)->first();
    }
}

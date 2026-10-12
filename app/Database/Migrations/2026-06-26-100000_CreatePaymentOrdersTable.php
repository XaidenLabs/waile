<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pending/completed checkout orders from the public sales page.
 *
 * A row is created when a visitor starts checkout, carries the gateway's order
 * id, and on successful capture is marked 'paid' and linked to the provisioned
 * user (user_id). No password is stored — the account is created with a random
 * password that is emailed to the buyer.
 */
class CreatePaymentOrdersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'plan_id'          => ['type' => 'INT', 'unsigned' => true],
            'gateway'          => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'paypal'],
            'gateway_order_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'buyer_name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'buyer_email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'currency'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'USD'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'failed', 'cancelled'], 'default' => 'pending'],
            'user_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('gateway_order_id');
        $this->forge->addKey('buyer_email');
        $this->forge->createTable('payment_orders');
    }

    public function down(): void
    {
        $this->forge->dropTable('payment_orders', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-tenant in-app notifications (shown in the topbar bell). First use:
 * surfacing failed WhatsApp sends (e.g. the 24-hour window) that happen in the
 * background from funnels / automations / AI, which were previously only logged.
 */
class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'info'],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'lead_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->createTable('notifications');
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}

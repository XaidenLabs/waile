<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Index messages.wa_message_id — used by the webhook idempotency check that
 * skips duplicate Meta webhook deliveries. Prefix length keeps it within
 * InnoDB key limits on older MySQL/utf8mb4.
 */
class IndexMessagesWaMessageId extends Migration
{
    public function up(): void
    {
        $this->db->query('CREATE INDEX idx_messages_wa_message_id ON messages (wa_message_id(191))');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX idx_messages_wa_message_id ON messages');
    }
}

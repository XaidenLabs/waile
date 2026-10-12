<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cached copy of a tenant's WhatsApp message templates, synced from Meta's
 * Graph API (GET /{waba_id}/message_templates). Templates are the only way to
 * open a conversation with a customer outside the 24-hour service window.
 *
 * We store the parsed shape (header format, variable counts, body text) so the
 * compose UI can render the right inputs and preview without re-parsing Meta's
 * raw component tree each time.
 */
class CreateWaTemplatesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'language'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'en_US'],
            'category'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'APPROVED'],
            'header_format' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'TEXT|IMAGE|VIDEO|DOCUMENT|NONE'],
            'header_text'   => ['type' => 'TEXT', 'null' => true],
            'header_params' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'body_text'     => ['type' => 'TEXT', 'null' => true],
            'body_params'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'footer_text'   => ['type' => 'TEXT', 'null' => true],
            'buttons'       => ['type' => 'TEXT', 'null' => true, 'comment' => 'raw buttons JSON'],
            'meta_id'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'comment' => 'Meta template id'],
            'synced_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'name', 'language']);
        $this->forge->createTable('wa_templates');
    }

    public function down(): void
    {
        $this->forge->dropTable('wa_templates', true);
    }
}

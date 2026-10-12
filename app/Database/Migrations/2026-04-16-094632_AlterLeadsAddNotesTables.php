<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Changes:
 *  1. Alter leads.status  → new | hot | warm | cold | converted | lost
 *  2. Add leads.temperature column (kept for backward compat, same values)
 *  3. Create lead_notes table
 *  4. Create lead_status_logs table
 */
class AlterLeadsAddNotesTables extends Migration
{
    public function up(): void
    {
        // 1. Alter leads.status enum to include hot/warm/cold
        $this->db->query("
            ALTER TABLE leads
            MODIFY COLUMN status
            ENUM('new','hot','warm','cold','converted','lost')
            NOT NULL DEFAULT 'new'
        ");

        // 2. lead_notes
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'lead_id'    => ['type' => 'INT', 'unsigned' => true],
            'note'       => ['type' => 'TEXT'],
            'type'       => ['type' => 'ENUM', 'constraint' => ['general', 'call', 'email', 'meeting', 'whatsapp'], 'default' => 'general'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'comment' => 'user_id who wrote it'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lead_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lead_notes');

        // 3. lead_status_logs
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'lead_id'    => ['type' => 'INT', 'unsigned' => true],
            'old_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'new_status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'changed_by' => ['type' => 'INT', 'unsigned' => true, 'comment' => 'user_id'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lead_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lead_status_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('lead_status_logs', true);
        $this->forge->dropTable('lead_notes',       true);

        $this->db->query("
            ALTER TABLE leads
            MODIFY COLUMN status
            ENUM('new','contacted','qualified','lost','converted')
            NOT NULL DEFAULT 'new'
        ");
    }
}

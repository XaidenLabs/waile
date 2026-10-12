<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Global application settings — a generic key/value store grouped by section.
 *
 * Used first for SMTP/email configuration (group = 'smtp') so the platform
 * owner manages mail from the admin UI instead of the .env file. Future global
 * settings (payment keys, branding, etc.) can reuse the same table.
 *
 * Columns are prefixed (setting_*) to avoid SQL reserved words like `key`/`group`.
 */
class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'setting_group' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['setting_group', 'setting_key']);
        $this->forge->createTable('settings');
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
    }
}

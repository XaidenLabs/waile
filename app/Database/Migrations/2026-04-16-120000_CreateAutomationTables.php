<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAutomationTables extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // automation_rules
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            'user_id'        => ['type' => 'INT',       'unsigned' => true],
            'name'           => ['type' => 'VARCHAR',   'constraint' => 150],
            'description'    => ['type' => 'TEXT',      'null' => true],

            // Trigger ------------------------------------------------
            // new_lead | no_reply | status_change | keyword_match
            'trigger_type'   => ['type' => 'ENUM',
                                 'constraint' => ['new_lead','no_reply','status_change','keyword_match'],
                                 'default' => 'new_lead'],
            // JSON config — varies by trigger_type:
            //   new_lead:      { "delay_minutes": 0 }
            //   no_reply:      { "minutes": 60 }
            //   status_change: { "from_status": "warm", "to_status": "hot" }
            //   keyword_match: { "keywords": ["price","buy"] }
            'trigger_config' => ['type' => 'JSON', 'null' => true],

            // Action -------------------------------------------------
            // send_message | send_template | update_status | add_note
            'action_type'    => ['type' => 'ENUM',
                                 'constraint' => ['send_message','send_template','update_status','add_note'],
                                 'default' => 'send_message'],
            // JSON config — varies by action_type:
            //   send_message:  { "message": "Hi {lead_name}…" }
            //   send_template: { "template": "welcome", "lang": "en_US" }
            //   update_status: { "status": "hot" }
            //   add_note:      { "note": "Auto follow-up sent", "type": "whatsapp" }
            'action_config'  => ['type' => 'JSON', 'null' => true],

            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'run_count'  => ['type' => 'INT',      'unsigned' => true, 'default' => 0],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['user_id', 'trigger_type', 'is_active']);
        $this->forge->createTable('automation_rules');

        // ----------------------------------------------------------------
        // automation_logs
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            'user_id'         => ['type' => 'INT',      'unsigned' => true],
            'rule_id'         => ['type' => 'INT',      'unsigned' => true],
            'lead_id'         => ['type' => 'INT',      'unsigned' => true],
            'conversation_id' => ['type' => 'INT',      'unsigned' => true, 'null' => true],

            // pending → executed or failed
            'status'          => ['type' => 'ENUM',
                                  'constraint' => ['pending','executed','failed','skipped'],
                                  'default' => 'pending'],

            'trigger_data'    => ['type' => 'JSON',     'null' => true],   // snapshot of what triggered it
            'error'           => ['type' => 'TEXT',     'null' => true],   // error message if failed

            'triggered_at'    => ['type' => 'DATETIME', 'null' => true],
            'executed_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['rule_id', 'status']);
        $this->forge->addKey(['lead_id']);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->createTable('automation_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('automation_logs',  true);
        $this->forge->dropTable('automation_rules', true);
    }
}

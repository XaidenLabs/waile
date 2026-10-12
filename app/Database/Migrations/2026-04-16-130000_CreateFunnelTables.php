<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFunnelTables extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // funnels — top-level funnel definition
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'   => ['type' => 'TEXT',    'null' => true],

            // How this funnel gets triggered:
            //   manual   — admin starts it manually from lead profile
            //   keyword  — incoming message contains trigger_value
            //   new_lead — auto-starts for every new lead
            'trigger_type'  => ['type' => 'ENUM',
                                'constraint' => ['manual', 'keyword', 'new_lead'],
                                'default' => 'manual'],
            'trigger_value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true,
                                'comment' => 'Keyword or condition that starts the funnel'],

            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'is_active']);
        $this->forge->createTable('funnels');

        // ----------------------------------------------------------------
        // funnel_steps — individual steps inside a funnel
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'funnel_id'   => ['type' => 'INT', 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'step_order'  => ['type' => 'INT', 'unsigned' => true,
                              'comment' => '1-based display order'],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true,
                              'comment' => 'Short admin label'],

            // Step types:
            //   message  — send text, auto-advance
            //   question — send text, wait for reply, route via conditions
            //   action   — silent: update status / add note / add tag
            //   end      — terminate funnel (mark complete)
            'step_type'   => ['type' => 'ENUM',
                              'constraint' => ['message', 'question', 'action', 'end'],
                              'default' => 'message'],

            'message'     => ['type' => 'TEXT', 'null' => true,
                              'comment' => 'Text sent to the lead (supports {variables})'],

            // For question steps — type of expected answer
            // text | number | choice | yesno
            'input_type'  => ['type' => 'ENUM',
                              'constraint' => ['text', 'number', 'choice', 'yesno'],
                              'null' => true],
            // JSON array of option strings for choice/yesno types
            'choices'     => ['type' => 'JSON', 'null' => true],
            // Lead field to save the answer into (name/email/phone/notes or a custom key)
            'save_to'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],

            // For action steps
            'action_type'   => ['type' => 'ENUM',
                                'constraint' => ['update_status', 'add_note', 'add_tag'],
                                'null' => true],
            'action_config' => ['type' => 'JSON', 'null' => true],

            // Default next step after this step
            // NULL = advance to step_order + 1; 0 = end funnel
            'next_step_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true,
                                'default' => null],

            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['funnel_id', 'step_order']);
        $this->forge->createTable('funnel_steps');

        // ----------------------------------------------------------------
        // funnel_step_conditions — routing conditions on question steps
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'step_id'        => ['type' => 'INT', 'unsigned' => true],
            'funnel_id'      => ['type' => 'INT', 'unsigned' => true],

            // How to match the lead's reply:
            //   contains    — reply contains condition_value (case-insensitive)
            //   equals      — exact match (trimmed, lowercased)
            //   starts_with — reply starts with condition_value
            //   is_number   — reply is numeric (condition_value ignored)
            //   choice_num  — reply is a number matching a choice index (1, 2, 3…)
            //   any         — always matches (catch-all / default)
            'condition_type' => ['type' => 'ENUM',
                                 'constraint' => ['contains', 'equals', 'starts_with', 'is_number', 'choice_num', 'any'],
                                 'default' => 'contains'],
            'condition_value'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],

            // Where to go if this condition matches (FK to funnel_steps.id; 0 = end)
            'next_step_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            'sort_order'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0,
                                 'comment' => 'Conditions checked in this order; first match wins'],

            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['step_id', 'sort_order']);
        $this->forge->createTable('funnel_step_conditions');

        // ----------------------------------------------------------------
        // funnel_sessions — tracks each lead's progress through a funnel
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true],
            'funnel_id'       => ['type' => 'INT', 'unsigned' => true],
            'lead_id'         => ['type' => 'INT', 'unsigned' => true],
            'conversation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'current_step_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            // active | completed | abandoned
            'status'          => ['type' => 'ENUM',
                                  'constraint' => ['active', 'completed', 'abandoned'],
                                  'default' => 'active'],

            // JSON bag of collected answers: { "name": "John", "budget": "10000", … }
            'data'            => ['type' => 'JSON', 'null' => true],

            'started_at'      => ['type' => 'DATETIME', 'null' => true],
            'completed_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['lead_id', 'status']);
        $this->forge->addKey(['funnel_id']);
        $this->forge->createTable('funnel_sessions');
    }

    public function down(): void
    {
        $this->forge->dropTable('funnel_sessions',         true);
        $this->forge->dropTable('funnel_step_conditions',  true);
        $this->forge->dropTable('funnel_steps',            true);
        $this->forge->dropTable('funnels',                 true);
    }
}

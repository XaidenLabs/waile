<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Core schema for AI WhatsApp Lead Engine (multi-tenant SaaS)
 *
 * Tables:
 *  1. plans             – Subscription plans
 *  2. users             – SaaS tenants (business accounts)
 *  3. subscriptions     – User → Plan linkage
 *  4. leads             – Captured leads (tenant-scoped)
 *  5. conversations     – WhatsApp chat sessions per lead
 *  6. messages          – Individual messages inside a conversation
 *  7. ai_configs        – Per-tenant OpenAI / prompt configuration
 *  8. whatsapp_configs  – Per-tenant WhatsApp API credentials
 *  9. api_tokens        – JWT / API key store per tenant
 */
class CreateCoreSchema extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // 1. plans
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'price'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'lead_limit'  => ['type' => 'INT', 'default' => 100, 'comment' => '-1 = unlimited'],
            'ai_replies'  => ['type' => 'INT', 'default' => 500, 'comment' => '-1 = unlimited'],
            'features'    => ['type' => 'JSON', 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('plans');

        // ----------------------------------------------------------------
        // 2. users (tenants)
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'password'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'company_name'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'timezone'       => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'UTC'],
            'role'           => ['type' => 'ENUM', 'constraint' => ['superadmin', 'admin', 'user'], 'default' => 'user'],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'suspended'], 'default' => 'active'],
            'email_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'verify_token'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reset_token'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reset_expires'  => ['type' => 'DATETIME', 'null' => true],
            'last_login'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');

        // ----------------------------------------------------------------
        // 3. subscriptions
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'plan_id'    => ['type' => 'INT', 'unsigned' => true],
            'status'     => ['type' => 'ENUM', 'constraint' => ['active', 'expired', 'cancelled'], 'default' => 'active'],
            'starts_at'  => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'plans', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('subscriptions');

        // ----------------------------------------------------------------
        // 4. leads
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['new', 'contacted', 'qualified', 'lost', 'converted'], 'default' => 'new'],
            'score'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'comment' => '0-100'],
            'tags'         => ['type' => 'JSON', 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'assigned_to'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'last_contact' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('phone');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('leads');

        // ----------------------------------------------------------------
        // 5. conversations
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'lead_id'      => ['type' => 'INT', 'unsigned' => true],
            'wa_thread_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['active', 'closed', 'paused'], 'default' => 'active'],
            'ai_enabled'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'started_at'   => ['type' => 'DATETIME', 'null' => true],
            'ended_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('lead_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('conversations');

        // ----------------------------------------------------------------
        // 6. messages
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true, 'comment' => 'tenant'],
            'conversation_id' => ['type' => 'INT', 'unsigned' => true],
            'lead_id'         => ['type' => 'INT', 'unsigned' => true],
            'direction'       => ['type' => 'ENUM', 'constraint' => ['inbound', 'outbound'], 'default' => 'inbound'],
            'sender'          => ['type' => 'ENUM', 'constraint' => ['lead', 'ai', 'agent'], 'default' => 'lead'],
            'content'         => ['type' => 'TEXT'],
            'wa_message_id'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'message_type'    => ['type' => 'ENUM', 'constraint' => ['text', 'image', 'audio', 'document', 'template'], 'default' => 'text'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['sent', 'delivered', 'read', 'failed'], 'default' => 'sent'],
            'tokens_used'     => ['type' => 'INT', 'default' => 0],
            'metadata'        => ['type' => 'JSON', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('conversation_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('conversation_id', 'conversations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('messages');

        // ----------------------------------------------------------------
        // 7. ai_configs
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'              => ['type' => 'INT', 'unsigned' => true],
            'system_prompt'        => ['type' => 'TEXT', 'null' => true],
            'model'                => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'gpt-4o-mini'],
            'temperature'          => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0.70],
            'max_tokens'           => ['type' => 'INT', 'default' => 500],
            'language'             => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'fallback_message'     => ['type' => 'TEXT', 'null' => true],
            'qualification_rules'  => ['type' => 'JSON', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_configs');

        // ----------------------------------------------------------------
        // 8. whatsapp_configs
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'phone_number_id'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'access_token'     => ['type' => 'TEXT'],
            'verify_token'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'waba_id'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'webhook_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('whatsapp_configs');

        // ----------------------------------------------------------------
        // 9. api_tokens
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'token'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at'   => ['type' => 'DATETIME', 'null' => true],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_tokens');
    }

    public function down(): void
    {
        $this->forge->dropTable('api_tokens',        true);
        $this->forge->dropTable('whatsapp_configs',  true);
        $this->forge->dropTable('ai_configs',        true);
        $this->forge->dropTable('messages',          true);
        $this->forge->dropTable('conversations',     true);
        $this->forge->dropTable('leads',             true);
        $this->forge->dropTable('subscriptions',     true);
        $this->forge->dropTable('users',             true);
        $this->forge->dropTable('plans',             true);
    }
}

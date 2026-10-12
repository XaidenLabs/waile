<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds DeepSeek as a selectable AI provider alongside OpenAI.
 * - ai_provider      : which provider the tenant uses ('openai' | 'deepseek')
 * - deepseek_api_key : DeepSeek key, stored separately so switching providers
 *                      never discards the OpenAI key (encrypted by the model).
 */
class AddDeepSeekToAiConfigs extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('ai_configs', [
            'ai_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'openai',
                'after'      => 'user_id',
            ],
            'deepseek_api_key' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'openai_api_key',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('ai_configs', ['ai_provider', 'deepseek_api_key']);
    }
}

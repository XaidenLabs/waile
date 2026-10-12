<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpenAIKeyToAiConfigs extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('ai_configs', [
            'openai_api_key' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'user_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('ai_configs', 'openai_api_key');
    }
}

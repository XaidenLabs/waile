<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $plans = [
            [
                'name'       => 'Free',
                'slug'       => 'free',
                'price'      => 0.00,
                'lead_limit' => 50,
                'ai_replies' => 100,
                'features'   => json_encode(['whatsapp_bot', 'lead_capture', 'basic_dashboard']),
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Starter',
                'slug'       => 'starter',
                'price'      => 29.00,
                'lead_limit' => 500,
                'ai_replies' => 2000,
                'features'   => json_encode(['whatsapp_bot', 'lead_capture', 'basic_dashboard', 'ai_qualification', 'csv_export']),
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Professional',
                'slug'       => 'professional',
                'price'      => 79.00,
                'lead_limit' => 5000,
                'ai_replies' => 20000,
                'features'   => json_encode(['whatsapp_bot', 'lead_capture', 'advanced_dashboard', 'ai_qualification', 'csv_export', 'api_access', 'team_members', 'custom_prompt']),
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Enterprise',
                'slug'       => 'enterprise',
                'price'      => 199.00,
                'lead_limit' => -1,
                'ai_replies' => -1,
                'features'   => json_encode(['whatsapp_bot', 'lead_capture', 'advanced_dashboard', 'ai_qualification', 'csv_export', 'api_access', 'team_members', 'custom_prompt', 'white_label', 'priority_support', 'custom_integrations']),
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('plans')->insertBatch($plans);
    }
}

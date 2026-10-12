<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAdminRole extends Migration
{
    public function up(): void
    {
        // Demote any existing 'admin' users to 'user' before shrinking the ENUM
        $this->db->query("UPDATE users SET role = 'user' WHERE role = 'admin'");

        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['superadmin', 'user'],
                'default'    => 'user',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['superadmin', 'admin', 'user'],
                'default'    => 'user',
            ],
        ]);
    }
}

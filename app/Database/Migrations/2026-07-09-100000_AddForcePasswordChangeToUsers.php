<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Flags accounts that must set a new password on next login — used for the
 * seeded default super-admin so the well-known install password can never be
 * left in place on a live site.
 */
class AddForcePasswordChangeToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'force_password_change' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'force_password_change');
    }
}

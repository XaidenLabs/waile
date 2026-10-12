<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Notes created automatically (by a funnel or automation) should be attributed
 * to what created them, not to the tenant owner. This optional label overrides
 * the "created_by" user name in the UI when present.
 */
class AddAuthorLabelToLeadNotes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('lead_notes', [
            'author_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'created_by',
                'comment'    => 'Overrides author name for system-generated notes (e.g. "Funnel: Welcome")',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('lead_notes', 'author_label');
    }
}

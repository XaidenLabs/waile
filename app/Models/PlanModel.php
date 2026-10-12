<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table         = 'plans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'slug', 'price', 'lead_limit', 'ai_replies', 'features', 'is_active',
    ];

    /**
     * Active plans for the public sales page, cheapest first.
     */
    public function activePlans(): array
    {
        $plans = $this->where('is_active', 1)->orderBy('price', 'ASC')->findAll();

        foreach ($plans as &$p) {
            $p['features'] = $p['features'] ? (json_decode($p['features'], true) ?: []) : [];
        }

        return $plans;
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('is_active', 1)->first();
    }
}

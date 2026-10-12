<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table          = 'leads';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;

    protected $allowedFields = [
        'user_id', 'name', 'phone', 'email', 'source',
        'status', 'score', 'tags', 'notes',
        'assigned_to', 'last_contact',
    ];

    public const STATUSES = ['new', 'hot', 'warm', 'cold', 'converted', 'lost'];
    public const SOURCES  = ['website', 'facebook', 'instagram', 'whatsapp', 'referral', 'manual', 'other'];

    // Status badge colours for views
    public const STATUS_COLORS = [
        'new'       => 'secondary',
        'hot'       => 'danger',
        'warm'      => 'warning',
        'cold'      => 'info',
        'converted' => 'success',
        'lost'      => 'dark',
    ];

    // Status icons
    public const STATUS_ICONS = [
        'new'       => 'bi-star',
        'hot'       => 'bi-fire',
        'warm'      => 'bi-thermometer-half',
        'cold'      => 'bi-snow',
        'converted' => 'bi-trophy-fill',
        'lost'      => 'bi-x-circle',
    ];

    protected $validationRules = [
        'phone'  => 'required|max_length[30]',
        'status' => 'permit_empty|in_list[new,hot,warm,cold,converted,lost]',
        'score'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];

    // ------------------------------------------------------------------
    // Tenant-scoped query helpers
    // ------------------------------------------------------------------

    public function forTenant(int $userId): static
    {
        return $this->where($this->table . '.user_id', $userId);
    }

    public function getByPhone(int $userId, string $phone): array|null
    {
        return $this->where('user_id', $userId)->where('phone', $phone)->first();
    }

    /**
     * Count leads a tenant created since a given datetime — used for per-plan
     * monthly lead-limit enforcement. (Soft-deleted leads are excluded.)
     */
    public function countCreatedSince(int $userId, string $since): int
    {
        return $this->where('user_id', $userId)
                    ->where('created_at >=', $since)
                    ->countAllResults();
    }

    /**
     * Filterable, searchable, paginated list for the admin panel.
     */
    public function getFiltered(int $userId, array $filters = [], int $perPage = 20): array
    {
        $builder = $this->where('user_id', $userId);

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $builder->groupStart()
                ->like('name',  $s)
                ->orLike('phone', $s)
                ->orLike('email', $s)
                ->groupEnd();
        }

        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $builder->where('source', $filters['source']);
        }

        if (! empty($filters['score_min'])) {
            $builder->where('score >=', (int) $filters['score_min']);
        }

        $sort = in_array($filters['sort'] ?? '', ['name', 'phone', 'status', 'score', 'created_at'])
            ? $filters['sort'] : 'created_at';
        $dir  = ($filters['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        return [
            'leads' => $builder->orderBy($sort, $dir)->paginate($perPage, 'default', (int) ($filters['page'] ?? 1)),
            'pager' => $this->pager,
            'total' => $this->where('user_id', $userId)->countAllResults(),
        ];
    }

    public function getStats(int $userId): array
    {
        $result = [];
        foreach (self::STATUSES as $s) {
            $result[$s] = $this->where('user_id', $userId)->where('status', $s)->countAllResults();
        }
        $result['total'] = array_sum($result);
        return $result;
    }

    /**
     * Update status and return old status for logging.
     */
    public function updateStatus(int $id, string $newStatus): string|null
    {
        $lead = $this->find($id);
        if (! $lead) return null;

        $old = $lead['status'];
        $this->update($id, [
            'status'       => $newStatus,
            'last_contact' => date('Y-m-d H:i:s'),
        ]);

        return $old;
    }

    /**
     * Decode tags JSON into array, or return empty array.
     */
    public static function parseTags(mixed $tags): array
    {
        if (is_array($tags)) return $tags;
        if (empty($tags))    return [];

        $decoded = json_decode($tags, true);
        return is_array($decoded) ? $decoded : [];
    }
}

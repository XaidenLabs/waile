<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table         = 'subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'plan_id', 'status', 'starts_at', 'expires_at',
    ];

    /**
     * Create an active subscription for a user for one billing period.
     */
    public function activate(int $userId, int $planId, string $period = '+1 month'): int|false
    {
        $now = date('Y-m-d H:i:s');

        return $this->insert([
            'user_id'    => $userId,
            'plan_id'    => $planId,
            'status'     => 'active',
            'starts_at'  => $now,
            'expires_at' => date('Y-m-d H:i:s', strtotime($period)),
        ]);
    }

    /**
     * The latest subscription row for a user (or null).
     */
    public function currentForUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)->orderBy('id', 'DESC')->first();
    }

    /**
     * Whether the user is allowed to use the app.
     *
     * Rule: a user with NO subscription is treated as unrestricted (admin-granted
     * / legacy accounts). A user WITH a subscription must have an 'active' one that
     * hasn't passed its expiry. Expired rows are marked lazily here.
     */
    public function isActiveFor(int $userId): bool
    {
        $sub = $this->currentForUser($userId);

        if (! $sub) {
            return true; // no plan attached → not gated
        }

        if ($sub['status'] === 'active'
            && ! empty($sub['expires_at'])
            && strtotime($sub['expires_at']) < time()) {
            $this->update($sub['id'], ['status' => 'expired']);
            return false;
        }

        return $sub['status'] === 'active';
    }

    /**
     * Grant or extend a user's subscription by one period on the given plan.
     * Extends from the later of "now" or the current expiry so time isn't lost.
     */
    public function grantOrExtend(int $userId, int $planId, string $period = '+1 month'): void
    {
        $sub  = $this->currentForUser($userId);
        $base = ($sub && ! empty($sub['expires_at']) && strtotime($sub['expires_at']) > time())
            ? strtotime($sub['expires_at'])
            : time();

        $expires = date('Y-m-d H:i:s', strtotime($period, $base));

        if ($sub) {
            $this->update($sub['id'], [
                'plan_id'    => $planId,
                'status'     => 'active',
                'expires_at' => $expires,
            ]);
        } else {
            $this->insert([
                'user_id'    => $userId,
                'plan_id'    => $planId,
                'status'     => 'active',
                'starts_at'  => date('Y-m-d H:i:s'),
                'expires_at' => $expires,
            ]);
        }
    }

    public function cancelFor(int $userId): void
    {
        if ($sub = $this->currentForUser($userId)) {
            $this->update($sub['id'], ['status' => 'cancelled']);
        }
    }
}

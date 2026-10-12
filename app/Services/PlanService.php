<?php

namespace App\Services;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use App\Models\LeadModel;
use App\Models\MessageModel;

/**
 * Resolves a tenant's active plan and enforces its limits + features.
 *
 * Philosophy (matches SubscriptionFilter): a user with NO subscription is
 * UNRESTRICTED (admin-granted / legacy accounts, and super admins). Only users
 * with a subscription are gated by their plan.
 *
 * Usage is counted per SUBSCRIPTION CYCLE — a monthly window anchored to the
 * subscription's start date (so it resets on each monthly anniversary).
 *
 *   $plan = new PlanService($userId);
 *   if (! $plan->canSendAi())   { ...skip AI reply... }
 *   if (! $plan->canAddLead())  { ...block + upgrade notice... }
 *   if (! $plan->hasFeature('csv_export')) { ...lock... }
 */
class PlanService
{
    private int $userId;
    private bool $loaded = false;
    private ?array $sub  = null;   // subscription row
    private ?array $plan = null;   // plan row (features decoded); null = unrestricted

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $this->sub = (new SubscriptionModel())->currentForUser($this->userId);

        if ($this->sub && ! empty($this->sub['plan_id'])) {
            $plan = (new PlanModel())->find($this->sub['plan_id']);
            if ($plan) {
                $plan['features'] = ! empty($plan['features'])
                    ? (json_decode($plan['features'], true) ?: [])
                    : [];
                $this->plan = $plan;
            }
        }
    }

    /** No subscription/plan → unrestricted (not gated). */
    public function isUnrestricted(): bool
    {
        $this->load();
        return $this->plan === null;
    }

    public function plan(): ?array
    {
        $this->load();
        return $this->plan;
    }

    // ── Features ──────────────────────────────────────────────────────

    public function hasFeature(string $key): bool
    {
        $this->load();
        if ($this->plan === null) {
            return true; // unrestricted
        }
        return in_array($key, $this->plan['features'] ?? [], true);
    }

    // ── Limits (-1 = unlimited) ───────────────────────────────────────

    public function leadLimit(): int
    {
        $this->load();
        return $this->plan === null ? -1 : (int) $this->plan['lead_limit'];
    }

    public function aiLimit(): int
    {
        $this->load();
        return $this->plan === null ? -1 : (int) $this->plan['ai_replies'];
    }

    // ── Usage (current subscription cycle) ────────────────────────────

    /**
     * Start of the current monthly cycle, anchored to the subscription start.
     * Falls back to the start of the calendar month if there's no start date.
     */
    public function cycleStart(): string
    {
        $this->load();

        $anchor = $this->sub['starts_at'] ?? null;
        if (empty($anchor)) {
            return date('Y-m-01 00:00:00');
        }

        try {
            $start = new \DateTime($anchor);
        } catch (\Throwable) {
            return date('Y-m-01 00:00:00');
        }

        $now = new \DateTime();
        if ($start > $now) {
            return $start->format('Y-m-d H:i:s');
        }

        // Advance month-by-month to the most recent anniversary <= now.
        $cursor = clone $start;
        while (true) {
            $next = (clone $cursor)->modify('+1 month');
            if ($next > $now) {
                break;
            }
            $cursor = $next;
        }

        return $cursor->format('Y-m-d H:i:s');
    }

    public function leadsUsed(): int
    {
        return (new LeadModel())->countCreatedSince($this->userId, $this->cycleStart());
    }

    public function aiUsed(): int
    {
        return (new MessageModel())->countAiSince($this->userId, $this->cycleStart());
    }

    // ── Enforcement checks ────────────────────────────────────────────

    public function canAddLead(): bool
    {
        $limit = $this->leadLimit();
        return $limit < 0 || $this->leadsUsed() < $limit;
    }

    public function canSendAi(): bool
    {
        $limit = $this->aiLimit();
        return $limit < 0 || $this->aiUsed() < $limit;
    }

    // ── UI summary ────────────────────────────────────────────────────

    /**
     * @return array everything the dashboard / plan page needs to render usage.
     */
    public function usage(): array
    {
        $this->load();

        return [
            'unrestricted' => $this->plan === null,
            'plan_name'    => $this->plan['name']  ?? null,
            'plan_price'   => $this->plan['price'] ?? null,
            'features'     => $this->plan['features'] ?? [],
            'cycle_start'  => $this->cycleStart(),
            'expires_at'   => $this->sub['expires_at'] ?? null,
            'lead_limit'   => $this->leadLimit(),
            'leads_used'   => $this->leadsUsed(),
            'ai_limit'     => $this->aiLimit(),
            'ai_used'      => $this->aiUsed(),
        ];
    }
}

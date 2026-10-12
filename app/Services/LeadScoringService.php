<?php

namespace App\Services;

use App\Models\LeadModel;

/**
 * Deterministic lead engagement scoring (0–100).
 *
 * Runs on every inbound message so the score always reflects the real
 * conversation — no dependency on a second AI call (which was unreliable and
 * left scores stuck at 0 for funnel-driven chats).
 *
 * Signals:
 *   - conversation volume (inbound messages from the lead)
 *   - profile completeness (name, email)
 *   - funnel answers collected + funnel completion
 *   - current qualification status
 */
class LeadScoringService
{
    public function score(int $userId, int $leadId): int
    {
        $db   = \Config\Database::connect();
        $lead = (new LeadModel())->find($leadId);
        if (! $lead) return 0;

        $score = 0;

        // 1. Conversation engagement — how many times the lead has replied.
        $inbound = (int) $db->table('messages')
            ->where('user_id', $userId)
            ->where('lead_id', $leadId)
            ->where('direction', 'inbound')
            ->countAllResults();
        $score += min($inbound * 6, 40);

        // 2. Profile completeness.
        if (! empty($lead['name']))  $score += 5;
        if (! empty($lead['email'])) $score += 10;

        // 3. Funnel answers collected + completion.
        $sessions = $db->table('funnel_sessions')
            ->select('status, data')
            ->where('user_id', $userId)
            ->where('lead_id', $leadId)
            ->get()->getResultArray();

        $answers   = 0;
        $completed = false;
        foreach ($sessions as $s) {
            $data = json_decode($s['data'] ?? '', true);
            if (is_array($data)) {
                $answers = max($answers, count($data));
            }
            if (($s['status'] ?? '') === 'completed') {
                $completed = true;
            }
        }
        $score += min($answers * 5, 20);
        if ($completed) $score += 10;

        // 4. Qualification status signal.
        $score += match ($lead['status'] ?? 'new') {
            'converted' => 30,
            'hot'       => 15,
            'warm'      => 8,
            'cold'      => 3,
            default     => 0,
        };

        $score = max(0, min(100, $score));

        // Persist only when it actually changes.
        if ((int) ($lead['score'] ?? 0) !== $score) {
            (new LeadModel())->update($leadId, ['score' => $score]);
        }

        return $score;
    }
}

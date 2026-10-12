<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\AiConfigModel;
use App\Traits\ApiResponseTrait;

/**
 * REST API — Dashboard statistics
 *
 * GET /api/v1/stats    Aggregated tenant stats for dashboard / reporting
 */
class StatsApiController extends BaseController
{
    use ApiResponseTrait;

    private function userId(): int
    {
        return (int) $this->request->userId;
    }

    // ------------------------------------------------------------------
    // GET /api/v1/stats
    // ------------------------------------------------------------------

    public function index()
    {
        $userId = $this->userId();

        $leadModel    = new LeadModel();
        $convModel    = new ConversationModel();
        $messageModel = new MessageModel();

        // Lead stats
        $leadStats = $leadModel->getStats($userId);

        // Conversation stats
        $convStats = $convModel->getStats($userId);

        // Message stats
        $msgStats = $messageModel->getStats($userId);

        // Tokens used (sum of tokens_used from messages this month)
        $db         = \Config\Database::connect();
        $tokensMonth = (int) $db->table('messages')
            ->selectSum('tokens_used')
            ->where('user_id', $userId)
            ->where('sender', 'ai')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->get()
            ->getRow()
            ->tokens_used;

        $tokensTotal = (int) $db->table('messages')
            ->selectSum('tokens_used')
            ->where('user_id', $userId)
            ->where('sender', 'ai')
            ->get()
            ->getRow()
            ->tokens_used;

        // New leads today
        $leadsToday = (int) $db->table('leads')
            ->where('user_id', $userId)
            ->where('DATE(created_at)', date('Y-m-d'))
            ->whereNull('deleted_at')
            ->countAllResults();

        return $this->success([
            'leads'         => $leadStats,
            'leads_today'   => $leadsToday,
            'conversations' => $convStats,
            'messages'      => $msgStats,
            'ai_tokens'     => [
                'this_month' => $tokensMonth,
                'total'      => $tokensTotal,
            ],
        ]);
    }
}

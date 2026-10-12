<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user_id');

        $leadModel    = new LeadModel();
        $convModel    = new ConversationModel();
        $messageModel = new MessageModel();

        $db = \Config\Database::connect();

        // AI tokens used this month
        $tokensMonth = (int) $db->table('messages')
            ->selectSum('tokens_used')
            ->where('user_id', $userId)
            ->where('sender', 'ai')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->get()->getRow()->tokens_used;

        // Recent conversations (last 5)
        $recentConvs = $db->table('conversations c')
            ->select([
                'c.id', 'c.status', 'c.ai_enabled', 'c.updated_at',
                'l.name as lead_name', 'l.phone as lead_phone',
                'm.content as last_message',
            ])
            ->join('leads l', 'l.id = c.lead_id', 'left')
            ->join(
                '(SELECT conversation_id, content FROM messages
                  WHERE id IN (SELECT MAX(id) FROM messages GROUP BY conversation_id)) m',
                'm.conversation_id = c.id', 'left'
            )
            ->where('c.user_id', $userId)
            ->orderBy('c.updated_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        // ── 14-day trend windows for sparklines + "vs last 7 days" deltas ──
        $dayStart = date('Y-m-d', strtotime('-13 days')) . ' 00:00:00';
        $days14   = [];
        for ($i = 13; $i >= 0; $i--) {
            $days14[] = date('Y-m-d', strtotime("-$i days"));
        }

        // Daily COUNT series for a table/conditions over the last 14 days.
        $countWindow = function (string $table, array $where, string $col = 'created_at') use ($db, $userId, $days14, $dayStart): array {
            $b = $db->table($table)->select("DATE($col) d, COUNT(*) c")
                ->where('user_id', $userId)->where("$col >=", $dayStart);
            foreach ($where as $k => $v) {
                $b->where($k, $v);
            }
            $map = array_column($b->groupBy("DATE($col)")->get()->getResultArray(), 'c', 'd');
            return array_map(static fn($d) => (int) ($map[$d] ?? 0), $days14);
        };

        // Split a 14-day series into { spark:last7, delta:% vs prev7 }.
        $mk = static function (array $vals): array {
            $prev  = array_sum(array_slice($vals, 0, 7));
            $cur   = array_sum(array_slice($vals, 7, 7));
            $delta = $prev > 0 ? (int) round(($cur - $prev) / $prev * 100) : ($cur > 0 ? 100 : 0);
            return ['spark' => array_slice($vals, 7, 7), 'delta' => $delta];
        };

        // Tokens are a SUM, handled separately.
        $tokRows = $db->table('messages')->select('DATE(created_at) d, SUM(tokens_used) c')
            ->where('user_id', $userId)->where('sender', 'ai')->where('created_at >=', $dayStart)
            ->groupBy('DATE(created_at)')->get()->getResultArray();
        $tokMap  = array_column($tokRows, 'c', 'd');
        $tokVals = array_map(static fn($d) => (int) ($tokMap[$d] ?? 0), $days14);

        $data = [
            'title' => lang('Titles.dashboard'),
            'lead_stats'          => $leadModel->getStats($userId),
            'msg_stats'           => $messageModel->getStats($userId),
            'conv_stats'          => $convModel->getStats($userId),
            'active_conversations'=> $convModel->forTenant($userId)->where('status', 'active')->countAllResults(),
            'tokens_month'        => $tokensMonth,
            'recent_convs'        => $recentConvs,
            'plan_usage'          => plan_usage(), // null when unrestricted / superadmin
            'kpi'                 => [
                'leads'     => $mk($countWindow('leads', [])),
                'converted' => $mk($countWindow('leads', ['status' => 'converted'])),
                'chats'     => $mk($countWindow('conversations', [])),
                'tokens'    => $mk($tokVals),
            ],
            'activity_spark'      => [
                'recv' => $mk($countWindow('messages', ['direction' => 'inbound']))['spark'],
                'sent' => $mk($countWindow('messages', ['direction' => 'outbound']))['spark'],
                'ai'   => $mk($countWindow('messages', ['sender' => 'ai']))['spark'],
            ],
        ];

        return view('admin/dashboard/index', $data);
    }
}

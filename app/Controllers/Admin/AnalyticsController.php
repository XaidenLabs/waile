<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LeadModel;

class AnalyticsController extends BaseController
{
    private int $userId;

    public function __construct()
    {
        $this->userId = (int) session()->get('user_id');
    }

    // ------------------------------------------------------------------
    // GET /admin/analytics
    // ------------------------------------------------------------------

    public function index()
    {
        if (! plan_feature('advanced_dashboard')) {
            return redirect()->to('/panel/plan')
                ->with('error', lang('Messages.advancedAnalyticsLocked'));
        }

        $db     = \Config\Database::connect();
        $userId = $this->userId;

        // ── Lead stats ─────────────────────────────────────────────────
        $leadModel = new LeadModel();
        $leadStats = $leadModel->getStats($userId);

        // Period comparison: new leads this month vs last month
        $period = $db->query("
            SELECT
                SUM(YEAR(created_at)  = YEAR(NOW())
                    AND MONTH(created_at) = MONTH(NOW()))                           AS this_month,
                SUM(YEAR(created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                    AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))) AS last_month,
                SUM(DATE(created_at)  = CURDATE())                                   AS today,
                SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))                   AS this_week
            FROM leads
            WHERE user_id = ? AND deleted_at IS NULL
        ", [$userId])->getRow();

        $leadsThisMonth = (int) $period->this_month;
        $leadsLastMonth = (int) $period->last_month;
        $leadsToday     = (int) $period->today;
        $leadsThisWeek  = (int) $period->this_week;

        // Month-over-month % change
        $momChange = $leadsLastMonth > 0
            ? round(($leadsThisMonth - $leadsLastMonth) / $leadsLastMonth * 100, 1)
            : null;

        // ── Message / conversation stats ───────────────────────────────
        $totalMessages = (int) $db->table('messages')
            ->where('user_id', $userId)->countAllResults();

        $aiMessages = (int) $db->table('messages')
            ->where('user_id', $userId)->where('sender', 'ai')->countAllResults();

        $tokensTotal = (int) $db->table('messages')
            ->selectSum('tokens_used')
            ->where('user_id', $userId)
            ->where('sender', 'ai')
            ->get()->getRow()->tokens_used;

        $convTotal = (int) $db->table('conversations')
            ->where('user_id', $userId)->countAllResults();

        // ── Conversion rate ────────────────────────────────────────────
        $convRate = $leadStats['total'] > 0
            ? round($leadStats['converted'] / $leadStats['total'] * 100, 1)
            : 0;

        // ── Avg days to convert (lead created → first 'converted' log) ─
        $avgDaysConvert = $db->query("
            SELECT AVG(DATEDIFF(sl.created_at, l.created_at)) AS avg_days
            FROM leads l
            INNER JOIN lead_status_logs sl
                ON sl.lead_id = l.id
               AND sl.new_status = 'converted'
               AND sl.id = (
                   SELECT MIN(id) FROM lead_status_logs
                   WHERE lead_id = l.id AND new_status = 'converted'
               )
            WHERE l.user_id = ? AND l.deleted_at IS NULL
        ", [$userId])->getRow()->avg_days;

        $avgDaysToConvert = $avgDaysConvert ? round((float) $avgDaysConvert, 1) : null;

        // ── Response rate ──────────────────────────────────────────────
        // % of conversations that had at least one inbound message AND
        // at least one subsequent outbound reply
        $rrRow = $db->query("
            SELECT
                COUNT(DISTINCT CASE WHEN direction = 'outbound' THEN conversation_id END) AS replied,
                COUNT(DISTINCT CASE WHEN direction = 'inbound'  THEN conversation_id END) AS inbound
            FROM messages
            WHERE user_id = ?
        ", [$userId])->getRow();

        $responseRate = ($rrRow->inbound > 0)
            ? round($rrRow->replied / $rrRow->inbound * 100, 1)
            : 0;

        // ── Avg first response time (minutes) ─────────────────────────
        $avgResponse = $db->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, m1.created_at, m2.created_at)) AS avg_min
            FROM messages m1
            JOIN messages m2
              ON m2.conversation_id = m1.conversation_id
             AND m2.direction = 'outbound'
             AND m2.id = (
                SELECT MIN(id) FROM messages
                WHERE conversation_id = m1.conversation_id
                  AND direction = 'outbound'
                  AND id > m1.id
             )
            WHERE m1.user_id = ?
              AND m1.direction = 'inbound'
        ", [$userId])->getRow()->avg_min;

        $avgResponseMin = $avgResponse ? round((float) $avgResponse, 1) : 0;

        // ── Lead score distribution ────────────────────────────────────
        $scoreDistribution = $db->query("
            SELECT
                CASE
                    WHEN score BETWEEN  0 AND 25 THEN '0–25'
                    WHEN score BETWEEN 26 AND 50 THEN '26–50'
                    WHEN score BETWEEN 51 AND 75 THEN '51–75'
                    ELSE '76–100'
                END AS bucket,
                COUNT(*) AS cnt
            FROM leads
            WHERE user_id = ? AND deleted_at IS NULL
            GROUP BY bucket
            ORDER BY bucket
        ", [$userId])->getResultArray();

        // ── Conversion funnel (stage drop-off) ────────────────────────
        // Ordered by typical pipeline progression
        $funnelOrder  = ['new', 'cold', 'warm', 'hot', 'converted', 'lost'];
        $conversionFunnel = [];
        foreach ($funnelOrder as $s) {
            $conversionFunnel[] = ['status' => $s, 'cnt' => (int)($leadStats[$s] ?? 0)];
        }

        // ── Weekly conversion trend (last 8 weeks) ─────────────────────
        $weeklyConversion = $db->query("
            SELECT
                YEARWEEK(created_at, 1)    AS week_num,
                MIN(DATE(created_at))      AS week_start,
                COUNT(*)                   AS total,
                SUM(status = 'converted')  AS converted
            FROM leads
            WHERE user_id = ?
              AND deleted_at IS NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
            GROUP BY YEARWEEK(created_at, 1)
            ORDER BY week_num ASC
        ", [$userId])->getResultArray();

        // ── Top lead sources ───────────────────────────────────────────
        $sources = $db->query("
            SELECT source, COUNT(*) AS cnt
            FROM leads
            WHERE user_id = ? AND deleted_at IS NULL
            GROUP BY source
            ORDER BY cnt DESC
        ", [$userId])->getResultArray();

        // ── Active funnels completion stats ────────────────────────────
        $funnelStats = $db->query("
            SELECT f.name,
                   COUNT(fs.id)                        AS total,
                   SUM(fs.status = 'completed')        AS completed,
                   SUM(fs.status = 'abandoned')        AS abandoned,
                   SUM(fs.status = 'active')           AS active
            FROM funnels f
            LEFT JOIN funnel_sessions fs ON fs.funnel_id = f.id
            WHERE f.user_id = ?
            GROUP BY f.id
            ORDER BY total DESC
            LIMIT 5
        ", [$userId])->getResultArray();

        // ── Automation execution stats (last 30 days) ─────────────────
        $automationStats = $db->query("
            SELECT DATE(created_at) AS day,
                   SUM(status = 'executed') AS executed,
                   SUM(status = 'failed')   AS failed
            FROM automation_logs
            WHERE user_id = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId])->getResultArray();

        return view('admin/analytics/index', [
            'title' => lang('Titles.analytics'),
            // Lead stats
            'lead_stats'         => $leadStats,
            'leads_this_month'   => $leadsThisMonth,
            'leads_last_month'   => $leadsLastMonth,
            'leads_today'        => $leadsToday,
            'leads_this_week'    => $leadsThisWeek,
            'mom_change'         => $momChange,
            // Message stats
            'total_messages'     => $totalMessages,
            'ai_messages'        => $aiMessages,
            'tokens_total'       => $tokensTotal,
            'conv_total'         => $convTotal,
            // Rates
            'conv_rate'          => $convRate,
            'response_rate'      => $responseRate,
            'avg_response'       => $avgResponseMin,
            'avg_days_convert'   => $avgDaysToConvert,
            // Charts
            'score_distribution' => $scoreDistribution,
            'conversion_funnel'  => $conversionFunnel,
            'weekly_conversion'  => $weeklyConversion,
            'sources'            => $sources,
            'funnel_stats'       => $funnelStats,
            'automation_stats'   => $automationStats,
        ]);
    }

    // ------------------------------------------------------------------
    // GET /admin/analytics/data  (AJAX — returns chart JSON)
    // ------------------------------------------------------------------

    public function data()
    {
        if (! plan_feature('advanced_dashboard')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['error' => lang('Messages.advancedAnalyticsShort')]);
        }

        $db     = \Config\Database::connect();
        $userId = $this->userId;
        $days   = min((int) ($this->request->getGet('days') ?? 30), 90);

        // Messages per day (inbound / AI / agent)
        $msgRows = $db->query("
            SELECT
                DATE(created_at) AS day,
                SUM(direction = 'inbound')                         AS inbound,
                SUM(sender = 'ai')                                 AS ai,
                SUM(direction = 'outbound' AND sender != 'ai')     AS agent
            FROM messages
            WHERE user_id = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId, $days])->getResultArray();

        // New leads per day
        $leadRows = $db->query("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM leads
            WHERE user_id = ?
              AND deleted_at IS NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId, $days])->getResultArray();

        // AI tokens per day
        $tokenRows = $db->query("
            SELECT DATE(created_at) AS day, SUM(tokens_used) AS tokens
            FROM messages
            WHERE user_id = ?
              AND sender = 'ai'
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId, $days])->getResultArray();

        // Lead status distribution (donut)
        $statusRows = $db->query("
            SELECT status, COUNT(*) AS cnt
            FROM leads
            WHERE user_id = ? AND deleted_at IS NULL
            GROUP BY status
        ", [$userId])->getResultArray();

        // Lead source distribution (donut)
        $sourceRows = $db->query("
            SELECT COALESCE(NULLIF(source,''),'unknown') AS source, COUNT(*) AS cnt
            FROM leads
            WHERE user_id = ? AND deleted_at IS NULL
            GROUP BY source
        ", [$userId])->getResultArray();

        // Response rate per day: conversations with outbound / conversations with inbound
        $responseRows = $db->query("
            SELECT
                DATE(created_at)                                                       AS day,
                COUNT(DISTINCT CASE WHEN direction = 'inbound'  THEN conversation_id END) AS inbound_convs,
                COUNT(DISTINCT CASE WHEN direction = 'outbound' THEN conversation_id END) AS replied_convs
            FROM messages
            WHERE user_id = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId, $days])->getResultArray();

        // Conversion rate per day
        // (leads created on day that eventually became converted / all leads created on that day)
        $convRateRows = $db->query("
            SELECT
                DATE(created_at) AS day,
                COUNT(*)                   AS total,
                SUM(status = 'converted')  AS converted
            FROM leads
            WHERE user_id = ?
              AND deleted_at IS NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$userId, $days])->getResultArray();

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setJSON([
                'messages'     => $msgRows,
                'leads'        => $leadRows,
                'tokens'       => $tokenRows,
                'status'       => $statusRows,
                'sources'      => $sourceRows,
                'response'     => $responseRows,
                'conv_rate'    => $convRateRows,
            ]);
    }
}

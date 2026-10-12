<?php

use App\Services\PlanService;

/**
 * Plan/feature helpers for the CURRENT logged-in tenant (session-based).
 * Used in controllers and views to gate features and show usage.
 *
 *   if (plan_feature('csv_export')) { ... }
 *   $usage = plan_usage();
 */

if (! function_exists('plan_service')) {
    /**
     * PlanService for the current session user (cached per request), or null
     * when there's no logged-in user.
     */
    function plan_service(): ?PlanService
    {
        static $svc = false;

        if ($svc === false) {
            $uid = (int) session()->get('user_id');
            $svc = $uid > 0 ? new PlanService($uid) : null;
        }

        return $svc;
    }
}

if (! function_exists('plan_feature')) {
    /**
     * Does the current tenant's plan include this feature?
     * Super admins and unrestricted (no-subscription) accounts always pass.
     */
    function plan_feature(string $key): bool
    {
        if (session()->get('user_role') === 'superadmin') {
            return true;
        }

        $svc = plan_service();
        return $svc ? $svc->hasFeature($key) : true;
    }
}

if (! function_exists('plan_usage')) {
    /**
     * Usage summary for the current tenant (limits + used), or null.
     */
    function plan_usage(): ?array
    {
        $svc = plan_service();
        return $svc ? $svc->usage() : null;
    }
}

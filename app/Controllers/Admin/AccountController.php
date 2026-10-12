<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use App\Services\PlanService;

/**
 * Pages shown to a tenant about their own account / subscription / plan.
 */
class AccountController extends BaseController
{
    public function inactive()
    {
        $userId = (int) session()->get('user_id');
        $sub    = (new SubscriptionModel())->currentForUser($userId);
        $plan   = ($sub && ! empty($sub['plan_id'])) ? (new PlanModel())->find($sub['plan_id']) : null;

        return view('admin/inactive', [
            'title' => lang('Titles.subscription'),
            'sub'   => $sub,
            'plan'  => $plan,
        ]);
    }

    /**
     * Plan & Usage page — current plan, this-cycle usage, and included features.
     */
    public function plan()
    {
        $svc = new PlanService((int) session()->get('user_id'));

        return view('admin/plan', [
            'title' => lang('Titles.planUsage'),
            'usage' => $svc->usage(),
        ]);
    }
}

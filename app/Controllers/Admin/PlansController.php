<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlanModel;

/**
 * Super Admin: manage subscription plans shown on the public sales page.
 */
class PlansController extends BaseController
{
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();

        if (session()->get('user_role') !== 'superadmin') {
            redirect()->to('/panel/dashboard')->with('error', lang('Messages.accessDenied'))->send();
            exit;
        }
    }

    public function index()
    {
        return view('admin/plans/index', [
            'title' => lang('Titles.plans'),
            'plans' => $this->planModel->orderBy('price', 'ASC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('admin/plans/form', ['title' => lang('Titles.addPlan'), 'plan' => null]);
    }

    public function edit(int $id)
    {
        $plan = $this->planModel->find($id);
        if (! $plan) {
            return redirect()->to('/panel/plans')->with('error', lang('Messages.planNotFound'));
        }

        return view('admin/plans/form', ['title' => lang('Titles.editPlan'), 'plan' => $plan]);
    }

    public function store()
    {
        return $this->persist(null);
    }

    public function update(int $id)
    {
        if (! $this->planModel->find($id)) {
            return redirect()->to('/panel/plans')->with('error', lang('Messages.planNotFound'));
        }

        return $this->persist($id);
    }

    public function toggle(int $id)
    {
        $plan = $this->planModel->find($id);
        if (! $plan) {
            return redirect()->back()->with('error', lang('Messages.planNotFound'));
        }

        $this->planModel->update($id, ['is_active' => $plan['is_active'] ? 0 : 1]);

        return redirect()->to('/panel/plans')->with('success', lang('Messages.planUpdated'));
    }

    public function delete(int $id)
    {
        $plan = $this->planModel->find($id);
        if (! $plan) {
            return redirect()->back()->with('error', lang('Messages.planNotFound'));
        }

        // Don't delete a plan that subscriptions/orders depend on — deactivate instead.
        $db   = \Config\Database::connect();
        $used = $db->table('subscriptions')->where('plan_id', $id)->countAllResults()
              + $db->table('payment_orders')->where('plan_id', $id)->countAllResults();

        if ($used > 0) {
            return redirect()->to('/panel/plans')
                ->with('error', lang('Messages.planInUse'));
        }

        $this->planModel->delete($id);

        return redirect()->to('/panel/plans')->with('success', "Plan '{$plan['name']}' deleted.");
    }

    // ------------------------------------------------------------------

    private function persist(?int $id)
    {
        $rules = [
            'name'       => 'required|min_length[2]|max_length[100]',
            'price'      => 'required|decimal|greater_than_equal_to[0]',
            'lead_limit' => 'required|integer',
            'ai_replies' => 'required|integer',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name     = trim($this->request->getPost('name'));
        $slugInput = trim($this->request->getPost('slug') ?? '');
        $slug     = $this->uniqueSlug($slugInput !== '' ? $slugInput : $name, $id);

        // Features: one per line → JSON array
        $features = array_values(array_filter(array_map('trim',
            explode("\n", $this->request->getPost('features') ?? ''))));

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'price'      => (float) $this->request->getPost('price'),
            'lead_limit' => (int) $this->request->getPost('lead_limit'),
            'ai_replies' => (int) $this->request->getPost('ai_replies'),
            'features'   => json_encode($features),
            'is_active'  => $this->request->getPost('is_active') ? 1 : 0,
        ];

        if ($id) {
            $this->planModel->update($id, $data);
            $msg = lang('Messages.planUpdated');
        } else {
            $this->planModel->insert($data);
            $msg = lang('Messages.planCreated');
        }

        return redirect()->to('/panel/plans')->with('success', $msg);
    }

    /**
     * Build a URL-safe, unique slug (ignoring the row being edited).
     */
    private function uniqueSlug(string $source, ?int $ignoreId): string
    {
        helper('text');
        $base = url_title(strtolower($source), '-', true) ?: 'plan';
        $slug = $base;
        $i    = 1;

        while (true) {
            $q = $this->planModel->where('slug', $slug);
            if ($ignoreId) {
                $q->where('id !=', $ignoreId);
            }
            if ($q->countAllResults() === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PaymentOrderModel;
use App\Models\PlanModel;
use App\Models\UserModel;
use App\Payments\PaymentManager;
use App\Services\TenantProvisioner;

/**
 * Super Admin: payment / order management — view every checkout and its status.
 */
class OrdersController extends BaseController
{
    protected PaymentOrderModel $orders;

    public function __construct()
    {
        $this->orders = new PaymentOrderModel();

        if (session()->get('user_role') !== 'superadmin') {
            redirect()->to('/panel/dashboard')->with('error', lang('Messages.accessDenied'))->send();
            exit;
        }
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('search'));
        $page   = max((int) ($this->request->getGet('page') ?? 1), 1);
        $perPage = 20;

        $builder = $this->orders
            ->select('payment_orders.*, plans.name AS plan_name')
            ->join('plans', 'plans.id = payment_orders.plan_id', 'left')
            ->orderBy('payment_orders.id', 'DESC');

        if ($status && in_array($status, ['pending', 'paid', 'failed', 'cancelled'], true)) {
            $builder->where('payment_orders.status', $status);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('payment_orders.buyer_email', $search)
                ->orLike('payment_orders.buyer_name', $search)
                ->orLike('payment_orders.gateway_order_id', $search)
                ->groupEnd();
        }

        $total  = $builder->countAllResults(false);
        $orders = $builder->paginate($perPage, 'default', $page);

        return view('admin/orders/index', [
            'title' => lang('Titles.orders'),
            'orders'   => $orders,
            'pager'    => $this->orders->pager,
            'total'    => $total,
            'status'   => $status,
            'search'   => $search,
            'summary'  => $this->summary(),
            'currency' => (new PaymentManager())->currency(),
        ]);
    }

    public function show(int $id)
    {
        $order = $this->orders->find($id);
        if (! $order) {
            return redirect()->to('/panel/orders')->with('error', lang('Messages.orderNotFound'));
        }

        $plan = $order['plan_id'] ? (new PlanModel())->find($order['plan_id']) : null;
        $user = $order['user_id'] ? (new UserModel())->find($order['user_id']) : null;

        return view('admin/orders/show', [
            'title' => lang('Titles.order', [$order['id']]),
            'order' => $order,
            'plan'  => $plan,
            'user'  => $user,
        ]);
    }

    /**
     * Manually change an order's payment status. Marking an unprovisioned order
     * as "paid" creates (or links) the tenant account — useful for offline /
     * bank-transfer payments and reconciliation.
     */
    public function updateStatus(int $id)
    {
        $order = $this->orders->find($id);
        if (! $order) {
            return redirect()->to('/panel/orders')->with('error', lang('Messages.orderNotFound'));
        }

        $newStatus = (string) $this->request->getPost('status');
        if (! in_array($newStatus, ['pending', 'paid', 'failed', 'cancelled'], true)) {
            return redirect()->back()->with('error', lang('Messages.invalidStatus'));
        }

        $note = '';

        // Marking as paid for the first time → provision (new) or link + extend (existing).
        if ($newStatus === 'paid' && empty($order['user_id'])) {
            $result = (new TenantProvisioner())->provisionOrExtend(
                $order['buyer_name'],
                $order['buyer_email'],
                (int) $order['plan_id']
            );
            $this->orders->update($id, ['status' => 'paid', 'user_id' => $result['user_id']]);

            $note = $result['created']
                ? lang('Messages.orderNoteCreated', [$order['buyer_email']])
                : lang('Messages.orderNoteLinked', [$order['buyer_email']]);
        } else {
            $this->orders->update($id, ['status' => $newStatus]);
        }

        return redirect()->to('/panel/orders/' . $id)
            ->with('success', lang('Messages.orderMarkedAs', [$newStatus]) . $note);
    }

    // ------------------------------------------------------------------

    /**
     * Counts + paid revenue for the summary cards (fresh builders, no state leak).
     */
    private function summary(): array
    {
        $db = db_connect();

        $counts = [];
        foreach (['paid', 'pending', 'failed', 'cancelled'] as $s) {
            $counts[$s] = $db->table('payment_orders')->where('status', $s)->countAllResults();
        }

        $revenue = (float) ($db->table('payment_orders')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->get()->getRow()->amount ?? 0);

        return [
            'revenue'   => $revenue,
            'paid'      => $counts['paid'],
            'pending'   => $counts['pending'],
            'failed'    => $counts['failed'],
            'cancelled' => $counts['cancelled'],
            'total'     => array_sum($counts),
        ];
    }
}

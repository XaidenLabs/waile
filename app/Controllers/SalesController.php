<?php

namespace App\Controllers;

use App\Models\PlanModel;
use App\Models\UserModel;
use App\Models\SubscriptionModel;
use App\Models\PaymentOrderModel;
use App\Payments\PaymentManager;
use App\Services\EmailService;

/**
 * Public sales funnel: pricing page → checkout → payment → account provisioning.
 *
 *   GET  /pricing             — branded marketing + pricing page
 *   GET  /checkout/{slug}     — checkout form for a plan
 *   POST /checkout            — create order; free → provision, paid → gateway
 *   GET  /payment/success     — gateway return: capture + provision
 *   GET  /payment/cancel      — gateway cancel
 */
class SalesController extends BaseController
{
    public function pricing()
    {
        return view('sales/pricing', [
            'title' => lang('Titles.pricing', [app_name()]),
            'plans' => (new PlanModel())->activePlans(),
            'sales' => (new \App\Models\SettingModel())->getGroup('sales'),
        ]);
    }

    public function checkout(string $slug)
    {
        $plan = (new PlanModel())->findBySlug($slug);
        if (! $plan) {
            return redirect()->to('pricing')->with('error', lang('Messages.planUnavailable'));
        }

        return view('sales/checkout', [
            'title' => lang('Titles.checkout', [$plan['name']]),
            'plan'  => $plan,
        ]);
    }

    public function process()
    {
        $rules = [
            'plan_slug' => 'required',
            'name'      => 'required|min_length[2]|max_length[150]',
            'email'     => 'required|valid_email|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $plan = (new PlanModel())->findBySlug($this->request->getPost('plan_slug'));
        if (! $plan) {
            return redirect()->to('pricing')->with('error', lang('Messages.planUnavailable'));
        }

        $name  = trim($this->request->getPost('name'));
        $email = strtolower(trim($this->request->getPost('email')));

        if ((new UserModel())->findByEmail($email)) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.emailExists'));
        }

        $pm       = new PaymentManager();
        $currency = $pm->currency();
        $price    = (float) $plan['price'];
        $orders   = new PaymentOrderModel();

        // --- Free plan: provision immediately, no payment ---
        if ($price <= 0) {
            $orderId = $orders->insert([
                'plan_id' => $plan['id'], 'gateway' => 'free', 'buyer_name' => $name,
                'buyer_email' => $email, 'amount' => 0, 'currency' => $currency, 'status' => 'paid',
            ]);
            $result = (new \App\Services\TenantProvisioner())
                ->provisionOrExtend($name, $email, (int) $plan['id']);
            $orders->update($orderId, ['user_id' => $result['user_id']]);

            return redirect()->to('payment/success')->with('free_done', 1)->with('email', $email);
        }

        // --- Paid plan: hand off to the gateway ---
        $gateway = $pm->gateway();
        if (! $gateway) {
            return redirect()->to('pricing')
                ->with('error', lang('Messages.paymentUnavailable'));
        }

        $orderId = $orders->insert([
            'plan_id' => $plan['id'], 'gateway' => $gateway->key(), 'buyer_name' => $name,
            'buyer_email' => $email, 'amount' => $price, 'currency' => $currency, 'status' => 'pending',
        ]);

        try {
            $created = $gateway->createOrder(
                $price,
                $currency,
                app_name() . ' — ' . $plan['name'] . ' plan',
                site_url('payment/success'),
                site_url('payment/cancel')
            );
        } catch (\Throwable $e) {
            log_message('error', 'Checkout createOrder failed: ' . $e->getMessage());
            $orders->update($orderId, ['status' => 'failed']);
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.paymentStartFailed'));
        }

        $orders->update($orderId, ['gateway_order_id' => $created['id']]);

        return redirect()->to($created['approveUrl']);
    }

    public function success()
    {
        $token  = (string) $this->request->getGet('token'); // gateway order id
        $orders = new PaymentOrderModel();

        if ($token !== '') {
            $order = $orders->findByGatewayOrderId($token);
            if (! $order) {
                return $this->message('error', lang('Messages.orderNotFoundSales'));
            }

            // Already provisioned (e.g. page refresh) — don't double-charge/create.
            if ($order['status'] === 'paid' && $order['user_id']) {
                return $this->message('success', lang('Messages.accountReady'), $order['buyer_email']);
            }

            $gateway = (new PaymentManager())->gateway();
            if (! $gateway) {
                return $this->message('error', lang('Messages.paymentGatewayUnavailable'));
            }

            $capture = $gateway->captureOrder($token);
            if (! $capture['paid']) {
                $orders->update($order['id'], ['status' => 'failed']);
                return $this->message('error', lang('Messages.paymentNotCompleted'));
            }

            $result = (new \App\Services\TenantProvisioner())
                ->provisionOrExtend($order['buyer_name'], $order['buyer_email'], (int) $order['plan_id']);
            $orders->update($order['id'], ['status' => 'paid', 'user_id' => $result['user_id']]);

            $msg = $result['created']
                ? lang('Messages.paymentReceivedReady')
                : lang('Messages.paymentReceivedUpdated');

            return $this->message('success', $msg, $order['buyer_email']);
        }

        // Free-plan completion
        if (session()->getFlashdata('free_done')) {
            return $this->message('success', lang('Messages.accountReady'), session()->getFlashdata('email'));
        }

        return redirect()->to('pricing');
    }

    public function cancel()
    {
        $token = (string) $this->request->getGet('token');
        if ($token !== '') {
            $orders = new PaymentOrderModel();
            if ($order = $orders->findByGatewayOrderId($token)) {
                $orders->update($order['id'], ['status' => 'cancelled']);
            }
        }

        return $this->message('error', lang('Messages.paymentCancelled'));
    }

    // ------------------------------------------------------------------

    private function message(string $type, string $text, ?string $email = null)
    {
        return view('sales/message', [
            'title' => lang('Titles.checkoutPlain'),
            'type'  => $type,   // 'success' | 'error'
            'text'  => $text,
            'email' => $email,
        ]);
    }
}

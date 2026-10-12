<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Models\WhatsAppConfigModel;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use App\Services\EmailService;

/**
 * Superadmin-only: manage all business accounts (tenants).
 */
class TenantsController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();

        // Guard: superadmin only
        if (session()->get('user_role') !== 'superadmin') {
            redirect()->to('/panel/dashboard')->with('error', lang('Messages.accessDenied'))->send();
            exit;
        }
    }

    // ------------------------------------------------------------------
    // List all tenants
    // ------------------------------------------------------------------

    public function index()
    {
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');
        $page   = (int) ($this->request->getGet('page') ?? 1);
        $perPage = 15;

        $builder = $this->userModel
            ->where('role !=', 'superadmin')
            ->orderBy('created_at', 'DESC');

        if ($search) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->orLike('company_name', $search)
                ->groupEnd();
        }

        if ($status) {
            $builder->where('status', $status);
        }

        $total   = $builder->countAllResults(false);
        $tenants = $builder->paginate($perPage, 'default', $page);

        return view('admin/tenants/index', [
            'title' => lang('Titles.tenantManagement'),
            'tenants' => $tenants,
            'total'   => $total,
            'pager'   => $this->userModel->pager,
            'search'  => $search,
            'status'  => $status,
        ]);
    }

    // ------------------------------------------------------------------
    // Create tenant — form
    // ------------------------------------------------------------------

    public function create()
    {
        return view('admin/tenants/create', [
            'title' => lang('Titles.addTenant'),
        ]);
    }

    // ------------------------------------------------------------------
    // Create tenant — store
    // ------------------------------------------------------------------

    public function store()
    {
        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'email'        => 'required|valid_email|max_length[255]|is_unique[users.email]',
            'company_name' => 'permit_empty|max_length[255]',
            'phone'        => 'permit_empty|max_length[20]',
            'timezone'     => 'required|max_length[100]',
            'password'     => 'required|min_length[8]|max_length[72]',
            'status'       => 'required|in_list[active,inactive,suspended]',
        ];

        $messages = [
            'email'    => ['is_unique' => lang('Messages.emailAlreadyRegistered')],
            'password' => ['min_length' => lang('Messages.passwordMin8')],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $name     = trim($this->request->getPost('name'));
        $email    = strtolower(trim($this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        $userId = $this->userModel->insert([
            'name'         => $name,
            'company_name' => trim($this->request->getPost('company_name') ?? ''),
            'email'        => $email,
            'phone'        => trim($this->request->getPost('phone') ?? ''),
            'timezone'     => $this->request->getPost('timezone'),
            'password'     => $password, // hashed by model hook
            'role'         => 'user',
            'status'       => $this->request->getPost('status'),
        ]);

        if (! $userId) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.tenantCreateFailed'));
        }

        // Send the welcome email with login credentials (non-blocking).
        $emailSent = $this->sendTenantEmail($email, lang('Emails.subjWelcome', [$this->appName()]), 'tenant_welcome', [
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);

        $msg = $emailSent
            ? lang('Messages.tenantCreatedEmailSent')
            : lang('Messages.tenantCreatedEmailFailed');

        return redirect()->to("/panel/tenants/{$userId}")->with('success', $msg);
    }

    // ------------------------------------------------------------------
    // View single tenant
    // ------------------------------------------------------------------

    public function show(int $id)
    {
        $tenant = $this->userModel->find($id);

        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->to('/panel/tenants')
                ->with('error', lang('Messages.tenantNotFound'));
        }

        $leadModel = new LeadModel();
        $convModel = new ConversationModel();
        $waModel   = new WhatsAppConfigModel();

        $stats = [
            'leads'              => $leadModel->forTenant($id)->countAllResults(),
            'active_convs'       => $convModel->forTenant($id)->where('status', 'active')->countAllResults(),
            'whatsapp_connected' => (bool) $waModel->getForTenant($id),
            'lead_breakdown'     => $leadModel->getStats($id),
        ];

        $subModel    = new SubscriptionModel();
        $planModel   = new PlanModel();
        $sub         = $subModel->currentForUser($id);
        $currentPlan = ($sub && ! empty($sub['plan_id'])) ? $planModel->find($sub['plan_id']) : null;

        return view('admin/tenants/show', [
            'title' => lang('Titles.tenant', [$tenant['company_name'] ?: $tenant['name']]),
            'tenant'       => $tenant,
            'stats'        => $stats,
            'subscription' => $sub,
            'currentPlan'  => $currentPlan,
            'plans'        => $planModel->where('is_active', 1)->orderBy('price', 'ASC')->findAll(),
        ]);
    }

    // ------------------------------------------------------------------
    // Subscription management (grant / extend / cancel)
    // ------------------------------------------------------------------

    public function saveSubscription(int $id)
    {
        $tenant = $this->userModel->find($id);
        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->to('/panel/tenants')->with('error', lang('Messages.tenantNotFound'));
        }

        $subModel = new SubscriptionModel();

        if ($this->request->getPost('action') === 'cancel') {
            $subModel->cancelFor($id);
            return redirect()->back()->with('success', lang('Messages.subscriptionCancelled'));
        }

        $planId = (int) $this->request->getPost('plan_id');
        if (! $planId || ! (new PlanModel())->find($planId)) {
            return redirect()->back()->with('error', lang('Messages.choosePlanValid'));
        }

        $period  = (string) $this->request->getPost('period');
        $allowed = ['+1 month', '+3 months', '+6 months', '+1 year'];
        if (! in_array($period, $allowed, true)) {
            $period = '+1 month';
        }

        $subModel->grantOrExtend($id, $planId, $period);

        return redirect()->back()->with('success', lang('Messages.subscriptionUpdated'));
    }

    // ------------------------------------------------------------------
    // Edit tenant form
    // ------------------------------------------------------------------

    public function edit(int $id)
    {
        $tenant = $this->userModel->find($id);

        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->to('/panel/tenants')
                ->with('error', lang('Messages.tenantNotFound'));
        }

        return view('admin/tenants/edit', [
            'title' => lang('Titles.editTenant', [$tenant['company_name'] ?: $tenant['name']]),
            'tenant' => $tenant,
        ]);
    }

    // ------------------------------------------------------------------
    // Update tenant
    // ------------------------------------------------------------------

    public function update(int $id)
    {
        $tenant = $this->userModel->find($id);

        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->to('/panel/tenants')
                ->with('error', lang('Messages.tenantNotFound'));
        }

        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'email'        => "required|valid_email|max_length[255]|is_unique[users.email,id,{$id}]",
            'company_name' => 'permit_empty|max_length[255]',
            'phone'        => 'permit_empty|max_length[20]',
            'timezone'     => 'required|max_length[100]',
            'status'       => 'required|in_list[active,inactive,suspended]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'         => trim($this->request->getPost('name')),
            'email'        => trim($this->request->getPost('email')),
            'company_name' => trim($this->request->getPost('company_name') ?? ''),
            'phone'        => trim($this->request->getPost('phone') ?? ''),
            'timezone'     => $this->request->getPost('timezone'),
            'status'       => $this->request->getPost('status'),
        ];

        // Only update password when a new one is provided
        $newPassword = trim($this->request->getPost('new_password') ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return redirect()->back()->withInput()
                    ->with('errors', ['new_password' => lang('Messages.passwordMin8')]);
            }
            $data['password'] = $newPassword; // hashed by model hook
        }

        $this->userModel->skipValidation(true)->update($id, $data);

        // If the admin set a new password, let the tenant know (non-blocking).
        if ($newPassword !== '') {
            $this->sendTenantEmail($data['email'], lang('Emails.subjPasswordChanged'), 'password_changed', [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $newPassword,
            ]);
        }

        return redirect()->to("/panel/tenants/{$id}")
            ->with('success', lang('Messages.tenantUpdated'));
    }

    // ------------------------------------------------------------------
    // Toggle status (active / suspended)
    // ------------------------------------------------------------------

    public function toggleStatus(int $id)
    {
        $tenant = $this->userModel->find($id);

        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->back()->with('error', lang('Messages.cannotModifyAccount'));
        }

        $newStatus = $tenant['status'] === 'active' ? 'suspended' : 'active';
        $this->userModel->update($id, ['status' => $newStatus]);

        // Notify the tenant their account was suspended / reactivated (non-blocking).
        $this->sendTenantEmail($tenant['email'], lang('Emails.subjAccountStatus'), 'account_status', [
            'name'   => $tenant['name'],
            'status' => $newStatus,
        ]);

        return redirect()->back()
            ->with('success', "Account {$newStatus} successfully.");
    }

    // ------------------------------------------------------------------
    // Delete tenant (soft delete)
    // ------------------------------------------------------------------

    public function delete(int $id)
    {
        $tenant = $this->userModel->find($id);

        if (! $tenant || $tenant['role'] === 'superadmin') {
            return redirect()->back()->with('error', lang('Messages.cannotDeleteAccount'));
        }

        $this->userModel->delete($id); // soft delete (deleted_at)

        return redirect()->to('/panel/tenants')
            ->with('success', "Tenant '{$tenant['name']}' deleted.");
    }

    // ------------------------------------------------------------------
    // Email helpers
    // ------------------------------------------------------------------

    /**
     * Render an email view and send it. Never throws — email is best-effort and
     * must not block the admin action. Returns true on a confirmed send.
     */
    private function sendTenantEmail(string $to, string $subject, string $view, array $data): bool
    {
        $data['appName']  = $this->appName();
        $data['loginUrl'] = site_url('login');

        try {
            $html = view('emails/' . $view, $data);
            return (new EmailService())->send($to, $subject, $html);
        } catch (\Throwable $e) {
            log_message('error', "Tenant email [{$view}] failed: " . $e->getMessage());
            return false;
        }
    }

    private function appName(): string
    {
        return app_name(); // branding('brand_name') → env APP_NAME → 'WALE'
    }
}

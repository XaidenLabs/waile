<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AutomationRuleModel;
use App\Models\AutomationLogModel;
use App\Models\LeadModel;

class AutomationsController extends BaseController
{
    protected AutomationRuleModel $ruleModel;
    protected AutomationLogModel  $logModel;
    protected int                 $userId;

    public function __construct()
    {
        $this->ruleModel = new AutomationRuleModel();
        $this->logModel  = new AutomationLogModel();
        $this->userId    = (int) session()->get('user_id');
    }

    // ------------------------------------------------------------------
    // INDEX — list all rules
    // ------------------------------------------------------------------

    public function index()
    {
        $rules = $this->ruleModel
            ->where('user_id', $this->userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        // Decode configs for display
        $rules = array_map(fn($r) => $this->ruleModel->decodeConfigs($r), $rules);

        $logCount = \Config\Database::connect()
            ->table('automation_logs')
            ->where('user_id', $this->userId)
            ->countAllResults();

        return view('admin/automations/index', [
            'title' => lang('Titles.automationRules'),
            'rules'        => $rules,
            'trigger_types'=> AutomationRuleModel::triggerTypeLabels(),
            'action_types' => AutomationRuleModel::actionTypeLabels(),
            'log_count'    => $logCount,
        ]);
    }

    // ------------------------------------------------------------------
    // CREATE form
    // ------------------------------------------------------------------

    public function create()
    {
        return view('admin/automations/form', [
            'title' => lang('Titles.newRule'),
            'rule'         => null,
            'trigger_types'=> AutomationRuleModel::triggerTypeLabels(),
            'action_types' => AutomationRuleModel::actionTypeLabels(),
            'statuses'     => LeadModel::STATUSES,
        ]);
    }

    // ------------------------------------------------------------------
    // STORE
    // ------------------------------------------------------------------

    public function store()
    {
        $data = $this->buildData();

        if (! $data) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->ruleModel->insert($data);

        return redirect()->to('/panel/automations')
            ->with('success', lang('Messages.ruleCreated'));
    }

    // ------------------------------------------------------------------
    // EDIT form
    // ------------------------------------------------------------------

    public function edit(int $id)
    {
        $rule = $this->ruleModel->where('user_id', $this->userId)->find($id);

        if (! $rule) {
            return redirect()->to('/panel/automations')->with('error', lang('Messages.ruleNotFound'));
        }

        $rule = $this->ruleModel->decodeConfigs($rule);

        return view('admin/automations/form', [
            'title' => lang('Titles.editRule'),
            'rule'         => $rule,
            'trigger_types'=> AutomationRuleModel::triggerTypeLabels(),
            'action_types' => AutomationRuleModel::actionTypeLabels(),
            'statuses'     => LeadModel::STATUSES,
        ]);
    }

    // ------------------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------------------

    public function update(int $id)
    {
        $rule = $this->ruleModel->where('user_id', $this->userId)->find($id);

        if (! $rule) {
            return redirect()->to('/panel/automations')->with('error', lang('Messages.ruleNotFound'));
        }

        $data = $this->buildData();

        if (! $data) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->ruleModel->update($id, $data);

        return redirect()->to('/panel/automations')
            ->with('success', lang('Messages.ruleUpdated'));
    }

    // ------------------------------------------------------------------
    // TOGGLE active/inactive
    // ------------------------------------------------------------------

    public function toggle(int $id)
    {
        $rule = $this->ruleModel->where('user_id', $this->userId)->find($id);

        if (! $rule) {
            return redirect()->back()->with('error', lang('Messages.ruleNotFound'));
        }

        $this->ruleModel->update($id, ['is_active' => $rule['is_active'] ? 0 : 1]);

        return redirect()->back()->with('success', lang('Messages.ruleStatusChanged', [$rule['is_active'] ? lang('Messages.statusPaused') : lang('Messages.statusActivated')]));
    }

    // ------------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------------

    public function delete(int $id)
    {
        $rule = $this->ruleModel->where('user_id', $this->userId)->find($id);

        if (! $rule) {
            return redirect()->back()->with('error', lang('Messages.ruleNotFound'));
        }

        $this->ruleModel->delete($id);

        return redirect()->to('/panel/automations')
            ->with('success', lang('Messages.ruleDeleted'));
    }

    // ------------------------------------------------------------------
    // LOGS
    // ------------------------------------------------------------------

    public function logs()
    {
        $result = $this->logModel->getForTenant($this->userId);

        return view('admin/automations/logs', [
            'title' => lang('Titles.automationLogs'),
            'result' => $result,
        ]);
    }

    // ------------------------------------------------------------------
    // Private: build + validate POST data
    // ------------------------------------------------------------------

    private function buildData(): ?array
    {
        $rules = [
            'name'        => 'required|min_length[2]|max_length[150]',
            'trigger_type'=> 'required|in_list[new_lead,no_reply,status_change,keyword_match]',
            'action_type' => 'required|in_list[send_message,send_template,update_status,add_note]',
        ];

        if (! $this->validate($rules)) {
            return null;
        }

        $triggerType = $this->request->getPost('trigger_type');
        $actionType  = $this->request->getPost('action_type');

        // Build trigger_config JSON
        $triggerConfig = match ($triggerType) {
            'new_lead'      => ['delay_minutes' => (int) ($this->request->getPost('delay_minutes') ?? 0)],
            'no_reply'      => ['minutes'       => max(1, (int) ($this->request->getPost('no_reply_minutes') ?? 60))],
            'status_change' => [
                'from_status' => $this->request->getPost('from_status') ?? '',
                'to_status'   => $this->request->getPost('to_status')   ?? '',
            ],
            'keyword_match' => [
                'keywords' => array_filter(
                    array_map('trim', explode(',', $this->request->getPost('keywords') ?? ''))
                ),
            ],
            default => [],
        };

        // Build action_config JSON
        $actionConfig = match ($actionType) {
            'send_message'  => ['message'  => trim($this->request->getPost('action_message') ?? '')],
            'send_template' => [
                'template' => trim($this->request->getPost('template_name') ?? ''),
                'lang'     => $this->request->getPost('template_lang')      ?? 'en_US',
            ],
            'update_status' => ['status'   => $this->request->getPost('target_status')  ?? ''],
            'add_note'      => [
                'note' => trim($this->request->getPost('note_text')  ?? ''),
                'type' => $this->request->getPost('note_type') ?? 'general',
            ],
            default => [],
        };

        return [
            'user_id'        => $this->userId,
            'name'           => trim($this->request->getPost('name')),
            'description'    => trim($this->request->getPost('description') ?? ''),
            'trigger_type'   => $triggerType,
            'trigger_config' => json_encode($triggerConfig),
            'action_type'    => $actionType,
            'action_config'  => json_encode($actionConfig),
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
        ];
    }
}

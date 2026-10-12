<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FunnelModel;
use App\Models\FunnelStepModel;
use App\Models\FunnelConditionModel;
use App\Models\FunnelSessionModel;
use App\Models\LeadModel;
use App\Services\FunnelService;

class FunnelsController extends BaseController
{
    protected FunnelModel       $funnelModel;
    protected FunnelStepModel   $stepModel;
    protected FunnelConditionModel $condModel;
    protected FunnelSessionModel   $sessionModel;
    protected int               $userId;

    public function __construct()
    {
        $this->funnelModel  = new FunnelModel();
        $this->stepModel    = new FunnelStepModel();
        $this->condModel    = new FunnelConditionModel();
        $this->sessionModel = new FunnelSessionModel();
        $this->userId       = (int) session()->get('user_id');
    }

    // ------------------------------------------------------------------
    // INDEX
    // ------------------------------------------------------------------

    public function index()
    {
        $funnels = $this->funnelModel
            ->where('user_id', $this->userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($funnels as &$f) {
            $f['step_count']    = $this->stepModel->where('funnel_id', $f['id'])->countAllResults();
            $f['session_count'] = $this->sessionModel->where('funnel_id', $f['id'])->countAllResults();
            $f['active_sessions'] = $this->sessionModel
                ->where('funnel_id', $f['id'])->where('status', 'active')->countAllResults();
        }
        unset($f);

        return view('admin/funnels/index', [
            'title' => lang('Titles.funnels'),
            'funnels'       => $funnels,
            'trigger_types' => FunnelModel::triggerTypeLabels(),
        ]);
    }

    // ------------------------------------------------------------------
    // CREATE form
    // ------------------------------------------------------------------

    public function create()
    {
        return view('admin/funnels/builder', [
            'title' => lang('Titles.newFunnel'),
            'funnel'          => null,
            'steps'           => [],
            'trigger_types'   => FunnelModel::triggerTypeLabels(),
            'step_types'      => FunnelStepModel::stepTypeLabels(),
            'input_types'     => FunnelStepModel::inputTypeLabels(),
            'condition_types' => FunnelConditionModel::conditionTypeLabels(),
            'statuses'        => LeadModel::STATUSES,
        ]);
    }

    // ------------------------------------------------------------------
    // STORE
    // ------------------------------------------------------------------

    public function store()
    {
        $funnelData = $this->validateFunnel();
        if (! $funnelData) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $funnelId = $this->funnelModel->insert($funnelData, true);
        $this->saveSteps($funnelId);

        return redirect()->to('/panel/funnels/' . $funnelId . '/edit')
            ->with('success', lang('Messages.funnelCreated'));
    }

    // ------------------------------------------------------------------
    // EDIT form
    // ------------------------------------------------------------------

    public function edit(int $id)
    {
        $funnel = $this->funnelModel->forTenant($this->userId)->find($id);
        if (! $funnel) {
            return redirect()->to('/panel/funnels')->with('error', lang('Messages.funnelNotFound'));
        }

        $funnel = $this->funnelModel->getWithSteps($id);

        return view('admin/funnels/builder', [
            'title' => lang('Titles.editFunnel', [esc($funnel['name'])]),
            'funnel'          => $funnel,
            'steps'           => $funnel['steps'] ?? [],
            'trigger_types'   => FunnelModel::triggerTypeLabels(),
            'step_types'      => FunnelStepModel::stepTypeLabels(),
            'input_types'     => FunnelStepModel::inputTypeLabels(),
            'condition_types' => FunnelConditionModel::conditionTypeLabels(),
            'statuses'        => LeadModel::STATUSES,
        ]);
    }

    // ------------------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------------------

    public function update(int $id)
    {
        $funnel = $this->funnelModel->forTenant($this->userId)->find($id);
        if (! $funnel) {
            return redirect()->to('/panel/funnels')->with('error', lang('Messages.funnelNotFound'));
        }

        $funnelData = $this->validateFunnel();
        if (! $funnelData) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->funnelModel->update($id, $funnelData);

        // Replace all steps (delete + re-insert)
        $this->stepModel->deleteForFunnel($id);
        $this->saveSteps($id);

        return redirect()->to('/panel/funnels/' . $id . '/edit')
            ->with('success', lang('Messages.funnelUpdated'));
    }

    // ------------------------------------------------------------------
    // TOGGLE active
    // ------------------------------------------------------------------

    public function toggle(int $id)
    {
        $funnel = $this->funnelModel->forTenant($this->userId)->find($id);
        if (! $funnel) return redirect()->back()->with('error', lang('Messages.funnelNotFound'));

        $this->funnelModel->update($id, ['is_active' => $funnel['is_active'] ? 0 : 1]);

        return redirect()->back()->with('success', lang('Messages.funnelStatusChanged', [$funnel['is_active'] ? lang('Messages.statusPaused') : lang('Messages.statusActivated')]));
    }

    // ------------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------------

    public function delete(int $id)
    {
        $funnel = $this->funnelModel->forTenant($this->userId)->find($id);
        if (! $funnel) return redirect()->back()->with('error', lang('Messages.funnelNotFound'));

        $this->stepModel->deleteForFunnel($id);
        $this->funnelModel->delete($id);

        return redirect()->to('/panel/funnels')
            ->with('success', lang('Messages.funnelDeleted'));
    }

    // ------------------------------------------------------------------
    // SESSIONS list
    // ------------------------------------------------------------------

    public function sessions()
    {
        $result = $this->sessionModel->getForTenant($this->userId);

        return view('admin/funnels/sessions', [
            'title' => lang('Titles.funnelSessions'),
            'result' => $result,
        ]);
    }

    // ------------------------------------------------------------------
    // MANUAL start: admin starts a funnel for a specific lead
    // POST /admin/funnels/start
    // ------------------------------------------------------------------

    public function start()
    {
        $funnelId = (int) $this->request->getPost('funnel_id');
        $leadId   = (int) $this->request->getPost('lead_id');

        $lead  = (new LeadModel())->forTenant($this->userId)->find($leadId);
        $funnel = $this->funnelModel->forTenant($this->userId)->find($funnelId);

        if (! $lead || ! $funnel) {
            return redirect()->back()->with('error', lang('Messages.leadOrFunnelNotFound'));
        }

        // Get or create an active conversation
        $conv = (new \App\Models\ConversationModel())->getActiveForLead($this->userId, $leadId);
        if (! $conv) {
            return redirect()->back()->with('error', lang('Messages.noActiveConv'));
        }

        $service = new FunnelService();
        $started = $service->startFunnel($this->userId, $leadId, (int) $conv['id'], $funnelId);

        if ($started) {
            return redirect()->to('/panel/conversations/' . $conv['id'])
                ->with('success', lang('Messages.funnelStarted'));
        }

        return redirect()->back()->with('error', lang('Messages.funnelStartFailed'));
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------
    // AI FUNNEL GENERATOR  (knowledge base + optional flowchart image)
    // ------------------------------------------------------------------

    public function importForm()
    {
        return view('admin/funnels/import', ['title' => lang('Titles.generateFunnelAi')]);
    }

    /**
     * One-click: drop a ready-made sample funnel into the builder so the tenant
     * can learn from a working example instead of a blank canvas.
     */
    public function sample()
    {
        $funnel   = \App\Services\FunnelImportService::sample();
        $funnelId = $this->funnelModel->insert([
            'user_id'       => $this->userId,
            'name'          => $funnel['name'],
            'description'   => 'A starter example — edit the steps to fit your business, then set it Active.',
            'trigger_type'  => 'manual',
            'trigger_value' => '',
            'is_active'     => 0,
        ], true);

        $this->saveSteps($funnelId, $funnel['steps']);

        return redirect()->to('/panel/funnels/' . $funnelId . '/edit')
            ->with('success', lang('Messages.sampleFunnelAdded'));
    }

    public function importGenerate()
    {
        $knowledge = trim((string) $this->request->getPost('knowledge'));
        if (mb_strlen($knowledge) < 20) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.aiDescribeFunnel'));
        }

        $svc = new \App\Services\FunnelImportService($this->userId);
        if (! $svc->isReady()) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.addOpenAiKeyFirst'));
        }

        // Optional flowchart image → data URI
        $imageData = null;
        $file = $this->request->getFile('flowchart');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                return redirect()->back()->withInput()->with('error', lang('Messages.flowchartType'));
            }
            if ($file->getSizeByUnit('mb') > 8) {
                return redirect()->back()->withInput()->with('error', lang('Messages.flowchartSize'));
            }
            $imageData = 'data:' . $file->getMimeType() . ';base64,'
                . base64_encode(file_get_contents($file->getTempName()));
        }

        $result = $svc->generate($knowledge, $imageData);
        if (! $result['success']) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.aiFunnelFailed', [$result['error']]));
        }

        $funnel   = $result['funnel'];
        $funnelId = $this->funnelModel->insert([
            'user_id'       => $this->userId,
            'name'          => $funnel['name'],
            'description'   => lang('Messages.aiFunnelDescription'),
            'trigger_type'  => 'manual',
            'trigger_value' => '',
            'is_active'     => 0,
        ], true);

        $this->saveSteps($funnelId, $funnel['steps']);

        return redirect()->to('/panel/funnels/' . $funnelId . '/edit')
            ->with('success', lang('Messages.aiFunnelCreated'));
    }

    // ------------------------------------------------------------------

    private function validateFunnel(): ?array
    {
        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'trigger_type' => 'required|in_list[manual,keyword,new_lead]',
        ];

        if (! $this->validate($rules)) return null;

        return [
            'user_id'       => $this->userId,
            'name'          => trim($this->request->getPost('name')),
            'description'   => trim($this->request->getPost('description') ?? ''),
            'trigger_type'  => $this->request->getPost('trigger_type'),
            'trigger_value' => trim($this->request->getPost('trigger_value') ?? ''),
            'is_active'     => (int) (bool) $this->request->getPost('is_active'),
        ];
    }

    /**
     * Save all steps from POST data.
     *
     * POST format (step index = 0-based):
     *   steps[0][name], steps[0][step_type], steps[0][message],
     *   steps[0][input_type], steps[0][choices], steps[0][save_to],
     *   steps[0][action_type], steps[0][action_config_*],
     *   steps[0][conditions][0][type], steps[0][conditions][0][value], steps[0][conditions][0][next_step]
     *
     * next_step is a 0-based index into the steps array (resolved to real IDs after insert).
     */
    private function saveSteps(int $funnelId, ?array $rawSteps = null): void
    {
        $rawSteps = $rawSteps ?? ($this->request->getPost('steps') ?? []);
        if (empty($rawSteps)) return;

        $insertedIds = [];  // index → real DB id

        // Pass 1: insert all steps (without next_step_id and conditions)
        foreach ($rawSteps as $idx => $raw) {
            $stepType = $raw['step_type'] ?? 'message';

            $choices = null;
            if (in_array($raw['input_type'] ?? '', ['choice', 'yesno'])) {
                $rawChoices = array_filter(array_map('trim', explode("\n", $raw['choices'] ?? '')));
                $choices = ! empty($rawChoices) ? json_encode(array_values($rawChoices)) : null;
            }

            $actionConfig = null;
            if ($stepType === 'action') {
                $actionConfig = json_encode([
                    'status' => $raw['action_status'] ?? '',
                    'note'   => $raw['action_note']   ?? '',
                    'type'   => $raw['action_note_type'] ?? 'general',
                    'tag'    => $raw['action_tag']    ?? '',
                ]);
            }

            $id = $this->stepModel->insert([
                'funnel_id'     => $funnelId,
                'user_id'       => $this->userId,
                'step_order'    => $idx + 1,
                'name'          => trim($raw['name'] ?? ('Step ' . ($idx + 1))),
                'step_type'     => $stepType,
                'message'       => trim($raw['message'] ?? ''),
                'input_type'    => $raw['input_type'] ?? null,
                'choices'       => $choices,
                'save_to'       => trim($raw['save_to'] ?? '') ?: null,
                'action_type'   => $raw['action_type'] ?? null,
                'action_config' => $actionConfig,
                'next_step_id'  => null, // resolved in pass 2
            ], true);

            $insertedIds[$idx] = $id;
        }

        // Pass 2: resolve next_step_id references and insert conditions
        foreach ($rawSteps as $idx => $raw) {
            $stepId = $insertedIds[$idx];

            // Resolve explicit next step index
            $nextIdx = isset($raw['next_step']) && $raw['next_step'] !== ''
                ? (int) $raw['next_step']
                : null;

            if ($nextIdx !== null && isset($insertedIds[$nextIdx])) {
                $this->stepModel->update($stepId, ['next_step_id' => $insertedIds[$nextIdx]]);
            }

            // Insert conditions
            $conditions = $raw['conditions'] ?? [];
            foreach ($conditions as $sort => $cond) {
                $condType  = $cond['type']  ?? 'any';
                $condValue = $cond['value'] ?? '';

                // Resolve next_step index for this condition
                $condNextIdx = isset($cond['next_step']) && $cond['next_step'] !== ''
                    ? (int) $cond['next_step']
                    : null;

                $condNextId = ($condNextIdx !== null && isset($insertedIds[$condNextIdx]))
                    ? $insertedIds[$condNextIdx]
                    : null;

                $this->condModel->insert([
                    'step_id'         => $stepId,
                    'funnel_id'       => $funnelId,
                    'condition_type'  => $condType,
                    'condition_value' => $condValue,
                    'next_step_id'    => $condNextId,
                    'sort_order'      => (int) $sort,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}

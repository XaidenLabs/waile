<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\LeadNoteModel;
use App\Models\LeadStatusLogModel;
use App\Models\FunnelModel;

class LeadsController extends BaseController
{
    protected LeadModel          $leadModel;
    protected LeadNoteModel      $noteModel;
    protected LeadStatusLogModel $logModel;
    protected int                $userId;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
        $this->noteModel = new LeadNoteModel();
        $this->logModel  = new LeadStatusLogModel();
        $this->userId    = (int) session()->get('user_id');
    }

    // ==================================================================
    // INDEX — list with filters
    // ==================================================================

    public function index()
    {
        $filters = [
            'search'    => $this->request->getGet('search'),
            'status'    => $this->request->getGet('status'),
            'source'    => $this->request->getGet('source'),
            'score_min' => $this->request->getGet('score_min'),
            'sort'      => $this->request->getGet('sort') ?? 'created_at',
            'dir'       => $this->request->getGet('dir')  ?? 'desc',
            'page'      => $this->request->getGet('page') ?? 1,
        ];

        $result = $this->leadModel->getFiltered($this->userId, $filters, 20);

        $funnelModel = new FunnelModel();
        $funnels = $funnelModel->where('user_id', $this->userId)
                               ->where('is_active', 1)
                               ->findAll();

        return view('admin/leads/index', [
            'title' => lang('Titles.leads'),
            'leads'   => $result['leads'],
            'pager'   => $result['pager'],
            'total'   => $result['total'],
            'stats'   => $this->leadModel->getStats($this->userId),
            'filters' => $filters,
            'statuses'=> LeadModel::STATUSES,
            'sources' => LeadModel::SOURCES,
            'colors'  => LeadModel::STATUS_COLORS,
            'icons'   => LeadModel::STATUS_ICONS,
            'funnels' => $funnels,
        ]);
    }

    // ==================================================================
    // CSV EXPORT
    // ==================================================================

    public function export()
    {
        if (! plan_feature('csv_export')) {
            return redirect()->to('/panel/plan')
                ->with('error', lang('Messages.csvExportLocked'));
        }

        $filters = [
            'search'    => $this->request->getGet('search'),
            'status'    => $this->request->getGet('status'),
            'source'    => $this->request->getGet('source'),
            'score_min' => $this->request->getGet('score_min'),
            'sort'      => $this->request->getGet('sort') ?? 'created_at',
            'dir'       => $this->request->getGet('dir')  ?? 'desc',
            'page'      => 1,
        ];

        $result = $this->leadModel->getFiltered($this->userId, $filters, 5000);
        $leads  = $result['leads'];

        $filename = 'leads_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Phone', 'Email', 'Status', 'Source', 'Score', 'Tags', 'Created At']);
        foreach ($leads as $lead) {
            $tags = is_string($lead['tags']) ? $lead['tags'] : '';
            fputcsv($out, [
                $lead['id'],
                $lead['name'] ?? '',
                $lead['phone'],
                $lead['email'] ?? '',
                $lead['status'],
                $lead['source'] ?? '',
                $lead['score'],
                $tags,
                $lead['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    // ==================================================================
    // CREATE
    // ==================================================================

    public function create()
    {
        return view('admin/leads/create', [
            'title' => lang('Titles.addLead'),
            'statuses' => LeadModel::STATUSES,
            'sources'  => LeadModel::SOURCES,
        ]);
    }

    public function store()
    {
        $rules = [
            'phone'  => 'required|max_length[30]',
            'name'   => 'permit_empty|max_length[150]',
            'email'  => 'permit_empty|valid_email|max_length[255]',
            'source' => 'permit_empty|in_list[' . implode(',', LeadModel::SOURCES) . ']',
            'status' => 'permit_empty|in_list[' . implode(',', LeadModel::STATUSES) . ']',
            'score'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Prevent duplicate phone per tenant
        if ($this->leadModel->getByPhone($this->userId, $this->request->getPost('phone'))) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.leadPhoneExists'));
        }

        // Plan lead quota
        $plan = plan_service();
        if ($plan && ! $plan->canAddLead()) {
            return redirect()->to('/panel/plan')->with('error',
                "You've reached your plan's lead limit ({$plan->leadLimit()} per cycle). Upgrade to add more leads.");
        }

        $tagsRaw = $this->request->getPost('tags');
        $tags    = $this->parseTags($tagsRaw);

        $leadId = $this->leadModel->insert([
            'user_id' => $this->userId,
            'name'    => trim($this->request->getPost('name') ?? ''),
            'phone'   => trim($this->request->getPost('phone')),
            'email'   => trim($this->request->getPost('email') ?? ''),
            'source'  => $this->request->getPost('source') ?? 'manual',
            'status'  => $this->request->getPost('status') ?? 'new',
            'score'   => (int) ($this->request->getPost('score') ?? 0),
            'tags'    => json_encode($tags),
            'notes'   => trim($this->request->getPost('notes') ?? ''),
        ]);

        if (! $leadId) {
            return redirect()->back()->withInput()
                ->with('error', lang('Messages.leadCreateFailed'));
        }

        // Log initial status
        $this->logModel->log($this->userId, $leadId, null, $this->request->getPost('status') ?? 'new', 'Lead created');

        // Opening note
        $openingNote = trim($this->request->getPost('first_note') ?? '');
        if ($openingNote !== '') {
            $this->noteModel->addNote($this->userId, $leadId, $openingNote);
        }

        return redirect()->to('/panel/leads/' . $leadId)
            ->with('success', lang('Messages.leadCreated'));
    }

    // ==================================================================
    // SHOW
    // ==================================================================

    public function show(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        return view('admin/leads/show', [
            'title' => lang('Titles.lead', [$lead['name'] ?: $lead['phone']]),
            'lead'       => $lead,
            'tags'       => LeadModel::parseTags($lead['tags']),
            'notes'      => $this->noteModel->forLeadWithAuthor($id),
            'status_log' => $this->logModel->forLead($id),
            'statuses'   => LeadModel::STATUSES,
            'colors'     => LeadModel::STATUS_COLORS,
            'icons'      => LeadModel::STATUS_ICONS,
            'note_types' => ['general', 'call', 'email', 'meeting', 'whatsapp'],
        ]);
    }

    // ==================================================================
    // EDIT / UPDATE
    // ==================================================================

    public function edit(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        return view('admin/leads/edit', [
            'title' => lang('Titles.editLead'),
            'lead'     => $lead,
            'tags'     => LeadModel::parseTags($lead['tags']),
            'statuses' => LeadModel::STATUSES,
            'sources'  => LeadModel::SOURCES,
            'colors'   => LeadModel::STATUS_COLORS,
        ]);
    }

    public function update(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        $rules = [
            'phone'  => 'required|max_length[30]',
            'name'   => 'permit_empty|max_length[150]',
            'email'  => 'permit_empty|valid_email|max_length[255]',
            'source' => 'permit_empty|in_list[' . implode(',', LeadModel::SOURCES) . ']',
            'status' => 'permit_empty|in_list[' . implode(',', LeadModel::STATUSES) . ']',
            'score'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $newStatus = $this->request->getPost('status') ?? $lead['status'];
        $oldStatus = $lead['status'];

        $tags = $this->parseTags($this->request->getPost('tags'));

        $this->leadModel->update($id, [
            'name'   => trim($this->request->getPost('name') ?? ''),
            'phone'  => trim($this->request->getPost('phone')),
            'email'  => trim($this->request->getPost('email') ?? ''),
            'source' => $this->request->getPost('source'),
            'status' => $newStatus,
            'score'  => (int) ($this->request->getPost('score') ?? $lead['score']),
            'tags'   => json_encode($tags),
            'notes'  => trim($this->request->getPost('notes') ?? ''),
        ]);

        // Log status change
        if ($oldStatus !== $newStatus) {
            $this->logModel->log($this->userId, $id, $oldStatus, $newStatus, 'Updated via edit form');
        }

        return redirect()->to('/panel/leads/' . $id)
            ->with('success', lang('Messages.leadUpdated'));
    }

    // ==================================================================
    // DELETE (soft)
    // ==================================================================

    public function delete(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        $this->leadModel->delete($id);

        return redirect()->to('/panel/leads')
            ->with('success', lang('Messages.leadDeleted'));
    }

    // ==================================================================
    // ADD NOTE  (POST from lead show page)
    // ==================================================================

    public function addNote(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        $note = trim($this->request->getPost('note') ?? '');
        $type = $this->request->getPost('type') ?? 'general';

        if (empty($note)) {
            return redirect()->back()->with('error', lang('Messages.noteEmpty'));
        }

        $this->noteModel->addNote($this->userId, $id, $note, $type);

        return redirect()->to('/panel/leads/' . $id . '#notes')
            ->with('success', lang('Messages.noteAdded'));
    }

    // ==================================================================
    // QUICK STATUS UPDATE  (POST from lead show page)
    // ==================================================================

    public function updateStatus(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId)->find($id);

        if (! $lead) {
            return redirect()->to('/panel/leads')->with('error', lang('Messages.leadNotFound'));
        }

        $newStatus = $this->request->getPost('status');
        $note      = trim($this->request->getPost('status_note') ?? '');

        if (! in_array($newStatus, LeadModel::STATUSES)) {
            return redirect()->back()->with('error', lang('Messages.invalidStatus'));
        }

        $oldStatus = $this->leadModel->updateStatus($id, $newStatus);
        $this->logModel->log($this->userId, $id, $oldStatus, $newStatus, $note ?: null);

        // Automation: fire status_change rules
        try {
            (new \App\Services\AutomationService())->onStatusChange($this->userId, $id, $oldStatus, $newStatus);
        } catch (\Throwable $e) {
            log_message('error', 'Automation onStatusChange error: ' . $e->getMessage());
        }

        return redirect()->to('/panel/leads/' . $id . '#timeline')
            ->with('success', lang('Messages.statusUpdatedTo', [ucfirst($newStatus)]));
    }

    // ==================================================================
    // Private
    // ==================================================================

    private function parseTags(mixed $raw): array
    {
        if (empty($raw)) return [];

        // Handle comma-separated string from form input
        if (is_string($raw)) {
            // Check if it's JSON first
            $json = json_decode($raw, true);
            if (is_array($json)) return $json;

            // Split comma-separated tags
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        return is_array($raw) ? $raw : [];
    }
}

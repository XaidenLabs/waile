<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\LeadNoteModel;
use App\Models\LeadStatusLogModel;
use App\Services\AutomationService;
use App\Traits\ApiResponseTrait;

/**
 * REST API — Leads
 *
 * GET    /api/v1/leads                  List (filtered, paginated)
 * POST   /api/v1/leads                  Create
 * GET    /api/v1/leads/{id}             Show
 * PUT    /api/v1/leads/{id}             Update
 * DELETE /api/v1/leads/{id}             Soft-delete
 * GET    /api/v1/leads/{id}/notes       List notes
 * POST   /api/v1/leads/{id}/notes       Add note
 * POST   /api/v1/leads/{id}/status      Update status
 */
class LeadsApiController extends BaseController
{
    use ApiResponseTrait;

    protected LeadModel          $leadModel;
    protected LeadNoteModel      $noteModel;
    protected LeadStatusLogModel $logModel;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
        $this->noteModel = new LeadNoteModel();
        $this->logModel  = new LeadStatusLogModel();
    }

    private function userId(): int
    {
        return (int) $this->request->userId;
    }

    // ------------------------------------------------------------------
    // GET /api/v1/leads
    // ------------------------------------------------------------------

    public function index()
    {
        $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
        $page    = max((int) ($this->request->getGet('page') ?? 1), 1);

        $filters = [
            'search'  => $this->request->getGet('search'),
            'status'  => $this->request->getGet('status'),
            'source'  => $this->request->getGet('source'),
            'sort'    => $this->request->getGet('sort') ?? 'created_at',
            'dir'     => $this->request->getGet('dir')  ?? 'desc',
            'page'    => $page,
        ];

        $result = $this->leadModel->getFiltered($this->userId(), $filters, $perPage);

        $leads = array_map(fn($l) => $this->formatLead($l), $result['leads'] ?? []);

        return $this->success(
            $leads,
            'Leads fetched',
            200,
            $this->paginationMeta($result['total'], $perPage, $page)
        );
    }

    // ------------------------------------------------------------------
    // POST /api/v1/leads
    // ------------------------------------------------------------------

    public function create()
    {
        $body  = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'phone'  => 'required|max_length[30]',
            'name'   => 'permit_empty|max_length[150]',
            'email'  => 'permit_empty|valid_email',
            'source' => 'permit_empty|in_list[' . implode(',', LeadModel::SOURCES) . ']',
            'status' => 'permit_empty|in_list[' . implode(',', LeadModel::STATUSES) . ']',
            'score'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        if ($this->leadModel->getByPhone($this->userId(), $body['phone'])) {
            return $this->fail(lang('Messages.leadPhoneExists'), 409);
        }

        // Plan lead quota
        $plan = new \App\Services\PlanService($this->userId());
        if (! $plan->canAddLead()) {
            return $this->fail("Lead limit reached for your plan ({$plan->leadLimit()} per cycle). Upgrade to add more.", 403);
        }

        $tags = is_array($body['tags'] ?? null) ? $body['tags'] : [];

        $leadId = $this->leadModel->insert([
            'user_id' => $this->userId(),
            'name'    => trim($body['name']   ?? ''),
            'phone'   => trim($body['phone']),
            'email'   => trim($body['email']  ?? ''),
            'source'  => $body['source']      ?? 'manual',
            'status'  => $body['status']      ?? 'new',
            'score'   => (int) ($body['score'] ?? 0),
            'tags'    => json_encode($tags),
            'notes'   => trim($body['notes']  ?? ''),
        ]);

        if (! $leadId) {
            return $this->fail('Failed to create lead.', 500);
        }

        $this->logModel->log($this->userId(), $leadId, null, $body['status'] ?? 'new', 'Created via API');

        return $this->success($this->formatLead($this->leadModel->find($leadId)), 'Lead created', 201);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/leads/{id}
    // ------------------------------------------------------------------

    public function show(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        return $this->success($this->formatLead($lead));
    }

    // ------------------------------------------------------------------
    // PUT /api/v1/leads/{id}
    // ------------------------------------------------------------------

    public function update(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        $body  = $this->request->getJSON(true) ?? [];

        $rules = [
            'phone'  => 'permit_empty|max_length[30]',
            'email'  => 'permit_empty|valid_email',
            'source' => 'permit_empty|in_list[' . implode(',', LeadModel::SOURCES) . ']',
            'status' => 'permit_empty|in_list[' . implode(',', LeadModel::STATUSES) . ']',
            'score'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $data = array_filter([
            'name'   => isset($body['name'])   ? trim($body['name'])  : null,
            'phone'  => isset($body['phone'])   ? trim($body['phone']) : null,
            'email'  => isset($body['email'])   ? trim($body['email']) : null,
            'source' => $body['source']  ?? null,
            'status' => $body['status']  ?? null,
            'score'  => isset($body['score']) ? (int) $body['score'] : null,
            'notes'  => isset($body['notes']) ? trim($body['notes'])  : null,
            'tags'   => isset($body['tags'])  ? json_encode((array) $body['tags']) : null,
        ], fn($v) => $v !== null);

        if (! empty($data)) {
            $newStatus = $data['status'] ?? null;
            $oldStatus = $lead['status'];

            $this->leadModel->update($id, $data);

            if ($newStatus && $newStatus !== $oldStatus) {
                $this->logModel->log($this->userId(), $id, $oldStatus, $newStatus, 'Updated via API');
            }
        }

        return $this->success($this->formatLead($this->leadModel->find($id)), 'Lead updated');
    }

    // ------------------------------------------------------------------
    // DELETE /api/v1/leads/{id}
    // ------------------------------------------------------------------

    public function delete(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        $this->leadModel->delete($id);

        return $this->success([], 'Lead deleted');
    }

    // ------------------------------------------------------------------
    // GET /api/v1/leads/{id}/notes
    // ------------------------------------------------------------------

    public function notes(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        return $this->success($this->noteModel->forLeadWithAuthor($id));
    }

    // ------------------------------------------------------------------
    // POST /api/v1/leads/{id}/notes
    // ------------------------------------------------------------------

    public function addNote(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        $body = $this->request->getJSON(true) ?? $this->request->getPost();
        $note = trim($body['note'] ?? '');
        $type = $body['type'] ?? 'general';

        if (empty($note)) {
            return $this->validationError(['note' => 'Note is required.']);
        }

        if (! in_array($type, ['general', 'call', 'email', 'meeting', 'whatsapp'])) {
            $type = 'general';
        }

        $noteId = $this->noteModel->addNote($this->userId(), $id, $note, $type);

        return $this->success(
            $this->noteModel->find($noteId),
            'Note added',
            201
        );
    }

    // ------------------------------------------------------------------
    // POST /api/v1/leads/{id}/status
    // ------------------------------------------------------------------

    public function updateStatus(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        $body      = $this->request->getJSON(true) ?? $this->request->getPost();
        $newStatus = $body['status'] ?? '';
        $note      = trim($body['note'] ?? '');

        if (! in_array($newStatus, LeadModel::STATUSES)) {
            return $this->validationError([
                'status' => 'Invalid status. Allowed: ' . implode(', ', LeadModel::STATUSES),
            ]);
        }

        $oldStatus = $this->leadModel->updateStatus($id, $newStatus);
        $this->logModel->log($this->userId(), $id, $oldStatus, $newStatus, $note ?: 'Updated via API');

        // Automation: fire status_change rules
        try {
            (new \App\Services\AutomationService())->onStatusChange($this->userId(), $id, $oldStatus, $newStatus);
        } catch (\Throwable $e) {
            log_message('error', 'Automation onStatusChange error: ' . $e->getMessage());
        }

        return $this->success(
            ['id' => $id, 'old_status' => $oldStatus, 'new_status' => $newStatus],
            'Status updated'
        );
    }

    // ------------------------------------------------------------------
    // GET /api/v1/leads/{id}/timeline
    // ------------------------------------------------------------------

    public function timeline(int $id)
    {
        $lead = $this->leadModel->forTenant($this->userId())->find($id);

        if (! $lead) return $this->notFound(lang('Messages.leadNotFound'));

        return $this->success($this->logModel->forLead($id));
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function formatLead(array $lead): array
    {
        $lead['tags'] = LeadModel::parseTags($lead['tags']);
        return $lead;
    }
}

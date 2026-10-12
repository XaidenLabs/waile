<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\LeadModel;
use App\Traits\ApiResponseTrait;

/**
 * REST API — Conversations
 *
 * GET  /api/v1/conversations          List tenant conversations (paginated)
 * GET  /api/v1/conversations/{id}     Show conversation + last messages
 * POST /api/v1/conversations/{id}/close  Close conversation
 */
class ConversationsApiController extends BaseController
{
    use ApiResponseTrait;

    protected ConversationModel $convModel;
    protected MessageModel      $messageModel;
    protected LeadModel         $leadModel;

    public function __construct()
    {
        $this->convModel    = new ConversationModel();
        $this->messageModel = new MessageModel();
        $this->leadModel    = new LeadModel();
    }

    private function userId(): int
    {
        return (int) $this->request->userId;
    }

    // ------------------------------------------------------------------
    // GET /api/v1/conversations
    // ------------------------------------------------------------------

    public function index()
    {
        $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
        $page    = max((int) ($this->request->getGet('page')    ?? 1), 1);
        $status  = $this->request->getGet('status'); // active | closed | paused
        $leadId  = $this->request->getGet('lead_id');

        $builder = $this->convModel->forTenant($this->userId());

        if ($status) $builder->where('status', $status);
        if ($leadId) $builder->where('lead_id', (int) $leadId);

        $total = $builder->countAllResults(false);
        $convs = $builder->orderBy('updated_at', 'DESC')
                         ->paginate($perPage, 'default', $page);

        return $this->success(
            $convs,
            'Conversations fetched',
            200,
            $this->paginationMeta($total, $perPage, $page)
        );
    }

    // ------------------------------------------------------------------
    // GET /api/v1/conversations/{id}
    // ------------------------------------------------------------------

    public function show(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId())->find($id);

        if (! $conv) {
            return $this->notFound(lang('Messages.conversationNotFound'));
        }

        $lead     = $this->leadModel->find($conv['lead_id']);
        $messages = $this->messageModel->getHistory($id, 50);

        return $this->success([
            'conversation' => $conv,
            'lead'         => $lead,
            'messages'     => $messages,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/v1/conversations/{id}/close
    // ------------------------------------------------------------------

    public function close(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId())->find($id);

        if (! $conv) {
            return $this->notFound(lang('Messages.conversationNotFound'));
        }

        if ($conv['status'] === 'closed') {
            return $this->fail('Conversation is already closed.', 409);
        }

        $this->convModel->close($id);

        return $this->success(['id' => $id, 'status' => 'closed'], lang('Messages.conversationClosed'));
    }
}

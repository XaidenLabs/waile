<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\LeadModel;
use App\Services\WhatsAppService;
use App\Traits\ApiResponseTrait;

/**
 * REST API — Messages within a conversation
 *
 * GET  /api/v1/conversations/{id}/messages   List messages (paginated)
 * POST /api/v1/conversations/{id}/messages   Send a message as agent
 */
class MessagesApiController extends BaseController
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
    // GET /api/v1/conversations/{id}/messages
    // ------------------------------------------------------------------

    public function index(int $convId)
    {
        $conv = $this->convModel->forTenant($this->userId())->find($convId);

        if (! $conv) {
            return $this->notFound(lang('Messages.conversationNotFound'));
        }

        $perPage = min((int) ($this->request->getGet('per_page') ?? 50), 200);
        $page    = max((int) ($this->request->getGet('page')    ?? 1), 1);

        $builder = $this->messageModel->where('conversation_id', $convId);

        $total    = $builder->countAllResults(false);
        $messages = $builder->orderBy('created_at', 'ASC')
                            ->paginate($perPage, 'default', $page);

        return $this->success(
            $messages,
            'Messages fetched',
            200,
            $this->paginationMeta($total, $perPage, $page)
        );
    }

    // ------------------------------------------------------------------
    // POST /api/v1/conversations/{id}/messages
    // ------------------------------------------------------------------

    public function send(int $convId)
    {
        $conv = $this->convModel->forTenant($this->userId())->find($convId);

        if (! $conv) {
            return $this->notFound(lang('Messages.conversationNotFound'));
        }

        if ($conv['status'] !== 'active') {
            return $this->fail('Cannot send to a ' . $conv['status'] . ' conversation.', 422);
        }

        $body    = $this->request->getJSON(true) ?? $this->request->getPost();
        $content = trim($body['message'] ?? '');

        if (empty($content)) {
            return $this->validationError(['message' => 'Message is required.']);
        }

        $lead   = $this->leadModel->find($conv['lead_id']);
        $wa     = new WhatsAppService($this->userId());
        $result = $wa->sendText($lead['phone'], $content, false, false); // stored manually below

        if (isset($result['error'])) {
            return $this->fail('WhatsApp API error: ' . ($result['error']['message'] ?? 'Send failed.'), 502);
        }

        $msgId = $this->messageModel->insert([
            'user_id'         => $this->userId(),
            'conversation_id' => $convId,
            'lead_id'         => $conv['lead_id'],
            'direction'       => 'outbound',
            'sender'          => 'agent',
            'content'         => $content,
            'wa_message_id'   => $result['messages'][0]['id'] ?? null,
            'message_type'    => 'text',
            'status'          => 'sent',
        ]);

        return $this->success($this->messageModel->find($msgId), 'Message sent', 201);
    }
}

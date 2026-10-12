<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\WhatsAppService;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Traits\ApiResponseTrait;

/**
 * REST API — WhatsApp messaging
 *
 * POST /api/v1/whatsapp/send          Send text to any phone
 * POST /api/v1/whatsapp/send-template Send template message
 * GET  /api/v1/whatsapp/messages      List all messages (paginated)
 * GET  /api/v1/whatsapp/conversations List conversations
 */
class WhatsAppApiController extends BaseController
{
    use ApiResponseTrait;

    protected WhatsAppService  $wa;
    protected LeadModel        $leadModel;
    protected ConversationModel $convModel;
    protected MessageModel     $messageModel;

    public function __construct()
    {
        $this->leadModel    = new LeadModel();
        $this->convModel    = new ConversationModel();
        $this->messageModel = new MessageModel();
    }

    private function userId(): int
    {
        return (int) $this->request->userId;
    }

    private function wa(): WhatsAppService
    {
        if (! isset($this->wa)) {
            $this->wa = new WhatsAppService($this->userId());
        }
        return $this->wa;
    }

    // ------------------------------------------------------------------
    // POST /api/v1/whatsapp/send
    // ------------------------------------------------------------------

    public function send()
    {
        $body = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'to'      => 'required|max_length[30]',
            'message' => 'required|min_length[1]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $to      = trim($body['to']);
        $message = trim($body['message']);

        // Verify tenant has WhatsApp configured
        $lead = $this->leadModel->getByPhone($this->userId(), $to);
        if (! $lead) {
            return $this->fail("No lead found with phone [{$to}]. Create the lead first.", 404);
        }

        $result = $this->wa()->sendText($to, $message);

        if (isset($result['error'])) {
            return $this->fail('WhatsApp API error: ' . ($result['error']['message'] ?? json_encode($result['error'])), 502);
        }

        return $this->success([
            'wa_message_id' => $result['messages'][0]['id'] ?? null,
            'to'            => $to,
            'message'       => $message,
        ], 'Message sent successfully', 201);
    }

    // ------------------------------------------------------------------
    // POST /api/v1/whatsapp/send-template
    // ------------------------------------------------------------------

    public function sendTemplate()
    {
        $body = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'to'       => 'required|max_length[30]',
            'template' => 'required|max_length[100]',
            'lang'     => 'permit_empty|max_length[10]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $result = $this->wa()->sendTemplate(
            $body['to'],
            $body['template'],
            $body['lang']        ?? 'en_US',
            $body['components']  ?? []
        );

        if (isset($result['error'])) {
            return $this->fail('WhatsApp API error: ' . ($result['error']['message'] ?? json_encode($result['error'])), 502);
        }

        return $this->success($result, 'Template sent successfully', 201);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/whatsapp/messages
    // ------------------------------------------------------------------

    public function messages()
    {
        $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
        $page    = max((int) ($this->request->getGet('page')    ?? 1), 1);
        $leadId  = $this->request->getGet('lead_id');
        $dir     = $this->request->getGet('direction'); // inbound | outbound

        $builder = $this->messageModel->where('user_id', $this->userId());

        if ($leadId)  $builder->where('lead_id',  (int) $leadId);
        if ($dir)     $builder->where('direction', $dir);

        $total    = $builder->countAllResults(false);
        $messages = $builder->orderBy('created_at', 'DESC')
                            ->paginate($perPage, 'default', $page);

        return $this->success(
            $messages,
            'Messages fetched',
            200,
            $this->paginationMeta($total, $perPage, $page)
        );
    }

    // ------------------------------------------------------------------
    // GET /api/v1/whatsapp/conversations
    // ------------------------------------------------------------------

    public function conversations()
    {
        $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
        $page    = max((int) ($this->request->getGet('page') ?? 1), 1);

        $total = $this->convModel->forTenant($this->userId())->countAllResults(false);

        $convs = $this->convModel->forTenant($this->userId())
            ->orderBy('updated_at', 'DESC')
            ->paginate($perPage, 'default', $page);

        return $this->success(
            $convs,
            'Conversations fetched',
            200,
            $this->paginationMeta($total, $perPage, $page)
        );
    }
}

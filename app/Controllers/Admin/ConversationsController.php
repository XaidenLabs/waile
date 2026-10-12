<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\LeadModel;
use App\Models\FunnelModel;
use App\Services\WhatsAppService;
use App\Services\FunnelService;

class ConversationsController extends BaseController
{
    protected $db;
    protected ConversationModel $convModel;
    protected MessageModel      $messageModel;
    protected LeadModel         $leadModel;
    protected int               $userId;

    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->convModel    = new ConversationModel();
        $this->messageModel = new MessageModel();
        $this->leadModel    = new LeadModel();
        $this->userId       = (int) session()->get('user_id');
    }

    // ------------------------------------------------------------------
    // INDEX — conversation list
    // ------------------------------------------------------------------

    public function index()
    {
        $status = $this->request->getGet('status') ?? '';

        $builder = $this->db->table('conversations c')
            ->select([
                'c.id', 'c.status', 'c.ai_enabled',
                'c.started_at', 'c.updated_at',
                'l.id as lead_id', 'l.name as lead_name', 'l.phone as lead_phone',
                'l.status as lead_status',
                'm.content as last_message',
                'm.direction as last_direction',
                'm.created_at as last_message_at',
                '(SELECT COUNT(*) FROM messages WHERE conversation_id=c.id AND direction="inbound" AND status!="read") as unread',
            ])
            ->join('leads l', 'l.id = c.lead_id', 'left')
            ->join(
                '(SELECT conversation_id, content, direction, created_at
                  FROM messages
                  WHERE id IN (SELECT MAX(id) FROM messages GROUP BY conversation_id)
                ) m',
                'm.conversation_id = c.id',
                'left'
            )
            ->where('c.user_id', $this->userId);

        if ($status) {
            $builder->where('c.status', $status);
        }

        $conversations = $builder->orderBy('c.updated_at', 'DESC')->get()->getResultArray();

        $stats = $this->convModel->getStats($this->userId);
        $msgStats = $this->messageModel->getStats($this->userId);

        return view('admin/conversations/index', [
            'title' => lang('Titles.conversations'),
            'conversations' => $conversations,
            'stats'         => $stats,
            'msg_stats'     => $msgStats,
            'status_filter' => $status,
        ]);
    }

    // ------------------------------------------------------------------
    // SHOW — chat thread
    // ------------------------------------------------------------------

    public function show(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId)->find($id);

        if (! $conv) {
            return redirect()->to('/panel/conversations')->with('error', lang('Messages.conversationNotFound'));
        }

        $lead     = $this->leadModel->find($conv['lead_id']);
        $messages = $this->messageModel->forConversation($id);

        $funnelModel = new FunnelModel();
        $funnels = $funnelModel->where('user_id', $this->userId)
                               ->where('is_active', 1)
                               ->findAll();

        return view('admin/conversations/show', [
            'title' => lang('Titles.chat', [$lead['name'] ?: $lead['phone']]),
            'conv'      => $conv,
            'lead'      => $lead,
            'messages'  => $messages,
            'funnels'   => $funnels,
        ]);
    }

    // ------------------------------------------------------------------
    // POLL — AJAX: return messages newer than given ID
    // ------------------------------------------------------------------

    public function poll(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId)->find($id);

        if (! $conv) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Not found']);
        }

        $afterId  = (int) ($this->request->getGet('after') ?? 0);
        $db       = \Config\Database::connect();
        $messages = $db->table('messages')
            ->where('conversation_id', $id)
            ->where('id >', $afterId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(['messages' => $messages]);
    }

    // ------------------------------------------------------------------
    // SEND MESSAGE (from admin chat view)
    // ------------------------------------------------------------------

    public function sendMessage(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId)->find($id);

        if (! $conv) {
            return redirect()->back()->with('error', lang('Messages.conversationNotFound'));
        }

        $message = trim($this->request->getPost('message') ?? '');

        if (empty($message)) {
            return redirect()->back()->with('error', lang('Messages.messageEmpty'));
        }

        $lead = $this->leadModel->find($conv['lead_id']);
        $wa   = new WhatsAppService($this->userId);
        $result = $wa->sendText($lead['phone'], $message, false, false); // stored manually below

        if (isset($result['error'])) {
            return redirect()->back()->with('error', lang('Messages.failedToSend', [$result['error']['message'] ?? lang('Messages.apiError')]));
        }

        // Store message manually since agent sent from admin panel
        $this->messageModel->insert([
            'user_id'         => $this->userId,
            'conversation_id' => $id,
            'lead_id'         => $conv['lead_id'],
            'direction'       => 'outbound',
            'sender'          => 'agent',
            'content'         => $message,
            'wa_message_id'   => $result['messages'][0]['id'] ?? null,
            'message_type'    => 'text',
            'status'          => 'sent',
        ]);

        return redirect()->to('/panel/conversations/' . $id)->with('success', lang('Messages.messageSent'));
    }

    // ------------------------------------------------------------------
    // TOGGLE AI
    // ------------------------------------------------------------------

    public function toggleAi(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId)->find($id);

        if (! $conv) {
            return redirect()->back()->with('error', lang('Messages.conversationNotFound'));
        }

        $newState = $this->convModel->toggleAi($id);
        $label    = $newState ? 'enabled' : 'paused';

        return redirect()->back()->with('success', "AI auto-reply {$label}.");
    }

    // ------------------------------------------------------------------
    // CLOSE
    // ------------------------------------------------------------------

    public function close(int $id)
    {
        $conv = $this->convModel->forTenant($this->userId)->find($id);

        if (! $conv) {
            return redirect()->back()->with('error', lang('Messages.conversationNotFound'));
        }

        $this->convModel->close($id);

        return redirect()->to('/panel/conversations')->with('success', lang('Messages.conversationClosed'));
    }
}

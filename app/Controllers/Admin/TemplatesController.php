<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\WhatsAppTemplateModel;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Services\TemplateService;

/**
 * WhatsApp message templates — sync from Meta and use them to open chats with
 * customers outside the 24-hour window (single, from a conversation, or bulk).
 */
class TemplatesController extends BaseController
{
    protected WhatsAppTemplateModel $templateModel;
    protected LeadModel             $leadModel;
    protected int                   $userId;

    public function __construct()
    {
        $this->templateModel = new WhatsAppTemplateModel();
        $this->leadModel     = new LeadModel();
        $this->userId        = (int) session()->get('user_id');
    }

    // ------------------------------------------------------------------
    // LIST + SYNC
    // ------------------------------------------------------------------

    public function index()
    {
        $svc = new TemplateService($this->userId);

        return view('admin/templates/index', [
            'title' => lang('Titles.templates'),
            'templates' => $this->templateModel->where('user_id', $this->userId)->orderBy('name', 'ASC')->findAll(),
            'canSync'   => $svc->canSync(),
        ]);
    }

    public function sync()
    {
        $svc = new TemplateService($this->userId);

        if (! $svc->canSync()) {
            return redirect()->to('panel/templates')
                ->with('error', lang('Messages.addWhatsappCredsFirst'));
        }

        $res = $svc->syncFromMeta();

        if (! $res['success']) {
            return redirect()->to('panel/templates')->with('error', lang('Messages.syncFailed', [$res['error']]));
        }

        return redirect()->to('panel/templates')
            ->with('success', "Synced {$res['count']} approved template(s) from Meta.");
    }

    // ------------------------------------------------------------------
    // COMPOSE + SEND (single lead)
    // ------------------------------------------------------------------

    /**
     * Compose screen. Reached from a lead (?lead_id=) or a conversation (?conversation_id=).
     */
    public function compose()
    {
        $leadId = (int) ($this->request->getGet('lead_id') ?? 0);
        $convId = (int) ($this->request->getGet('conversation_id') ?? 0);

        if ($convId && ! $leadId) {
            $conv = (new ConversationModel())->forTenant($this->userId)->find($convId);
            $leadId = $conv ? (int) $conv['lead_id'] : 0;
        }

        $lead = $leadId ? $this->leadModel->forTenant($this->userId)->find($leadId) : null;
        if (! $lead) {
            return redirect()->to('panel/leads')->with('error', lang('Messages.selectLeadToMessage'));
        }

        $templates = $this->templateModel->forTenant($this->userId);
        if (empty($templates)) {
            return redirect()->to('panel/templates')
                ->with('error', lang('Messages.noApprovedTemplates'));
        }

        return view('admin/templates/send', [
            'title' => lang('Titles.sendTemplate'),
            'lead'       => $lead,
            'templates'  => $templates,
            'backUrl'    => $convId ? site_url('panel/conversations/' . $convId) : site_url('panel/leads/' . $leadId),
        ]);
    }

    public function send()
    {
        $leadId     = (int) $this->request->getPost('lead_id');
        $templateId = (int) $this->request->getPost('template_id');

        $lead     = $this->leadModel->forTenant($this->userId)->find($leadId);
        $template = $this->templateModel->findForTenant($this->userId, $templateId);

        if (! $lead || ! $template) {
            return redirect()->to('panel/leads')->with('error', lang('Messages.leadOrTemplateNotFound'));
        }

        $vars = $this->collectVars();

        $res = (new TemplateService($this->userId))->sendToLead($lead, $template, $vars);

        if (! $res['success']) {
            return redirect()->back()->withInput()->with('error', lang('Messages.couldNotSend', [$res['error']]));
        }

        // Land in the conversation so the user sees the sent template.
        $conv = (new ConversationModel())->getActiveForLead($this->userId, $leadId);
        $to   = $conv ? ('panel/conversations/' . $conv['id']) : ('panel/leads/' . $leadId);

        return redirect()->to($to)->with('success', lang('Messages.templateSent'));
    }

    // ------------------------------------------------------------------
    // BULK / BROADCAST
    // ------------------------------------------------------------------

    public function bulk()
    {
        $templates = $this->templateModel->forTenant($this->userId);
        if (empty($templates)) {
            return redirect()->to('panel/templates')
                ->with('error', lang('Messages.noApprovedTemplates'));
        }

        $leads = $this->leadModel->forTenant($this->userId)
            ->where('phone IS NOT NULL')->where('phone !=', '')
            ->orderBy('created_at', 'DESC')
            ->findAll(500);

        return view('admin/templates/bulk', [
            'title' => lang('Titles.broadcastTemplate'),
            'templates'      => $templates,
            'leads'          => $leads,
            'maxSend'        => self::BULK_LIMIT,
            'mappableFields' => TemplateService::MAPPABLE_FIELDS,
        ]);
    }

    private const BULK_LIMIT = 200;

    public function bulkSend()
    {
        $templateId = (int) $this->request->getPost('template_id');
        $leadIds    = $this->request->getPost('lead_ids') ?? [];

        $template = $this->templateModel->findForTenant($this->userId, $templateId);
        if (! $template) {
            return redirect()->back()->with('error', lang('Messages.templateNotFound'));
        }
        if (empty($leadIds) || ! is_array($leadIds)) {
            return redirect()->back()->with('error', lang('Messages.selectRecipient'));
        }

        // Each template variable is mapped to a lead field (or a fixed value),
        // and resolved per-recipient so every message is personalised.
        $map     = $this->collectMapping();
        $leadIds = array_slice(array_map('intval', $leadIds), 0, self::BULK_LIMIT);
        $svc     = new TemplateService($this->userId);
        $sent    = 0;
        $failed  = 0;

        foreach ($leadIds as $leadId) {
            $lead = $this->leadModel->forTenant($this->userId)->find($leadId);
            if (! $lead) { continue; }

            $vars = $svc->resolveVarsForLead($template, $map, $lead);
            $res  = $svc->sendToLead($lead, $template, $vars);
            $res['success'] ? $sent++ : $failed++;
        }

        $msg = "Broadcast complete: {$sent} sent" . ($failed ? ", {$failed} failed (see Notifications)." : '.');

        return redirect()->to('panel/templates')->with($failed && ! $sent ? 'error' : 'success', $msg);
    }

    // ------------------------------------------------------------------

    /**
     * Build a broadcast variable→source map from the posted form.
     * Form fields: field[body][1]=name|phone|…|custom, custom[body][1]=text.
     */
    private function collectMapping(): array
    {
        $fields = (array) ($this->request->getPost('field')  ?? []);
        $custom = (array) ($this->request->getPost('custom') ?? []);
        $map    = ['header_media' => trim((string) $this->request->getPost('header_media'))];

        foreach (['header', 'body'] as $scope) {
            $map[$scope] = [];
            foreach ((array) ($fields[$scope] ?? []) as $i => $source) {
                $i = (int) $i;
                if ($source === 'custom') {
                    $map[$scope][$i] = ['type' => 'text', 'value' => (string) ($custom[$scope][$i] ?? '')];
                } else {
                    $map[$scope][$i] = ['type' => 'field', 'value' => (string) $source];
                }
            }
        }

        return $map;
    }

    /**
     * Flatten posted variable inputs into the shape TemplateService expects.
     */
    private function collectVars(): array
    {
        return [
            'body'         => array_map('strval', (array) $this->request->getPost('body') ?? []),
            'header'       => array_map('strval', (array) $this->request->getPost('header') ?? []),
            'header_media' => trim((string) $this->request->getPost('header_media')),
        ];
    }
}

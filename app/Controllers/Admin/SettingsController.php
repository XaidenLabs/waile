<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\WhatsAppConfigModel;
use App\Models\AiConfigModel;
use App\Services\EncryptionService;
use App\Services\AIService;
use App\Services\PromptService;

class SettingsController extends BaseController
{
    protected UserModel           $userModel;
    protected WhatsAppConfigModel $waModel;
    protected int                 $userId;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->waModel   = new WhatsAppConfigModel();
        $this->userId    = (int) session()->get('user_id');
    }

    // ==================================================================
    // PROFILE
    // ==================================================================

    public function profile()
    {
        $user = $this->userModel->find($this->userId);

        return view('admin/settings/profile', [
            'title' => lang('Titles.profile'),
            'user'  => $user,
        ]);
    }

    public function saveProfile()
    {
        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'company_name' => 'permit_empty|max_length[255]',
            'phone'        => 'permit_empty|max_length[20]',
            'timezone'     => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'         => trim($this->request->getPost('name')),
            'company_name' => trim($this->request->getPost('company_name') ?? ''),
            'phone'        => trim($this->request->getPost('phone') ?? ''),
            'timezone'     => $this->request->getPost('timezone'),
        ];

        // Password change is optional
        $newPassword = trim($this->request->getPost('new_password') ?? '');
        if ($newPassword !== '') {
            $confirmPassword = trim($this->request->getPost('confirm_password') ?? '');

            if (strlen($newPassword) < 8) {
                return redirect()->back()->withInput()
                    ->with('error', lang('Messages.newPasswordMin8'));
            }
            if ($newPassword !== $confirmPassword) {
                return redirect()->back()->withInput()
                    ->with('error', lang('Messages.passwordsNoMatch'));
            }

            $currentPassword = trim($this->request->getPost('current_password') ?? '');
            $user = $this->userModel->find($this->userId);

            if (! $this->userModel->verifyPassword($currentPassword, $user['password'])) {
                return redirect()->back()->withInput()
                    ->with('error', lang('Messages.currentPasswordIncorrect'));
            }

            $data['password'] = $newPassword; // bcrypt applied by model hook
        }

        $this->userModel->update($this->userId, $data);

        // Refresh session name & company
        session()->set('user_name', $data['name']);
        session()->set('company_name', $data['company_name']);

        return redirect()->to('/panel/settings/profile')
            ->with('success', lang('Messages.profileUpdated'));
    }

    // ==================================================================
    // WHATSAPP CONFIG
    // ==================================================================

    public function whatsapp()
    {
        $config = $this->waModel->getForTenant($this->userId);
        $enc    = new EncryptionService();

        // Mask the token for display
        if ($config && ! empty($config['access_token'])) {
            $config['access_token_masked'] = $enc->mask($config['access_token']);
        }

        return view('admin/settings/whatsapp', [
            'title' => lang('Titles.whatsappConfig'),
            'config' => $config,
        ]);
    }

    public function saveWhatsapp()
    {
        $rules = [
            'phone_number_id' => 'required|max_length[100]',
            'waba_id'         => 'permit_empty|max_length[100]',
            'verify_token'    => 'permit_empty|max_length[100]',
        ];

        // access_token required only when no config exists yet
        $existing = $this->waModel->getForTenant($this->userId);
        if (! $existing) {
            $rules['access_token'] = 'required';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'phone_number_id' => trim($this->request->getPost('phone_number_id')),
            'waba_id'         => trim($this->request->getPost('waba_id') ?? ''),
            'verify_token'    => trim($this->request->getPost('verify_token') ?? ''),
            'is_active'       => 1,
        ];

        // Only update access_token if a new one was submitted
        $newToken = trim($this->request->getPost('access_token') ?? '');
        if ($newToken !== '') {
            $data['access_token'] = $newToken; // encrypted by model hook
        }

        $this->waModel->saveForTenant($this->userId, $data);

        return redirect()->to('/panel/settings/whatsapp')
            ->with('success', lang('Messages.whatsappSaved'));
    }

    // ==================================================================
    // AI CONFIG
    // ==================================================================

    public function ai()
    {
        $aiModel = new AiConfigModel();
        $config  = $aiModel->getForTenant($this->userId);
        $enc     = new EncryptionService();

        if (! empty($config['openai_api_key'])) {
            $config['openai_api_key_masked'] = $enc->mask($config['openai_api_key']);
        }
        if (! empty($config['deepseek_api_key'])) {
            $config['deepseek_api_key_masked'] = $enc->mask($config['deepseek_api_key']);
        }

        return view('admin/settings/ai', [
            'title' => lang('Titles.aiConfig'),
            'config'     => $config,
            'variables'  => PromptService::availableVariables(),
        ]);
    }

    public function saveAi()
    {
        $rules = [
            'ai_provider'      => 'required|in_list[openai,deepseek]',
            'model'            => 'required|in_list[gpt-4o,gpt-4o-mini,gpt-4-turbo,gpt-3.5-turbo,deepseek-chat,deepseek-reasoner]',
            'temperature'      => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[2]',
            'max_tokens'       => 'required|integer|greater_than_equal_to[100]|less_than_equal_to[4000]',
            'language'         => 'required|in_list[en,hi,ar,fr,es,de,pt]',
            'system_prompt'    => 'permit_empty|max_length[4000]',
            'fallback_message' => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $qualRulesInput = trim($this->request->getPost('qualification_rules') ?? '');
        if ($qualRulesInput !== '') {
            $lines = array_filter(array_map('trim', explode("\n", $qualRulesInput)));
            $qualRules = ! empty($lines) ? json_encode(array_values($lines)) : null;
        } else {
            $qualRules = null;
        }

        $data = [
            'ai_provider'         => $this->request->getPost('ai_provider'),
            'model'               => $this->request->getPost('model'),
            'temperature'         => (float) $this->request->getPost('temperature'),
            'max_tokens'          => (int)   $this->request->getPost('max_tokens'),
            'language'            => $this->request->getPost('language'),
            'system_prompt'       => trim($this->request->getPost('system_prompt') ?? ''),
            'fallback_message'    => trim($this->request->getPost('fallback_message') ?? ''),
            'qualification_rules' => $qualRules,
        ];

        // Keep the model coherent with the chosen provider (guards a stale POST).
        $isDeepSeekModel = str_starts_with($data['model'], 'deepseek');
        if ($data['ai_provider'] === 'deepseek' && ! $isDeepSeekModel) {
            $data['model'] = 'deepseek-chat';
        } elseif ($data['ai_provider'] === 'openai' && $isDeepSeekModel) {
            $data['model'] = 'gpt-4o-mini';
        }

        // Custom system prompt is a paid feature — keep the default otherwise.
        if (! plan_feature('custom_prompt')) {
            $data['system_prompt'] = '';
        }

        // Only update each provider's API key if a new one was submitted
        $newOpenAiKey = trim($this->request->getPost('openai_api_key') ?? '');
        if ($newOpenAiKey !== '') {
            $data['openai_api_key'] = $newOpenAiKey; // encrypted by model hook
        }
        $newDeepSeekKey = trim($this->request->getPost('deepseek_api_key') ?? '');
        if ($newDeepSeekKey !== '') {
            $data['deepseek_api_key'] = $newDeepSeekKey; // encrypted by model hook
        }

        $aiModel = new AiConfigModel();
        $aiModel->saveForTenant($this->userId, $data);

        return redirect()->to('/panel/settings/ai')
            ->with('success', lang('Messages.aiConfigSaved'));
    }

    /**
     * AJAX: test AI prompt and return a dry-run reply.
     * POST /admin/settings/ai/test
     */
    public function testAi()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        $message        = trim($this->request->getPost('message') ?? '');
        $promptOverride = trim($this->request->getPost('system_prompt') ?? '') ?: null;

        if (empty($message)) {
            return $this->response->setJSON(['success' => false, 'error' => lang('Messages.messageRequired')]);
        }

        try {
            $ai     = new AIService($this->userId);
            $result = $ai->testReply($message, $promptOverride);
            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', 'AI test error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

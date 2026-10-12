<?php

namespace App\Services;

/**
 * Compiles prompt templates by injecting business / lead context variables.
 *
 * Supported variables (wrap in {curly_braces}):
 *   {business_name}  — tenant's company name
 *   {agent_name}     — tenant's full name
 *   {lead_name}      — lead's name (or "there" if unknown)
 *   {lead_phone}     — lead's phone number
 *   {lead_email}     — lead's email
 *   {lead_status}    — lead's current status
 *   {lead_source}    — how the lead was acquired
 *   {date}           — today's date (d M Y)
 *   {time}           — current time (H:i)
 *   {language}       — configured response language
 *   {custom_*}       — any key from the $extra array
 */
class PromptService
{
    // ------------------------------------------------------------------
    // Default system prompt template
    // ------------------------------------------------------------------

    public static function defaultTemplate(): string
    {
        return <<<PROMPT
You are a professional sales assistant for {business_name}.
Your name is Alexa.
Your goal is to engage leads in a natural, friendly conversation and help them understand our offerings.

Guidelines:
- Greet warmly and introduce yourself as an assistant for {business_name}.
- Ask about the lead's needs, budget, and timeline to qualify them.
- Provide helpful, concise answers (2–3 sentences max).
- If asked something you don't know, say "Let me connect you with our team."
- Never reveal that you are an AI unless directly asked.
- Always respond in {language}.
- Today is {date}. Current time: {time}.

Lead context:
- Name: {lead_name}
- Phone: {lead_phone}
- Source: {lead_source}
- Current status: {lead_status}
PROMPT;
    }

    // ------------------------------------------------------------------
    // Compile a template with context variables
    // ------------------------------------------------------------------

    /**
     * @param  string $template  Raw prompt template with {variable} placeholders
     * @param  array  $vars      Key-value pairs to inject
     * @return string            Compiled prompt
     */
    public function compile(string $template, array $vars): string
    {
        $search  = [];
        $replace = [];

        foreach ($vars as $key => $value) {
            $search[]  = '{' . $key . '}';
            $replace[] = (string) ($value ?? '');
        }

        return str_replace($search, $replace, $template);
    }

    // ------------------------------------------------------------------
    // Build variable map from tenant user + lead records
    // ------------------------------------------------------------------

    /**
     * @param  array      $user   Row from users table
     * @param  array|null $lead   Row from leads table (optional)
     * @param  string     $lang   Language code from ai_configs
     * @param  array      $extra  Any additional custom variables
     * @return array
     */
    public function buildVars(array $user, ?array $lead = null, string $lang = 'en', array $extra = []): array
    {
        $vars = [
            'business_name' => $user['company_name'] ?: $user['name'] ?: 'Our Business',
            'agent_name'    => $user['name']  ?? 'Assistant',
            'lead_name'     => ($lead && ! empty($lead['name'])) ? $lead['name'] : 'there',
            'lead_phone'    => $lead['phone']  ?? '',
            'lead_email'    => $lead['email']  ?? '',
            'lead_status'   => $lead['status'] ?? 'new',
            'lead_source'   => $lead['source'] ?? 'unknown',
            'date'          => date('d M Y'),
            'time'          => date('H:i'),
            'language'      => $this->languageLabel($lang),
        ];

        // Merge custom variables (prefixed or not)
        foreach ($extra as $k => $v) {
            $vars['custom_' . $k] = $v;
            $vars[$k]             = $v; // allow both {key} and {custom_key}
        }

        return $vars;
    }

    // ------------------------------------------------------------------
    // Describe all available variables for the UI
    // ------------------------------------------------------------------

    public static function availableVariables(): array
    {
        return [
            '{business_name}' => 'Your company / business name',
            '{agent_name}'    => 'Your full name (account holder)',
            '{lead_name}'     => 'Lead\'s name (or "there" if unknown)',
            '{lead_phone}'    => 'Lead\'s phone number',
            '{lead_email}'    => 'Lead\'s email address',
            '{lead_status}'   => 'Current lead status (new/hot/warm/cold…)',
            '{lead_source}'   => 'Where the lead came from',
            '{date}'          => 'Today\'s date',
            '{time}'          => 'Current time',
            '{language}'      => 'Configured response language',
        ];
    }

    // ------------------------------------------------------------------

    private function languageLabel(string $code): string
    {
        return match(strtolower($code)) {
            'hi'    => 'Hindi',
            'ar'    => 'Arabic',
            'fr'    => 'French',
            'es'    => 'Spanish',
            'de'    => 'German',
            'pt'    => 'Portuguese',
            default => 'English',
        };
    }
}

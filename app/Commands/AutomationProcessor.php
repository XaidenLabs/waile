<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\AutomationService;

/**
 * Automation cron processor
 *
 * Run every minute via system cron:
 *   * * * * * php /path/to/spark automation:process >> /dev/null 2>&1
 *
 * Or on Windows Task Scheduler:
 *   php C:\xampp\htdocs\wale\spark automation:process
 *
 * What it does:
 *   - Fires "no_reply" rules for stale conversations
 *   - Processes delayed "new_lead" rules (those with delay_minutes > 0)
 */
class AutomationProcessor extends BaseCommand
{
    protected $group       = 'WALE';
    protected $name        = 'automation:process';
    protected $description = 'Process pending automation rules (no_reply, delayed new_lead).';
    protected $usage       = 'automation:process';

    public function run(array $params): void
    {
        $start = microtime(true);
        CLI::write('[' . date('Y-m-d H:i:s') . '] Running automation processor…', 'yellow');

        try {
            $service = new AutomationService();
            $fired   = $service->onNoReplyCheck();

            $elapsed = round(microtime(true) - $start, 3);
            CLI::write("Done — {$fired} rule(s) fired in {$elapsed}s", 'green');

        } catch (\Throwable $e) {
            CLI::error('Automation processor error: ' . $e->getMessage());
            log_message('error', 'AutomationProcessor: ' . $e->getMessage());
        }
    }
}

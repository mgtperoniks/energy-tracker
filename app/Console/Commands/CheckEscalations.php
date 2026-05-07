<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EscalationService;

class CheckEscalations extends Command
{
    protected $signature = 'audit:check-escalations';
    protected $description = 'Scan open incidents and trigger escalations based on rules';

    public function handle(EscalationService $service)
    {
        $this->info("Scanning pending escalations...");
        $service->checkPendingEscalations();
        $this->info("Scan complete.");
    }
}

<?php
// app/Console/Commands/DailyDigestCommand.php

namespace App\Console\Commands;

use App\Actions\SendDailyDigest;
use Illuminate\Console\Command;

class DailyDigestCommand extends Command
{
    protected $signature = 'report:daily';

    protected $description = 'Email a daily heartbeat: live health + 24h of auto-fix activity';

    public function handle(SendDailyDigest $action): int
    {
        $action->handle();
        $this->info('Daily digest sent.');

        return self::SUCCESS;
    }
}

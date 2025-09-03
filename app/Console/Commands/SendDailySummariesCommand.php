<?php

namespace App\Console\Commands;

use App\Jobs\SendDailySummariesJob;
use Illuminate\Console\Command;

class SendDailySummariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-summary:send {--sync : Run synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily summaries to all users with the feature enabled';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🌅 Starting daily summary processing...');
        
        if ($this->option('sync')) {
            $this->info('⚡ Running synchronously...');
            SendDailySummariesJob::dispatchSync();
        } else {
            $this->info('📤 Queueing daily summary job...');
            SendDailySummariesJob::dispatch();
        }

        $this->info('✅ Daily summary job has been ' . ($this->option('sync') ? 'executed' : 'queued') . ' successfully!');
        
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateExpiredDemoAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expired demo accounts status to inactive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired demo accounts...');

        $expiredCount = User::where('is_demo', true)
            ->where('demo_expires_at', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        if ($expiredCount > 0) {
            $this->info("Updated {$expiredCount} expired demo accounts to inactive status.");
            Log::info("Updated {$expiredCount} expired demo accounts to inactive status.");
        } else {
            $this->info('No expired demo accounts found.');
        }

        return 0;
    }
}

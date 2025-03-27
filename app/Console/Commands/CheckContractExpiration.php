<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Traits\GeneralTrait;
use Illuminate\Console\Command;

class CheckContractExpiration extends Command
{
    use GeneralTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired contracts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Log::info('check contract expiration working');
        // Find contracts that are not already canceled and have passed their end date
        $expiredContracts = Quote::where('contract_end_date', '<', now())
            ->where('is_contracted', 1)
            ->whereNull('contract_cancelled_at')
            ->get();

        $updatedCount = 0;
        $failedCount = 0;
    
        foreach ($expiredContracts as $contract) {
            //calculate calculations on contract cancel
            $result = $this->calculateCancelContractCalculations($contract->id, 'expired');
            if ($result) {
                $updatedCount++;
            } else {
                $failedCount++;
            }
        }
    
        $this->info("Checked contract expirations. {$updatedCount} contracts marked as expired. {$failedCount} contracts failed to cancel.");
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\ServiceInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly invoices to clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $endOfMonth = Carbon::now()->endOfMonth();
        $user_ids = Job::where('is_completed', 0)->whereDate('job_date', now())->pluck('user_id') ->unique(); 
        $unpaidInvoices= ServiceInvoice::where('status', 'unpaid')->where('issued_date', '<=', $endOfMonth)->orderBy('issued_date', 'asc')->whereIn('user_id',$user_ids)->get();

        foreach($unpaidInvoices as $invoice){
            \Log::info($invoice->id);
        }
    }
}

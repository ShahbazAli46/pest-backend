<?php

namespace App\Console\Commands;

use App\Models\ServiceInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-monthly';

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
        $unpaidInvoices= ServiceInvoice::where('status', 'unpaid')->where('issued_date', '<=', $endOfMonth)->orderBy('issued_date', 'asc')->get();

        foreach ($unpaidInvoices as $invoice) {
            Mail::to($invoice->user->email)->send(new \App\Mail\InvoiceMail($invoice));
        }
    }
}

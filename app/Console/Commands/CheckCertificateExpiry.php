<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrainingRecord;

class CheckCertificateExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-certificate-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiringSoonThreshold = now()->addDays(30); // Certificates expiring in the next 30 days
        $expiredThreshold = now(); // Certificates that have already expired

        // Update 'expiring_soon' status
        \App\Models\TrainingRecord::where('expiry_date', '<=', $expiringSoonThreshold)
            ->where('expiry_date', '>', $expiredThreshold)
            ->update(['status' => 'expiring_soon']);

        // Update 'expired' status
        \App\Models\TrainingRecord::where('expiry_date', '<=', $expiredThreshold)
            ->update(['status' => 'expired']);

        $this->info('Certificate expiry statuses updated successfully.');
    }
}

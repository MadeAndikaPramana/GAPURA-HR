
<?php
// Buat file migration baru dengan:
// php artisan make:migration fix_training_records_compliance_status

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update compliance_status untuk semua training records yang sudah ada
        DB::transaction(function () {
            $trainingRecords = DB::table('training_records')->get();

            foreach ($trainingRecords as $record) {
                $complianceStatus = 'compliant';

                if ($record->expiry_date) {
                    $expiryDate = Carbon::parse($record->expiry_date);
                    $now = Carbon::now();

                    if ($expiryDate->isPast()) {
                        $complianceStatus = 'expired';
                    } elseif ($expiryDate->diffInDays($now) <= 30) {
                        $complianceStatus = 'expiring_soon';
                    }
                } else {
                    $complianceStatus = 'not_required';
                }

                DB::table('training_records')
                    ->where('id', $record->id)
                    ->update([
                        'compliance_status' => $complianceStatus,
                        'status' => 'completed', // Set semua jadi completed karena sudah ada certificate
                        'completion_date' => $record->completion_date ?: $record->issue_date,
                        'training_date' => $record->training_date ?: $record->issue_date,
                        'updated_at' => now()
                    ]);
            }
        });

        echo "Updated compliance status for " . DB::table('training_records')->count() . " training records.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback jika diperlukan
        DB::table('training_records')->update([
            'compliance_status' => 'compliant',
            'status' => 'registered'
        ]);
    }
};

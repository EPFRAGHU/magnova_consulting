<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('wages:apply-bonus-setting');
            Log::info(trim(Artisan::output()));
        } catch (Throwable $e) {
            Log::error('Applying the unit bonus setting to saved months failed: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Recalculation is not reversible; re-save wages to refresh them.
    }
};

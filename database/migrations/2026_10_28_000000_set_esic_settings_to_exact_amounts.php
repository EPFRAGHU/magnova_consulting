<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Amounts are shown and saved exactly, to the paisa. ESIC settings that were saved with a whole-rupee rounding
     * now use the exact mode. Wages already saved keep the rounding recorded in their own ESIC snapshot.
     */
    public function up(): void
    {
        DB::table('esic_settings')->where('rounding', '!=', 'exact')->update(['rounding' => 'exact']);
    }

    public function down(): void
    {
        // The earlier rounding choice of each row is not kept, so there is nothing to restore.
    }
};

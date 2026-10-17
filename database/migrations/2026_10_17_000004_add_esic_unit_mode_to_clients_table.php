<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Per-unit ESIC arrangement: 'statutory' follows the establishment's ESIC rules,
            // 'ceiling' gives ESIC on the ceiling even above it, 'cutoff' gives no ESIC above the ceiling.
            // The last two are business arrangements with the client, not the ESI Act rule.
            $table->string('esic_unit_mode', 20)->default('statutory')->after('gst_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('esic_unit_mode');
        });
    }
};

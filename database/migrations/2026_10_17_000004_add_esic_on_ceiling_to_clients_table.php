<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Unit-specific, non-statutory arrangement: ESIC on the wage ceiling even when wages are above it.
            $table->boolean('esic_on_ceiling')->default(false)->after('gst_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('esic_on_ceiling');
        });
    }
};

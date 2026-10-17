<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wages', function (Blueprint $table) {
            // The ESIC rules and decision used when this wage was saved (null for wages saved before this column existed).
            $table->json('esic_snapshot')->nullable()->after('esic_employee');
        });
    }

    public function down(): void
    {
        Schema::table('wages', function (Blueprint $table) {
            $table->dropColumn('esic_snapshot');
        });
    }
};

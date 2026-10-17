<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Existing employees stay ESIC-applicable, as before this change.
            $table->boolean('esic_applicable')->default(true)->after('esic_no');
            // Optional: the date from which the employee has been covered, when it is not visible from saved wages.
            $table->date('esic_covered_from')->nullable()->after('esic_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['esic_applicable', 'esic_covered_from']);
        });
    }
};

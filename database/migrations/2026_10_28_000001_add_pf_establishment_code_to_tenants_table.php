<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The establishment's PF code (e.g. ORBBS2012397000); a payslip prints it followed by the employee's 7-digit member ID. */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('pf_establishment_code', 30)->nullable()->after('pan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('pf_establishment_code');
        });
    }
};

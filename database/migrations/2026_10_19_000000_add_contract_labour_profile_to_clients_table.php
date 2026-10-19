<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // 'standard' is how every unit works today. 'contract_labour' is a client served under a principal
            // employer, with its own attendance, wage sheet and invoice layout.
            $table->string('payroll_profile', 30)->default('standard')->after('esic_unit_mode');
            $table->string('principal_employer_name')->nullable()->after('payroll_profile');
            $table->text('principal_employer_address')->nullable()->after('principal_employer_name');
            $table->string('principal_employer_gstin', 20)->nullable()->after('principal_employer_address');
            $table->date('contract_start')->nullable()->after('principal_employer_gstin');
            $table->date('contract_end')->nullable()->after('contract_start');
            // Contractor address as it must print on this client's registers (can differ from the office address).
            $table->text('register_contractor_address')->nullable()->after('contract_end');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_profile', 'principal_employer_name', 'principal_employer_address', 'principal_employer_gstin',
                'contract_start', 'contract_end', 'register_contractor_address',
            ]);
        });
    }
};

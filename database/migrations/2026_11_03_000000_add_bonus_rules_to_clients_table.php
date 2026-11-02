<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-client bonus rule. Every default reproduces what the single bonus tick did before (8.33% of the monthly wage,
     * nothing above Rs 21,000, shown on the invoice, ESIC as per the ESIC settings), so existing clients are unchanged.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('bonus_type', 12)->default('percent')->after('bonus_enabled');
            $table->decimal('bonus_value', 10, 2)->nullable()->after('bonus_type');
            $table->boolean('bonus_prorate')->default(false)->after('bonus_value');
            $table->string('bonus_limit_mode', 20)->default('up_to')->after('bonus_prorate');
            $table->decimal('bonus_ceiling', 10, 2)->nullable()->after('bonus_limit_mode');
            $table->decimal('bonus_value_above', 10, 2)->nullable()->after('bonus_ceiling');
            $table->boolean('bonus_billed')->default(true)->after('bonus_value_above');
            $table->boolean('bonus_in_esic')->nullable()->after('bonus_billed');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['bonus_type', 'bonus_value', 'bonus_prorate', 'bonus_limit_mode', 'bonus_ceiling', 'bonus_value_above', 'bonus_billed', 'bonus_in_esic']);
        });
    }
};

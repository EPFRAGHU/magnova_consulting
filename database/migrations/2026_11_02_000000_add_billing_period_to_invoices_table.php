<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A billing period the user typed in; blank means the whole calendar month of the invoice. */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('billing_period_start')->nullable()->after('year');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['billing_period_start', 'billing_period_end']);
        });
    }
};

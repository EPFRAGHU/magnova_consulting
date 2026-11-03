<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additive and optional: blank days keep the whole calendar month on invoices.
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedTinyInteger('billing_from_day')->nullable();
            $table->string('billing_from_month', 10)->default('same');
            $table->unsignedTinyInteger('billing_to_day')->nullable();
            $table->string('billing_to_month', 10)->default('same');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['billing_from_day', 'billing_from_month', 'billing_to_day', 'billing_to_month']);
        });
    }
};

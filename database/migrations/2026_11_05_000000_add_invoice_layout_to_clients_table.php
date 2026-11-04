<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additive: every existing client keeps the standard tax invoice.
        Schema::table('clients', function (Blueprint $table) {
            $table->string('invoice_layout', 20)->default('standard');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('invoice_layout');
        });
    }
};

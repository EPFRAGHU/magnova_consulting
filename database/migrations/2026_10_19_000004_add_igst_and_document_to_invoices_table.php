<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // IGST for an invoice to a buyer in another state (regular invoices only ever have CGST + SGST).
            $table->decimal('igst', 12, 2)->default(0)->after('sgst');
            // The full printed content of a contract-labour invoice as it was issued, so its PDF never changes
            // when the wage sheet is edited later. Null for regular invoices.
            $table->json('document')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['igst', 'document']);
        });
    }
};

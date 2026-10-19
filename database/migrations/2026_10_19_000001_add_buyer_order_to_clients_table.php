<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // The principal employer's order (PO / work order) number and date, printed as "Buyer's Order No." on the invoice.
            $table->string('buyer_order_no')->nullable()->after('register_contractor_address');
            $table->date('buyer_order_date')->nullable()->after('buyer_order_no');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['buyer_order_no', 'buyer_order_date']);
        });
    }
};

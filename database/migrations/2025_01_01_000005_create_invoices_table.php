<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no')->unique();
            $table->string('ref_no')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('place_of_supply')->nullable();
            $table->boolean('reverse_charge')->default(false);
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('rate_per_day', 10, 2);
            $table->integer('no_of_persons');
            $table->decimal('no_of_duties', 8, 2);
            $table->decimal('base_amount', 12, 2);
            $table->decimal('epf_employer', 12, 2);
            $table->decimal('epf_admin', 12, 2)->default(0);
            $table->decimal('edli_cont', 12, 2)->default(0);
            $table->decimal('esi_employer', 12, 2);
            $table->decimal('taxable_value', 12, 2);
            $table->decimal('service_charge', 12, 2);
            $table->decimal('cgst', 12, 2);
            $table->decimal('sgst', 12, 2);
            $table->decimal('grand_total', 12, 2);
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
            $table->timestamps();

            $table->unique(['client_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The saved Register of Wages of a contract-labour client, one row per employee and month. It is kept apart
        // from `wages` (regular units) because its columns and rules differ.
        Schema::create('contract_wages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('category')->nullable();
            $table->decimal('rate_per_day', 10, 2);
            $table->decimal('paid_days', 6, 2);
            $table->decimal('ot_hours', 7, 2)->default(0);
            // Every calculated column of the sheet, at full precision, and the rates they were calculated with.
            $table->json('figures');
            $table->json('parameters')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['client_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_wages');
    }
};

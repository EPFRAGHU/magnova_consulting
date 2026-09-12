<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('working_days', 5, 2);
            $table->decimal('rate_per_day', 10, 2);
            $table->decimal('gross_wages', 10, 2);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('epf_employee', 10, 2);
            $table->decimal('esic_employee', 10, 2);
            $table->decimal('net_salary', 10, 2);
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wages');
    }
};

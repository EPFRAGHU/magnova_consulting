<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('name');
            $table->string('uan')->nullable();
            $table->string('epf_member_id')->nullable();
            $table->string('esic_no')->nullable();
            $table->enum('skill_category', ['skilled', 'unskilled']);
            $table->string('designation')->nullable();
            $table->decimal('rate_per_day', 10, 2);
            $table->date('date_of_joining')->nullable();
            $table->enum('status', ['active', 'inactive', 'left'])->default('active');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

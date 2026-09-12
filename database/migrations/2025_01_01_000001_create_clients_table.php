<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit_label')->nullable();
            $table->text('address')->nullable();
            $table->string('gstin')->nullable();
            $table->string('sac_code')->nullable();
            $table->string('gemc_no')->nullable();
            // Who the invoice is billed to, when it differs from the duty
            // site itself (e.g. a field unit's parent HQ pays the bill) —
            // falls back to name/address when left blank.
            $table->string('billing_name')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

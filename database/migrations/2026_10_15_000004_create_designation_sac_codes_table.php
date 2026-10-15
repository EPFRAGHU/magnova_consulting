<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('designation_sac_codes')) {
            return;
        }

        Schema::create('designation_sac_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('designation');
            $table->string('sac_code', 8);
            $table->timestamps();

            $table->unique(['tenant_id', 'designation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designation_sac_codes');
    }
};

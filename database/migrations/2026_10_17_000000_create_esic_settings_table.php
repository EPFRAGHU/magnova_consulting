<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per change of ESIC rules for an establishment. Rows are only ever added, never edited,
        // so every earlier wage month can still be explained by the rules that applied then.
        Schema::create('esic_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->boolean('establishment_covered')->default(true);
            $table->string('calculation_mode', 20)->default('statutory');
            $table->decimal('coverage_ceiling', 10, 2)->default(21000);
            $table->decimal('employee_rate', 5, 2)->default(0.75);
            $table->decimal('employer_rate', 5, 2)->default(3.25);
            $table->decimal('daily_wage_exemption', 8, 2)->nullable()->default(176);
            $table->boolean('include_bonus')->default(false);
            $table->string('rounding', 10)->default('nearest');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esic_settings');
    }
};

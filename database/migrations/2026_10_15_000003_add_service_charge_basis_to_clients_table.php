<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'service_charge_on_basic')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('service_charge_on_basic')->default(false)->after('bonus_enabled');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clients', 'service_charge_on_basic')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('service_charge_on_basic');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'gst_exempt')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('gst_exempt')->default(false)->after('service_charge_on_basic');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clients', 'gst_exempt')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('gst_exempt');
        });
    }
};

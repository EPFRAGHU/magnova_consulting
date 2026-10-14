<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'bonus_enabled')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('bonus_enabled')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clients', 'bonus_enabled')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('bonus_enabled');
        });
    }
};

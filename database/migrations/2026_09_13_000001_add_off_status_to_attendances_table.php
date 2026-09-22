<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement("ALTER TABLE attendances MODIFY status ENUM('present', 'absent', 'holiday', 'leave', 'sunday', 'off') NOT NULL");
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('status')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::getConnection()->table('attendances')->where('status', 'off')->update(['status' => 'holiday']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement("ALTER TABLE attendances MODIFY status ENUM('present', 'absent', 'holiday', 'leave', 'sunday') NOT NULL");
        }
    }
};
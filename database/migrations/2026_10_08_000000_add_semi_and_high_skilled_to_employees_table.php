<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement("ALTER TABLE employees MODIFY skill_category ENUM('unskilled', 'semi_skilled', 'skilled', 'high_skilled') NOT NULL");
        } else {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('skill_category')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::getConnection()->table('employees')->where('skill_category', 'semi_skilled')->update(['skill_category' => 'unskilled']);
        Schema::getConnection()->table('employees')->where('skill_category', 'high_skilled')->update(['skill_category' => 'skilled']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement("ALTER TABLE employees MODIFY skill_category ENUM('skilled', 'unskilled') NOT NULL");
        }
    }
};

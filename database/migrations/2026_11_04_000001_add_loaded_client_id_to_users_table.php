<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The client each user has switched ON in Clients & Sites; every data menu shows only that client.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('loaded_client_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('loaded_client_id');
        });
    }
};

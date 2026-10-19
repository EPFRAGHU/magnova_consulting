<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('establishment_address1')->nullable()->after('address');
            $table->string('establishment_address2')->nullable()->after('establishment_address1');
            $table->string('establishment_city')->nullable()->after('establishment_address2');
            $table->string('establishment_district')->nullable()->after('establishment_city');
            $table->string('establishment_pin', 10)->nullable()->after('establishment_district');
            $table->string('establishment_mobile', 30)->nullable()->after('contact_phone');
            $table->string('establishment_email')->nullable()->after('contact_email');
            $table->string('establishment_website')->nullable()->after('establishment_email');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'establishment_address1',
                'establishment_address2',
                'establishment_city',
                'establishment_district',
                'establishment_pin',
                'establishment_mobile',
                'establishment_email',
                'establishment_website',
            ]);
        });
    }
};

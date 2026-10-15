<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * designation_sac_codes becomes the master list of designations: the SAC code is
     * optional (the company default applies), and a description is stored beside it.
     * Every designation already typed on an employee is added to the list.
     */
    public function up(): void
    {
        Schema::table('designation_sac_codes', function (Blueprint $table) {
            $table->string('sac_code', 8)->nullable()->change();
        });

        if (! Schema::hasColumn('designation_sac_codes', 'sac_description')) {
            Schema::table('designation_sac_codes', function (Blueprint $table) {
                $table->string('sac_description')->nullable()->after('sac_code');
            });
        }

        $now = now();

        DB::table('employees')
            ->whereNotNull('designation')
            ->where('designation', '!=', '')
            ->select('tenant_id', 'designation')
            ->distinct()
            ->get()
            ->each(function ($row) use ($now) {
                $designation = trim($row->designation);

                $exists = DB::table('designation_sac_codes')
                    ->where('tenant_id', $row->tenant_id)
                    ->where('designation', $designation)
                    ->exists();

                if ($designation !== '' && ! $exists) {
                    DB::table('designation_sac_codes')->insert([
                        'tenant_id' => $row->tenant_id,
                        'designation' => $designation,
                        'sac_code' => null,
                        'sac_description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // The list is data; nothing to undo safely.
    }
};

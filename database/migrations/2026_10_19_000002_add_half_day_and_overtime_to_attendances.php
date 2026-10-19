<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // The status was a fixed list; a contract-labour client also needs 'half_day'. A plain string keeps
            // every existing value (present, absent, holiday, leave, sunday, off) exactly as it is.
            $table->string('status', 20)->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            // Overtime hours worked on that day (contract-labour attendance register).
            $table->decimal('ot_hours', 5, 2)->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('ot_hours');
        });
    }
};

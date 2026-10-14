<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the demo company details from the starter data, and only those:
     * a value someone has already entered by hand is left alone.
     */
    public function up(): void
    {
        DB::table('tenants')
            ->where('name', 'Magnova Consulting Pvt Ltd')
            ->update(['name' => 'Magnova Consulting Pvt. Ltd.']);

        DB::table('tenants')
            ->where('address', 'like', 'Plot No. 123, Nayapalli%')
            ->update([
                'address' => 'Plot No. 481/770, P.O. Balianta, Dandilo, Bhubaneswar - 752100, Khordha, Odisha',
                'establishment_address1' => 'Plot No. 481/770, P.O. Balianta, Dandilo',
                'establishment_address2' => null,
                'establishment_city' => 'Bhubaneswar',
                'establishment_district' => 'Khordha',
                'establishment_pin' => '752100',
            ]);

        DB::table('tenants')
            ->where('contact_phone', '9861000000')
            ->update(['contact_phone' => '7008960018']);

        DB::table('tenants')
            ->where('establishment_mobile', '9861000000')
            ->update(['establishment_mobile' => '7008960018']);
    }

    public function down(): void
    {
        // Company details are data, not structure; nothing to undo.
    }
};

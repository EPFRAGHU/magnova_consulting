<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes points 1 ("Total payment due in 30 days") and 2 ("Interest @ 18% p.a. ...") from Terms and Conditions
 * texts already saved for an establishment, and renumbers what is left. Only those two lines, in their exact
 * original wording, are touched; anything an establishment wrote itself stays as it is.
 */
return new class extends Migration
{
    private const REMOVE = [
        '/^\s*1\.\s*Total payment due in 30 days\.?\s*$/i',
        '/^\s*2\.\s*Interest @ 18% p\.a\. will be charged if payment is delayed beyond terms\.?\s*$/i',
    ];

    public function up(): void
    {
        DB::table('tenants')->whereNotNull('terms_and_conditions')->orderBy('id')->each(function ($tenant) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $tenant->terms_and_conditions);
            $kept = array_values(array_filter($lines, function (string $line) {
                foreach (self::REMOVE as $pattern) {
                    if (preg_match($pattern, $line)) {
                        return false;
                    }
                }

                return true;
            }));

            if (count($kept) === count($lines)) {
                return;
            }

            $number = 0;
            $kept = array_map(function (string $line) use (&$number) {
                return preg_match('/^\s*\d+\.\s+(.*)$/', $line, $m) ? (++$number).'. '.$m[1] : $line;
            }, $kept);

            DB::table('tenants')->where('id', $tenant->id)->update(['terms_and_conditions' => implode("\n", $kept)]);
        });
    }

    public function down(): void
    {
        // The removed wording is deliberately not restored.
    }
};

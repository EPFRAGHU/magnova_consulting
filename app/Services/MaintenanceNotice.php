<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * App-wide "under maintenance" banner. It only warns; it never blocks the app.
 * Stored in app_settings so it survives deploys and cache flushes.
 */
class MaintenanceNotice
{
    public const KEY = 'maintenance_notice';

    public const DEFAULT_MESSAGE = 'The app is under maintenance. Please do not use it or enter any data until this message goes away.';

    /** The message to show, or null when the banner is off. */
    public function message(): ?string
    {
        $value = $this->read();

        return $value === null || $value === '' ? null : $value;
    }

    public function turnOn(?string $message): void
    {
        $message = trim((string) $message) !== '' ? trim((string) $message) : self::DEFAULT_MESSAGE;

        DB::table('app_settings')->updateOrInsert(
            ['key' => self::KEY],
            ['value' => $message, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function turnOff(): void
    {
        DB::table('app_settings')->where('key', self::KEY)->delete();
    }

    private function read(): ?string
    {
        // This runs on every page, so a missing table (before the migration has run)
        // or a database hiccup must never break the page: just show no banner.
        try {
            return DB::table('app_settings')->where('key', self::KEY)->value('value');
        } catch (\Throwable) {
            return null;
        }
    }
}

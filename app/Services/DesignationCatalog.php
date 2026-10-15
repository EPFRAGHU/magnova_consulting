<?php

namespace App\Services;

use App\Models\DesignationSacCode;

/** The single source for "designation -> SAC code and description" used on every screen and document. */
class DesignationCatalog
{
    /** @var array<int, array<string, DesignationSacCode>> */
    private array $cache = [];

    public function defaultSac(): string
    {
        return (string) config('magnova.hsn_sac_code');
    }

    /** Built-in description for a known SAC code, or null. */
    public static function describe(?string $code): ?string
    {
        return $code ? (config('sac_catalog')[$code] ?? null) : null;
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    /** @return array<string, DesignationSacCode> keyed by lower-case designation */
    private function forTenant(int $tenantId): array
    {
        return $this->cache[$tenantId] ??= DesignationSacCode::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->mapWithKeys(fn (DesignationSacCode $row) => [mb_strtolower(trim($row->designation)) => $row])
            ->all();
    }

    /**
     * SAC code and description for a designation. A designation with no code of its own
     * (or one that is not in the list) gets the company default code.
     *
     * @return array{sac_code: string, sac_description: ?string, in_list: bool, has_own_sac: bool}
     */
    public function lookup(?int $tenantId, ?string $designation): array
    {
        $row = ($tenantId && $designation !== null && trim($designation) !== '')
            ? ($this->forTenant($tenantId)[mb_strtolower(trim($designation))] ?? null)
            : null;

        $hasOwn = $row && $row->sac_code;
        $code = $hasOwn ? $row->sac_code : $this->defaultSac();

        return [
            'sac_code' => $code,
            'sac_description' => ($hasOwn && $row->sac_description) ? $row->sac_description : self::describe($code),
            'in_list' => (bool) $row,
            'has_own_sac' => (bool) $hasOwn,
        ];
    }
}

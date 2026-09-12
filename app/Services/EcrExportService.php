<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Wage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EcrExportService
{
    private const EPS_WAGE_CEILING = 15000;

    public function generateForTenant(int $tenantId, int $month, int $year): string
    {
        $wages = Wage::query()
            ->where('tenant_id', $tenantId)
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get();

        $lines = $wages->map(function (Wage $wage) {
            $employee = $wage->employee;

            $grossWages = (float) $wage->gross_wages;
            $epfWages = $grossWages;
            $epsWages = min($epfWages, self::EPS_WAGE_CEILING);
            $edliWages = $epsWages;

            $epfContribution = (float) $wage->epf_employee;
            $epsContribution = round($epsWages * 8.33 / 100, 2);
            $epfEpsDifference = round($epfContribution - $epsContribution, 2);

            return implode('#~#', [
                $employee->uan,
                Str::upper($employee->name),
                $grossWages,
                $epfWages,
                $epsWages,
                $edliWages,
                $epfContribution,
                $epsContribution,
                $epfEpsDifference,
                0, // NCP days
                0, // Refund of advances
            ]);
        });

        return $lines->implode("\r\n");
    }

    public function generateAndStore(int $tenantId, int $month, int $year): string
    {
        $tenant = Tenant::findOrFail($tenantId);
        $content = $this->generateForTenant($tenantId, $month, $year);

        $establishmentCode = $tenant->slug;
        $path = sprintf('ecr/ECR_%s_%s%s.txt', $establishmentCode, str_pad((string) $month, 2, '0', STR_PAD_LEFT), $year);

        Storage::put($path, $content);

        return $path;
    }
}

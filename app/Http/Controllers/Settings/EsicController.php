<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EsicSetting;
use App\Models\Wage;
use App\Services\EsicCalculator;
use App\Support\WageMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EsicController extends Controller
{
    public function edit(Request $request, EsicCalculator $esic): Response
    {
        $tenantId = $request->user()->tenant_id;
        $now = now();
        $current = $esic->settingsFor($tenantId, $now->month, $now->year);
        $month = WageMonth::month();
        $year = WageMonth::year();

        return Inertia::render('settings/esic', [
            'current' => $this->present($current),
            'history' => EsicSetting::query()->orderByDesc('effective_from')->get()->map(fn (EsicSetting $s) => $this->present($s))->values(),
            'modes' => collect(EsicSetting::MODES)->map(fn (string $mode) => ['value' => $mode, 'label' => EsicSetting::modeLabel($mode)])->values(),
            'wage_month' => ['month' => $month, 'year' => $year],
            // Employees above the ceiling whose saved wages show no ESIC because no earlier coverage was found.
            'attention' => Wage::query()
                ->with('employee')
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->filter(fn (Wage $wage) => ($wage->esic_snapshot['reason'] ?? null) === 'above_ceiling_not_covered')
                ->map(fn (Wage $wage) => [
                    'employee_id' => $wage->employee_id,
                    'name' => $wage->employee?->name,
                    'gross_wages' => (float) $wage->gross_wages,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request, EsicCalculator $esic): RedirectResponse
    {
        $request->merge([
            'establishment_covered' => $request->boolean('establishment_covered'),
            'include_bonus' => $request->boolean('include_bonus'),
            'confirm_capped' => $request->boolean('confirm_capped'),
        ]);

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'establishment_covered' => ['boolean'],
            'calculation_mode' => ['required', Rule::in(EsicSetting::MODES)],
            'coverage_ceiling' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'daily_wage_exemption' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'include_bonus' => ['boolean'],
            'rounding' => ['required', Rule::in(EsicSetting::ROUNDING)],
            'note' => ['nullable', 'string', 'max:255'],
            'confirm_capped' => ['boolean'],
        ], [
            'effective_from.required' => 'Choose the date from which these rules apply.',
        ]);

        if ($data['calculation_mode'] !== 'statutory' && ! $data['confirm_capped']) {
            return back()->withErrors(['confirm_capped' => 'Tick the box to confirm you want a non-statutory mode.'])->withInput();
        }

        // Rules apply from the first day of the month of the chosen date.
        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfMonth();

        if (EsicSetting::query()->whereDate('effective_from', $effectiveFrom->toDateString())->exists()) {
            return back()->withErrors(['effective_from' => 'Rules already exist from '.$effectiveFrom->format('F Y').'. Rules are never edited, so choose a later month.'])->withInput();
        }

        unset($data['confirm_capped']);
        EsicSetting::create(array_merge($data, [
            'tenant_id' => $request->user()->tenant_id,
            'effective_from' => $effectiveFrom->toDateString(),
        ]));
        $esic->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ESIC rules saved from '.$effectiveFrom->format('F Y').'. Wages already saved keep their figures; save wages again to apply the new rules.']);

        return to_route('esic.edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(EsicSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'effective_from' => $setting->effective_from?->toDateString(),
            'establishment_covered' => (bool) $setting->establishment_covered,
            'calculation_mode' => $setting->calculation_mode,
            'coverage_ceiling' => (float) $setting->coverage_ceiling,
            'employee_rate' => (float) $setting->employee_rate,
            'employer_rate' => (float) $setting->employer_rate,
            'daily_wage_exemption' => $setting->daily_wage_exemption,
            'include_bonus' => (bool) $setting->include_bonus,
            'rounding' => $setting->rounding,
            'note' => $setting->note,
        ];
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DesignationSacCode;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SacCodeController extends Controller
{
    public function edit(): Response
    {
        $saved = DesignationSacCode::query()->get()->mapWithKeys(
            fn (DesignationSacCode $row) => [mb_strtolower($row->designation) => $row],
        );

        $inUse = Employee::query()
            ->whereNotNull('designation')
            ->where('designation', '!=', '')
            ->distinct()
            ->orderBy('designation')
            ->pluck('designation');

        $rows = $inUse
            ->merge($saved->pluck('designation'))
            ->unique(fn (string $designation) => mb_strtolower($designation))
            ->values()
            ->map(fn (string $designation) => [
                'designation' => $designation,
                'sac_code' => $saved->get(mb_strtolower($designation))?->sac_code ?? '',
            ]);

        return Inertia::render('settings/sac-codes', [
            'rows' => $rows,
            'default_sac' => config('magnova.hsn_sac_code'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['array'],
            'rows.*.designation' => ['required', 'string', 'max:255'],
            'rows.*.sac_code' => ['nullable', 'string', 'regex:/^[0-9]{6}$/'],
        ], [
            'rows.*.sac_code.regex' => 'A SAC code is 6 digits, like 998525.',
        ]);

        $tenantId = $request->user()->tenant_id;

        foreach ($data['rows'] ?? [] as $row) {
            $code = trim((string) ($row['sac_code'] ?? ''));
            $existing = DesignationSacCode::query()->where('designation', $row['designation'])->first();

            if ($code === '') {
                $existing?->delete();

                continue;
            }

            if ($existing) {
                $existing->update(['sac_code' => $code]);
            } else {
                DesignationSacCode::create([
                    'tenant_id' => $tenantId,
                    'designation' => $row['designation'],
                    'sac_code' => $code,
                ]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SAC codes updated.']);

        return to_route('sac-codes.edit');
    }
}

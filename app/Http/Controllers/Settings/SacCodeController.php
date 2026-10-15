<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DesignationSacCode;
use App\Models\Employee;
use App\Services\DesignationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** The master list of designations: name, SAC code and description, used by every screen and document. */
class SacCodeController extends Controller
{
    public function edit(): Response
    {
        $rows = DesignationSacCode::query()
            ->orderBy('designation')
            ->get()
            ->map(fn (DesignationSacCode $row) => [
                'designation' => $row->designation,
                'sac_code' => $row->sac_code ?? '',
                'sac_description' => $row->sac_description ?: (DesignationCatalog::describe($row->sac_code) ?? ''),
                'in_use' => Employee::query()->where('designation', $row->designation)->exists(),
            ]);

        return Inertia::render('settings/sac-codes', [
            'rows' => $rows,
            'default_sac' => config('magnova.hsn_sac_code'),
            'default_description' => DesignationCatalog::describe(config('magnova.hsn_sac_code')),
            'catalog' => config('sac_catalog'),
        ]);
    }

    public function update(Request $request, DesignationCatalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['array'],
            'rows.*.designation' => ['required', 'string', 'max:255'],
            'rows.*.sac_code' => ['nullable', 'string', 'regex:/^[0-9]{6}$/'],
            'rows.*.sac_description' => ['nullable', 'string', 'max:255'],
        ], [
            'rows.*.sac_code.regex' => 'A SAC code is 6 digits, like 998525.',
        ]);

        $rows = collect($data['rows'] ?? [])->map(fn (array $row) => [
            'designation' => trim($row['designation']),
            'sac_code' => trim((string) ($row['sac_code'] ?? '')) ?: null,
            'sac_description' => trim((string) ($row['sac_description'] ?? '')) ?: null,
        ])->filter(fn (array $row) => $row['designation'] !== '');

        $names = $rows->pluck('designation')->map(fn ($name) => mb_strtolower($name));
        if ($names->count() !== $names->unique()->count()) {
            throw ValidationException::withMessages(['rows' => 'The same designation is listed twice.']);
        }

        $tenantId = $request->user()->tenant_id;

        foreach ($rows as $row) {
            $existing = DesignationSacCode::query()->where('designation', $row['designation'])->first();
            $values = ['sac_code' => $row['sac_code'], 'sac_description' => $row['sac_description']];

            if ($existing) {
                $existing->update($values + ($existing->designation !== $row['designation'] ? ['designation' => $row['designation']] : []));
            } else {
                DesignationSacCode::create(['tenant_id' => $tenantId, 'designation' => $row['designation']] + $values);
            }
        }

        // Designations removed from the list go away, unless an employee still uses them.
        DesignationSacCode::query()->get()->each(function (DesignationSacCode $existing) use ($names) {
            if (! $names->contains(mb_strtolower($existing->designation))
                && ! Employee::query()->where('designation', $existing->designation)->exists()) {
                $existing->delete();
            }
        });

        $catalog->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Designations and SAC codes updated.']);

        return to_route('sac-codes.edit');
    }
}

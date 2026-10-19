<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContractWageSheet;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Calculates, saves and prints the Register of Wages of a contract-labour client. */
class ContractWageController extends Controller
{
    private function contractClient(Request $request): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        abort_unless($client->payroll_profile === 'contract_labour', 422, 'This unit does not use the contract labour wage sheet.');

        return [$client, (int) $data['month'], (int) $data['year']];
    }

    public function save(Request $request, ContractWageSheet $sheet): RedirectResponse
    {
        [$client, $month, $year] = $this->contractClient($request);

        $count = $sheet->save($client, $month, $year);

        Inertia::flash('toast', ['type' => 'success', 'message' => $count === 1 ? 'Wages saved for 1 employee.' : "Wages saved for {$count} employees."]);

        return back();
    }

    public function register(Request $request, PdfExportService $pdf): HttpResponse
    {
        [$client, $month, $year] = $this->contractClient($request);

        $fileName = 'register-of-wages-'.str_replace(['/', '\\', ' '], '-', $client->name)."-{$month}-{$year}.pdf";

        return $pdf->contractLabourWageRegister($client, $month, $year)->download($fileName);
    }
}

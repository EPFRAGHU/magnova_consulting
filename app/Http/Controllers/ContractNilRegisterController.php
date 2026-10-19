<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContractNilRegister;
use App\Services\PdfExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Printable Register of Fines / Register of Deductions of a contract-labour unit (NIL, one line per workman). */
class ContractNilRegisterController extends Controller
{
    public function show(Request $request, string $kind, PdfExportService $pdf, ContractNilRegister $registers): HttpResponse
    {
        abort_unless(in_array($kind, ContractNilRegister::KINDS, true), 404);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $client = Client::findOrFail((int) $data['client_id']);
        abort_unless($client->payroll_profile === 'contract_labour', 422, 'This unit does not use the contract labour registers.');

        $fileName = $registers->fileStem($kind).'-'.str_replace(['/', '\\', ' '], '-', $client->name)."-{$data['month']}-{$data['year']}.pdf";

        return $pdf->contractLabourNilRegister($kind, $client, (int) $data['month'], (int) $data['year'])->download($fileName);
    }
}

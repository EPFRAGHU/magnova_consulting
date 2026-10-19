<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use Illuminate\Contracts\View\View;

/**
 * The statutory Register of Fines and Register of Deductions for damage or loss of a contract-labour client.
 * The client's own sheets print one line per workman with NIL, as Magnova imposes neither, so both registers
 * share one layout and differ only in title and columns.
 */
class ContractNilRegister
{
    public const KINDS = ['fines', 'deductions'];

    /**
     * Column headings of each register, in the client's order. `sub` is a second heading line printed under the
     * main one; `width` is the column share in percent of the page.
     */
    private const REGISTERS = [
        'fines' => [
            'title' => 'Register of Fines',
            'file' => 'register-of-fines',
            'columns' => [
                ['label' => 'Sr. No.', 'width' => 4],
                ['label' => 'Name of workman', 'width' => 15],
                ['label' => "Father's/ Husband's name", 'width' => 11],
                ['label' => 'Designation / Nature of employment', 'width' => 10],
                ['label' => 'Act/Omission for which fine imposed', 'width' => 10],
                ['label' => 'Date of offence', 'width' => 6],
                ['label' => 'Whether workman showed cause against fine', 'width' => 8],
                ['label' => "Name of person in whose presence employee's explanation was heard", 'width' => 10],
                ['label' => 'Wage periods and wages payable', 'width' => 8],
                ['label' => 'Amount of fine imposed', 'width' => 6],
                ['label' => 'Date on which fine realized', 'width' => 7],
                ['label' => 'Remarks', 'width' => 5],
            ],
        ],
        'deductions' => [
            'title' => 'Register of deductions for damage or loss',
            'file' => 'register-of-deductions',
            'columns' => [
                ['label' => 'Sr. No.', 'width' => 4],
                ['label' => 'Name of workman', 'width' => 14],
                ['label' => "Father's/ Husband's name", 'width' => 10],
                ['label' => 'Designation / Nature of employment', 'width' => 9],
                ['label' => 'Particulars of damage or loss', 'width' => 9],
                ['label' => 'Date of damage or loss', 'width' => 6],
                ['label' => 'Whether workman showed cause against deduction', 'width' => 8],
                ['label' => "Name of person in whose presence employee's explanation was heard", 'width' => 9],
                ['label' => 'Amount of deduction imposed', 'width' => 8],
                ['label' => 'No. of installments', 'width' => 6],
                ['label' => 'Date of recovery', 'sub' => 'First installment', 'width' => 6],
                ['label' => 'Date on which fine realized', 'sub' => 'Last installment', 'width' => 6],
                ['label' => 'Remarks', 'width' => 5],
            ],
        ],
    ];

    public function title(string $kind): string
    {
        return self::REGISTERS[$kind]['title'];
    }

    public function fileStem(string $kind): string
    {
        return self::REGISTERS[$kind]['file'];
    }

    /** @return array<int, array{serial: int, name: string}> the active workmen of the unit, in register order */
    public function workmen(Client $client): array
    {
        return Employee::query()->where('client_id', $client->id)->where('status', 'active')->orderBySkill()->get()
            ->values()
            ->map(fn (Employee $employee, int $i) => ['serial' => $i + 1, 'name' => $employee->name])
            ->all();
    }

    public function view(string $kind, Client $client, int $month, int $year): View
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $register = self::REGISTERS[$kind];

        return view('pdf.contract-labour-nil-register', [
            'title' => $register['title'],
            'columns' => $register['columns'],
            'workmen' => $this->workmen($client),
        ] + app(ContractAttendanceRegister::class)->headings($client, $month, $year));
    }
}

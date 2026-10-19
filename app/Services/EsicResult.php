<?php

namespace App\Services;

/** The outcome of one employee's ESIC calculation for one wage month, plus the rules it was based on. */
final class EsicResult
{
    /** @param array<string, mixed> $settings the settings used, as stored in a snapshot */
    public function __construct(
        public readonly bool $covered,
        public readonly float $contributionWages,
        public readonly float $employeeAmount,
        public readonly float $employerAmount,
        public readonly string $reason,
        public readonly string $mode,
        public readonly array $settings,
        public readonly bool $employeeShareExempt = false,
    ) {}

    /**
     * What is stored with a finalised wage so it can always be explained later.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->settings + [
            'mode' => $this->mode,
            'covered' => $this->covered,
            'reason' => $this->reason,
            'contribution_wages' => $this->contributionWages,
            'employee_amount' => $this->employeeAmount,
            'employer_amount' => $this->employerAmount,
            'employee_share_exempt' => $this->employeeShareExempt,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self(
            covered: (bool) ($snapshot['covered'] ?? false),
            contributionWages: (float) ($snapshot['contribution_wages'] ?? 0),
            employeeAmount: (float) ($snapshot['employee_amount'] ?? 0),
            employerAmount: (float) ($snapshot['employer_amount'] ?? 0),
            reason: (string) ($snapshot['reason'] ?? 'snapshot'),
            mode: (string) ($snapshot['mode'] ?? 'statutory'),
            settings: array_diff_key($snapshot, array_flip([
                'mode', 'covered', 'reason', 'contribution_wages', 'employee_amount', 'employer_amount', 'employee_share_exempt',
            ])),
            employeeShareExempt: (bool) ($snapshot['employee_share_exempt'] ?? false),
        );
    }
}

<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property Carbon $invoice_date
 * @property Carbon|null $due_date
 * @property array<string, mixed>|null $document
 */
class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_id', 'invoice_no', 'ref_no', 'invoice_date', 'due_date',
        'place_of_supply', 'reverse_charge', 'month', 'year',
        'rate_per_day', 'no_of_persons', 'no_of_duties', 'base_amount',
        'epf_employer', 'epf_admin', 'edli_cont', 'esi_employer', 'taxable_value', 'service_charge',
        'cgst', 'sgst', 'igst', 'grand_total', 'status', 'document',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'reverse_charge' => 'boolean',
            'document' => 'array',
            'rate_per_day' => 'decimal:2',
            'no_of_duties' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'epf_employer' => 'decimal:2',
            'epf_admin' => 'decimal:2',
            'edli_cont' => 'decimal:2',
            'esi_employer' => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    /** Indian financial year (April to March) of a date, e.g. "26-27" for 14 Oct 2026. */
    public static function financialYearLabel(CarbonInterface $date): string
    {
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return substr((string) $startYear, 2).'-'.substr((string) ($startYear + 1), 2);
    }

    /** Fixed part of an invoice number: company initials and financial year, e.g. "MCPL/26-27/". */
    public static function numberPrefix(string $tenantName, CarbonInterface $date): string
    {
        $initials = collect(preg_split('/\s+/', trim(preg_replace('~^\s*m/s\.?\s*~i', '', $tenantName))) ?: [])
            ->filter()
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');

        return $initials.'/'.self::financialYearLabel($date).'/';
    }

    /** True for an invoice issued from a contract-labour client's wage sheet (it carries its own printed content). */
    public function isContractLabour(): bool
    {
        return ! empty($this->document);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /** @return HasMany<InvoiceLineItem, $this> */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }
}

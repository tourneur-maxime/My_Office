<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $client_id
 * @property int $user_id
 * @property int|null $quote_id
 * @property string $invoice_number
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon|null $due_date
 * @property InvoiceStatus $status
 * @property float $subtotal
 * @property float $vat_amount
 * @property float $total
 * @property \Carbon\Carbon|null $paid_at
 * @property array|null $facturx_metadata
 * @property string|null $pdf_path
 * @property bool $is_compliant
 * @property-read \Carbon\Carbon|null $effective_due_date
 * @property-read Prospect $client
 * @property-read User $user
 * @property-read Quote|null $quote
 * @property-read \Illuminate\Database\Eloquent\Collection|InvoiceLineItem[] $lineItems
 */
class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'client_id',
        'user_id',
        'quote_id',
        'template_id',
        'branding_snapshot',
        'invoice_number',
        'type',
        'credit_note_for_id',
        'issue_date',
        'service_date',
        'due_date',
        'status',
        'subtotal',
        'vat_amount',
        'total',
        'paid_at',
        'facturx_metadata',
        'pdf_path',
        'is_compliant',
        'signature_hash',
        'is_ready_for_signature',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'service_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
        'type' => InvoiceType::class,
        'facturx_metadata' => 'array',
        'branding_snapshot' => 'array',
        'is_compliant' => 'boolean',
        'is_ready_for_signature' => 'boolean',
    ];

    /**
     * Scope a query to filter invoices by various criteria.
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['client_id'] ?? null, function ($query, $clientId) {
            $query->where('client_id', $clientId);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['is_compliant'] ?? null, function ($query, $isCompliant) {
            $query->where('is_compliant', $isCompliant === 'true' || $isCompliant === true);
        })->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        })->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            $query->whereDate('issue_date', '<=', $dateTo);
        })->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('invoice_number', 'like', "%{$search}%");
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'status', 'paid_at']) // Include invoice_number
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logFillable(); // Log all fillable attributes on creation and update
    }

    public function client()
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function creditNoteFor()
    {
        return $this->belongsTo(Invoice::class, 'credit_note_for_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(Invoice::class, 'credit_note_for_id');
    }

    /**
     * Get the effective due date, falling back to company defaults if not set.
     */
    public function getEffectiveDueDateAttribute(): ?\Carbon\Carbon
    {
        if ($this->due_date) {
            return $this->due_date;
        }

        $company = $this->user->companyProfile;
        if ($company && $company->default_payment_delay_days !== null && $this->issue_date) {
            return $this->issue_date->copy()->addDays($company->default_payment_delay_days);
        }

        return null;
    }

    /**
     * Check for gaps in the invoice numbering sequence.
     * Returns an array of gaps if found.
     */
    public static function checkSequenceGaps($userId)
    {
        $numbers = self::where('user_id', $userId)
            ->whereNotNull('invoice_number')
            ->pluck('invoice_number')
            ->map(function ($num) {
                $parts = explode('-', $num);

                return (int) end($parts);
            })
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return [];
        }

        $gaps = [];
        $min = $numbers->min();
        $max = $numbers->max();

        for ($i = $min; $i <= $max; $i++) {
            if (! $numbers->contains($i)) {
                $gaps[] = $i;
            }
        }

        return $gaps;
    }

    protected static function booted()
    {
        static::updating(function ($invoice) {
            if ($invoice->isDirty('invoice_number') && $invoice->getOriginal('invoice_number') !== null) {
                throw new \Exception('Invoice number cannot be changed once assigned.');
            }
        });

        static::deleting(function ($invoice) {
            if (!$invoice->isForceDeleting() && $invoice->status !== InvoiceStatus::Brouillon) {
                throw new \Exception('Seules les factures en brouillon peuvent être supprimées. Utilisez un avoir pour corriger une facture finalisée.');
            }
        });
    }
}

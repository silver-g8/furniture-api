<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApPaymentAllocation extends Model
{
    use HasFactory;

    protected $table = 'ap_payment_allocations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'allocated_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allocated_amount' => 'float',
    ];

    /**
     * Get the payment that owns the allocation.
     *
     * @return BelongsTo<ApPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(ApPayment::class, 'payment_id');
    }

    /**
     * Get the invoice that owns the allocation.
     *
     * @return BelongsTo<ApInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class, 'invoice_id');
    }
}

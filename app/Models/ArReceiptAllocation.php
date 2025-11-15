<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArReceiptAllocation extends Model
{
    use HasFactory;
    protected $table = 'ar_receipt_allocations';

    protected $fillable = [
        'receipt_id',
        'invoice_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ArReceipt::class, 'receipt_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'invoice_id');
    }
}


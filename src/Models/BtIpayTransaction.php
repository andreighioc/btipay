<?php

namespace AndreiGhioc\BtiPay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use AndreiGhioc\BtiPay\Enums\OrderStatus;
use AndreiGhioc\BtiPay\Responses\ActionCodeMessages;

class BtiPayTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'BtiPay_transactions';

    protected $fillable = [
        'order_id',
        'order_number',
        'payment_type',
        'amount',
        'currency',
        'status',
        'action_code',
        'action_code_description',
        'form_url',
        'return_url',
        'description',
        'masked_pan',
        'card_expiration',
        'cardholder_name',
        'approval_code',
        'auth_ref_num',
        'terminal_id',
        'payment_way',
        'approved_amount',
        'deposited_amount',
        'refunded_amount',
        'eci',
        'customer_email',
        'customer_phone',
        'customer_ip',
        'error_code',
        'error_message',
        'chargeback',
        'loyalty_order_id',
        'loyalty_amount',
        'payable_type',
        'payable_id',
        'raw_register_response',
        'raw_status_response',
    ];

    protected $casts = [
        'amount'                 => 'integer',
        'approved_amount'        => 'integer',
        'deposited_amount'       => 'integer',
        'refunded_amount'        => 'integer',
        'loyalty_amount'         => 'integer',
        'chargeback'             => 'boolean',
        'raw_register_response'  => 'array',
        'raw_status_response'    => 'array',
    ];

    /**
     * Get the parent payable model (Order, Booking, etc.).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Status Helpers ──────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'DEPOSITED';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'DECLINED';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'REFUNDED';
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->status === 'PARTIALLY_REFUNDED';
    }

    public function isReversed(): bool
    {
        return $this->status === 'REVERSED';
    }

    public function isPending(): bool
    {
        return $this->status === 'CREATED';
    }

    // ─── Amount Helpers ──────────────────────────────────────────────

    /**
     * Get the amount formatted in major currency units (e.g. RON).
     */
    public function getAmountFormattedAttribute(): float
    {
        return $this->amount / 100;
    }

    /**
     * Get the deposited amount formatted.
     */
    public function getDepositedAmountFormattedAttribute(): float
    {
        return $this->deposited_amount / 100;
    }

    /**
     * Get the refunded amount formatted.
     */
    public function getRefundedAmountFormattedAttribute(): float
    {
        return $this->refunded_amount / 100;
    }

    /**
     * Get the remaining refundable amount.
     */
    public function getRefundableAmountAttribute(): int
    {
        return $this->deposited_amount - $this->refunded_amount;
    }

    /**
     * Get the currency label (RON, EUR, USD).
     */
    public function getCurrencyLabelAttribute(): string
    {
        return match ($this->currency) {
            '946' => 'RON',
            '978' => 'EUR',
            '840' => 'USD',
            default => $this->currency,
        };
    }

    // ─── Error Helpers ───────────────────────────────────────────────

    /**
     * Get the human-readable action code message.
     */
    public function getActionCodeMessageAttribute(): ?string
    {
        if (empty($this->action_code) || $this->action_code == '0') {
            return null;
        }

        return ActionCodeMessages::getMessage((int) $this->action_code);
    }

    /**
     * Check if the card should NOT be retried for this error.
     */
    public function shouldNotRetryWithSameCard(): bool
    {
        if (empty($this->action_code)) {
            return false;
        }

        return ActionCodeMessages::shouldNotRetryWithSameCard((int) $this->action_code);
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'DEPOSITED');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'DECLINED');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'CREATED');
    }

    public function scopePreAuthorized($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['REFUNDED', 'PARTIALLY_REFUNDED']);
    }

    public function scopeForPayable($query, Model $payable)
    {
        return $query->where('payable_type', get_class($payable))
                     ->where('payable_id', $payable->getKey());
    }
}

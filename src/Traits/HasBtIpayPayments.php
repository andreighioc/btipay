<?php

namespace AndreiGhioc\BtiPay\Traits;

use AndreiGhioc\BtiPay\Models\BtiPayTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait to add to any model that can have BT iPay payments (e.g. Order, Booking).
 *
 * Usage:
 *   class Order extends Model
 *   {
 *       use HasBtiPayPayments;
 *   }
 *
 *   $order->BtiPayTransactions; // all transactions
 *   $order->latestBtiPayTransaction; // latest transaction
 *   $order->isPaidViaBtiPay(); // check if paid
 */
trait HasBtiPayPayments
{
    /**
     * Get all BT iPay transactions for this model.
     */
    public function BtiPayTransactions(): MorphMany
    {
        return $this->morphMany(BtiPayTransaction::class, 'payable');
    }

    /**
     * Get the latest BT iPay transaction.
     */
    public function getLatestBtiPayTransactionAttribute(): ?BtiPayTransaction
    {
        return $this->BtiPayTransactions()->latest()->first();
    }

    /**
     * Check if this model has a successful payment.
     */
    public function isPaidViaBtiPay(): bool
    {
        return $this->BtiPayTransactions()->successful()->exists();
    }

    /**
     * Check if this model has a pending payment.
     */
    public function hasPendingBtiPayPayment(): bool
    {
        return $this->BtiPayTransactions()
            ->whereIn('status', ['CREATED', 'APPROVED'])
            ->exists();
    }

    /**
     * Get the total paid amount in minor currency units.
     */
    public function getTotalPaidViaBtiPay(): int
    {
        return (int) $this->BtiPayTransactions()
            ->successful()
            ->sum('deposited_amount');
    }

    /**
     * Get the total refunded amount in minor currency units.
     */
    public function getTotalRefundedViaBtiPay(): int
    {
        return (int) $this->BtiPayTransactions()
            ->sum('refunded_amount');
    }
}

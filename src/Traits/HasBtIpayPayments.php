<?php

namespace BtIpay\Laravel\Traits;

use BtIpay\Laravel\Models\BtIpayTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait to add to any model that can have BT iPay payments (e.g. Order, Booking).
 *
 * Usage:
 *   class Order extends Model
 *   {
 *       use HasBtIpayPayments;
 *   }
 *
 *   $order->btipayTransactions; // all transactions
 *   $order->latestBtipayTransaction; // latest transaction
 *   $order->isPaidViaBtipay(); // check if paid
 */
trait HasBtIpayPayments
{
    /**
     * Get all BT iPay transactions for this model.
     */
    public function btipayTransactions(): MorphMany
    {
        return $this->morphMany(BtIpayTransaction::class, 'payable');
    }

    /**
     * Get the latest BT iPay transaction.
     */
    public function getLatestBtipayTransactionAttribute(): ?BtIpayTransaction
    {
        return $this->btipayTransactions()->latest()->first();
    }

    /**
     * Check if this model has a successful payment.
     */
    public function isPaidViaBtipay(): bool
    {
        return $this->btipayTransactions()->successful()->exists();
    }

    /**
     * Check if this model has a pending payment.
     */
    public function hasPendingBtipayPayment(): bool
    {
        return $this->btipayTransactions()
            ->whereIn('status', ['CREATED', 'APPROVED'])
            ->exists();
    }

    /**
     * Get the total paid amount in minor currency units.
     */
    public function getTotalPaidViaBtipay(): int
    {
        return (int) $this->btipayTransactions()
            ->successful()
            ->sum('deposited_amount');
    }

    /**
     * Get the total refunded amount in minor currency units.
     */
    public function getTotalRefundedViaBtipay(): int
    {
        return (int) $this->btipayTransactions()
            ->sum('refunded_amount');
    }
}

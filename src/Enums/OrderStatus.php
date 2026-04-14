<?php

namespace AndreiGhioc\BtiPay\Enums;

/**
 * Order status values returned by getOrderStatusExtended.do
 */
enum OrderStatus: int
{
    case CREATED             = 0;
    case APPROVED            = 1;
    case DEPOSITED           = 2;
    case REVERSED            = 3;
    case REFUNDED            = 4;
    case ACS_AUTH_INITIATED  = 5;
    case DECLINED            = 6;
    case PARTIALLY_REFUNDED  = 7;

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED            => 'Created (not paid)',
            self::APPROVED           => 'Pre-authorized (approved)',
            self::DEPOSITED          => 'Deposited (paid)',
            self::REVERSED           => 'Reversed (cancelled)',
            self::REFUNDED           => 'Fully refunded',
            self::ACS_AUTH_INITIATED => 'ACS authorization initiated',
            self::DECLINED           => 'Declined',
            self::PARTIALLY_REFUNDED => 'Partially refunded',
        };
    }

    /**
     * Get the Romanian label for the status.
     */
    public function labelRo(): string
    {
        return match ($this) {
            self::CREATED            => 'Creată (neplătită)',
            self::APPROVED           => 'Pre-autorizată (aprobată)',
            self::DEPOSITED          => 'Încasată (plătită)',
            self::REVERSED           => 'Reversată (anulată)',
            self::REFUNDED           => 'Rambursată total',
            self::ACS_AUTH_INITIATED => 'Autorizare ACS inițiată',
            self::DECLINED           => 'Declinată',
            self::PARTIALLY_REFUNDED => 'Rambursată parțial',
        };
    }

    /**
     * Check if this status represents a successful payment.
     */
    public function isPaid(): bool
    {
        return $this === self::DEPOSITED;
    }

    /**
     * Check if this status represents a final/terminal state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::DEPOSITED,
            self::REVERSED,
            self::REFUNDED,
            self::DECLINED,
        ]);
    }
}

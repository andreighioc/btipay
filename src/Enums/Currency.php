<?php

namespace BtIpay\Laravel\Enums;

/**
 * Currency codes supported by BT iPay (ISO 4217 numeric).
 */
enum Currency: int
{
    case RON = 946;
    case EUR = 978;
    case USD = 840;

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::RON => 'RON',
            self::EUR => 'EUR',
            self::USD => 'USD',
        };
    }
}

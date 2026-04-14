<?php

namespace BtIpay\Laravel\Enums;

/**
 * Payment type supported by BT iPay.
 */
enum PaymentType: string
{
    case ONE_PHASE = '1phase';
    case TWO_PHASE = '2phase';

    /**
     * Get the API endpoint for registering an order.
     */
    public function registerEndpoint(): string
    {
        return match ($this) {
            self::ONE_PHASE => '/payment/rest/register.do',
            self::TWO_PHASE => '/payment/rest/registerPreAuth.do',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONE_PHASE => '1-Phase (Direct Payment)',
            self::TWO_PHASE => '2-Phase (Pre-Authorization)',
        };
    }
}

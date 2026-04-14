<?php

namespace BtIpay\Laravel\Events;

use BtIpay\Laravel\Models\BtIpayTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentDeclined
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BtIpayTransaction $transaction,
        public ?int $actionCode = null,
        public ?string $actionCodeDescription = null
    ) {}
}

<?php

namespace AndreiGhioc\BtiPay\Events;

use AndreiGhioc\BtiPay\Models\BtiPayTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentDeclined
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BtiPayTransaction $transaction,
        public ?int $actionCode = null,
        public ?string $actionCodeDescription = null
    ) {}
}

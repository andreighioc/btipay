<?php

namespace AndreiGhioc\BtiPay\Events;

use AndreiGhioc\BtiPay\Models\BtiPayTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BtiPayTransaction $transaction
    ) {}
}

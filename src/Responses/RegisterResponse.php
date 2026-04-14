<?php

namespace BtIpay\Laravel\Responses;

class RegisterResponse extends BaseResponse
{
    /**
     * Get the iPay order ID (UUID).
     */
    public function getOrderId(): ?string
    {
        return $this->raw['orderId'] ?? null;
    }

    /**
     * Get the payment form URL to redirect the customer to.
     */
    public function getFormUrl(): ?string
    {
        return $this->raw['formUrl'] ?? null;
    }

    /**
     * Check if the registration was successful and URLs are available.
     */
    public function isSuccessful(): bool
    {
        return parent::isSuccessful() && ! empty($this->getFormUrl());
    }
}

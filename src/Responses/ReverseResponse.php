<?php

namespace BtIpay\Laravel\Responses;

class ReverseResponse extends BaseResponse
{
    /**
     * Get the action code from the processing system.
     */
    public function getActionCode(): ?int
    {
        return isset($this->raw['actionCode']) ? (int) $this->raw['actionCode'] : null;
    }

    /**
     * Get the loyalty operation action code (for RON + LOY transactions).
     */
    public function getLoyaltyActionCode(): ?int
    {
        return isset($this->raw['loyaltyOperationActionCode'])
            ? (int) $this->raw['loyaltyOperationActionCode']
            : null;
    }
}

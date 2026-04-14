<?php

namespace AndreiGhioc\BtiPay\Responses;

class DepositResponse extends BaseResponse
{
    /**
     * Get the action code from the processing system.
     */
    public function getActionCode(): ?int
    {
        return isset($this->raw['actionCode']) ? (int) $this->raw['actionCode'] : null;
    }

    /**
     * Get the action code description.
     */
    public function getActionCodeDescription(): ?string
    {
        return $this->raw['actionCodeDescription'] ?? null;
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

    /**
     * Get the loyalty operation action code description.
     */
    public function getLoyaltyActionCodeDescription(): ?string
    {
        return $this->raw['loyaltyOperationActionCodeDescription'] ?? null;
    }
}

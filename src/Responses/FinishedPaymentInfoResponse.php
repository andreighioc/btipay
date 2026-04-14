<?php

namespace BtIpay\Laravel\Responses;

class FinishedPaymentInfoResponse extends BaseResponse
{
    /**
     * Get the action code.
     */
    public function getActionCode(): ?int
    {
        return isset($this->raw['actionCode']) ? (int) $this->raw['actionCode'] : null;
    }

    /**
     * Get the action description.
     */
    public function getActionDescription(): ?string
    {
        return $this->raw['actionDesc'] ?? null;
    }

    /**
     * Get the iPay order ID (mdOrder).
     */
    public function getMdOrder(): ?string
    {
        return $this->raw['mdOrder'] ?? null;
    }

    /**
     * Get the merchant order number.
     */
    public function getOrderNumber(): ?string
    {
        return $this->raw['orderNumber'] ?? null;
    }

    /**
     * Get the order description.
     */
    public function getOrderDescription(): ?string
    {
        return $this->raw['orderDescription'] ?? null;
    }

    /**
     * Get the formatted amount.
     */
    public function getAmountFormatted(): ?float
    {
        return isset($this->raw['amountFormatted']) ? (float) $this->raw['amountFormatted'] : null;
    }

    /**
     * Get the currency name.
     */
    public function getCurrencyName(): ?string
    {
        return $this->raw['currencyName'] ?? null;
    }

    /**
     * Get the payment reference number (RRN).
     */
    public function getPaymentRefNum(): ?string
    {
        return $this->raw['paymentRefNum'] ?? null;
    }

    /**
     * Get the approval code.
     */
    public function getApprovalCode(): ?string
    {
        return $this->raw['approvalCode'] ?? null;
    }

    /**
     * Get the loyalty amount (for RON + LOY).
     */
    public function getLoyaltyAmount(): ?string
    {
        return $this->raw['loyaltyAmount'] ?? null;
    }

    /**
     * Get the loyalty link orderNumber (for RON + LOY).
     */
    public function getLoyaltyLink(): ?string
    {
        return $this->raw['loyaltyLink'] ?? null;
    }

    /**
     * Get the payment status.
     */
    public function getStatus(): ?string
    {
        return $this->raw['status'] ?? null;
    }

    /**
     * Get the registration date.
     */
    public function getDate(): ?string
    {
        return $this->raw['date'] ?? null;
    }

    /**
     * Get the payment date.
     */
    public function getPaymentDate(): ?string
    {
        return $this->raw['paymentDate'] ?? null;
    }

    /**
     * Get the merchant full name.
     */
    public function getMerchantFullName(): ?string
    {
        return $this->raw['merchantFullName'] ?? null;
    }

    /**
     * Get the issuing bank name.
     */
    public function getBankName(): ?string
    {
        return $this->raw['bankName'] ?? null;
    }

    /**
     * Get the masked card PAN.
     */
    public function getMaskedPan(): ?string
    {
        return $this->raw['maskedPan'] ?? null;
    }

    /**
     * Get the card country code.
     */
    public function getPanCountryCode(): ?string
    {
        return $this->raw['panCountryCode'] ?? null;
    }

    /**
     * Check if this response indicates a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this->getActionCode() === 0;
    }
}

<?php

namespace AndreiGhioc\BtiPay\Responses;

use AndreiGhioc\BtiPay\Enums\OrderStatus;

class OrderStatusResponse extends BaseResponse
{
    /**
     * Get the merchant order number.
     */
    public function getOrderNumber(): ?string
    {
        return $this->raw['orderNumber'] ?? null;
    }

    /**
     * Get the order status numeric value.
     */
    public function getOrderStatus(): ?int
    {
        return isset($this->raw['orderStatus']) ? (int) $this->raw['orderStatus'] : null;
    }

    /**
     * Get the order status as an enum.
     */
    public function getOrderStatusEnum(): ?OrderStatus
    {
        $status = $this->getOrderStatus();

        return $status !== null ? OrderStatus::tryFrom($status) : null;
    }

    /**
     * Get the order status label.
     */
    public function getOrderStatusLabel(): ?string
    {
        $enum = $this->getOrderStatusEnum();

        return $enum?->label();
    }

    /**
     * Check if the transaction was successful (paid/deposited).
     */
    public function isPaid(): bool
    {
        return $this->getOrderStatus() === OrderStatus::DEPOSITED->value;
    }

    /**
     * Check if the transaction is pre-authorized (2-phase).
     */
    public function isPreAuthorized(): bool
    {
        return $this->getOrderStatus() === OrderStatus::APPROVED->value;
    }

    /**
     * Check if the transaction was declined.
     */
    public function isDeclined(): bool
    {
        return $this->getOrderStatus() === OrderStatus::DECLINED->value;
    }

    /**
     * Check if the transaction was refunded (fully).
     */
    public function isRefunded(): bool
    {
        return $this->getOrderStatus() === OrderStatus::REFUNDED->value;
    }

    /**
     * Check if the transaction was partially refunded.
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->getOrderStatus() === OrderStatus::PARTIALLY_REFUNDED->value;
    }

    /**
     * Check if the transaction was reversed.
     */
    public function isReversed(): bool
    {
        return $this->getOrderStatus() === OrderStatus::REVERSED->value;
    }

    /**
     * Check if the payment is still pending (registered but not paid).
     */
    public function isPending(): bool
    {
        return $this->getOrderStatus() === OrderStatus::CREATED->value;
    }

    /**
     * Get the action code.
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
     * Get the payment way (CARD / CARD_BINDING / BT_PAY).
     */
    public function getPaymentWay(): ?string
    {
        return $this->raw['paymentWay'] ?? null;
    }

    /**
     * Get the amount in minor currency units.
     */
    public function getAmount(): ?int
    {
        return isset($this->raw['amount']) ? (int) $this->raw['amount'] : null;
    }

    /**
     * Get the amount formatted in major currency units (e.g. RON).
     */
    public function getAmountFormatted(): ?float
    {
        $amount = $this->getAmount();

        return $amount !== null ? $amount / 100 : null;
    }

    /**
     * Get the currency code.
     */
    public function getCurrency(): ?string
    {
        return $this->raw['currency'] ?? null;
    }

    /**
     * Get the order registration date as a timestamp.
     */
    public function getDate(): ?int
    {
        return isset($this->raw['date']) ? (int) $this->raw['date'] : null;
    }

    /**
     * Get the order registration date as a Carbon instance.
     */
    public function getDateCarbon(): ?\Carbon\Carbon
    {
        $timestamp = $this->getDate();

        return $timestamp !== null ? \Carbon\Carbon::createFromTimestampMs($timestamp) : null;
    }

    /**
     * Get the order description.
     */
    public function getOrderDescription(): ?string
    {
        return $this->raw['orderDescription'] ?? null;
    }

    /**
     * Get the customer IP address.
     */
    public function getIp(): ?string
    {
        return $this->raw['ip'] ?? null;
    }

    /**
     * Get card authentication info.
     */
    public function getCardAuthInfo(): array
    {
        return $this->raw['cardAuthInfo'] ?? [];
    }

    /**
     * Get the masked card PAN.
     */
    public function getMaskedPan(): ?string
    {
        return $this->getCardAuthInfo()['pan'] ?? null;
    }

    /**
     * Get the card expiration date (YYYYMM format).
     */
    public function getCardExpiration(): ?string
    {
        return $this->getCardAuthInfo()['expiration'] ?? null;
    }

    /**
     * Get the cardholder name.
     */
    public function getCardholderName(): ?string
    {
        return $this->getCardAuthInfo()['cardholderName'] ?? null;
    }

    /**
     * Get the approval code.
     */
    public function getApprovalCode(): ?string
    {
        return $this->getCardAuthInfo()['approvalCode'] ?? null;
    }

    /**
     * Get payment amount info.
     */
    public function getPaymentAmountInfo(): array
    {
        return $this->raw['paymentAmountInfo'] ?? [];
    }

    /**
     * Get the approved amount.
     */
    public function getApprovedAmount(): ?int
    {
        return isset($this->getPaymentAmountInfo()['approvedAmount'])
            ? (int) $this->getPaymentAmountInfo()['approvedAmount']
            : null;
    }

    /**
     * Get the deposited amount.
     */
    public function getDepositedAmount(): ?int
    {
        return isset($this->getPaymentAmountInfo()['depositedAmount'])
            ? (int) $this->getPaymentAmountInfo()['depositedAmount']
            : null;
    }

    /**
     * Get the refunded amount.
     */
    public function getRefundedAmount(): ?int
    {
        return isset($this->getPaymentAmountInfo()['refundedAmount'])
            ? (int) $this->getPaymentAmountInfo()['refundedAmount']
            : null;
    }

    /**
     * Get payment state string (e.g., DEPOSITED, APPROVED, DECLINED).
     */
    public function getPaymentState(): ?string
    {
        return $this->getPaymentAmountInfo()['paymentState'] ?? null;
    }

    /**
     * Get bank info.
     */
    public function getBankInfo(): array
    {
        return $this->raw['bankInfo'] ?? [];
    }

    /**
     * Get the auth reference number (RRN).
     */
    public function getAuthRefNum(): ?string
    {
        return $this->raw['authRefNum'] ?? null;
    }

    /**
     * Get the terminal ID.
     */
    public function getTerminalId(): ?string
    {
        return $this->raw['terminalId'] ?? null;
    }

    /**
     * Get the authorization date/time.
     */
    public function getAuthDateTime(): ?int
    {
        return isset($this->raw['authDateTime']) ? (int) $this->raw['authDateTime'] : null;
    }

    /**
     * Get binding info (for saved cards / network tokens).
     */
    public function getBindingInfo(): array
    {
        return $this->raw['bindingInfo'] ?? [];
    }

    /**
     * Get the merchant order params.
     */
    public function getMerchantOrderParams(): array
    {
        return $this->raw['merchantOrderParams'] ?? [];
    }

    /**
     * Get attributes list.
     */
    public function getAttributes(): array
    {
        return $this->raw['attributes'] ?? [];
    }

    /**
     * Get refund entries.
     */
    public function getRefunds(): array
    {
        return $this->raw['refunds'] ?? [];
    }

    /**
     * Check if chargeback flag is present.
     */
    public function isChargeback(): bool
    {
        return (bool) ($this->raw['chargeback'] ?? false);
    }

    /**
     * Get the ECI value for 3DSecure.
     */
    public function getEci(): ?int
    {
        $secureAuth = $this->getCardAuthInfo()['secureAuthInfo'] ?? [];

        return isset($secureAuth['eci']) ? (int) $secureAuth['eci'] : null;
    }

    /**
     * Get the order bundle details.
     */
    public function getOrderBundle(): array
    {
        return $this->raw['orderBundle'] ?? [];
    }

    /**
     * Get a human-readable error message for the action code.
     * Covers the 22 most common errors required by BT iPay documentation.
     */
    public function getActionCodeMessage(): ?string
    {
        $code = $this->getActionCode();

        if ($code === null || $code === 0) {
            return null;
        }

        return ActionCodeMessages::getMessage($code);
    }
}

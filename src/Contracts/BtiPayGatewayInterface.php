<?php

namespace AndreiGhioc\BtiPay\Contracts;

use AndreiGhioc\BtiPay\Responses\RegisterResponse;
use AndreiGhioc\BtiPay\Responses\DepositResponse;
use AndreiGhioc\BtiPay\Responses\ReverseResponse;
use AndreiGhioc\BtiPay\Responses\RefundResponse;
use AndreiGhioc\BtiPay\Responses\OrderStatusResponse;
use AndreiGhioc\BtiPay\Responses\FinishedPaymentInfoResponse;

interface BtiPayGatewayInterface
{
    public function register(array $params): RegisterResponse;
    public function registerPreAuth(array $params): RegisterResponse;
    public function deposit(string $orderId, int $amount, bool $depositLoyalty = false): DepositResponse;
    public function reverse(string $orderId, bool $reverseLoyalty = false): ReverseResponse;
    public function refund(string $orderId, int $amount, bool $refundLoyalty = false): RefundResponse;
    public function getOrderStatus(?string $orderId = null, ?string $orderNumber = null, bool $includeCardArt = false): OrderStatusResponse;
    public function getFinishedPaymentInfo(string $orderId, string $token, ?string $language = null): FinishedPaymentInfoResponse;
    public function getPaymentUrl(string $orderNumber, int $amount, ?string $returnUrl = null, array $options = []): string;
}

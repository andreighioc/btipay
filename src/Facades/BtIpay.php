<?php

namespace AndreiGhioc\BtiPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AndreiGhioc\BtiPay\Responses\RegisterResponse register(array $params)
 * @method static \AndreiGhioc\BtiPay\Responses\RegisterResponse registerPreAuth(array $params)
 * @method static \AndreiGhioc\BtiPay\Responses\DepositResponse deposit(string $orderId, int $amount, bool $depositLoyalty = false)
 * @method static \AndreiGhioc\BtiPay\Responses\ReverseResponse reverse(string $orderId, bool $reverseLoyalty = false)
 * @method static \AndreiGhioc\BtiPay\Responses\RefundResponse refund(string $orderId, int $amount, bool $refundLoyalty = false)
 * @method static \AndreiGhioc\BtiPay\Responses\OrderStatusResponse getOrderStatus(string $orderId = null, string $orderNumber = null, bool $includeCardArt = false)
 * @method static \AndreiGhioc\BtiPay\Responses\FinishedPaymentInfoResponse getFinishedPaymentInfo(string $orderId, string $token, string $language = null)
 * @method static string getPaymentUrl(string $orderNumber, int $amount, string $returnUrl = null, array $options = [])
 *
 * @see \AndreiGhioc\BtiPay\BtiPay
 */
class BtiPay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'btipay';
    }
}

<?php

namespace BtIpay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use BtIpay\Laravel\BtIpayGateway;

/**
 * @method static \BtIpay\Laravel\Responses\RegisterResponse register(array $params)
 * @method static \BtIpay\Laravel\Responses\RegisterResponse registerPreAuth(array $params)
 * @method static \BtIpay\Laravel\Responses\DepositResponse deposit(string $orderId, int $amount, bool $depositLoyalty = false)
 * @method static \BtIpay\Laravel\Responses\ReverseResponse reverse(string $orderId, bool $reverseLoyalty = false)
 * @method static \BtIpay\Laravel\Responses\RefundResponse refund(string $orderId, int $amount, bool $refundLoyalty = false)
 * @method static \BtIpay\Laravel\Responses\OrderStatusResponse getOrderStatus(string $orderId = null, string $orderNumber = null, bool $includeCardArt = false)
 * @method static \BtIpay\Laravel\Responses\FinishedPaymentInfoResponse getFinishedPaymentInfo(string $orderId, string $token, string $language = null)
 * @method static string getPaymentUrl(string $orderNumber, int $amount, string $returnUrl = null, array $options = [])
 *
 * @see \BtIpay\Laravel\BtIpayGateway
 */
class BtIpay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'btipay';
    }
}

<?php

namespace AndreiGhioc\BtiPay;

use AndreiGhioc\BtiPay\Enums\Currency;
use AndreiGhioc\BtiPay\Enums\OrderStatus;
use AndreiGhioc\BtiPay\Responses\RegisterResponse;
use AndreiGhioc\BtiPay\Responses\DepositResponse;
use AndreiGhioc\BtiPay\Responses\ReverseResponse;
use AndreiGhioc\BtiPay\Responses\RefundResponse;
use AndreiGhioc\BtiPay\Responses\OrderStatusResponse;
use AndreiGhioc\BtiPay\Responses\FinishedPaymentInfoResponse;
use AndreiGhioc\BtiPay\Exceptions\BtiPayValidationException;
use AndreiGhioc\BtiPay\Exceptions\IncompleteBtiPayConfigurationException;
use AndreiGhioc\BtiPay\Contracts\BtiPayGatewayInterface;

class BtiPay implements BtiPayGatewayInterface
{
    protected BtiPayClient $client;
    protected array $config;

    public function __construct(array $config = [])
    {
        if (empty($config['username']) || empty($config['password'])) {
            throw new IncompleteBtiPayConfigurationException('BT iPay Username and Password must be provided correctly in the config/env.');
        }

        $this->config = $config;
        $this->client = new BtiPayClient(
            $config['username'] ?? '',
            $config['password'] ?? '',
            $config['environment'] ?? 'sandbox',
            $config['auth_method'] ?? 'header',
            $config['http'] ?? []
        );
    }

    /**
     * Register a 1-phase payment order.
     * The payment is automatically deposited upon success.
     *
     * @param  array $params Required: orderNumber, amount. Optional: currency, returnUrl, description, etc.
     * @return RegisterResponse
     *
     * @throws BtiPayValidationException
     */
    public function register(array $params): RegisterResponse
    {
        $this->validateRegisterParams($params);
        $requestData = $this->buildRegisterParams($params);

        $response = $this->client->post(BtiPayClient::ENDPOINT_REGISTER, $requestData);

        return new RegisterResponse($response);
    }

    /**
     * Register a 2-phase pre-authorization payment order.
     * Funds are blocked and require explicit deposit to capture.
     *
     * @param  array $params Required: orderNumber, amount. Optional: currency, returnUrl, description, etc.
     * @return RegisterResponse
     *
     * @throws BtiPayValidationException
     */
    public function registerPreAuth(array $params): RegisterResponse
    {
        $this->validateRegisterParams($params);
        $requestData = $this->buildRegisterParams($params);

        $response = $this->client->post(BtiPayClient::ENDPOINT_REGISTER_PRE_AUTH, $requestData);

        return new RegisterResponse($response);
    }

    /**
     * Capture (deposit) a pre-authorized 2-phase payment.
     * Can only be done once. Amount can be less than or equal to the pre-authorized amount.
     *
     * @param  string $orderId        The UUID order ID from iPay
     * @param  int    $amount         Amount in minor currency units (e.g. bani)
     * @param  bool   $depositLoyalty Capture both RON and LOY transactions
     * @return DepositResponse
     */
    public function deposit(string $orderId, int $amount, bool $depositLoyalty = false): DepositResponse
    {
        $params = [
            'orderId' => $orderId,
            'amount'  => $amount,
        ];

        if ($depositLoyalty) {
            $params['depositLoyalty'] = 'true';
        }

        $response = $this->client->post(BtiPayClient::ENDPOINT_DEPOSIT, $params);

        return new DepositResponse($response);
    }

    /**
     * Reverse (cancel) a pre-authorized 2-phase payment.
     * Only available for orders with APPROVED status, within 24 hours.
     *
     * @param  string $orderId        The UUID order ID from iPay
     * @param  bool   $reverseLoyalty Reverse both RON and LOY transactions
     * @return ReverseResponse
     */
    public function reverse(string $orderId, bool $reverseLoyalty = false): ReverseResponse
    {
        $params = [
            'orderId' => $orderId,
        ];

        if ($reverseLoyalty) {
            $params['reverseLoyalty'] = 'true';
        }

        $response = $this->client->post(BtiPayClient::ENDPOINT_REVERSE, $params);

        return new ReverseResponse($response);
    }

    /**
     * Refund a deposited payment (partial or full).
     * Multiple partial refunds are allowed, but total cannot exceed the deposited amount.
     *
     * @param  string $orderId       The UUID order ID from iPay
     * @param  int    $amount        Amount to refund in minor currency units
     * @param  bool   $refundLoyalty Refund both RON and LOY transactions
     * @return RefundResponse
     */
    public function refund(string $orderId, int $amount, bool $refundLoyalty = false): RefundResponse
    {
        $params = [
            'orderId' => $orderId,
            'amount'  => $amount,
        ];

        if ($refundLoyalty) {
            $params['refundLoyalty'] = 'true';
        }

        $response = $this->client->post(BtiPayClient::ENDPOINT_REFUND, $params);

        return new RefundResponse($response);
    }

    /**
     * Get extended order status and transaction details.
     *
     * @param  string|null $orderId       The UUID order ID from iPay
     * @param  string|null $orderNumber   The merchant's order number
     * @param  bool        $includeCardArt Include card art in response
     * @return OrderStatusResponse
     *
     * @throws BtiPayValidationException
     */
    public function getOrderStatus(
        ?string $orderId = null,
        ?string $orderNumber = null,
        bool $includeCardArt = false
    ): OrderStatusResponse {
        if (empty($orderId) && empty($orderNumber)) {
            throw new BtiPayValidationException(
                'Either orderId or orderNumber must be provided.'
            );
        }

        $params = [];

        if ($orderId) {
            $params['orderId'] = $orderId;
        }

        if ($orderNumber) {
            $params['orderNumber'] = $orderNumber;
        }

        if ($includeCardArt) {
            $params['includeCardArt'] = 'true';
        }

        $response = $this->client->post(BtiPayClient::ENDPOINT_ORDER_STATUS, $params);

        return new OrderStatusResponse($response);
    }

    /**
     * Get finished payment info (for payment link payments from iPay console).
     * This endpoint does NOT require authentication.
     *
     * @param  string      $orderId  The UUID order ID
     * @param  string      $token    Temporary token (valid for 10 minutes)
     * @param  string|null $language ISO 639-1 language code
     * @return FinishedPaymentInfoResponse
     */
    public function getFinishedPaymentInfo(
        string $orderId,
        string $token,
        ?string $language = null
    ): FinishedPaymentInfoResponse {
        $params = [
            'orderId' => $orderId,
            'token'   => $token,
        ];

        if ($language) {
            $params['language'] = $language;
        }

        $response = $this->client->post(
            BtiPayClient::ENDPOINT_FINISHED_PAYMENT_INFO,
            $params,
            false // This endpoint does not require authentication
        );

        return new FinishedPaymentInfoResponse($response);
    }

    /**
     * Convenience method: Register a payment and return the payment URL.
     *
     * @param  string      $orderNumber Unique order number in merchant system
     * @param  int         $amount      Amount in minor currency units
     * @param  string|null $returnUrl   Override the default return URL
     * @param  array       $options     Additional options (description, email, orderBundle, etc.)
     * @return string The payment form URL to redirect the customer to
     *
     * @throws BtiPayValidationException
     */
    public function getPaymentUrl(
        string $orderNumber,
        int $amount,
        ?string $returnUrl = null,
        array $options = []
    ): string {
        $params = array_merge($options, [
            'orderNumber' => $orderNumber,
            'amount'      => $amount,
        ]);

        if ($returnUrl) {
            $params['returnUrl'] = $returnUrl;
        }

        $paymentType = config('btipay.payment_type', '1phase');

        $response = $paymentType === '2phase'
            ? $this->registerPreAuth($params)
            : $this->register($params);

        if (! $response->isSuccessful()) {
            throw new BtiPayValidationException(
                'Payment registration failed: ' . $response->getErrorMessage()
            );
        }

        return $response->getFormUrl();
    }

    /**
     * Validate required params for register/registerPreAuth.
     *
     * @throws BtiPayValidationException
     */
    protected function validateRegisterParams(array $params): void
    {
        if (empty($params['orderNumber'])) {
            throw new BtiPayValidationException('orderNumber is required.');
        }

        if (! isset($params['amount']) || $params['amount'] <= 0) {
            throw new BtiPayValidationException('amount must be a positive integer (in minor currency units).');
        }

        $returnUrl = $params['returnUrl'] ?? config('btipay.return_url');
        if (empty($returnUrl)) {
            throw new BtiPayValidationException(
                'returnUrl is required. Set it in the request params or in BtiPay.return_url config.'
            );
        }
    }

    /**
     * Build the request params for register/registerPreAuth,
     * applying defaults from config.
     */
    protected function buildRegisterParams(array $params): array
    {
        $data = [
            'orderNumber' => $params['orderNumber'],
            'amount'      => $params['amount'],
            'currency'    => $params['currency'] ?? config('btipay.currency', 946),
            'returnUrl'   => $params['returnUrl'] ?? config('btipay.return_url'),
        ];

        // Optional string parameters
        $optionalStrings = [
            'description', 'language', 'pageView', 'email',
            'childId', 'clientId', 'bindingId', 'jsonParams',
        ];

        foreach ($optionalStrings as $key) {
            if (! empty($params[$key])) {
                $data[$key] = $params[$key];
            }
        }

        // Apply defaults from config
        if (empty($data['language']) && config('btipay.language')) {
            $data['language'] = config('btipay.language');
        }

        if (empty($data['pageView']) && config('btipay.page_view')) {
            $data['pageView'] = config('btipay.page_view');
        }

        // Session timeout
        if (! empty($params['sessionTimeoutSecs'])) {
            $data['sessionTimeoutSecs'] = $params['sessionTimeoutSecs'];
        }

        if (! empty($params['expirationDate'])) {
            $data['expirationDate'] = $params['expirationDate'];
        }

        // Order bundle (JSON)
        if (! empty($params['orderBundle'])) {
            $orderBundle = $params['orderBundle'];
            if (is_array($orderBundle)) {
                $data['orderBundle'] = json_encode($orderBundle, JSON_UNESCAPED_UNICODE);
            } else {
                $data['orderBundle'] = $orderBundle;
            }
        }

        return $data;
    }
}

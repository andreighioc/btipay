<?php

namespace BtIpay\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use BtIpay\Laravel\Exceptions\BtIpayException;
use BtIpay\Laravel\Exceptions\BtIpayAuthenticationException;
use BtIpay\Laravel\Exceptions\BtIpayConnectionException;

class BtIpayClient
{
    protected Client $httpClient;
    protected string $username;
    protected string $password;
    protected string $environment;
    protected string $authMethod;
    protected string $baseUrl;

    /**
     * Base URLs for each environment.
     */
    protected const BASE_URLS = [
        'sandbox'    => 'https://ecclients-sandbox.btrl.ro',
        'production' => 'https://ecclients.btrl.ro',
    ];

    /**
     * API endpoint paths.
     */
    public const ENDPOINT_REGISTER            = '/payment/rest/register.do';
    public const ENDPOINT_REGISTER_PRE_AUTH    = '/payment/rest/registerPreAuth.do';
    public const ENDPOINT_DEPOSIT              = '/payment/rest/deposit.do';
    public const ENDPOINT_REVERSE              = '/payment/rest/reverse.do';
    public const ENDPOINT_REFUND               = '/payment/rest/refund.do';
    public const ENDPOINT_ORDER_STATUS         = '/payment/rest/getOrderStatusExtended.do';
    public const ENDPOINT_FINISHED_PAYMENT_INFO = '/payment/rest/getFinishedPaymentInfo.do';

    public function __construct(
        string $username,
        string $password,
        string $environment = 'sandbox',
        string $authMethod = 'header',
        array  $httpConfig = []
    ) {
        $this->username    = $username;
        $this->password    = $password;
        $this->environment = $environment;
        $this->authMethod  = $authMethod;

        $customBaseUrls = config('btipay.base_urls', []);
        $baseUrls = array_merge(self::BASE_URLS, $customBaseUrls);
        $this->baseUrl = $baseUrls[$environment] ?? self::BASE_URLS['sandbox'];

        $this->httpClient = new Client([
            'base_uri'        => $this->baseUrl,
            'timeout'         => $httpConfig['timeout'] ?? 30,
            'connect_timeout' => $httpConfig['connect_timeout'] ?? 10,
            'verify'          => $httpConfig['verify_ssl'] ?? true,
        ]);
    }

    /**
     * Send a POST request to the specified endpoint.
     *
     * @param  string $endpoint  The API endpoint path
     * @param  array  $params    Request parameters
     * @param  bool   $requiresAuth Whether the endpoint requires authentication
     * @return array  Decoded JSON response
     *
     * @throws BtIpayException
     * @throws BtIpayAuthenticationException
     * @throws BtIpayConnectionException
     */
    public function post(string $endpoint, array $params = [], bool $requiresAuth = true): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        if ($requiresAuth) {
            if ($this->authMethod === 'header') {
                // Recommended: Basic Auth header
                $credentials = base64_encode($this->username . ':' . $this->password);
                $options['headers']['Authorization'] = 'Basic ' . $credentials;
            } else {
                // Legacy: add credentials in body
                $params['userName'] = $this->username;
                $params['password'] = $this->password;
            }
        }

        $options['form_params'] = $params;

        $this->logRequest($endpoint, $params);

        try {
            $response = $this->httpClient->post($endpoint, $options);
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BtIpayException(
                    'Invalid JSON response from BT iPay API: ' . json_last_error_msg()
                );
            }

            $this->logResponse($endpoint, $decoded);

            return $decoded;
        } catch (GuzzleException $e) {
            $this->logError($endpoint, $e->getMessage());

            if ($e->getCode() === 401 || $e->getCode() === 403) {
                throw new BtIpayAuthenticationException(
                    'Authentication failed: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }

            throw new BtIpayConnectionException(
                'Connection error to BT iPay API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the base URL for the current environment.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the current environment.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if we are in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    /**
     * Log the API request if logging is enabled.
     */
    protected function logRequest(string $endpoint, array $params): void
    {
        if (! config('btipay.logging.enabled', false)) {
            return;
        }

        // Mask sensitive data before logging
        $safeParams = $params;
        foreach (['userName', 'password'] as $key) {
            if (isset($safeParams[$key])) {
                $safeParams[$key] = '***';
            }
        }

        Log::channel(config('btipay.logging.channel', 'stack'))
            ->info('BT iPay Request', [
                'endpoint' => $endpoint,
                'params'   => $safeParams,
            ]);
    }

    /**
     * Log the API response if logging is enabled.
     */
    protected function logResponse(string $endpoint, array $response): void
    {
        if (! config('btipay.logging.enabled', false)) {
            return;
        }

        Log::channel(config('btipay.logging.channel', 'stack'))
            ->info('BT iPay Response', [
                'endpoint' => $endpoint,
                'response' => $response,
            ]);
    }

    /**
     * Log API error if logging is enabled.
     */
    protected function logError(string $endpoint, string $message): void
    {
        if (! config('btipay.logging.enabled', false)) {
            return;
        }

        Log::channel(config('btipay.logging.channel', 'stack'))
            ->error('BT iPay Error', [
                'endpoint' => $endpoint,
                'message'  => $message,
            ]);
    }
}

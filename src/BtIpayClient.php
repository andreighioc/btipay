<?php

namespace AndreiGhioc\BtiPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use AndreiGhioc\BtiPay\Exceptions\BtiPayException;
use AndreiGhioc\BtiPay\Exceptions\BtiPayAuthenticationException;
use AndreiGhioc\BtiPay\Exceptions\BtiPayConnectionException;

class BtiPayClient
{
    protected string $username;
    protected string $password;
    protected string $environment;
    protected string $authMethod;
    protected string $baseUrl;
    protected array  $httpConfig;

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

        $this->httpConfig = array_merge([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'verify'          => true,
        ], $httpConfig);
    }

    /**
     * Send a POST request to the specified endpoint.
     *
     * @param  string $endpoint  The API endpoint path
     * @param  array  $params    Request parameters
     * @param  bool   $requiresAuth Whether the endpoint requires authentication
     * @return array  Decoded JSON response
     *
     * @throws BtiPayException
     * @throws BtiPayAuthenticationException
     * @throws BtiPayConnectionException
     */
    public function post(string $endpoint, array $params = [], bool $requiresAuth = true): array
    {
        $request = Http::baseUrl($this->baseUrl)
            ->withOptions($this->httpConfig)
            ->acceptJson()
            ->asForm();

        if ($requiresAuth) {
            if ($this->authMethod === 'header') {
                $request->withBasicAuth($this->username, $this->password);
            } else {
                $params['userName'] = $this->username;
                $params['password'] = $this->password;
            }
        }

        $this->logRequest($endpoint, $params);

        try {
            $response = $request->post($endpoint, $params);

            if ($response->status() === 401 || $response->status() === 403) {
                // iPay specific access denied check
                throw new BtiPayAuthenticationException(
                    'Authentication failed: access denied (401/403) - ' . $response->body(),
                    $response->status()
                );
            }

            // Throw on 500s or standard 400s
            if ($response->serverError() || $response->clientError()) {
                $response->throw();
            }

            $decoded = $response->json();

            if (!is_array($decoded)) {
                throw new BtiPayException(
                    'Invalid or malformed JSON response from BT iPay API: ' . $response->body()
                );
            }

            $this->logResponse($endpoint, $decoded);

            return $decoded;
        } catch (RequestException $e) {
            $this->logError($endpoint, $e->getMessage());
            throw new BtiPayConnectionException(
                'HTTP error from BT iPay API: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                $e
            );
        } catch (ConnectionException $e) {
            $this->logError($endpoint, $e->getMessage());
            throw new BtiPayConnectionException(
                'Connection error to BT iPay API: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                $e
            );
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    protected function logRequest(string $endpoint, array $params): void
    {
        if (! config('btipay.logging', false)) {
            return;
        }

        $safeParams = $params;
        foreach (['userName', 'password', 'pan', 'cvv'] as $key) {
            if (isset($safeParams[$key])) {
                $safeParams[$key] = '***';
            }
        }

        Log::channel(config('btipay.log_channel', 'stack'))
            ->info('BT iPay Request', [
                'endpoint' => $endpoint,
                'params'   => $safeParams,
            ]);
    }

    protected function logResponse(string $endpoint, array $response): void
    {
        if (! config('btipay.logging', false)) {
            return;
        }

        Log::channel(config('btipay.log_channel', 'stack'))
            ->info('BT iPay Response', [
                'endpoint' => $endpoint,
                'response' => $response,
            ]);
    }

    protected function logError(string $endpoint, string $message): void
    {
        if (! config('btipay.logging', false)) {
            return;
        }

        Log::channel(config('btipay.log_channel', 'stack'))
            ->error('BT iPay Error', [
                'endpoint' => $endpoint,
                'message'  => $message,
            ]);
    }
}

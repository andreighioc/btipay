<?php

namespace AndreiGhioc\BtiPay\Responses;

abstract class BaseResponse
{
    protected array $raw;

    public function __construct(array $response)
    {
        $this->raw = $response;
    }

    /**
     * Get the raw response array.
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ?int
    {
        $code = $this->raw['errorCode'] ?? null;

        return $code !== null ? (int) $code : null;
    }

    /**
     * Get the error message.
     */
    public function getErrorMessage(): ?string
    {
        return $this->raw['errorMessage'] ?? null;
    }

    /**
     * Check if the response is successful (no errors).
     */
    public function isSuccessful(): bool
    {
        return $this->getErrorCode() === 0 || $this->getErrorCode() === null;
    }

    /**
     * Check if the response contains an error.
     */
    public function hasError(): bool
    {
        return ! $this->isSuccessful();
    }

    /**
     * Get a value from the raw response.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /**
     * Convert the response to array.
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * Convert the response to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->raw, $options);
    }
}

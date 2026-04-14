<?php

namespace AndreiGhioc\BtiPay\Builders;

use AndreiGhioc\BtiPay\Exceptions\BtiPayValidationException;

/**
 * Fluent builder for the orderBundle parameter required by register.do / registerPreAuth.do.
 *
 * Usage:
 *   $bundle = OrderBundle::make()
 *       ->orderCreationDate('2024-03-22')
 *       ->email('client@example.com')
 *       ->phone('40740123456')
 *       ->deliveryInfo('comanda', '642', 'Cluj-Napoca', 'Str. Speranței 10')
 *       ->billingInfo('642', 'Cluj-Napoca', 'Str. Speranței 10')
 *       ->toArray();
 */
class OrderBundle
{
    protected ?string $orderCreationDate = null;
    protected ?string $email = null;
    protected ?string $phone = null;
    protected ?string $contact = null;

    protected array $deliveryInfo = [];
    protected array $billingInfo = [];

    /**
     * Static factory method.
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Set the order creation date (yyyy-MM-dd format).
     */
    public function orderCreationDate(string $date): static
    {
        $this->orderCreationDate = $date;

        return $this;
    }

    /**
     * Set the customer email.
     */
    public function email(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set the customer phone (digits only, international format e.g. 40740123456).
     */
    public function phone(string $phone): static
    {
        // Strip non-numeric characters
        $this->phone = preg_replace('/\D/', '', $phone);

        return $this;
    }

    /**
     * Set the contact person.
     */
    public function contact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Set delivery information.
     *
     * @param string      $deliveryType Type of delivery
     * @param string      $country      ISO 3166-1 numeric country code (e.g. "642" for Romania)
     * @param string      $city         City name
     * @param string      $postAddress  Postal address
     * @param string|null $postalCode   Postal code
     * @param string|null $postAddress2 Additional address line
     * @param string|null $postAddress3 Additional address line
     * @param string|null $state        ISO 3166-2 state code
     */
    public function deliveryInfo(
        string $deliveryType,
        string $country,
        string $city,
        string $postAddress,
        ?string $postalCode = null,
        ?string $postAddress2 = null,
        ?string $postAddress3 = null,
        ?string $state = null
    ): static {
        $this->deliveryInfo = array_filter([
            'deliveryType' => $deliveryType,
            'country'      => $country,
            'city'         => $city,
            'postAddress'  => $postAddress,
            'postalCode'   => $postalCode,
            'postAddress2' => $postAddress2,
            'postAddress3' => $postAddress3,
            'state'        => $state,
        ], fn ($value) => $value !== null);

        return $this;
    }

    /**
     * Set billing information.
     *
     * @param string      $country      ISO 3166-1 numeric country code
     * @param string      $city         City name
     * @param string      $postAddress  Postal address
     * @param string|null $postalCode   Postal code
     * @param string|null $postAddress2 Additional address line
     * @param string|null $postAddress3 Additional address line
     * @param string|null $state        ISO 3166-2 state code
     */
    public function billingInfo(
        string $country,
        string $city,
        string $postAddress,
        ?string $postalCode = null,
        ?string $postAddress2 = null,
        ?string $postAddress3 = null,
        ?string $state = null
    ): static {
        $this->billingInfo = array_filter([
            'country'      => $country,
            'city'         => $city,
            'postAddress'  => $postAddress,
            'postalCode'   => $postalCode,
            'postAddress2' => $postAddress2,
            'postAddress3' => $postAddress3,
            'state'        => $state,
        ], fn ($value) => $value !== null);

        return $this;
    }

    /**
     * Build the minimum required orderBundle (without customer details).
     * Use this when you don't have real customer data.
     */
    public function minimal(string $country = '642', string $city = 'N/A', string $address = 'N/A'): static
    {
        $this->deliveryInfo('standard', $country, $city, $address);
        $this->billingInfo($country, $city, $address);

        return $this;
    }

    /**
     * Convert the builder to an array.
     *
     * @throws BtiPayValidationException
     */
    public function toArray(): array
    {
        $bundle = [];

        if ($this->orderCreationDate) {
            $bundle['orderCreationDate'] = $this->orderCreationDate;
        }

        $customerDetails = [];

        if ($this->email) {
            $customerDetails['email'] = $this->email;
        }

        if ($this->phone) {
            $customerDetails['phone'] = $this->phone;
        }

        if ($this->contact) {
            $customerDetails['contact'] = $this->contact;
        }

        if (! empty($this->deliveryInfo)) {
            $customerDetails['deliveryInfo'] = $this->deliveryInfo;
        }

        if (! empty($this->billingInfo)) {
            $customerDetails['billingInfo'] = $this->billingInfo;
        }

        if (! empty($customerDetails)) {
            $bundle['customerDetails'] = $customerDetails;
        }

        return $bundle;
    }

    /**
     * Convert the builder to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}

# BT iPay - Laravel Package

> 🇷🇴 [Versiunea în română](README_RO.md)

Laravel package for integrating with the **Banca Transilvania iPay** payment platform.

Supports **1-Phase** payments (automatic capture) and **2-Phase** payments (pre-authorization + manual deposit), refunds, reversals, transaction status verification, and loyalty point payments (StarBT).

## Requirements

- PHP 8.1+ (Laravel 13 requires PHP 8.3+)
- Laravel 10, 11, 12 or 13
- API credentials from Banca Transilvania

## Installation

```bash
composer require BtiPay/laravel
```

Full installation (config, migrations, controller, routes, views):

```bash
php artisan BtiPay:install
php artisan migrate
```

The `BtiPay:install` command creates:
- `config/BtiPay.php` — configuration
- `database/migrations/` — `BtiPay_transactions` table
- `app/Http/Controllers/BtiPayController.php` — complete controller with `pay`, `process`, `finish`
- `routes/BtiPay.php` — web routes (`/BtiPay/pay`, `/BtiPay/process`, `/BtiPay/finish`)
- `resources/views/BtiPay/` — Blade views (`pay.blade.php`, `finish.blade.php`)

Optionally, publish only what you need:

```bash
php artisan BtiPay:install --controller   # controller only
php artisan BtiPay:install --routes       # routes only
php artisan BtiPay:install --views        # views only
php artisan BtiPay:install --force        # overwrite existing files
```

After installation, include the routes in your app. In `routes/web.php`:

```php
require __DIR__.'/BtiPay.php';
```

Or in `bootstrap/app.php` (Laravel 11+):

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    then: function () {
        require base_path('routes/BtiPay.php');
    },
)
```

## Configuration

Add to your `.env`:

```env
BtiPay_ENVIRONMENT=sandbox
BtiPay_USERNAME=your_api_username
BtiPay_PASSWORD=your_api_password
BtiPay_AUTH_METHOD=header
BtiPay_RETURN_URL=https://your-site.com/BtiPay/finish
BtiPay_CURRENCY=946
BtiPay_LANGUAGE=ro
BtiPay_PAYMENT_TYPE=1phase
BtiPay_LOGGING=true
```

### Available Environments

| Environment | Description |
|---|---|
| `sandbox` | Test environment (https://ecclients-sandbox.btrl.ro) |
| `production` | Production environment (https://ecclients.btrl.ro) |

### Supported Currencies (ISO 4217)

| Currency | Code |
|---|---|
| RON | 946 |
| EUR | 978 |
| USD | 840 |

## Usage

### 1. Simple Payment (1-Phase)

```php
use BtiPay\Laravel\Facades\BtiPay;
use BtiPay\Laravel\Builders\OrderBundle;

// Build orderBundle
$bundle = OrderBundle::make()
    ->orderCreationDate(now()->format('Y-m-d'))
    ->email('client@example.com')
    ->phone('40740123456')
    ->deliveryInfo('delivery', '642', 'Cluj-Napoca', 'Str. Example 10', '400000')
    ->billingInfo('642', 'Cluj-Napoca', 'Str. Example 10', '400000');

// Register order
$response = BtiPay::register([
    'orderNumber'  => 'ORD-' . time(),
    'amount'       => 1500, // 15.00 RON (in minor units / bani)
    'currency'     => 946,
    'returnUrl'    => route('BtiPay.finish'),
    'description'  => 'Order #123',
    'email'        => 'client@example.com',
    'orderBundle'  => $bundle->toArray(),
]);

if ($response->isSuccessful()) {
    // Redirect to the BT payment page
    return redirect($response->getFormUrl());
} else {
    // Registration error
    echo $response->getErrorMessage();
}
```

### 2. Pre-Authorized Payment (2-Phase)

```php
// Register pre-authorization
$response = BtiPay::registerPreAuth([
    'orderNumber'  => 'ORD-' . time(),
    'amount'       => 5000, // 50.00 RON
    'returnUrl'    => route('BtiPay.finish'),
    'description'  => 'Delivery order #456',
    'orderBundle'  => $bundle->toArray(),
]);

// Redirect customer to formUrl...

// --- Later, upon delivery: Capture (deposit) ---
$depositResponse = BtiPay::deposit(
    orderId: $response->getOrderId(),
    amount: 5000
);

if ($depositResponse->isSuccessful()) {
    echo 'Payment captured successfully!';
}
```

### 3. Reversal (Cancel Pre-Authorization)

```php
$reverseResponse = BtiPay::reverse(
    orderId: 'uuid-order-id'
);
```

### 4. Refund

```php
// Partial refund
$refundResponse = BtiPay::refund(
    orderId: 'uuid-order-id',
    amount: 500 // Refund 5.00 RON
);

// Full refund
$refundResponse = BtiPay::refund(
    orderId: 'uuid-order-id',
    amount: 5000 // Refund full amount
);
```

### 5. Transaction Status Check

```php
$status = BtiPay::getOrderStatus(orderId: 'uuid-order-id');

// or by orderNumber
$status = BtiPay::getOrderStatus(orderNumber: 'ORD-123');

if ($status->isPaid()) {
    echo 'Transaction completed successfully!';
    echo 'Amount: ' . $status->getAmountFormatted() . ' RON';
    echo 'Card: ' . $status->getMaskedPan();
} elseif ($status->isDeclined()) {
    echo 'Transaction declined: ' . $status->getActionCodeMessage();
}
```

### 6. Shortcut: Get Payment URL

```php
$paymentUrl = BtiPay::getPaymentUrl(
    orderNumber: 'ORD-' . time(),
    amount: 2500,
    returnUrl: route('BtiPay.finish'),
    options: [
        'description' => 'Service payment',
        'email' => 'client@email.com',
    ]
);

return redirect($paymentUrl);
```

### 7. Finish Page (Return URL)

If you ran `php artisan BtiPay:install`, the controller and views are already created.
The route `GET /BtiPay/finish` is automatically registered as `BtiPay.finish`.

The generated controller (`BtiPayController`) automatically handles:
- Status verification via `getOrderStatusExtended.do`
- Transaction update in the database (card, amount, RRN, ECI, etc.)
- Display of all 22 required error messages
- Retry restrictions for action codes 803, 804, 913
- Event dispatch: `PaymentCompleted` / `PaymentDeclined`

For custom integration, you can use the facade directly:

```php
$status = BtiPay::getOrderStatus(orderId: $request->get('orderId'));

if ($status->isPaid()) {
    // Payment successful - card, amount, RRN available
    $status->getMaskedPan();
    $status->getAmountFormatted();
    $status->getAuthRefNum();
}

if ($status->isDeclined()) {
    $status->getActionCodeMessage(); // message in the configured language
}
```

### 8. Tracking with BtiPayTransaction Model

```php
use BtiPay\Laravel\Models\BtiPayTransaction;

// Create transaction
$transaction = BtiPayTransaction::create([
    'order_id'       => $response->getOrderId(),
    'order_number'   => 'ORD-123',
    'payment_type'   => '1phase',
    'amount'         => 1500,
    'currency'       => '946',
    'status'         => 'CREATED',
    'form_url'       => $response->getFormUrl(),
    'customer_email' => 'client@email.com',
]);

// Associate with a model (e.g. Order)
$order = Order::find(1);
$transaction->payable()->associate($order);
$transaction->save();

// Queries
BtiPayTransaction::successful()->get();     // All paid transactions
BtiPayTransaction::declined()->get();       // All declined
BtiPayTransaction::preAuthorized()->get();  // Awaiting deposit
```

### 9. Model Trait

```php
use BtiPay\Laravel\Traits\HasBtiPayPayments;

class Order extends Model
{
    use HasBtiPayPayments;
}

// Usage
$order = Order::find(1);
$order->BtiPayTransactions;          // All transactions
$order->latestBtiPayTransaction;     // Latest transaction
$order->isPaidViaBtiPay();           // Is it paid?
$order->getTotalPaidViaBtiPay();     // Total paid (in minor units)
$order->getTotalRefundedViaBtiPay(); // Total refunded
```

### 10. Loyalty Point Payments (StarBT)

```php
// Deposit with loyalty
$response = BtiPay::deposit(
    orderId: 'uuid-ron-order-id',
    amount: 3000, // Total amount RON + LOY
    depositLoyalty: true
);

// Refund with loyalty
$response = BtiPay::refund(
    orderId: 'uuid-ron-order-id',
    amount: 4000,
    refundLoyalty: true
);

// Reverse with loyalty
$response = BtiPay::reverse(
    orderId: 'uuid-ron-order-id',
    reverseLoyalty: true
);
```

## Events

The package dispatches the following events that you can listen for:

| Event | Description |
|---|---|
| `PaymentRegistered` | Payment has been registered with iPay |
| `PaymentCompleted` | Payment completed successfully (DEPOSITED) |
| `PaymentDeclined` | Payment was declined |
| `PaymentRefunded` | Refund processed (partial or full) |

```php
// EventServiceProvider.php
protected $listen = [
    \BtiPay\Laravel\Events\PaymentCompleted::class => [
        \App\Listeners\SendPaymentConfirmation::class,
    ],
    \BtiPay\Laravel\Events\PaymentDeclined::class => [
        \App\Listeners\HandleFailedPayment::class,
    ],
];
```

## Error Codes (Action Codes)

The 22 required error codes to handle per BT documentation:

| Code | Description |
|---|---|
| 104 | Restricted card |
| 124 | Transaction cannot be authorized per regulations |
| 320 | Inactive card |
| 801 | Issuer unavailable |
| 803 | Card blocked ⚠️ Do NOT retry with the same card! |
| 804 | Transaction not allowed ⚠️ Do NOT retry with the same card! |
| 805 | Transaction declined |
| 861 | Invalid expiration date |
| 871 | Invalid CVV |
| 905 | Invalid card |
| 906 | Expired card |
| 913 | Invalid transaction ⚠️ Do NOT retry with the same card! |
| 914 | Invalid account |
| 915 | Insufficient funds |
| 917 | Transaction limit exceeded |
| 952 | Suspected fraud |
| 998 | Installments not allowed with this card |
| 341016 | 3DS2 authentication declined |
| 341017 | 3DS2 status unknown |
| 341018 | 3DS2 cancelled by customer |
| 341019 | 3DS2 authentication failed |
| 341020 | 3DS2 unknown status |

## Package Structure

```
BtiPay/
├── config/
│   └── BtiPay.php                 # Configuration
├── database/
│   └── migrations/                # Transactions table migration
├── stubs/
│   ├── BtiPayController.php.stub  # Controller (published via BtiPay:install)
│   ├── BtiPay-routes.php.stub     # Routes (published via BtiPay:install)
│   └── views/
│       ├── pay.blade.php.stub     # Payment form
│       └── finish.blade.php.stub  # Finish page (success/error)
├── src/
│   ├── Builders/
│   │   └── OrderBundle.php        # Fluent builder for orderBundle
│   ├── Console/
│   │   └── InstallCommand.php     # php artisan BtiPay:install
│   ├── Enums/
│   │   ├── Currency.php           # Currency enum (RON/EUR/USD)
│   │   ├── OrderStatus.php        # Transaction status enum
│   │   └── PaymentType.php        # Payment type enum (1phase/2phase)
│   ├── Events/
│   │   ├── PaymentCompleted.php
│   │   ├── PaymentDeclined.php
│   │   ├── PaymentRefunded.php
│   │   └── PaymentRegistered.php
│   ├── Exceptions/
│   │   ├── BtiPayAuthenticationException.php
│   │   ├── BtiPayConnectionException.php
│   │   ├── BtiPayException.php
│   │   └── BtiPayValidationException.php
│   ├── Facades/
│   │   └── BtiPay.php             # Laravel Facade
│   ├── Models/
│   │   └── BtiPayTransaction.php  # Eloquent Model
│   ├── Responses/
│   │   ├── ActionCodeMessages.php  # Error messages (RO/EN)
│   │   ├── BaseResponse.php
│   │   ├── DepositResponse.php
│   │   ├── FinishedPaymentInfoResponse.php
│   │   ├── OrderStatusResponse.php
│   │   ├── RefundResponse.php
│   │   ├── RegisterResponse.php
│   │   └── ReverseResponse.php
│   ├── Traits/
│   │   └── HasBtiPayPayments.php  # Trait for models
│   ├── BtiPayClient.php           # HTTP Client
│   ├── BtiPayGateway.php          # Main Gateway
│   └── BtiPayServiceProvider.php  # Service Provider
├── composer.json
├── README.md
└── README_RO.md
```

## License

MIT License

## Contact

For BT iPay API issues: aplicatiiecommerce@btrl.ro

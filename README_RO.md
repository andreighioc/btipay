# BT iPay - Laravel Package

> 🇬🇧 [English version](README.md)

Package Laravel pentru integrarea cu platforma de plăți **Banca Transilvania iPay**.

Suportă plăți **1-Phase** (încasare automată) și **2-Phase** (pre-autorizare + deposit manual), rambursări, reversare, verificare status tranzacție, și plăți cu puncte de loialitate (StarBT).

## Cerințe

- PHP 8.1+ (Laravel 13 necesită PHP 8.3+)
- Laravel 10, 11, 12 sau 13
- Credențiale API de la Banca Transilvania

## Instalare

```bash
composer require BtiPay/laravel
```

Instalare completă (config, migrări, controller, rute, views):

```bash
php artisan BtiPay:install
php artisan migrate
```

Comanda `BtiPay:install` creează:
- `config/BtiPay.php` — configurare
- `database/migrations/` — tabelă `BtiPay_transactions`
- `app/Http/Controllers/BtiPayController.php` — controller complet cu `pay`, `process`, `finish`
- `routes/BtiPay.php` — rute web (`/BtiPay/pay`, `/BtiPay/process`, `/BtiPay/finish`)
- `resources/views/BtiPay/` — view-uri Blade (`pay.blade.php`, `finish.blade.php`)

Opțional, publică doar ce ai nevoie:

```bash
php artisan BtiPay:install --controller   # doar controller
php artisan BtiPay:install --routes       # doar rute
php artisan BtiPay:install --views        # doar views
php artisan BtiPay:install --force        # suprascrie fișierele existente
```

După instalare, include rutele în aplicație. În `routes/web.php`:

```php
require __DIR__.'/BtiPay.php';
```

Sau în `bootstrap/app.php` (Laravel 11+):

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    then: function () {
        require base_path('routes/BtiPay.php');
    },
)
```

## Configurare

Adăugați în `.env`:

```env
BtiPay_ENVIRONMENT=sandbox
BtiPay_USERNAME=your_api_username
BtiPay_PASSWORD=your_api_password
BtiPay_AUTH_METHOD=header
BtiPay_RETURN_URL=https://site-ul-meu.ro/BtiPay/finish
BtiPay_CURRENCY=946
BtiPay_LANGUAGE=ro
BtiPay_PAYMENT_TYPE=1phase
BtiPay_LOGGING=true
```

### Medii disponibile

| Mediu | Descriere |
|---|---|
| `sandbox` | Mediu de test (https://ecclients-sandbox.btrl.ro) |
| `production` | Mediu de producție (https://ecclients.btrl.ro) |

### Valute suportate (ISO 4217)

| Valută | Cod |
|---|---|
| RON | 946 |
| EUR | 978 |
| USD | 840 |

## Utilizare

### 1. Plată simplă (1-Phase)

```php
use BtiPay\Laravel\Facades\BtiPay;
use BtiPay\Laravel\Builders\OrderBundle;

// Construiește orderBundle
$bundle = OrderBundle::make()
    ->orderCreationDate(now()->format('Y-m-d'))
    ->email('client@example.com')
    ->phone('40740123456')
    ->deliveryInfo('livrare', '642', 'Cluj-Napoca', 'Str. Speranței 10', '400000')
    ->billingInfo('642', 'Cluj-Napoca', 'Str. Speranței 10', '400000');

// Înregistrare comandă
$response = BtiPay::register([
    'orderNumber'  => 'CMD-' . time(),
    'amount'       => 1500, // 15.00 RON (în bani)
    'currency'     => 946,
    'returnUrl'    => route('BtiPay.finish'),
    'description'  => 'Comanda #123',
    'email'        => 'client@example.com',
    'orderBundle'  => $bundle->toArray(),
]);

if ($response->isSuccessful()) {
    // Redirecționare către pagina de plată BT
    return redirect($response->getFormUrl());
} else {
    // Eroare la înregistrare
    echo $response->getErrorMessage();
}
```

### 2. Plată pre-autorizată (2-Phase)

```php
// Înregistrare pre-autorizare
$response = BtiPay::registerPreAuth([
    'orderNumber'  => 'CMD-' . time(),
    'amount'       => 5000, // 50.00 RON
    'returnUrl'    => route('BtiPay.finish'),
    'description'  => 'Comandă livrare #456',
    'orderBundle'  => $bundle->toArray(),
]);

// Redirect client la formUrl...

// --- Mai târziu, la livrare: Încasare (deposit) ---
$depositResponse = BtiPay::deposit(
    orderId: $response->getOrderId(),
    amount: 5000
);

if ($depositResponse->isSuccessful()) {
    echo 'Plată încasată cu succes!';
}
```

### 3. Reversare (anulare pre-autorizare)

```php
$reverseResponse = BtiPay::reverse(
    orderId: 'uuid-order-id'
);
```

### 4. Rambursare

```php
// Rambursare parțială
$refundResponse = BtiPay::refund(
    orderId: 'uuid-order-id',
    amount: 500 // Rambursare 5.00 RON
);

// Rambursare totală
$refundResponse = BtiPay::refund(
    orderId: 'uuid-order-id',
    amount: 5000 // Rambursare sumă completă
);
```

### 5. Verificare status tranzacție

```php
$status = BtiPay::getOrderStatus(orderId: 'uuid-order-id');

// sau prin orderNumber
$status = BtiPay::getOrderStatus(orderNumber: 'CMD-123');

if ($status->isPaid()) {
    echo 'Tranzacție finalizată cu succes!';
    echo 'Sumă: ' . $status->getAmountFormatted() . ' RON';
    echo 'Card: ' . $status->getMaskedPan();
} elseif ($status->isDeclined()) {
    echo 'Tranzacție declinată: ' . $status->getActionCodeMessage();
}
```

### 6. Shortcut: Obținere URL de plată

```php
$paymentUrl = BtiPay::getPaymentUrl(
    orderNumber: 'CMD-' . time(),
    amount: 2500,
    returnUrl: route('BtiPay.finish'),
    options: [
        'description' => 'Plată servicii',
        'email' => 'client@email.com',
    ]
);

return redirect($paymentUrl);
```

### 7. Pagina de finish (Return URL)

Dacă ai rulat `php artisan BtiPay:install`, controller-ul și view-urile sunt deja create.
Ruta `GET /BtiPay/finish` este înregistrată automat ca `BtiPay.finish`.

Controller-ul generat (`BtiPayController`) face automat:
- Verificare status prin `getOrderStatusExtended.do`
- Actualizare tranzacție în baza de date (card, sumă, RRN, ECI, etc.)
- Afișare cele 22 mesaje de eroare obligatorii
- Restricții retry pentru codurile 803, 804, 913
- Dispatch evenimente `PaymentCompleted` / `PaymentDeclined`

Pentru integrare custom, poți folosi direct facade-ul:

```php
$status = BtiPay::getOrderStatus(orderId: $request->get('orderId'));

if ($status->isPaid()) {
    // Plată reușită - card, sumă, RRN disponibile
    $status->getMaskedPan();
    $status->getAmountFormatted();
    $status->getAuthRefNum();
}

if ($status->isDeclined()) {
    $status->getActionCodeMessage(); // mesaj în limba configurată
}
```

### 8. Tracking cu modelul BtiPayTransaction

```php
use BtiPay\Laravel\Models\BtiPayTransaction;

// Creare tranzacție
$transaction = BtiPayTransaction::create([
    'order_id'       => $response->getOrderId(),
    'order_number'   => 'CMD-123',
    'payment_type'   => '1phase',
    'amount'         => 1500,
    'currency'       => '946',
    'status'         => 'CREATED',
    'form_url'       => $response->getFormUrl(),
    'customer_email' => 'client@email.com',
]);

// Legare la un model (ex: Order)
$order = Order::find(1);
$transaction->payable()->associate($order);
$transaction->save();

// Query-uri
BtiPayTransaction::successful()->get();     // Toate tranzacțiile plătite
BtiPayTransaction::declined()->get();       // Toate declinatele
BtiPayTransaction::preAuthorized()->get();  // Cele care așteaptă deposit
```

### 9. Trait pentru modele

```php
use BtiPay\Laravel\Traits\HasBtiPayPayments;

class Order extends Model
{
    use HasBtiPayPayments;
}

// Utilizare
$order = Order::find(1);
$order->BtiPayTransactions;          // Toate tranzacțiile
$order->latestBtiPayTransaction;     // Ultima tranzacție
$order->isPaidViaBtiPay();           // Este plătit?
$order->getTotalPaidViaBtiPay();     // Total plătit (în bani)
$order->getTotalRefundedViaBtiPay(); // Total rambursat
```

### 10. Plăți cu puncte de loialitate (StarBT)

```php
// Deposit cu loialitate
$response = BtiPay::deposit(
    orderId: 'uuid-ron-order-id',
    amount: 3000, // Sumă totală RON + LOY
    depositLoyalty: true
);

// Refund cu loialitate
$response = BtiPay::refund(
    orderId: 'uuid-ron-order-id',
    amount: 4000,
    refundLoyalty: true
);

// Reverse cu loialitate
$response = BtiPay::reverse(
    orderId: 'uuid-ron-order-id',
    reverseLoyalty: true
);
```

## Evenimente

Pachetul emite următoarele evenimente pe care le poți asculta:

| Eveniment | Descriere |
|---|---|
| `PaymentRegistered` | Plata a fost înregistrată la iPay |
| `PaymentCompleted` | Plata finalizată cu succes (DEPOSITED) |
| `PaymentDeclined` | Plata a fost declinată |
| `PaymentRefunded` | Rambursare efectuată (parțială sau totală) |

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

## Coduri de Eroare (Action Codes)

Cele 22 de erori obligatorii de tratat conform documentației BT:

| Cod | Descriere |
|---|---|
| 104 | Card restricționat |
| 124 | Tranzacție neautorizată conform reglementărilor |
| 320 | Card inactiv |
| 801 | Emitent indisponibil |
| 803 | Card blocat ⚠️ NU reîncerca cu același card! |
| 804 | Tranzacție nepermisă ⚠️ NU reîncerca cu același card! |
| 805 | Tranzacție respinsă |
| 861 | Dată expirare greșită |
| 871 | CVV greșit |
| 905 | Card invalid |
| 906 | Card expirat |
| 913 | Tranzacție invalidă ⚠️ NU reîncerca cu același card! |
| 914 | Cont invalid |
| 915 | Fonduri insuficiente |
| 917 | Limită tranzacționare depășită |
| 952 | Suspect de fraudă |
| 998 | Rate nepermise cu acest card |
| 341016 | Autentificare 3DS2 declinată |
| 341017 | Status 3DS2 necunoscut |
| 341018 | 3DS2 anulat de client |
| 341019 | 3DS2 eșuat |
| 341020 | 3DS2 status necunoscut |

## Structura pachetului

```
BtiPay/
├── config/
│   └── BtiPay.php                 # Configurare
├── database/
│   └── migrations/                # Migrare tabelă tranzacții
├── stubs/
│   ├── BtiPayController.php.stub  # Controller (publicat prin BtiPay:install)
│   ├── BtiPay-routes.php.stub     # Rute (publicat prin BtiPay:install)
│   └── views/
│       ├── pay.blade.php.stub     # Formular de plată
│       └── finish.blade.php.stub  # Pagina de finish (succes/eroare)
├── src/
│   ├── Builders/
│   │   └── OrderBundle.php        # Builder fluent pentru orderBundle
│   ├── Console/
│   │   └── InstallCommand.php     # php artisan BtiPay:install
│   ├── Enums/
│   │   ├── Currency.php           # Enum valute (RON/EUR/USD)
│   │   ├── OrderStatus.php        # Enum statusuri tranzacție
│   │   └── PaymentType.php        # Enum tip plată (1phase/2phase)
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
│   │   └── BtiPay.php             # Facade Laravel
│   ├── Models/
│   │   └── BtiPayTransaction.php  # Model Eloquent
│   ├── Responses/
│   │   ├── ActionCodeMessages.php  # Mesaje erori (RO/EN)
│   │   ├── BaseResponse.php
│   │   ├── DepositResponse.php
│   │   ├── FinishedPaymentInfoResponse.php
│   │   ├── OrderStatusResponse.php
│   │   ├── RefundResponse.php
│   │   ├── RegisterResponse.php
│   │   └── ReverseResponse.php
│   ├── Traits/
│   │   └── HasBtiPayPayments.php  # Trait pentru modele
│   ├── BtiPayClient.php           # Client HTTP
│   ├── BtiPayGateway.php          # Gateway principal
│   └── BtiPayServiceProvider.php  # Service Provider
├── composer.json
└── README.md
```

## Licență

MIT License

## Contact

Pentru probleme cu API-ul BT iPay: aplicatiiecommerce@btrl.ro

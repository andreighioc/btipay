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
composer require btipay/laravel
```

Instalare completă (config, migrări, controller, rute, views):

```bash
php artisan btipay:install
php artisan migrate
```

Comanda `btipay:install` creează:
- `config/btipay.php` — configurare
- `database/migrations/` — tabelă `btipay_transactions`
- `app/Http/Controllers/BtIpayController.php` — controller complet cu `pay`, `process`, `finish`
- `routes/btipay.php` — rute web (`/btipay/pay`, `/btipay/process`, `/btipay/finish`)
- `resources/views/btipay/` — view-uri Blade (`pay.blade.php`, `finish.blade.php`)

Opțional, publică doar ce ai nevoie:

```bash
php artisan btipay:install --controller   # doar controller
php artisan btipay:install --routes       # doar rute
php artisan btipay:install --views        # doar views
php artisan btipay:install --force        # suprascrie fișierele existente
```

După instalare, include rutele în aplicație. În `routes/web.php`:

```php
require __DIR__.'/btipay.php';
```

Sau în `bootstrap/app.php` (Laravel 11+):

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    then: function () {
        require base_path('routes/btipay.php');
    },
)
```

## Configurare

Adăugați în `.env`:

```env
BTIPAY_ENVIRONMENT=sandbox
BTIPAY_USERNAME=your_api_username
BTIPAY_PASSWORD=your_api_password
BTIPAY_AUTH_METHOD=header
BTIPAY_RETURN_URL=https://site-ul-meu.ro/btipay/finish
BTIPAY_CURRENCY=946
BTIPAY_LANGUAGE=ro
BTIPAY_PAYMENT_TYPE=1phase
BTIPAY_LOGGING=true
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
use BtIpay\Laravel\Facades\BtIpay;
use BtIpay\Laravel\Builders\OrderBundle;

// Construiește orderBundle
$bundle = OrderBundle::make()
    ->orderCreationDate(now()->format('Y-m-d'))
    ->email('client@example.com')
    ->phone('40740123456')
    ->deliveryInfo('livrare', '642', 'Cluj-Napoca', 'Str. Speranței 10', '400000')
    ->billingInfo('642', 'Cluj-Napoca', 'Str. Speranței 10', '400000');

// Înregistrare comandă
$response = BtIpay::register([
    'orderNumber'  => 'CMD-' . time(),
    'amount'       => 1500, // 15.00 RON (în bani)
    'currency'     => 946,
    'returnUrl'    => route('btipay.finish'),
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
$response = BtIpay::registerPreAuth([
    'orderNumber'  => 'CMD-' . time(),
    'amount'       => 5000, // 50.00 RON
    'returnUrl'    => route('btipay.finish'),
    'description'  => 'Comandă livrare #456',
    'orderBundle'  => $bundle->toArray(),
]);

// Redirect client la formUrl...

// --- Mai târziu, la livrare: Încasare (deposit) ---
$depositResponse = BtIpay::deposit(
    orderId: $response->getOrderId(),
    amount: 5000
);

if ($depositResponse->isSuccessful()) {
    echo 'Plată încasată cu succes!';
}
```

### 3. Reversare (anulare pre-autorizare)

```php
$reverseResponse = BtIpay::reverse(
    orderId: 'uuid-order-id'
);
```

### 4. Rambursare

```php
// Rambursare parțială
$refundResponse = BtIpay::refund(
    orderId: 'uuid-order-id',
    amount: 500 // Rambursare 5.00 RON
);

// Rambursare totală
$refundResponse = BtIpay::refund(
    orderId: 'uuid-order-id',
    amount: 5000 // Rambursare sumă completă
);
```

### 5. Verificare status tranzacție

```php
$status = BtIpay::getOrderStatus(orderId: 'uuid-order-id');

// sau prin orderNumber
$status = BtIpay::getOrderStatus(orderNumber: 'CMD-123');

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
$paymentUrl = BtIpay::getPaymentUrl(
    orderNumber: 'CMD-' . time(),
    amount: 2500,
    returnUrl: route('btipay.finish'),
    options: [
        'description' => 'Plată servicii',
        'email' => 'client@email.com',
    ]
);

return redirect($paymentUrl);
```

### 7. Pagina de finish (Return URL)

Dacă ai rulat `php artisan btipay:install`, controller-ul și view-urile sunt deja create.
Ruta `GET /btipay/finish` este înregistrată automat ca `btipay.finish`.

Controller-ul generat (`BtIpayController`) face automat:
- Verificare status prin `getOrderStatusExtended.do`
- Actualizare tranzacție în baza de date (card, sumă, RRN, ECI, etc.)
- Afișare cele 22 mesaje de eroare obligatorii
- Restricții retry pentru codurile 803, 804, 913
- Dispatch evenimente `PaymentCompleted` / `PaymentDeclined`

Pentru integrare custom, poți folosi direct facade-ul:

```php
$status = BtIpay::getOrderStatus(orderId: $request->get('orderId'));

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

### 8. Tracking cu modelul BtIpayTransaction

```php
use BtIpay\Laravel\Models\BtIpayTransaction;

// Creare tranzacție
$transaction = BtIpayTransaction::create([
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
BtIpayTransaction::successful()->get();     // Toate tranzacțiile plătite
BtIpayTransaction::declined()->get();       // Toate declinatele
BtIpayTransaction::preAuthorized()->get();  // Cele care așteaptă deposit
```

### 9. Trait pentru modele

```php
use BtIpay\Laravel\Traits\HasBtIpayPayments;

class Order extends Model
{
    use HasBtIpayPayments;
}

// Utilizare
$order = Order::find(1);
$order->btipayTransactions;          // Toate tranzacțiile
$order->latestBtipayTransaction;     // Ultima tranzacție
$order->isPaidViaBtipay();           // Este plătit?
$order->getTotalPaidViaBtipay();     // Total plătit (în bani)
$order->getTotalRefundedViaBtipay(); // Total rambursat
```

### 10. Plăți cu puncte de loialitate (StarBT)

```php
// Deposit cu loialitate
$response = BtIpay::deposit(
    orderId: 'uuid-ron-order-id',
    amount: 3000, // Sumă totală RON + LOY
    depositLoyalty: true
);

// Refund cu loialitate
$response = BtIpay::refund(
    orderId: 'uuid-ron-order-id',
    amount: 4000,
    refundLoyalty: true
);

// Reverse cu loialitate
$response = BtIpay::reverse(
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
    \BtIpay\Laravel\Events\PaymentCompleted::class => [
        \App\Listeners\SendPaymentConfirmation::class,
    ],
    \BtIpay\Laravel\Events\PaymentDeclined::class => [
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
btipay/
├── config/
│   └── btipay.php                 # Configurare
├── database/
│   └── migrations/                # Migrare tabelă tranzacții
├── stubs/
│   ├── BtIpayController.php.stub  # Controller (publicat prin btipay:install)
│   ├── btipay-routes.php.stub     # Rute (publicat prin btipay:install)
│   └── views/
│       ├── pay.blade.php.stub     # Formular de plată
│       └── finish.blade.php.stub  # Pagina de finish (succes/eroare)
├── src/
│   ├── Builders/
│   │   └── OrderBundle.php        # Builder fluent pentru orderBundle
│   ├── Console/
│   │   └── InstallCommand.php     # php artisan btipay:install
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
│   │   ├── BtIpayAuthenticationException.php
│   │   ├── BtIpayConnectionException.php
│   │   ├── BtIpayException.php
│   │   └── BtIpayValidationException.php
│   ├── Facades/
│   │   └── BtIpay.php             # Facade Laravel
│   ├── Models/
│   │   └── BtIpayTransaction.php  # Model Eloquent
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
│   │   └── HasBtIpayPayments.php  # Trait pentru modele
│   ├── BtIpayClient.php           # Client HTTP
│   ├── BtIpayGateway.php          # Gateway principal
│   └── BtIpayServiceProvider.php  # Service Provider
├── composer.json
└── README.md
```

## Licență

MIT License

## Contact

Pentru probleme cu API-ul BT iPay: aplicatiiecommerce@btrl.ro

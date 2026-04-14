<?php

namespace AndreiGhioc\BtiPay\Responses;

/**
 * Maps BT iPay actionCode values to human-readable messages.
 * Covers the 22 required error codes from the documentation,
 * plus additional codes from the annex.
 */
class ActionCodeMessages
{
    /**
     * The 22 required error messages (must be displayed to the customer).
     */
    protected static array $requiredMessages = [
        104    => 'Card restricționat (blocat temporar sau permanent).',
        124    => 'Tranzacția nu poate fi autorizată conform reglementărilor.',
        320    => 'Card inactiv. Vă rugăm activați cardul.',
        801    => 'Emitent indisponibil.',
        803    => 'Card blocat. Contactați banca emitentă sau reîncercați tranzacția cu alt card.',
        804    => 'Tranzacția nu este permisă. Contactați banca emitentă sau reîncercați tranzacția cu alt card.',
        805    => 'Tranzacție respinsă.',
        861    => 'Dată expirare card greșită.',
        871    => 'CVV greșit.',
        905    => 'Card invalid. Acesta nu există în baza de date.',
        906    => 'Card expirat.',
        913    => 'Tranzacție invalidă. Contactați banca emitentă sau reîncercați tranzacția cu alt card.',
        914    => 'Cont invalid. Vă rugăm contactați banca emitentă.',
        915    => 'Fonduri insuficiente.',
        917    => 'Limită tranzacționare depășită.',
        952    => 'Suspect de fraudă.',
        998    => 'Tranzacția în rate nu este permisă cu acest card. Te rugăm să folosești un card de credit emis de Banca Transilvania.',
        341016 => 'Autentificare 3DS2 declinată de bancă.',
        341017 => 'Status autentificare 3DS2 necunoscut.',
        341018 => 'Autentificare 3DS2 anulată de client.',
        341019 => 'Autentificare 3DS2 eșuată.',
        341020 => 'Status 3DS2 necunoscut.',
    ];

    /**
     * Action codes that must NOT be retried with the same card.
     */
    protected static array $noRetryWithSameCard = [803, 804, 913];

    /**
     * Extended error messages from the documentation annex.
     */
    protected static array $extendedMessages = [
        -20010  => 'Tranzacție respinsă - suma depășește limitele băncii emitente.',
        -2007   => 'Timpul de introducere a datelor a expirat.',
        -2006   => 'Autentificarea 3DSecure a fost refuzată.',
        -2002   => 'Limită de plată depășită.',
        -2001   => 'Adresa IP este blocată.',
        -2000   => 'Cardul este blocat.',
        -100    => 'Nu s-a realizat nicio tentativă de plată.',
        0       => 'Plată efectuată cu succes.',
        1       => 'Tranzacție refuzată. Identificarea nu a fost posibilă.',
        5       => 'Rețeaua a refuzat procesarea tranzacției.',
        100     => 'Card restricționat (limite de tranzacționare online).',
        101     => 'Card expirat.',
        103     => 'Contactați banca emitentă.',
        106     => 'Număr maxim de încercări PIN depășit. Cardul poate fi blocat temporar.',
        107     => 'Contactați banca emitentă.',
        109     => 'Identificator comerciant invalid.',
        110     => 'Sumă invalidă.',
        111     => 'Număr card incorect.',
        116     => 'Fonduri insuficiente.',
        117     => 'PIN incorect.',
        119     => 'Tranzacție ilegală.',
        120     => 'Tranzacția nu este permisă de banca emitentă.',
        121     => 'Limita zilnică de retragere depășită.',
        123     => 'Număr maxim de tranzacții depășit.',
        125     => 'Număr card incorect.',
        208     => 'Card pierdut.',
        209     => 'Limitele cardului au fost depășite.',
        400     => 'Reversarea a fost procesată.',
        902     => 'Tranzacție interzisă pentru acest card.',
        903     => 'Suma depășește limita băncii emitente.',
        904     => 'Format mesaj incorect.',
        907     => 'Emitent indisponibil.',
        909     => 'Eroare de sistem.',
        910     => 'Emitent indisponibil.',
        999     => 'Declinată - suspiciune de fraudă.',
        1001    => 'Timpul de introducere a datelor a expirat.',
    ];

    /**
     * Get the message for a given action code.
     */
    public static function getMessage(int $code, ?string $language = null): string
    {
        $lang = $language ?? config('BtiPay.language', 'ro');

        if ($lang === 'ro') {
            return static::$requiredMessages[$code]
                ?? static::$extendedMessages[$code]
                ?? 'Tranzacție refuzată, vă rugăm reveniți.';
        }

        // English fallback
        return static::getEnglishMessage($code);
    }

    /**
     * Get the English message for a given action code.
     */
    public static function getEnglishMessage(int $code): string
    {
        $en = [
            104    => 'Restricted card (temporarily or permanently blocked).',
            124    => 'Transaction cannot be authorized due to regulations.',
            320    => 'Inactive card. Please activate your card.',
            801    => 'Issuer unavailable.',
            803    => 'Card blocked. Contact issuing bank or try another card.',
            804    => 'Transaction not allowed. Contact issuing bank or try another card.',
            805    => 'Transaction declined.',
            861    => 'Invalid card expiration date.',
            871    => 'Invalid CVV.',
            905    => 'Invalid card. Card does not exist.',
            906    => 'Card expired.',
            913    => 'Invalid transaction. Contact issuing bank or try another card.',
            914    => 'Invalid account. Please contact issuing bank.',
            915    => 'Insufficient funds.',
            917    => 'Transaction limit exceeded.',
            952    => 'Suspected fraud.',
            998    => 'Installment payment is not allowed with this card. Please use a Banca Transilvania credit card.',
            341016 => '3DS2 authentication declined by bank.',
            341017 => '3DS2 authentication status unknown.',
            341018 => '3DS2 authentication cancelled by customer.',
            341019 => '3DS2 authentication failed.',
            341020 => '3DS2 unknown status.',
        ];

        return $en[$code] ?? 'Transaction declined, please try again.';
    }

    /**
     * Check if the card should NOT be retried for this action code.
     */
    public static function shouldNotRetryWithSameCard(int $code): bool
    {
        return in_array($code, static::$noRetryWithSameCard);
    }

    /**
     * Get all required action codes.
     */
    public static function getRequiredCodes(): array
    {
        return array_keys(static::$requiredMessages);
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Feature Flags
    |--------------------------------------------------------------------------
    |
    | Cada flag controla una validación de seguridad.
    | Valores posibles:
    |   'off'   → Desactivado (comportamiento legacy)
    |   'log'   → Registra la violación pero NO bloquea
    |   'block' → Registra Y bloquea la operación
    |
    */

    'webhook_amount_check' => env('SECURITY_WEBHOOK_AMOUNT_CHECK', 'off'),
    'webhook_signature_check' => env('SECURITY_WEBHOOK_SIGNATURE_CHECK', 'off'),
    'price_recalculation' => env('SECURITY_PRICE_RECALCULATION', 'block'),
    'coupon_validation' => env('SECURITY_COUPON_VALIDATION', 'block'),
];

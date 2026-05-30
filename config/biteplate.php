<?php

declare(strict_types=1);

return [
    // Which franchise this instance is running as (drives the Abstract Factory menu).
    'branch' => env('BITEPLATE_BRANCH', 'standard'), // standard | coastal | city_centre

    'currency' => env('BITEPLATE_CURRENCY', 'GBP'),

    // Password-less role switcher for local demos. MUST be false in production,
    // where Keycloak is the only way in.
    'dev_login' => filter_var(env('BITEPLATE_DEV_LOGIN', true), FILTER_VALIDATE_BOOL),

    // VAT rate applied by the billing facade.
    'tax_rate' => (float) env('BITEPLATE_TAX_RATE', 20.0),

    'kafka' => [
        'enabled' => filter_var(env('KAFKA_ENABLED', false), FILTER_VALIDATE_BOOL),
        'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        'topic' => env('KAFKA_TOPIC', 'biteplate.events'),
        'group' => env('KAFKA_GROUP', 'biteplate-consumers'),
    ],
];

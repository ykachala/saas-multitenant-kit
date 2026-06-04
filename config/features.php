<?php

return [
    'free' => [
        'api_access' => false,
        'custom_domain' => false,
        'webhooks' => false,
        'sso' => false,
        'audit_log' => false,
        'priority_support' => false,
    ],
    'starter' => [
        'api_access' => true,
        'custom_domain' => false,
        'webhooks' => true,
        'sso' => false,
        'audit_log' => false,
        'priority_support' => false,
    ],
    'pro' => [
        'api_access' => true,
        'custom_domain' => true,
        'webhooks' => true,
        'sso' => false,
        'audit_log' => true,
        'priority_support' => false,
    ],
    'enterprise' => [
        'api_access' => true,
        'custom_domain' => true,
        'webhooks' => true,
        'sso' => true,
        'audit_log' => true,
        'priority_support' => true,
    ],
];

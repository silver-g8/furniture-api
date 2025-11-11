<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for deployment automation, including
    | post-deployment callbacks, health checks, and environment-specific
    | settings.
    |
    */

    'environments' => [
        'staging' => [
            'url' => env('STAGING_URL', 'https://staging-api.furniture.example.com'),
            'branch' => 'develop',
            'auto_deploy' => true,
            'maintenance_mode' => false,
        ],
        'production' => [
            'url' => env('PRODUCTION_URL', 'https://api.furniture.example.com'),
            'branch' => 'main',
            'auto_deploy' => false,
            'maintenance_mode' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-Deployment Callbacks
    |--------------------------------------------------------------------------
    |
    | Commands to run after successful deployment. These will be executed
    | in the order specified.
    |
    */

    'post_deploy_commands' => [
        'migrate' => [
            'command' => 'migrate',
            'options' => ['--force' => true],
            'environments' => ['staging', 'production'],
        ],
        'cache_config' => [
            'command' => 'config:cache',
            'options' => [],
            'environments' => ['staging', 'production'],
        ],
        'cache_routes' => [
            'command' => 'route:cache',
            'options' => [],
            'environments' => ['staging', 'production'],
        ],
        'cache_views' => [
            'command' => 'view:cache',
            'options' => [],
            'environments' => ['staging', 'production'],
        ],
        'cache_events' => [
            'command' => 'event:cache',
            'options' => [],
            'environments' => ['production'],
        ],
        'generate_docs' => [
            'command' => 'l5-swagger:generate',
            'options' => [],
            'environments' => ['staging', 'production'],
        ],
        'restart_queue' => [
            'command' => 'queue:restart',
            'options' => [],
            'environments' => ['staging', 'production'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for post-deployment health checks.
    |
    */

    'health_check' => [
        'enabled' => true,
        'endpoint' => '/api/ping',
        'timeout' => 10,
        'retries' => 3,
        'retry_delay' => 2, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for pre-deployment backups.
    |
    */

    'backup' => [
        'enabled' => true,
        'database' => true,
        'storage' => false,
        'retention_days' => 30,
        'path' => storage_path('backups'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic rollback on deployment failure.
    |
    */

    'rollback' => [
        'enabled' => true,
        'auto_rollback_on_failure' => env('AUTO_ROLLBACK', true),
        'keep_releases' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for deployment notifications.
    |
    */

    'notifications' => [
        'enabled' => env('DEPLOY_NOTIFICATIONS_ENABLED', false),
        'channels' => ['slack', 'email'],
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
        'email_recipients' => env('DEPLOY_EMAIL_RECIPIENTS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Docker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Docker-based deployments.
    |
    */

    'docker' => [
        'registry' => env('DOCKER_REGISTRY', 'ghcr.io'),
        'image_name' => env('DOCKER_IMAGE_NAME', 'furniture-api'),
        'tag_strategy' => 'semver', // semver, branch, commit
    ],

    /*
    |--------------------------------------------------------------------------
    | CI/CD Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for CI/CD pipeline integration.
    |
    */

    'ci_cd' => [
        'provider' => env('CI_PROVIDER', 'github'), // github, gitlab, jenkins
        'run_tests' => true,
        'run_static_analysis' => true,
        'required_checks' => [
            'pint',
            'phpstan',
            'pest',
        ],
    ],
];

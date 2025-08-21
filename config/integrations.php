<?php
// config/integrations.php

return [

    /*
    |--------------------------------------------------------------------------
    | External HRIS Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for external Human Resource Information System integration
    | Set enabled to true when ready to sync with external HRIS
    |
    */

    'hris' => [
        'enabled' => env('HRIS_INTEGRATION_ENABLED', false),
        'api_url' => env('HRIS_API_URL'),
        'api_key' => env('HRIS_API_KEY'),
        'timeout' => env('HRIS_TIMEOUT', 30),
        'sync_frequency' => env('HRIS_SYNC_FREQUENCY', 'daily'), // daily, weekly, manual
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automated database backups
    |
    */

    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'path' => env('BACKUP_PATH', storage_path('backups')),
        'compress' => env('BACKUP_COMPRESS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for system notifications and cleanup
    |
    */

    'notifications' => [
        'enabled' => env('NOTIFICATIONS_ENABLED', true),
        'cleanup_enabled' => env('NOTIFICATION_CLEANUP_ENABLED', true),
        'cleanup_days' => env('NOTIFICATION_CLEANUP_DAYS', 90),
        'email_enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),
        'sms_enabled' => env('SMS_NOTIFICATIONS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for system health monitoring and alerts
    |
    */

    'monitoring' => [
        'enabled' => env('SYSTEM_MONITORING_ENABLED', true),
        'health_check_interval' => env('HEALTH_CHECK_INTERVAL', 30), // minutes
        'storage_warning_threshold' => env('STORAGE_WARNING_GB', 5), // GB
        'memory_warning_threshold' => env('MEMORY_WARNING_MB', 512), // MB
        'queue_warning_threshold' => env('QUEUE_WARNING_SIZE', 100), // jobs
    ],

    /*
    |--------------------------------------------------------------------------
    | Training Provider Integration
    |--------------------------------------------------------------------------
    |
    | Settings for training provider system integration
    |
    */

    'training_providers' => [
        'auto_rating_update' => env('AUTO_RATING_UPDATE', true),
        'rating_update_frequency' => env('RATING_UPDATE_FREQUENCY', 'weekly'),
        'external_api_enabled' => env('TRAINING_PROVIDER_API_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics cache and reporting
    |
    */

    'analytics' => [
        'cache_enabled' => env('ANALYTICS_CACHE_ENABLED', true),
        'cache_duration_days' => env('ANALYTICS_CACHE_DAYS', 30),
        'monthly_reports' => env('MONTHLY_REPORTS_ENABLED', true),
        'quarterly_audits' => env('QUARTERLY_AUDITS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for compliance violation monitoring and alerts
    |
    */

    'compliance' => [
        'critical_violation_threshold_days' => env('CRITICAL_VIOLATION_DAYS', 30),
        'warning_threshold_days' => env('WARNING_THRESHOLD_DAYS', 7),
        'auto_alerts_enabled' => env('COMPLIANCE_AUTO_ALERTS', true),
        'escalation_enabled' => env('COMPLIANCE_ESCALATION', true),
    ],

];

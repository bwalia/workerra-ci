<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Deployment extends BaseConfig
{
    /**
     * Writable directory paths for deployment artifacts
     */
    public array $writablePaths = [
        'secrets' => WRITEPATH . 'secret/',
        'values' => WRITEPATH . 'values/',
        'helm' => WRITEPATH . 'helm/',
        'logs' => WRITEPATH . 'deployment_logs/',
    ];

    /**
     * Kubeseal configuration
     */
    public array $kubeseal = [
        'binary' => '/usr/local/bin/kubeseal',
        'format' => 'yaml',
        'timeout' => 30,
        'retries' => 3,
        'retryDelay' => 5, // seconds
    ];

    /**
     * Helm configuration
     */
    public array $helm = [
        'binary' => '/usr/local/bin/helm',
        'timeout' => 300,
        'maxHistory' => 10,
    ];

    /**
     * Kubectl configuration
     */
    public array $kubectl = [
        'binary' => '/usr/local/bin/kubectl',
        'timeout' => 60,
    ];

    /**
     * Deployment configuration
     */
    public array $deployment = [
        'lockTimeout' => 1800, // 30 minutes
        'maxRetries' => 3,
        'retryDelay' => 5, // seconds
        'cleanupOldFiles' => true,
        'filesRetentionDays' => 7,
        'dryRunEnabled' => true,
    ];

    /**
     * Notification configuration
     */
    public array $notifications = [
        'enabled' => false,
        'channels' => ['email'],
        'slack' => [
            'webhook' => '',
            'channel' => '#deployments',
        ],
    ];

    /**
     * Security configuration
     */
    public array $security = [
        'allowedEnvironments' => ['dev', 'int', 'test', 'acc', 'prod'],
        'requireApprovalForProd' => true,
        'filePermissions' => 0600,
        'directoryPermissions' => 0700,
    ];

    /**
     * Reserved secret names
     */
    public array $reservedSecrets = [
        'KUBECONFIG',
        'TARGET_ENV',
    ];

    /**
     * Validation rules
     */
    public array $validation = [
        'uuidPattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        'environmentPattern' => '/^[a-z0-9-]+$/',
        'maxSecretSize' => 1048576, // 1MB
        'maxTemplateSize' => 10485760, // 10MB
    ];

    /**
     * Logging configuration
     */
    public array $logging = [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'logDeploymentSteps' => true,
        'logShellOutput' => true,
    ];

    /**
     * Email configuration for marketing templates
     * (should be moved to business-specific settings)
     */
    public array $marketing = [
        'defaultFromName' => 'System Administrator',
        'defaultFromEmail' => 'noreply@example.com',
        'defaultSubject' => 'Notification',
    ];
}

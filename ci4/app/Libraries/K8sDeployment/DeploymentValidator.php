<?php

namespace App\Libraries\K8sDeployment;

use Config\Deployment as DeploymentConfig;

/**
 * DeploymentValidator
 *
 * Validates deployment inputs and configurations
 */
class DeploymentValidator
{
    protected DeploymentConfig $config;

    public function __construct()
    {
        $this->config = config('Deployment');
    }

    /**
     * Validate UUID format
     */
    public function validateUuid(string $uuid): array
    {
        if (empty($uuid)) {
            return ['valid' => false, 'error' => 'UUID cannot be empty'];
        }

        if (!preg_match($this->config->validation['uuidPattern'], $uuid)) {
            return ['valid' => false, 'error' => 'Invalid UUID format'];
        }

        return ['valid' => true];
    }

    /**
     * Validate environment name
     */
    public function validateEnvironment(string $environment): array
    {
        if (empty($environment)) {
            return ['valid' => false, 'error' => 'Environment cannot be empty'];
        }

        if (!preg_match($this->config->validation['environmentPattern'], $environment)) {
            return ['valid' => false, 'error' => 'Environment name contains invalid characters'];
        }

        if (!in_array($environment, $this->config->security['allowedEnvironments'])) {
            return [
                'valid' => false,
                'error' => 'Environment not allowed. Allowed: ' . implode(', ', $this->config->security['allowedEnvironments'])
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate deployment configuration
     */
    public function validateDeploymentConfig(array $config): array
    {
        $errors = [];

        // Validate service UUID
        $uuidResult = $this->validateUuid($config['service_uuid'] ?? '');
        if (!$uuidResult['valid']) {
            $errors[] = 'Service: ' . $uuidResult['error'];
        }

        // Validate environment
        $envResult = $this->validateEnvironment($config['environment'] ?? '');
        if (!$envResult['valid']) {
            $errors[] = 'Environment: ' . $envResult['error'];
        }

        // Validate secrets exist
        if (empty($config['secrets']) || !is_array($config['secrets'])) {
            $errors[] = 'No secrets provided for deployment';
        }

        // Validate templates exist
        if (empty($config['secret_template_id'])) {
            $errors[] = 'No secret template provided';
        }

        if (empty($config['values_template_id'])) {
            $errors[] = 'No values template provided';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize filename for file operations
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove any path traversal attempts
        $filename = basename($filename);

        // Remove any non-alphanumeric characters except dash, underscore, and dot
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);

        return $filename;
    }

    /**
     * Validate file path is within allowed writable directories
     */
    public function validateFilePath(string $path): array
    {
        $realPath = realpath(dirname($path));

        if ($realPath === false) {
            return ['valid' => false, 'error' => 'Invalid file path'];
        }

        // Check if path is within WRITEPATH
        $writePath = realpath(WRITEPATH);
        if (strpos($realPath, $writePath) !== 0) {
            return ['valid' => false, 'error' => 'File path is outside writable directory'];
        }

        return ['valid' => true];
    }

    /**
     * Validate YAML syntax
     */
    public function validateYaml(string $yamlContent): array
    {
        try {
            \Symfony\Component\Yaml\Yaml::parse($yamlContent);
            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Invalid YAML syntax: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate required secrets are present
     */
    public function validateRequiredSecrets(array $secrets, array $required): array
    {
        $missing = [];
        $secretNames = array_column($secrets, 'key_name');

        foreach ($required as $requiredSecret) {
            if (!in_array($requiredSecret, $secretNames)) {
                $missing[] = $requiredSecret;
            }
        }

        if (!empty($missing)) {
            return [
                'valid' => false,
                'error' => 'Missing required secrets: ' . implode(', ', $missing)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Escape shell argument safely
     */
    public function escapeShellArg(string $arg): string
    {
        return escapeshellarg($arg);
    }

    /**
     * Validate shell command path
     */
    public function validateCommandPath(string $command): array
    {
        // Check if command is in allowed list
        $allowedCommands = [
            $this->config->kubeseal['binary'],
            $this->config->helm['binary'],
            $this->config->kubectl['binary'],
            '/bin/bash',
            '/bin/sh'
        ];

        $commandPath = explode(' ', $command)[0];

        if (!in_array($commandPath, $allowedCommands)) {
            return [
                'valid' => false,
                'error' => 'Command not in allowed list: ' . $commandPath
            ];
        }

        return ['valid' => true];
    }
}

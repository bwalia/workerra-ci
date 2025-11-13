<?php

namespace App\Libraries\K8sDeployment;

use App\Models\Core\Common_model;
use Config\Deployment as DeploymentConfig;

/**
 * SecretProcessor
 *
 * Handles secret retrieval and replacement in templates
 */
class SecretProcessor
{
    protected Common_model $commonModel;
    protected DeploymentConfig $config;
    protected DeploymentLogger $logger;

    public function __construct()
    {
        $this->commonModel = new Common_model();
        $this->config = config('Deployment');
        $this->logger = new DeploymentLogger();
    }

    /**
     * Set logger instance
     */
    public function setLogger(DeploymentLogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get all secrets for a service with environment overrides
     */
    public function getSecretsForEnvironment(string $serviceUuid, string $environment): array
    {
        $secrets = $this->commonModel->getDataWhere("secrets_services", $serviceUuid, "service_id");
        $processedSecrets = [];

        foreach ($secrets as $secretRelation) {
            $secret = $this->commonModel->getSingleRowWhere("secrets", $secretRelation['secret_id'], "id");

            if (!$secret) {
                $this->logger->warning("Secret not found", ['secret_id' => $secretRelation['secret_id']]);
                continue;
            }

            // Check for environment-specific override
            $overrideSecret = $this->commonModel->getSecretByServiceUuid(
                $secret['key_name'],
                $serviceUuid,
                $environment
            );

            if (!empty($overrideSecret) && $overrideSecret['secret_tags'] === $environment) {
                // Use environment-specific secret
                $processedSecrets[] = [
                    'key_name' => $overrideSecret['key_name'],
                    'key_value' => $overrideSecret['key_value'],
                    'secret_tags' => $overrideSecret['secret_tags'],
                    'is_override' => true
                ];
            } else {
                // Check if secret matches environment or is global
                if ($environment === $secret['secret_tags'] ||
                    !$secret['secret_tags'] ||
                    !isset($secret['secret_tags'])) {
                    $processedSecrets[] = [
                        'key_name' => $secret['key_name'],
                        'key_value' => $secret['key_value'],
                        'secret_tags' => $secret['secret_tags'] ?? null,
                        'is_override' => false
                    ];
                } else {
                    // Try to find global secret (no tag)
                    $globalSecret = $this->commonModel->getSecretByServiceUuid(
                        $secret['key_name'],
                        $serviceUuid,
                        NULL
                    );

                    if (!empty($globalSecret)) {
                        $processedSecrets[] = [
                            'key_name' => $globalSecret['key_name'],
                            'key_value' => $globalSecret['key_value'],
                            'secret_tags' => null,
                            'is_override' => false
                        ];
                    }
                }
            }
        }

        return $processedSecrets;
    }

    /**
     * Replace secrets in template content
     */
    public function replaceSecretsInTemplate(
        string $template,
        array $secrets,
        string $environment
    ): array {
        $replacedSecrets = [];
        $missingSecrets = [];

        foreach ($secrets as $secret) {
            $placeholder = $secret['key_name'];

            // Special handling for TARGET_ENV
            if ($placeholder === 'TARGET_ENV') {
                $template = str_replace($placeholder, $environment, $template);
                $replacedSecrets[] = $placeholder;
                continue;
            }

            // Check if placeholder exists in template
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $secret['key_value'], $template);
                $replacedSecrets[] = $placeholder;
            }
        }

        // Check for unreplaced placeholders
        preg_match_all('/\b[A-Z_]{3,}\b/', $template, $matches);
        $potentialPlaceholders = array_unique($matches[0]);

        foreach ($potentialPlaceholders as $placeholder) {
            if (!in_array($placeholder, $replacedSecrets) &&
                in_array($placeholder, $this->config->reservedSecrets)) {
                $missingSecrets[] = $placeholder;
            }
        }

        return [
            'template' => $template,
            'replaced' => $replacedSecrets,
            'missing' => $missingSecrets
        ];
    }

    /**
     * Validate required secrets are present
     */
    public function validateRequiredSecrets(
        string $serviceUuid,
        string $environment,
        array $requiredSecrets = null
    ): array {
        if ($requiredSecrets === null) {
            $requiredSecrets = $this->config->reservedSecrets;
        }

        $secrets = $this->getSecretsForEnvironment($serviceUuid, $environment);
        $secretNames = array_column($secrets, 'key_name');
        $missing = [];

        foreach ($requiredSecrets as $required) {
            if (!in_array($required, $secretNames)) {
                $missing[] = $required;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * Get KUBECONFIG secret
     */
    public function getKubeconfig(string $serviceUuid, string $environment): ?array
    {
        // Try environment-specific first
        $kubeconfig = $this->commonModel->getSecretByServiceUuid("KUBECONFIG", $serviceUuid, $environment);

        // Fall back to global
        if (empty($kubeconfig)) {
            $kubeconfig = $this->commonModel->getSecretByServiceUuid("KUBECONFIG", $serviceUuid, NULL);
        }

        if (empty($kubeconfig)) {
            return null;
        }

        return [
            'key_value' => $kubeconfig['key_value'],
            'is_base64' => true
        ];
    }

    /**
     * Decode base64 kubeconfig safely
     */
    public function decodeKubeconfig(string $base64Content): ?string
    {
        $decoded = base64_decode($base64Content, true);

        if ($decoded === false) {
            $this->logger->error("Failed to decode KUBECONFIG");
            return null;
        }

        return $decoded;
    }

    /**
     * Get secret by name with environment fallback
     */
    public function getSecretValue(
        string $serviceUuid,
        string $secretName,
        string $environment
    ): ?string {
        // Try environment-specific first
        $secret = $this->commonModel->getSecretByServiceUuid($secretName, $serviceUuid, $environment);

        // Fall back to global
        if (empty($secret)) {
            $secret = $this->commonModel->getSecretByServiceUuid($secretName, $serviceUuid, NULL);
        }

        return $secret['key_value'] ?? null;
    }

    /**
     * Sanitize secret values for logging
     */
    public function sanitizeForLogging(array $secrets): array
    {
        return array_map(function($secret) {
            return [
                'key_name' => $secret['key_name'],
                'key_value' => '***REDACTED***',
                'secret_tags' => $secret['secret_tags'] ?? null,
                'is_override' => $secret['is_override'] ?? false
            ];
        }, $secrets);
    }
}

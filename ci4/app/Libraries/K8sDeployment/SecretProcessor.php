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
        $this->config      = config('Deployment');
        $this->logger      = new DeploymentLogger();
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
        $secrets          = $this->commonModel->getDataWhere("secrets_services", $serviceUuid, "service_id");
        $processedSecrets = [];

        foreach ($secrets as $secretRelation) {
            $secret = $this->commonModel->getSingleRowWhere("secrets", $secretRelation['secret_id'], "id");

            if (! $secret) {
                $this->logger->warning("Secret not found", ['secret_id' => $secretRelation['secret_id']]);
                continue;
            }

            // Check for environment-specific override
            $overrideSecret = $this->commonModel->getSecretByServiceUuid(
                $secret['key_name'],
                $serviceUuid,
                $environment
            );

            if (! empty($overrideSecret) && $overrideSecret['secret_tags'] === $environment) {
                // Use environment-specific secret
                $processedSecrets[] = [
                    'key_name'    => $overrideSecret['key_name'],
                    'key_value'   => $overrideSecret['key_value'],
                    'secret_tags' => $overrideSecret['secret_tags'],
                    'is_override' => true,
                ];
            } else {
                // Check if secret matches environment or is global
                if ($environment === $secret['secret_tags'] ||
                    ! $secret['secret_tags'] ||
                    ! isset($secret['secret_tags'])) {
                    $processedSecrets[] = [
                        'key_name'    => $secret['key_name'],
                        'key_value'   => $secret['key_value'],
                        'secret_tags' => $secret['secret_tags'] ?? null,
                        'is_override' => false,
                    ];
                } else {
                    // Try to find global secret (no tag)
                    $globalSecret = $this->commonModel->getSecretByServiceUuid(
                        $secret['key_name'],
                        $serviceUuid,
                        null
                    );

                    if (! empty($globalSecret)) {
                        $processedSecrets[] = [
                            'key_name'    => $globalSecret['key_name'],
                            'key_value'   => $globalSecret['key_value'],
                            'secret_tags' => null,
                            'is_override' => false,
                        ];
                    }
                }
            }
        }

        return $processedSecrets;
    }

    /**
     * Replace secrets in template content
     * Handles both YAML placeholders (KEY_NAME) and bash variables ($KEY_NAME)
     */
    public function replaceSecretsInTemplate(
        string $template,
        array $secrets,
        string $environment
    ): array {
        $replacedSecrets = [];
        $missingSecrets  = [];

        // ALWAYS replace TARGET_ENV first (before processing other secrets)
        // This ensures $TARGET_ENV is replaced with environment name, not treated as a variable
        // IMPORTANT: Replace $TARGET_ENV first (with $), then TARGET_ENV (without $)
        // If we do it in reverse order, TARGET_ENV gets replaced in $TARGET_ENV, creating $test
        if (strpos($template, 'TARGET_ENV') !== false || strpos($template, '$TARGET_ENV') !== false) {
            $template          = str_replace('$TARGET_ENV', $environment, $template);  // Must be first!
            $template          = str_replace('TARGET_ENV', $environment, $template);
            $replacedSecrets[] = 'TARGET_ENV';
        }

        foreach ($secrets as $secret) {
            $placeholder = $secret['key_name'];

            // Skip TARGET_ENV - already handled above
            if ($placeholder === 'TARGET_ENV') {
                continue;
            }

            // Skip KUBECONFIG in deployment scripts - it's handled separately as a file path
            // KUBECONFIG contains base64 certificate data which should NOT be inserted into scripts
            if ($placeholder === 'KUBECONFIG') {
                $replacedSecrets[] = $placeholder;
                continue;
            }

            // Replace both YAML placeholder format (KEY_NAME) and bash variable format ($KEY_NAME)
            $replaced = false;

            // Check and replace YAML format (without $)
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $secret['key_value'], $template);
                $replaced = true;
            }

            // Check and replace bash variable format (with $)
            if (strpos($template, '$' . $placeholder) !== false) {
                $template = str_replace('$' . $placeholder, $secret['key_value'], $template);
                $replaced = true;
            }

            if ($replaced) {
                $replacedSecrets[] = $placeholder;
            }
        }

        // Check for unreplaced placeholders (both formats)
        preg_match_all('/\$?[A-Z_]{3,}\b/', $template, $matches);
        $potentialPlaceholders = array_unique($matches[0]);

        foreach ($potentialPlaceholders as $placeholder) {
            // Remove $ prefix for comparison
            $cleanPlaceholder = ltrim($placeholder, '$');

            if (! in_array($cleanPlaceholder, $replacedSecrets) &&
                in_array($cleanPlaceholder, $this->config->reservedSecrets)) {
                $missingSecrets[] = $cleanPlaceholder;
            }
        }

        return [
            'template' => $template,
            'replaced' => $replacedSecrets,
            'missing'  => $missingSecrets,
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

        $secrets     = $this->getSecretsForEnvironment($serviceUuid, $environment);
        $secretNames = array_column($secrets, 'key_name');
        $missing     = [];

        foreach ($requiredSecrets as $required) {
            if (! in_array($required, $secretNames)) {
                $missing[] = $required;
            }
        }

        return [
            'valid'   => empty($missing),
            'missing' => $missing,
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
            $kubeconfig = $this->commonModel->getSecretByServiceUuid("KUBECONFIG", $serviceUuid, null);
        }

        if (empty($kubeconfig)) {
            return null;
        }

        return [
            'key_value' => $kubeconfig['key_value'],
            'is_base64' => true,
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
            $secret = $this->commonModel->getSecretByServiceUuid($secretName, $serviceUuid, null);
        }

        return $secret['key_value'] ?? null;
    }

    /**
     * Sanitize secret values for logging
     */
    public function sanitizeForLogging(array $secrets): array
    {
        return array_map(function ($secret) {
            return [
                'key_name'    => $secret['key_name'],
                'key_value'   => '***REDACTED***',
                'secret_tags' => $secret['secret_tags'] ?? null,
                'is_override' => $secret['is_override'] ?? false,
            ];
        }, $secrets);
    }
}

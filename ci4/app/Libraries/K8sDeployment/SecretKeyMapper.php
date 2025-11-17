<?php
namespace App\Libraries\K8sDeployment;

use App\Libraries\UUID;
use App\Models\Core\Common_model;

/**
 * SecretKeyMapper
 *
 * Professional implementation for mapping sealed secret keys to values template keys.
 * This class handles the dynamic mapping between:
 * - "Take keys from" (source): Keys in sealed secret YAML files
 * - "Add keys for" (target): Keys in values template YAML files
 *
 * Database Schema:
 * Table: service__secret_value_template__key
 * - service_id: UUID of the service
 * - secret_temp_id: UUID of the secret template (source)
 * - secret_key: Path to key in sealed secret file (e.g., "spec,encryptedData,env")
 * - values_temp_id: UUID of the values template (target)
 * - values_key: Path to key in values file (e.g., "sealedSecrets.env" or "database.password")
 *
 * Usage Example:
 * ```php
 * $mapper = new SecretKeyMapper();
 *
 * // Save mappings from form submission
 * $mapper->saveMappings($serviceUuid, $secretTemplateIds, $valuesTemplateId, $formData);
 *
 * // Retrieve mappings for a service
 * $mappings = $mapper->getMappingsForService($serviceUuid);
 * ```
 */
class SecretKeyMapper
{
    protected Common_model $commonModel;
    protected DeploymentLogger $logger;
    protected DeploymentValidator $validator;

    /** @var string Database table name */
    protected const TABLE_NAME = 'service__secret_value_template__key';

    public function __construct()
    {
        $this->commonModel = new Common_model();
        $this->logger      = new DeploymentLogger();
        $this->validator   = new DeploymentValidator();
    }

    /**
     * Set logger instance
     */
    public function setLogger(DeploymentLogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Save secret-to-values key mappings from form submission
     *
     * @param string $serviceUuid Service UUID
     * @param array $secretTemplateIds Array of secret template UUIDs
     * @param string $valuesTemplateId Values template UUID
     * @param array $secretKeys Associative array: [secretTemplateId => [keyPath]]
     * @param array $valuesKeys Associative array: [valuesTemplateId/secretTemplateId => [keyPath]]
     * @return array Result with status and count
     */
    public function saveMappings(
        string $serviceUuid,
        array $secretTemplateIds,
        string $valuesTemplateId,
        array $secretKeys,
        array $valuesKeys
    ): array {
        $this->logger->info("Saving secret key mappings", [
            'service_uuid'          => $serviceUuid,
            'secret_template_count' => count($secretTemplateIds),
            'values_template_id'    => $valuesTemplateId,
        ]);

        try {
            // Build mappings from form data
            $mappings = $this->buildMappingsFromFormData(
                $serviceUuid,
                $secretTemplateIds,
                $valuesTemplateId,
                $secretKeys,
                $valuesKeys
            );

            // Validate mappings
            $validationResult = $this->validateMappings($mappings);
            if (! $validationResult['valid']) {
                $this->logger->error("Invalid mappings", ['errors' => $validationResult['errors']]);
                return [
                    'success' => false,
                    'errors'  => $validationResult['errors'],
                ];
            }

            // Delete existing mappings for this service
            $this->deleteMappingsForService($serviceUuid);

            // Insert new mappings
            $inserted = 0;
            foreach ($mappings as $mapping) {
                $success = $this->commonModel->insertTableData($mapping, self::TABLE_NAME);
                if ($success) {
                    $inserted++;
                }
            }

            $this->logger->info("Saved secret key mappings", [
                'service_uuid' => $serviceUuid,
                'count'        => $inserted,
            ]);

            return [
                'success' => true,
                'count'   => $inserted,
            ];
        } catch (\Exception $e) {
            $this->logger->error("Failed to save mappings", [
                'error'        => $e->getMessage(),
                'service_uuid' => $serviceUuid,
            ]);
            throw $e;
        }
    }

    /**
     * Build mappings array from form data
     *
     * @param string $serviceUuid Service UUID
     * @param array $secretTemplateIds Secret template UUIDs
     * @param string $valuesTemplateId Values template UUID
     * @param array $secretKeys Secret keys from form
     * @param array $valuesKeys Values keys from form
     * @return array Array of mapping records
     */
    protected function buildMappingsFromFormData(
        string $serviceUuid,
        array $secretTemplateIds,
        string $valuesTemplateId,
        array $secretKeys,
        array $valuesKeys
    ): array {
        $mappings = [];

        foreach ($secretTemplateIds as $secretTemplateId) {
            // Get secret key for this template
            $secretKey = $secretKeys[$secretTemplateId][0] ?? null;

            // Build composite key for values lookup: valuesTemplateId/secretTemplateId
            $valuesLookupKey = "{$valuesTemplateId}/{$secretTemplateId}";
            $valuesKey       = $valuesKeys[$valuesLookupKey][0] ?? null;

            // Skip if either key is missing or empty
            if (empty($secretKey) || empty($valuesKey)) {
                $this->logger->warning("Skipping mapping with empty keys", [
                    'secret_template_id' => $secretTemplateId,
                    'secret_key'         => $secretKey,
                    'values_key'         => $valuesKey,
                ]);
                continue;
            }

            $mappings[] = [
                'uuid'             => UUID::v5(UUID::v4(), 'services'),
                'service_id'       => $serviceUuid,
                'secret_temp_id'   => $secretTemplateId,
                'secret_key'       => $secretKey,
                'values_temp_id'   => $valuesTemplateId,
                'values_key'       => $valuesKey,
                'uuid_business_id' => session()->get('uuid_business') ?? '',
            ];
        }

        return $mappings;
    }

    /**
     * Validate mappings before saving
     *
     * @param array $mappings Array of mapping records
     * @return array Validation result
     */
    protected function validateMappings(array $mappings): array
    {
        $errors = [];

        foreach ($mappings as $index => $mapping) {
            // Validate required fields
            $requiredFields = ['service_id', 'secret_temp_id', 'secret_key', 'values_temp_id', 'values_key'];
            foreach ($requiredFields as $field) {
                if (empty($mapping[$field])) {
                    $errors[] = "Mapping #{$index}: Missing required field '{$field}'";
                }
            }

            // Validate UUID formats
            if (! $this->validator->isValidUuid($mapping['service_id'] ?? '')) {
                $errors[] = "Mapping #{$index}: Invalid service_id UUID";
            }
            if (! $this->validator->isValidUuid($mapping['secret_temp_id'] ?? '')) {
                $errors[] = "Mapping #{$index}: Invalid secret_temp_id UUID";
            }
            if (! $this->validator->isValidUuid($mapping['values_temp_id'] ?? '')) {
                $errors[] = "Mapping #{$index}: Invalid values_temp_id UUID";
            }

            // Validate key paths (basic check - non-empty string)
            if (! empty($mapping['secret_key']) && ! is_string($mapping['secret_key'])) {
                $errors[] = "Mapping #{$index}: secret_key must be a string";
            }
            if (! empty($mapping['values_key']) && ! is_string($mapping['values_key'])) {
                $errors[] = "Mapping #{$index}: values_key must be a string";
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Delete all mappings for a service
     *
     * @param string $serviceUuid Service UUID
     * @return bool Success status
     */
    public function deleteMappingsForService(string $serviceUuid): bool
    {
        $this->logger->info("Deleting mappings for service", ['service_uuid' => $serviceUuid]);
        return $this->commonModel->deleteTableData(self::TABLE_NAME, $serviceUuid, 'service_id');
    }

    /**
     * Get all mappings for a service
     *
     * @param string $serviceUuid Service UUID
     * @return array Array of mappings
     */
    public function getMappingsForService(string $serviceUuid): array
    {
        return $this->commonModel->getSingleRowMultipleWhere(
            self::TABLE_NAME,
            ['service_id' => $serviceUuid],
            'array'
        ) ?: [];
    }

    /**
     * Get mapping for a specific secret template
     *
     * @param string $serviceUuid Service UUID
     * @param string $secretTemplateId Secret template UUID
     * @return array|null Mapping record or null
     */
    public function getMappingForSecretTemplate(
        string $serviceUuid,
        string $secretTemplateId
    ): ?array {
        $mapping = $this->commonModel->getSingleRowMultipleWhere(
            self::TABLE_NAME,
            [
                'service_id'     => $serviceUuid,
                'secret_temp_id' => $secretTemplateId,
            ]
        );

        return $mapping ?: null;
    }

    /**
     * Get mappings for a specific values template
     *
     * @param string $serviceUuid Service UUID
     * @param string $valuesTemplateId Values template UUID
     * @return array Array of mappings
     */
    public function getMappingsForValuesTemplate(
        string $serviceUuid,
        string $valuesTemplateId
    ): array {
        return $this->commonModel->getSingleRowMultipleWhere(
            self::TABLE_NAME,
            [
                'service_id'     => $serviceUuid,
                'values_temp_id' => $valuesTemplateId,
            ],
            'array'
        ) ?: [];
    }

    /**
     * Extract value from sealed secret using mapping
     *
     * This method retrieves a value from a sealed secret YAML array using the
     * configured path from the mapping.
     *
     * @param array $sealedSecret Parsed sealed secret YAML as array
     * @param array $mapping Mapping configuration
     * @param string $delimiter Path delimiter (default: comma for backward compatibility)
     * @return mixed|null The extracted value or null if not found
     */
    public function extractSecretValue(
        array $sealedSecret,
        array $mapping,
        string $delimiter = ','
    ) {
        if (empty($mapping['secret_key'])) {
            $this->logger->warning("Empty secret_key in mapping", ['mapping' => $mapping]);
            return null;
        }

        $value = getNestedValue($sealedSecret, $mapping['secret_key'], $delimiter);

        if ($value === null) {
            $this->logger->warning("Secret key not found in sealed secret", [
                'secret_key' => $mapping['secret_key'],
                'mapping_id' => $mapping['uuid'] ?? 'unknown',
            ]);
        }

        return $value;
    }

    /**
     * Prepare sealed secrets array for template rendering
     *
     * This method builds the array structure used by TemplateRenderer::injectSealedSecrets()
     *
     * @param string $serviceUuid Service UUID
     * @param array $sealedSecretFiles Array of sealed secret file information
     * @return array Prepared sealed secrets array
     */
    public function prepareSealedSecretsForInjection(
        string $serviceUuid,
        array $sealedSecretFiles
    ): array {
        $sealedSecrets = [];

        foreach ($sealedSecretFiles as $fileInfo) {
            $mapping = $this->getMappingForSecretTemplate(
                $serviceUuid,
                $fileInfo['template_id']
            );

            if (empty($mapping)) {
                $this->logger->debug("No mapping found for secret template", [
                    'template_id' => $fileInfo['template_id'],
                ]);
                continue;
            }

            if (empty($fileInfo['content'])) {
                $this->logger->warning("Empty sealed secret content", [
                    'template_id' => $fileInfo['template_id'],
                ]);
                continue;
            }

            $value = $this->extractSecretValue($fileInfo['content'], $mapping);

            if ($value !== null) {
                $sealedSecrets[$mapping['secret_temp_id']]['env_file'] = $value;
            }
        }

        return $sealedSecrets;
    }

    /**
     * Get mapping statistics for a service
     *
     * Useful for debugging and reporting
     *
     * @param string $serviceUuid Service UUID
     * @return array Statistics
     */
    public function getMappingStats(string $serviceUuid): array
    {
        $mappings = $this->getMappingsForService($serviceUuid);

        $stats = [
            'total_mappings'          => count($mappings),
            'unique_secret_templates' => count(array_unique(array_column($mappings, 'secret_temp_id'))),
            'unique_values_templates' => count(array_unique(array_column($mappings, 'values_temp_id'))),
            'mappings'                => [],
        ];

        foreach ($mappings as $mapping) {
            $stats['mappings'][] = [
                'secret_template' => $mapping['secret_temp_id'],
                'secret_key'      => $mapping['secret_key'],
                'values_template' => $mapping['values_temp_id'],
                'values_key'      => $mapping['values_key'],
            ];
        }

        return $stats;
    }
}

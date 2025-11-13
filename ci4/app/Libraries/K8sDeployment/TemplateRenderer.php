<?php

namespace App\Libraries\K8sDeployment;

use App\Models\Core\Common_model;
use App\Models\ServiceDomainsModel;
use Config\Deployment as DeploymentConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * TemplateRenderer
 *
 * Handles rendering of Kubernetes templates with secrets
 */
class TemplateRenderer
{
    protected Common_model $commonModel;
    protected ServiceDomainsModel $serviceDomainModel;
    protected SecretProcessor $secretProcessor;
    protected DeploymentConfig $config;
    protected DeploymentLogger $logger;
    protected DeploymentValidator $validator;

    public function __construct()
    {
        $this->commonModel = new Common_model();
        $this->serviceDomainModel = new ServiceDomainsModel();
        $this->secretProcessor = new SecretProcessor();
        $this->config = config('Deployment');
        $this->logger = new DeploymentLogger();
        $this->validator = new DeploymentValidator();
    }

    /**
     * Set logger instance
     */
    public function setLogger(DeploymentLogger $logger): void
    {
        $this->logger = $logger;
        $this->secretProcessor->setLogger($logger);
    }

    /**
     * Render secret templates
     */
    public function renderSecretTemplates(
        string $serviceUuid,
        string $environment,
        array $secretTemplateIds
    ): array {
        $renderedTemplates = [];
        $secrets = $this->secretProcessor->getSecretsForEnvironment($serviceUuid, $environment);

        foreach ($secretTemplateIds as $index => $templateId) {
            $template = $this->commonModel->getSingleRowWhere("templates", $templateId, "uuid");

            if (!$template) {
                throw new \RuntimeException("Secret template not found: $templateId");
            }

            // Validate YAML
            $validationResult = $this->validator->validateYaml($template['template_content']);
            if (!$validationResult['valid']) {
                throw new \RuntimeException("Invalid YAML in template $templateId: " . $validationResult['error']);
            }

            // Replace secrets
            $result = $this->secretProcessor->replaceSecretsInTemplate(
                $template['template_content'],
                $secrets,
                $environment
            );

            if (!empty($result['missing'])) {
                $this->logger->warning("Missing secrets in template", [
                    'template_id' => $templateId,
                    'missing' => $result['missing']
                ]);
            }

            $renderedTemplates[] = [
                'index' => $index,
                'template_id' => $templateId,
                'content' => $result['template'],
                'replaced_secrets' => $result['replaced']
            ];
        }

        return $renderedTemplates;
    }

    /**
     * Render values template
     */
    public function renderValuesTemplate(
        string $serviceUuid,
        string $environment,
        string $valuesTemplateId,
        array $sealedSecrets = []
    ): array {
        $template = $this->commonModel->getSingleRowWhere("templates", $valuesTemplateId, "uuid");

        if (!$template) {
            throw new \RuntimeException("Values template not found: $valuesTemplateId");
        }

        // Validate YAML
        $validationResult = $this->validator->validateYaml($template['template_content']);
        if (!$validationResult['valid']) {
            throw new \RuntimeException("Invalid YAML in values template: " . $validationResult['error']);
        }

        // Replace secrets
        $secrets = $this->secretProcessor->getSecretsForEnvironment($serviceUuid, $environment);
        $result = $this->secretProcessor->replaceSecretsInTemplate(
            $template['template_content'],
            $secrets,
            $environment
        );

        // Parse YAML
        $valuesArray = Yaml::parse($result['template']);

        // Add domain configuration
        $valuesArray = $this->injectDomainConfig($serviceUuid, $valuesArray);

        // Inject sealed secrets
        $valuesArray = $this->injectSealedSecrets(
            $serviceUuid,
            $valuesTemplateId,
            $valuesArray,
            $sealedSecrets
        );

        return [
            'content' => Yaml::dump($valuesArray, 10, 2),
            'array' => $valuesArray,
            'replaced_secrets' => $result['replaced']
        ];
    }

    /**
     * Inject domain configuration into values
     */
    protected function injectDomainConfig(string $serviceUuid, array $valuesArray): array
    {
        $serviceDomains = $this->serviceDomainModel->getRowsByService($serviceUuid);
        $hostsArray = [];

        foreach ($serviceDomains as $serviceDomain) {
            $domainData = $this->commonModel->getSingleRowWhere("domains", $serviceDomain['domain_uuid'], "uuid");

            if (!empty($domainData) && $domainData) {
                $hostsArray[] = [
                    'host' => $domainData['name'],
                    'paths' => [[
                        'path' => $domainData['domain_path'],
                        'pathType' => $domainData['domain_path_type'],
                        'serviceName' => $domainData['domain_service_name'],
                        'servicePort' => (int) $domainData['domain_service_port'],
                    ]]
                ];
            }
        }

        if (isset($valuesArray['ingress']['hosts']) && !empty($hostsArray)) {
            if (!is_array($valuesArray['ingress']['hosts'])) {
                $valuesArray['ingress']['hosts'] = [];
            }
            $valuesArray['ingress']['hosts'] = array_merge(
                $valuesArray['ingress']['hosts'],
                $hostsArray
            );
        }

        return $valuesArray;
    }

    /**
     * Inject sealed secrets into values
     */
    protected function injectSealedSecrets(
        string $serviceUuid,
        string $valuesTemplateId,
        array $valuesArray,
        array $sealedSecrets
    ): array {
        $mappings = $this->commonModel->getSingleRowMultipleWhere(
            "service__secret_value_template__key",
            [
                "values_temp_id" => $valuesTemplateId,
                "service_id" => $serviceUuid
            ],
            "array"
        );

        if (empty($mappings)) {
            return $valuesArray;
        }

        foreach ($mappings as $mapping) {
            $secretTempId = $mapping['secret_temp_id'];
            $valuesKey = $mapping['values_key'];

            if (isset($sealedSecrets[$secretTempId]) && isset($sealedSecrets[$secretTempId]['env_file'])) {
                // Navigate to nested key using dot notation
                $keys = explode('.', $valuesKey);
                $this->setNestedValue($valuesArray, $keys, $sealedSecrets[$secretTempId]['env_file']);
            }
        }

        return $valuesArray;
    }

    /**
     * Set nested array value using key path
     */
    protected function setNestedValue(array &$array, array $keys, $value): void
    {
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;
    }

    /**
     * Render deployment steps script
     */
    public function renderDeploymentScript(
        string $serviceUuid,
        string $environment
    ): string {
        $stepsBlock = $this->commonModel->getSingleRowWhere("blocks_list", $serviceUuid, "uuid_linked_table");

        if (!$stepsBlock || empty($stepsBlock['text'])) {
            throw new \RuntimeException("Deployment steps not found for service");
        }

        $steps = base64_decode($stepsBlock['text']);
        $secrets = $this->secretProcessor->getSecretsForEnvironment($serviceUuid, $environment);

        // Replace secrets in steps
        $result = $this->secretProcessor->replaceSecretsInTemplate($steps, $secrets, $environment);

        // Replace values file path
        $valuesPath = $this->config->writablePaths['values'] . $environment . "-values-" . $serviceUuid . ".yaml";
        $script = str_replace("-f values", "-f " . $valuesPath, $result['template']);

        return $script;
    }

    /**
     * Write template to file safely
     */
    public function writeTemplateToFile(
        string $content,
        string $filename,
        string $subdir = ''
    ): array {
        // Sanitize filename
        $sanitizedFilename = $this->validator->sanitizeFilename($filename);

        // Build full path
        $directory = WRITEPATH . $subdir;
        $fullPath = $directory . $sanitizedFilename;

        // Validate path
        $pathValidation = $this->validator->validateFilePath($fullPath);
        if (!$pathValidation['valid']) {
            throw new \RuntimeException($pathValidation['error']);
        }

        // Ensure directory exists
        if (!is_dir($directory)) {
            $this->logger->info("Creating directory", ['path' => $directory]);

            if (!mkdir($directory, $this->config->security['directoryPermissions'], true)) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                $this->logger->error("Failed to create directory", [
                    'path' => $directory,
                    'error' => $errorMsg,
                    'permissions' => decoct($this->config->security['directoryPermissions'])
                ]);
                throw new \RuntimeException("Failed to create directory: $directory - $errorMsg");
            }

            $this->logger->info("Directory created successfully", ['path' => $directory]);
        }

        // Write file
        try {
            $result = file_put_contents($fullPath, $content);
            if ($result === false) {
                throw new \RuntimeException("Failed to write file: $fullPath");
            }

            // Set permissions
            chmod($fullPath, $this->config->security['filePermissions']);

            return [
                'success' => true,
                'path' => $fullPath,
                'bytes' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error("File write failed", [
                'path' => $fullPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Read template from file safely
     */
    public function readTemplateFromFile(string $path): ?string
    {
        $pathValidation = $this->validator->validateFilePath($path);
        if (!$pathValidation['valid']) {
            throw new \RuntimeException($pathValidation['error']);
        }

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: $path");
        }

        return $content;
    }
}

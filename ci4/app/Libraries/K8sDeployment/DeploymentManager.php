<?php

namespace App\Libraries\K8sDeployment;

use App\Models\Deployment_model;
use App\Models\Core\Common_model;
use App\Libraries\UUID;
use Config\Deployment as DeploymentConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * DeploymentManager
 *
 * Main orchestrator for Kubernetes deployments
 */
class DeploymentManager
{
    protected Deployment_model $deploymentModel;
    protected Common_model $commonModel;
    protected DeploymentConfig $config;
    protected DeploymentLogger $logger;
    protected DeploymentValidator $validator;
    protected SecretProcessor $secretProcessor;
    protected TemplateRenderer $templateRenderer;

    protected ?string $deploymentUuid = null;
    protected ?string $businessUuid = null;

    public function __construct()
    {
        $this->deploymentModel = new Deployment_model();
        $this->commonModel = new Common_model();
        $this->config = config('Deployment');
        $this->logger = new DeploymentLogger();
        $this->validator = new DeploymentValidator();
        $this->secretProcessor = new SecretProcessor();
        $this->templateRenderer = new TemplateRenderer();

        // Set logger for other components
        $this->secretProcessor->setLogger($this->logger);
        $this->templateRenderer->setLogger($this->logger);

        $this->businessUuid = session('uuid_business');

        // Ensure all required directories exist
        $this->ensureDirectoriesExist();
    }

    /**
     * Ensure all required directories exist
     */
    protected function ensureDirectoriesExist(): void
    {
        $directories = [
            WRITEPATH . 'secret/',
            WRITEPATH . 'values/',
            WRITEPATH . 'helm/',
            WRITEPATH . 'deployment_logs/',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!mkdir($directory, $this->config->security['directoryPermissions'], true)) {
                    log_message('error', "Failed to create directory: $directory");
                    throw new \RuntimeException("Failed to create required directory: $directory");
                }
                log_message('info', "Created directory: $directory");
            }
        }
    }

    /**
     * Deploy service to Kubernetes
     */
    public function deploy(string $serviceUuid, string $environment, ?string $deployedBy = null): array
    {
        // Create deployment record
        $this->deploymentUuid = UUID::v5(UUID::v4(), 'deployments');
        $this->logger->setDeploymentUuid($this->deploymentUuid);

        try {
            // Validate inputs
            $this->logger->startStep('validate_inputs');
            $this->validateDeploymentInputs($serviceUuid, $environment);
            $this->logger->completeStep('validate_inputs', 'Validation successful');

            // Check for locks
            $this->logger->startStep('check_locks');
            if ($this->deploymentModel->isDeploymentInProgress($serviceUuid, $environment)) {
                throw new \RuntimeException("Deployment already in progress for $serviceUuid in $environment");
            }
            $this->logger->completeStep('check_locks');

            // Create deployment record
            $this->createDeploymentRecord($serviceUuid, $environment, $deployedBy);

            // Get service configuration
            $this->logger->startStep('load_configuration');
            $serviceConfig = $this->loadServiceConfiguration($serviceUuid);
            $this->logger->completeStep('load_configuration');

            // Process secret templates
            $this->logger->startStep('process_secret_templates');
            $secretFiles = $this->processSecretTemplates($serviceUuid, $environment, $serviceConfig);
            $this->logger->completeStep('process_secret_templates', count($secretFiles) . ' secret templates processed');

            // Run kubeseal
            $this->logger->startStep('seal_secrets');
            $sealedSecrets = $this->sealSecrets($serviceUuid, $environment, $secretFiles);
            $this->logger->completeStep('seal_secrets', count($sealedSecrets) . ' secrets sealed');

            // Process values template
            $this->logger->startStep('process_values_template');
            $valuesFile = $this->processValuesTemplate($serviceUuid, $environment, $serviceConfig, $sealedSecrets);
            $this->logger->completeStep('process_values_template');

            // Generate deployment script
            $this->logger->startStep('generate_deployment_script');
            $scriptPath = $this->generateDeploymentScript($serviceUuid, $environment);
            $this->logger->completeStep('generate_deployment_script');

            // Execute deployment
            $this->logger->startStep('execute_deployment');
            $output = $this->executeDeployment($scriptPath);
            $this->logger->completeStep('execute_deployment', 'Deployment initiated');

            // Mark as successful
            $this->deploymentModel->updateStatus($this->deploymentUuid, 'success', [
                'helm_output' => $output
            ]);

            $this->logger->info("Deployment completed successfully");

            return [
                'success' => true,
                'deployment_uuid' => $this->deploymentUuid,
                'message' => 'Service deployment completed successfully',
                'output' => $output
            ];

        } catch (\Exception $e) {
            $this->logger->error("Deployment failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->deploymentUuid) {
                $this->deploymentModel->updateStatus($this->deploymentUuid, 'failed', [
                    'error_message' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'deployment_uuid' => $this->deploymentUuid,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate deployment inputs
     */
    protected function validateDeploymentInputs(string $serviceUuid, string $environment): void
    {
        $uuidValidation = $this->validator->validateUuid($serviceUuid);
        if (!$uuidValidation['valid']) {
            throw new \InvalidArgumentException($uuidValidation['error']);
        }

        $envValidation = $this->validator->validateEnvironment($environment);
        if (!$envValidation['valid']) {
            throw new \InvalidArgumentException($envValidation['error']);
        }

        // Validate required secrets exist
        $secretValidation = $this->secretProcessor->validateRequiredSecrets($serviceUuid, $environment);
        if (!$secretValidation['valid']) {
            throw new \RuntimeException(
                "Missing required secrets: " . implode(', ', $secretValidation['missing'])
            );
        }
    }

    /**
     * Create deployment record
     */
    protected function createDeploymentRecord(string $serviceUuid, string $environment, ?string $deployedBy): void
    {
        $deployedBy = $deployedBy ?? session('uuid');

        $this->deploymentModel->insert([
            'uuid' => $this->deploymentUuid,
            'service_uuid' => $serviceUuid,
            'environment' => $environment,
            'status' => 'in_progress',
            'deployed_by' => $deployedBy,
            'uuid_business_id' => $this->businessUuid
        ]);
    }

    /**
     * Load service configuration
     */
    protected function loadServiceConfiguration(string $serviceUuid): array
    {
        $serviceTemplates = $this->commonModel->getSingleRowWhere("templates__services", $serviceUuid, "service_id");

        if (!$serviceTemplates) {
            throw new \RuntimeException("Service templates configuration not found");
        }

        $secretTemplateIds = isJsonEncoded($serviceTemplates['secret_template_id'])
            ? json_decode($serviceTemplates['secret_template_id'], true)
            : [$serviceTemplates['secret_template_id']];

        return [
            'secret_template_ids' => $secretTemplateIds,
            'values_template_id' => $serviceTemplates['values_template_id']
        ];
    }

    /**
     * Process secret templates
     */
    protected function processSecretTemplates(string $serviceUuid, string $environment, array $config): array
    {
        $renderedTemplates = $this->templateRenderer->renderSecretTemplates(
            $serviceUuid,
            $environment,
            $config['secret_template_ids']
        );

        $secretFiles = [];

        foreach ($renderedTemplates as $template) {
            $filename = "{$environment}-secret-{$template['index']}-{$serviceUuid}.yaml";

            $result = $this->templateRenderer->writeTemplateToFile(
                $template['content'],
                $filename,
                'secret/'
            );

            $secretFiles[] = [
                'index' => $template['index'],
                'template_id' => $template['template_id'],
                'path' => $result['path']
            ];
        }

        return $secretFiles;
    }

    /**
     * Seal secrets using kubeseal
     */
    protected function sealSecrets(string $serviceUuid, string $environment, array $secretFiles): array
    {
        // Get kubeconfig
        $kubeconfigData = $this->secretProcessor->getKubeconfig($serviceUuid, $environment);
        if (!$kubeconfigData) {
            throw new \RuntimeException("KUBECONFIG not found for service");
        }

        // Decode and write kubeconfig
        $kubeconfig = $this->secretProcessor->decodeKubeconfig($kubeconfigData['key_value']);
        $kubeconfigPath = WRITEPATH . 'secret/k3s.yaml';

        $this->templateRenderer->writeTemplateToFile($kubeconfig, 'k3s.yaml', 'secret/');

        // Generate kubeseal script
        $script = $this->generateKubesealScript($serviceUuid, $environment, $secretFiles, $kubeconfigPath);
        $scriptPath = WRITEPATH . "secret/{$environment}-kubeseal-secret.sh";
        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0700);

        // Execute kubeseal
        $output = $this->executeCommand("bash " . escapeshellarg($scriptPath));

        if ($output['exit_code'] !== 0) {
            throw new \RuntimeException("Kubeseal failed: " . $output['stderr']);
        }

        // Read sealed secrets
        $sealedSecrets = [];
        foreach ($secretFiles as $secretFile) {
            $sealedPath = WRITEPATH . "secret/{$environment}-sealed-secret-{$secretFile['index']}-{$serviceUuid}.yaml";

            if (!file_exists($sealedPath)) {
                throw new \RuntimeException("Sealed secret file not created: $sealedPath");
            }

            $sealedContent = Yaml::parse(file_get_contents($sealedPath));

            // Extract sealed secret mappings
            $mapping = $this->commonModel->getSingleRowMultipleWhere(
                "service__secret_value_template__key",
                [
                    "secret_temp_id" => $secretFile['template_id'],
                    "service_id" => $serviceUuid
                ]
            );

            if (!empty($mapping)) {
                $envSecret = getNestedValue($sealedContent, $mapping['secret_key'], ",");
                if ($envSecret) {
                    $sealedSecrets[$mapping['secret_temp_id']]['env_file'] = $envSecret;
                }
            }
        }

        return $sealedSecrets;
    }

    /**
     * Generate kubeseal script
     */
    protected function generateKubesealScript(string $serviceUuid, string $environment, array $secretFiles, string $kubeconfigPath): string
    {
        $script = "#!/bin/bash\n";
        $script .= "set -e\n";
        $script .= "export KUBECONFIG=" . escapeshellarg($kubeconfigPath) . "\n";

        foreach ($secretFiles as $file) {
            $inputFile = WRITEPATH . "secret/{$environment}-secret-{$file['index']}-{$serviceUuid}.yaml";
            $outputFile = WRITEPATH . "secret/{$environment}-sealed-secret-{$file['index']}-{$serviceUuid}.yaml";

            $script .= $this->config->kubeseal['binary'] . " --format=yaml ";
            $script .= "< " . escapeshellarg($inputFile) . " ";
            $script .= "> " . escapeshellarg($outputFile) . "\n";
        }

        return $script;
    }

    /**
     * Process values template
     */
    protected function processValuesTemplate(string $serviceUuid, string $environment, array $config, array $sealedSecrets): string
    {
        $rendered = $this->templateRenderer->renderValuesTemplate(
            $serviceUuid,
            $environment,
            $config['values_template_id'],
            $sealedSecrets
        );

        $filename = "{$environment}-values-{$serviceUuid}.yaml";
        $result = $this->templateRenderer->writeTemplateToFile(
            $rendered['content'],
            $filename,
            'values/'
        );

        return $result['path'];
    }

    /**
     * Generate deployment script
     */
    protected function generateDeploymentScript(string $serviceUuid, string $environment): string
    {
        $script = $this->templateRenderer->renderDeploymentScript($serviceUuid, $environment);

        $filename = "{$environment}-install-{$serviceUuid}.sh";
        $result = $this->templateRenderer->writeTemplateToFile(
            $script,
            $filename,
            'helm/'
        );

        chmod($result['path'], 0700);

        return $result['path'];
    }

    /**
     * Execute deployment
     */
    protected function executeDeployment(string $scriptPath): string
    {
        $command = "bash " . escapeshellarg($scriptPath);
        $result = $this->executeCommand($command);

        if ($result['exit_code'] !== 0) {
            throw new \RuntimeException("Helm deployment failed: " . $result['stderr']);
        }

        return $result['stdout'];
    }

    /**
     * Execute shell command safely
     */
    protected function executeCommand(string $command, ?int $timeout = null): array
    {
        $timeout = $timeout ?? $this->config->helm['timeout'];

        $this->logger->debug("Executing command", ['command' => $command]);

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException("Failed to execute command");
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr
        ];
    }

    /**
     * Get deployment status
     */
    public function getDeploymentStatus(string $deploymentUuid): array
    {
        $deployment = $this->deploymentModel->getByUuid($deploymentUuid);

        if (!$deployment) {
            return [
                'found' => false,
                'error' => 'Deployment not found'
            ];
        }

        $logs = $this->logger->getDeploymentLogs($deploymentUuid);
        $summary = $this->logger->getDeploymentSummary($deploymentUuid);

        return [
            'found' => true,
            'deployment' => $deployment,
            'logs' => $logs,
            'summary' => $summary
        ];
    }
}

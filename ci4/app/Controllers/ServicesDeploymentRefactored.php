<?php

namespace App\Controllers;

use App\Libraries\K8sDeployment\DeploymentManager;
use App\Libraries\K8sDeployment\DeploymentLogger;
use App\Models\Deployment_model;
use CodeIgniter\API\ResponseTrait;

/**
 * ServicesDeploymentRefactored
 *
 * Refactored deployment methods using the new K8sDeployment libraries
 * This is a reference implementation showing how to use the new system
 *
 * To integrate: Copy these methods into Services.php and replace the old ones
 */
class ServicesDeploymentRefactored extends Api
{
    use ResponseTrait;

    protected DeploymentManager $deploymentManager;
    protected DeploymentLogger $deploymentLogger;
    protected Deployment_model $deploymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->deploymentManager = new DeploymentManager();
        $this->deploymentLogger = new DeploymentLogger();
        $this->deploymentModel = new Deployment_model();
    }

    /**
     * REFACTORED: Deploy service to Kubernetes
     *
     * This replaces the old deploy_service() method
     */
    public function deploy_service($uuid = null)
    {
        if (empty($uuid)) {
            return $this->failValidationErrors('Service UUID is required');
        }

        $post = $this->request->getPost();

        // Handle marketing email deployment (unchanged)
        if (isset($post['data']['serviceType']) && $post['data']['serviceType'] === "marketing") {
            return $this->deployMarketing($uuid);
        }

        // Handle Kubernetes deployment (REFACTORED)
        return $this->deployToKubernetes($uuid, $post);
    }

    /**
     * Deploy service to Kubernetes using new DeploymentManager
     */
    protected function deployToKubernetes(string $uuid, array $post): object
    {
        // Validate and extract environments
        $selectedTags = array_filter($post['data']['selectedTags'] ?? [], 'filterFalseValues');

        if (empty($selectedTags)) {
            return $this->fail('No environments selected', 400);
        }

        $results = [];
        $hasErrors = false;

        // Deploy to each selected environment
        foreach ($selectedTags as $tagData) {
            $environment = array_keys($tagData)[0] ?? null;

            if (empty($environment)) {
                $results[] = [
                    'environment' => 'unknown',
                    'success' => false,
                    'error' => 'Invalid environment selection'
                ];
                $hasErrors = true;
                continue;
            }

            try {
                // Use DeploymentManager to handle the deployment
                $result = $this->deploymentManager->deploy(
                    $uuid,
                    $environment,
                    session('uuid') // deployed_by
                );

                $results[] = array_merge([
                    'environment' => $environment
                ], $result);

                if (!$result['success']) {
                    $hasErrors = true;
                }

            } catch (\Exception $e) {
                $results[] = [
                    'environment' => $environment,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $hasErrors = true;
            }
        }

        // Return appropriate response
        if ($hasErrors) {
            return $this->respond([
                'message' => 'Some deployments failed. Check details for more information.',
                'status' => 'partial_success',
                'deployments' => $results
            ], 207); // 207 Multi-Status
        }

        return $this->respond([
            'message' => 'All deployments completed successfully',
            'status' => 'success',
            'deployments' => $results
        ]);
    }

    /**
     * Deploy marketing email (unchanged, extracted for clarity)
     */
    protected function deployMarketing(string $uuid): object
    {
        try {
            $this->create_marketing_template($uuid);
            return $this->respond([
                'message' => 'Email has been sent to selected companies.',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Get deployment status
     */
    public function deployment_status(string $deploymentUuid)
    {
        try {
            $status = $this->deploymentManager->getDeploymentStatus($deploymentUuid);

            if (!$status['found']) {
                return $this->failNotFound('Deployment not found');
            }

            return $this->respond($status);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Get deployment history for a service
     */
    public function deployment_history(string $serviceUuid, ?string $environment = null)
    {
        try {
            $deployments = $this->deploymentModel->getByService($serviceUuid, $environment);

            return $this->respond([
                'service_uuid' => $serviceUuid,
                'environment' => $environment,
                'deployments' => $deployments,
                'total' => count($deployments)
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Get deployment logs
     */
    public function deployment_logs(string $deploymentUuid)
    {
        try {
            $logs = $this->deploymentLogger->getDeploymentLogs($deploymentUuid);
            $summary = $this->deploymentLogger->getDeploymentSummary($deploymentUuid);

            return $this->respond([
                'deployment_uuid' => $deploymentUuid,
                'logs' => $logs,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Get active deployments
     */
    public function active_deployments()
    {
        try {
            $businessUuid = session('uuid_business');
            $deployments = $this->deploymentModel->getActiveDeployments($businessUuid);

            return $this->respond([
                'active_deployments' => $deployments,
                'count' => count($deployments)
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Get deployment statistics
     */
    public function deployment_statistics(?string $environment = null)
    {
        try {
            $businessUuid = session('uuid_business');
            $stats = $this->deploymentModel->getStatistics($businessUuid, $environment);

            return $this->respond($stats);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Rollback deployment (TO BE IMPLEMENTED)
     */
    public function rollback_deployment(string $deploymentUuid)
    {
        // TODO: Implement rollback functionality
        // 1. Get deployment configuration from database
        // 2. Get previous successful deployment
        // 3. Run helm rollback command
        // 4. Update deployment status

        return $this->failServerError('Rollback functionality not yet implemented');
    }
}

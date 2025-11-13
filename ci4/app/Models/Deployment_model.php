<?php

namespace App\Models;

use CodeIgniter\Model;

class Deployment_model extends Model
{
    protected $table = 'deployments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'service_uuid',
        'environment',
        'status',
        'deployed_by',
        'helm_release_name',
        'deployment_config',
        'started_at',
        'completed_at',
        'error_message',
        'kubectl_output',
        'helm_output',
        'uuid_business_id'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    // Validation
    protected $validationRules = [
        'uuid' => 'required|min_length[36]|max_length[36]',
        'service_uuid' => 'required|min_length[36]|max_length[36]',
        'environment' => 'required|max_length[50]',
        'status' => 'required|in_list[pending,in_progress,success,failed,rolled_back]',
        'deployed_by' => 'required|max_length[36]',
    ];

    protected $validationMessages = [
        'uuid' => [
            'required' => 'Deployment UUID is required'
        ],
        'service_uuid' => [
            'required' => 'Service UUID is required'
        ],
        'environment' => [
            'required' => 'Environment is required'
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get deployment by UUID
     */
    public function getByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }

    /**
     * Get deployments for a service
     */
    public function getByService(string $serviceUuid, ?string $environment = null): array
    {
        $builder = $this->where('service_uuid', $serviceUuid);

        if ($environment) {
            $builder->where('environment', $environment);
        }

        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Get latest deployment for service in environment
     */
    public function getLatestDeployment(string $serviceUuid, string $environment): ?array
    {
        return $this->where('service_uuid', $serviceUuid)
            ->where('environment', $environment)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Get successful deployments for rollback
     */
    public function getSuccessfulDeployments(string $serviceUuid, string $environment, int $limit = 10): array
    {
        return $this->where('service_uuid', $serviceUuid)
            ->where('environment', $environment)
            ->where('status', 'success')
            ->orderBy('completed_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get active deployments (in progress)
     */
    public function getActiveDeployments(?string $businessUuid = null): array
    {
        $builder = $this->where('status', 'in_progress');

        if ($businessUuid) {
            $builder->where('uuid_business_id', $businessUuid);
        }

        return $builder->findAll();
    }

    /**
     * Check if deployment is in progress
     */
    public function isDeploymentInProgress(string $serviceUuid, string $environment): bool
    {
        $count = $this->where('service_uuid', $serviceUuid)
            ->where('environment', $environment)
            ->where('status', 'in_progress')
            ->countAllResults();

        return $count > 0;
    }

    /**
     * Update deployment status
     */
    public function updateStatus(string $uuid, string $status, ?array $additionalData = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'in_progress' && !isset($additionalData['started_at'])) {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        if (in_array($status, ['success', 'failed', 'rolled_back']) && !isset($additionalData['completed_at'])) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        if ($additionalData) {
            $data = array_merge($data, $additionalData);
        }

        return $this->where('uuid', $uuid)->set($data)->update();
    }

    /**
     * Get deployment statistics
     */
    public function getStatistics(?string $businessUuid = null, ?string $environment = null): array
    {
        $builder = $this->builder();

        if ($businessUuid) {
            $builder->where('uuid_business_id', $businessUuid);
        }

        if ($environment) {
            $builder->where('environment', $environment);
        }

        $query = $builder->select('
            COUNT(*) as total_deployments,
            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_deployments,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_deployments,
            SUM(CASE WHEN status = "rolled_back" THEN 1 ELSE 0 END) as rolled_back_deployments,
            AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration_seconds
        ')->get()->getRowArray();

        return $query ?: [
            'total_deployments' => 0,
            'successful_deployments' => 0,
            'failed_deployments' => 0,
            'rolled_back_deployments' => 0,
            'avg_duration_seconds' => 0
        ];
    }

    /**
     * Get recent deployments
     */
    public function getRecentDeployments(int $limit = 10, ?string $businessUuid = null): array
    {
        $builder = $this;

        if ($businessUuid) {
            $builder = $builder->where('uuid_business_id', $businessUuid);
        }

        return $builder->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Cleanup old deployments
     */
    public function cleanupOldDeployments(int $daysToKeep = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        return $this->where('created_at <', $cutoffDate)
            ->where('status !=', 'in_progress')
            ->delete();
    }
}

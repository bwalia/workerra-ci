<?php

namespace App\Models;

use CodeIgniter\Model;

class DeploymentLog_model extends Model
{
    protected $table = 'deployment_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'deployment_uuid',
        'step',
        'status',
        'message',
        'output',
        'duration_ms'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $deletedField = null;

    // Validation
    protected $validationRules = [
        'deployment_uuid' => 'required|min_length[36]|max_length[36]',
        'step' => 'required|max_length[100]',
        'status' => 'required|in_list[pending,in_progress,success,failed,skipped]',
    ];

    protected $validationMessages = [];
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
     * Get logs for a deployment
     */
    public function getByDeployment(string $deploymentUuid): array
    {
        return $this->where('deployment_uuid', $deploymentUuid)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Get failed steps
     */
    public function getFailedSteps(string $deploymentUuid): array
    {
        return $this->where('deployment_uuid', $deploymentUuid)
            ->where('status', 'failed')
            ->findAll();
    }

    /**
     * Get step duration
     */
    public function getStepDuration(string $deploymentUuid, string $step): ?int
    {
        $log = $this->where('deployment_uuid', $deploymentUuid)
            ->where('step', $step)
            ->where('status', 'success')
            ->first();

        return $log ? $log['duration_ms'] : null;
    }

    /**
     * Delete logs for deployment
     */
    public function deleteByDeployment(string $deploymentUuid): bool
    {
        return $this->where('deployment_uuid', $deploymentUuid)->delete();
    }
}

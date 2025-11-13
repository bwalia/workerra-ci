<?php

namespace App\Libraries\K8sDeployment;

use App\Models\DeploymentLog_model;
use Config\Deployment as DeploymentConfig;

/**
 * DeploymentLogger
 *
 * Handles logging for deployment operations
 */
class DeploymentLogger
{
    protected DeploymentLog_model $logModel;
    protected DeploymentConfig $config;
    protected ?string $deploymentUuid = null;
    protected array $stepTimestamps = [];

    public function __construct()
    {
        $this->logModel = new DeploymentLog_model();
        $this->config = config('Deployment');
    }

    /**
     * Set the current deployment UUID for logging
     */
    public function setDeploymentUuid(string $uuid): void
    {
        $this->deploymentUuid = $uuid;
    }

    /**
     * Log a deployment step
     */
    public function logStep(string $step, string $status, ?string $message = null, ?string $output = null): void
    {
        if (!$this->config->logging['enabled']) {
            return;
        }

        $duration = null;
        if ($status === 'success' || $status === 'failed') {
            $duration = $this->calculateStepDuration($step);
        }

        $data = [
            'deployment_uuid' => $this->deploymentUuid,
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'output' => $this->shouldLogOutput() ? $output : null,
            'duration_ms' => $duration,
        ];

        try {
            $this->logModel->insert($data);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log deployment step: ' . $e->getMessage());
        }

        // Also log to CodeIgniter's log system
        $this->logToSystem($step, $status, $message);
    }

    /**
     * Start timing a step
     */
    public function startStep(string $step): void
    {
        $this->stepTimestamps[$step] = microtime(true);
        $this->logStep($step, 'in_progress', "Starting: $step");
    }

    /**
     * Mark step as successful
     */
    public function completeStep(string $step, ?string $message = null, ?string $output = null): void
    {
        $this->logStep($step, 'success', $message ?? "Completed: $step", $output);
        unset($this->stepTimestamps[$step]);
    }

    /**
     * Mark step as failed
     */
    public function failStep(string $step, string $error, ?string $output = null): void
    {
        $this->logStep($step, 'failed', "Failed: $error", $output);
        unset($this->stepTimestamps[$step]);
    }

    /**
     * Skip a step
     */
    public function skipStep(string $step, string $reason): void
    {
        $this->logStep($step, 'skipped', $reason);
    }

    /**
     * Log informational message
     */
    public function info(string $message, ?array $context = null): void
    {
        $contextStr = $context ? json_encode($context) : null;
        log_message('info', "[Deployment:{$this->deploymentUuid}] $message", $contextStr ? ['context' => $contextStr] : []);
    }

    /**
     * Log warning message
     */
    public function warning(string $message, ?array $context = null): void
    {
        $contextStr = $context ? json_encode($context) : null;
        log_message('warning', "[Deployment:{$this->deploymentUuid}] $message", $contextStr ? ['context' => $contextStr] : []);
    }

    /**
     * Log error message
     */
    public function error(string $message, ?array $context = null): void
    {
        $contextStr = $context ? json_encode($context) : null;
        log_message('error', "[Deployment:{$this->deploymentUuid}] $message", $contextStr ? ['context' => $contextStr] : []);
    }

    /**
     * Log debug message
     */
    public function debug(string $message, ?array $context = null): void
    {
        if ($this->config->logging['level'] === 'debug') {
            $contextStr = $context ? json_encode($context) : null;
            log_message('debug', "[Deployment:{$this->deploymentUuid}] $message", $contextStr ? ['context' => $contextStr] : []);
        }
    }

    /**
     * Get logs for a deployment
     */
    public function getDeploymentLogs(string $deploymentUuid): array
    {
        return $this->logModel->where('deployment_uuid', $deploymentUuid)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Calculate step duration in milliseconds
     */
    protected function calculateStepDuration(string $step): ?int
    {
        if (isset($this->stepTimestamps[$step])) {
            $duration = (microtime(true) - $this->stepTimestamps[$step]) * 1000;
            return (int) round($duration);
        }
        return null;
    }

    /**
     * Check if shell output should be logged
     */
    protected function shouldLogOutput(): bool
    {
        return $this->config->logging['logShellOutput'] ?? true;
    }

    /**
     * Log to CodeIgniter's logging system
     */
    protected function logToSystem(string $step, string $status, ?string $message): void
    {
        $level = match($status) {
            'failed' => 'error',
            'success' => 'info',
            'in_progress' => 'info',
            'skipped' => 'warning',
            default => 'info'
        };

        $logMessage = "[Deployment:{$this->deploymentUuid}] [$step] [$status]";
        if ($message) {
            $logMessage .= " $message";
        }

        log_message($level, $logMessage);
    }

    /**
     * Create a deployment summary
     */
    public function getDeploymentSummary(string $deploymentUuid): array
    {
        $logs = $this->getDeploymentLogs($deploymentUuid);

        $summary = [
            'total_steps' => count($logs),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total_duration_ms' => 0,
            'steps' => []
        ];

        foreach ($logs as $log) {
            switch ($log['status']) {
                case 'success':
                    $summary['successful']++;
                    break;
                case 'failed':
                    $summary['failed']++;
                    break;
                case 'skipped':
                    $summary['skipped']++;
                    break;
            }

            if ($log['duration_ms']) {
                $summary['total_duration_ms'] += $log['duration_ms'];
            }

            $summary['steps'][] = [
                'step' => $log['step'],
                'status' => $log['status'],
                'duration_ms' => $log['duration_ms'],
                'message' => $log['message']
            ];
        }

        return $summary;
    }
}

<?php require_once (APPPATH . 'Views/common/list-title.php'); ?>

<div class="main_content_iner">
    <div class="container-fluid p-0">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="white_card card_height_100 mb_30">
                    <div class="white_card_header">
                        <div class="box_header m-0">
                            <div class="main-title">
                                <h3 class="m-0">
                                    Job Phases
                                    <?php if (isset($job)): ?>
                                        - <?= $job->job_name ?> (<?= $job->job_number ?>)
                                    <?php endif; ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="white_card_body">

                        <!-- Job Info Card -->
                        <?php if (isset($job)): ?>
                        <div class="card mb-4 border-primary">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Project:</strong> <?= $job->project_name ?? 'N/A' ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Status:</strong>
                                        <?php
                                            $statusClass = 'secondary';
                                            switch($job->status) {
                                                case 'Planning': $statusClass = 'warning'; break;
                                                case 'In Progress': $statusClass = 'primary'; break;
                                                case 'Completed': $statusClass = 'success'; break;
                                                case 'On Hold': $statusClass = 'secondary'; break;
                                                case 'Cancelled': $statusClass = 'danger'; break;
                                            }
                                        ?>
                                        <span class="badge badge-<?= $statusClass ?>"><?= $job->status ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Priority:</strong>
                                        <?php
                                            $priorityClass = 'secondary';
                                            switch($job->priority) {
                                                case 'Low': $priorityClass = 'secondary'; break;
                                                case 'Normal': $priorityClass = 'info'; break;
                                                case 'High': $priorityClass = 'warning'; break;
                                                case 'Urgent': $priorityClass = 'danger'; break;
                                            }
                                        ?>
                                        <span class="badge badge-<?= $priorityClass ?>"><?= $job->priority ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Completion:</strong> <?= $job->completion_percentage ?? 0 ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="mb-3">
                            <a href="/project_job_phases/edit/0/<?= $jobUuid ?>" class="btn btn-primary">
                                <i class="fa fa-plus"></i> New Phase
                            </a>
                            <a href="/project_jobs/edit/<?= $jobUuid ?>" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Job
                            </a>
                            <a href="/project_jobs" class="btn btn-outline-secondary">
                                <i class="fa fa-list"></i> All Jobs
                            </a>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="phasesTable">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Phase Number</th>
                                        <th>Phase Name</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Assigned To</th>
                                        <th>Dates</th>
                                        <th>Hours</th>
                                        <th>Dependencies</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(APPPATH . 'Views/common/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#phasesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/project_job_phases/phasesList/<?= $jobUuid ?>',
            type: 'GET',
            dataSrc: 'data'
        },
        columns: [
            {
                data: 'phase_order',
                render: function(data, type, row) {
                    return `<span class="badge badge-light">${data}</span>`;
                }
            },
            { data: 'phase_number' },
            { data: 'phase_name' },
            {
                data: 'status',
                render: function(data) {
                    const badges = {
                        'Not Started': 'secondary',
                        'In Progress': 'primary',
                        'Completed': 'success',
                        'Blocked': 'danger'
                    };
                    return `<span class="badge badge-${badges[data] || 'secondary'}">${data}</span>`;
                }
            },
            {
                data: 'completion_percentage',
                render: function(data) {
                    return `<div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: ${data}%" aria-valuenow="${data}" aria-valuemin="0" aria-valuemax="100">${data}%</div>
                    </div>`;
                }
            },
            {
                data: null,
                render: function(row) {
                    if (row.assigned_user_name) return row.assigned_user_name;
                    if (row.assigned_employee_first_name) return row.assigned_employee_first_name + ' ' + row.assigned_employee_surname;
                    return 'Unassigned';
                }
            },
            {
                data: null,
                render: function(row) {
                    return (row.planned_start_date || 'N/A') + ' - ' + (row.planned_end_date || 'N/A');
                }
            },
            {
                data: null,
                render: function(row) {
                    const estimated = row.estimated_hours || 0;
                    const actual = row.actual_hours || 0;
                    return `${actual}h / ${estimated}h`;
                }
            },
            {
                data: 'depends_on_phase_name',
                render: function(data) {
                    return data ? `<small class="text-muted">${data}</small>` : '-';
                }
            },
            {
                data: 'uuid',
                render: function(data, type, row) {
                    return `
                        <a href="/project_job_phases/edit/${data}/<?= $jobUuid ?>" class="btn btn-sm btn-primary" title="Edit">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="/project_job_phases/delete/${data}" class="btn btn-sm btn-danger" onclick="return confirm('Delete this phase?')" title="Delete">
                            <i class="fa fa-trash"></i>
                        </a>
                    `;
                }
            }
        ],
        order: [[0, 'asc']], // Order by phase_order
        pageLength: 25,
        rowReorder: {
            dataSrc: 'phase_order'
        },
        columnDefs: [
            { orderable: false, targets: -1 } // Disable ordering on Actions column
        ]
    });

    // Handle row reordering
    $('#phasesTable').on('row-reorder', function(e, diff, edit) {
        const updates = [];
        for (let i = 0; i < diff.length; i++) {
            const rowData = $('#phasesTable').DataTable().row(diff[i].node).data();
            updates.push({
                uuid: rowData.uuid,
                phase_order: diff[i].newPosition + 1
            });
        }

        if (updates.length > 0) {
            $.ajax({
                url: '/project_job_phases/reorder',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    job_uuid: '<?= $jobUuid ?>',
                    phases: updates
                }),
                success: function(response) {
                    if (response.status) {
                        // Reload table to show updated order
                        $('#phasesTable').DataTable().ajax.reload(null, false);
                    } else {
                        alert('Failed to reorder phases: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error reordering phases');
                }
            });
        }
    });
});
</script>

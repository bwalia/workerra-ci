<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatientLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Unique identifier for log entry',
            ],
            'uuid_business_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Links to businesses/hospitals table',
            ],
            'log_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Auto-generated log reference number (LOG-000001)',
            ],

            // Patient and Staff links
            'patient_contact_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'Links to contacts.id (patient record)',
            ],
            'staff_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Links to hospital_staff.uuid (who recorded this)',
            ],

            // Log categorization
            'log_category' => [
                'type' => 'ENUM',
                'constraint' => [
                    'Medication',
                    'Vital Signs',
                    'Treatment',
                    'Observation',
                    'Admission',
                    'Discharge',
                    'Appointment',
                    'Lab Result',
                    'Procedure',
                    'Consultation',
                    'General Note',
                    'Other'
                ],
                'null' => false,
                'comment' => 'Category of log entry',
            ],
            'log_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Specific type within category (e.g., "Blood Pressure" for Vital Signs)',
            ],

            // Medication-specific fields
            'medication_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Name of medication administered',
            ],
            'dosage' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Dosage amount (e.g., 500mg, 2 tablets)',
            ],
            'route' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Route of administration (Oral, IV, IM, Topical, etc.)',
            ],
            'frequency' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Frequency (e.g., Once daily, TID, PRN)',
            ],
            'medication_status' => [
                'type' => 'ENUM',
                'constraint' => ['Prescribed', 'Administered', 'Refused', 'Held', 'Missed'],
                'null' => true,
                'comment' => 'Status of medication',
            ],

            // Vital signs fields
            'blood_pressure_systolic' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Systolic BP (mmHg)',
            ],
            'blood_pressure_diastolic' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Diastolic BP (mmHg)',
            ],
            'heart_rate' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Heart rate (bpm)',
            ],
            'temperature' => [
                'type' => 'DECIMAL',
                'constraint' => '4,1',
                'null' => true,
                'comment' => 'Body temperature (°C)',
            ],
            'respiratory_rate' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Breaths per minute',
            ],
            'oxygen_saturation' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'SpO2 percentage',
            ],
            'blood_glucose' => [
                'type' => 'DECIMAL',
                'constraint' => '5,1',
                'null' => true,
                'comment' => 'Blood glucose level (mmol/L)',
            ],
            'weight' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Weight (kg)',
            ],
            'height' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Height (cm)',
            ],

            // General log fields
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Title/summary of log entry',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Detailed description/notes',
            ],

            // Treatment/Procedure fields
            'procedure_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Name of procedure performed',
            ],
            'treatment_plan' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Treatment plan details',
            ],
            'outcome' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Outcome/result of treatment',
            ],

            // Lab results
            'test_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Name of lab test',
            ],
            'test_result' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Test result values',
            ],
            'reference_range' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Normal reference range',
            ],
            'abnormal_flag' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 if result is abnormal',
            ],

            // Admission/Discharge
            'admission_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Elective, Emergency, etc.',
            ],
            'ward' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Ward/room assignment',
            ],
            'bed_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Bed assignment',
            ],
            'diagnosis' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Primary diagnosis',
            ],
            'discharge_summary' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Discharge summary',
            ],

            // Timing
            'scheduled_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Scheduled time for action',
            ],
            'performed_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When action was performed',
            ],
            'administered_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When medication was administered',
            ],

            // Attachments and references
            'attachment_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'URL to attachment (lab report, X-ray, etc.)',
            ],
            'reference_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'External reference number',
            ],

            // Priority and status
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['Low', 'Normal', 'High', 'Urgent', 'Critical'],
                'default' => 'Normal',
                'comment' => 'Priority level',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Pending Review'],
                'default' => 'Completed',
                'comment' => 'Status of log entry',
            ],
            'is_flagged' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Flagged for review/attention',
            ],
            'flag_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Reason for flagging',
            ],

            // Compliance and signatures
            'verified_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'UUID of staff member who verified',
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When entry was verified',
            ],
            'digital_signature' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Digital signature data',
            ],

            // Audit fields
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'UUID of creator',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'modified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid_business_id');
        $this->forge->addKey('patient_contact_id');
        $this->forge->addKey('staff_uuid');
        $this->forge->addKey('log_category');
        $this->forge->addKey(['patient_contact_id', 'log_category']);
        $this->forge->addKey(['patient_contact_id', 'performed_datetime']);
        $this->forge->addKey('scheduled_datetime');
        $this->forge->addKey('is_flagged');

        $this->forge->createTable('patient_logs');
    }

    public function down()
    {
        $this->forge->dropTable('patient_logs');
    }
}

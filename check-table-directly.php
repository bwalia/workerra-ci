<?php
// Quick database check
require_once 'ci4/vendor/autoload.php';
$pathsConfig = new Config\Paths();
\CodeIgniter\Boot::bootCommand($pathsConfig);

$db = \Config\Database::connect();

echo "Checking deployments table...\n\n";

if ($db->tableExists('deployments')) {
    echo "✓ Table EXISTS\n\n";
    echo "Current columns:\n";
    $fields = $db->getFieldNames('deployments');
    foreach ($fields as $field) {
        echo "  - $field\n";
    }
    
    echo "\n";
    echo "Table creation statement:\n";
    $result = $db->query("SHOW CREATE TABLE deployments")->getResultArray();
    echo $result[0]['Create Table'] ?? 'Could not retrieve';
} else {
    echo "✗ Table DOES NOT EXIST\n";
}

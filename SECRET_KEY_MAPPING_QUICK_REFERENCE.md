# Secret Key Mapping System - Quick Reference

## TL;DR

The Secret Key Mapping System allows users to configure via UI which encrypted keys from sealed secrets get injected into which paths in Helm values files. No more hardcoded secret extraction!

## Quick Start

### 1. Configure in UI

Navigate to **Services → Edit Service → Service Detail**:

1. Select **Secret Template(s)**: Templates containing secrets to be sealed
2. Select **Values Template**: Helm values template
3. Configure mapping fields that appear:
   - **"Take keys from"**: Path in sealed secret (e.g., `spec,encryptedData,password`)
   - **"Add keys for"**: Path in values file (e.g., `mysql.auth.password`)

### 2. Deploy

Click **Deploy** button. The system automatically:
1. Renders secret templates
2. Runs kubeseal to encrypt them
3. Extracts configured keys from sealed secrets
4. Injects them into configured paths in values file
5. Deploys to Kubernetes

## Common Patterns

### Pattern 1: Database Credentials

**Secret Template**: `mysql-secret-template`
```yaml
apiVersion: v1
kind: Secret
metadata:
  name: mysql-creds
data:
  password: MYSQL_ROOT_PASSWORD  # Placeholder
```

**Configuration:**
- Take keys from: `spec,encryptedData,password`
- Add keys for: `mysql.auth.password`

**Result in Values:**
```yaml
mysql:
  auth:
    password: AgBX7Kj9...  # Encrypted value from sealed secret
```

### Pattern 2: API Keys

**Secret Template**: `app-config-secret`
```yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-config
data:
  apiKey: API_KEY  # Placeholder
  apiSecret: API_SECRET  # Placeholder
```

**Configuration (Multiple Mappings):**
1. Take keys from: `spec,encryptedData,apiKey` → Add keys for: `config.api.key`
2. Take keys from: `spec,encryptedData,apiSecret` → Add keys for: `config.api.secret`

**Result in Values:**
```yaml
config:
  api:
    key: AgBX...
    secret: AgBY...
```

### Pattern 3: Environment File

**Secret Template**: `env-file-secret`
```yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-env
data:
  env: ENV_FILE  # Placeholder for .env file content
```

**Configuration:**
- Take keys from: `spec,encryptedData,env`
- Add keys for: `sealedSecrets.env`

**Result in Values:**
```yaml
sealedSecrets:
  env: AgBXenv-file-encrypted-content...
```

## Path Syntax

### Secret Key Paths (Comma-Separated)

Navigate sealed secret YAML with commas:

```
spec,encryptedData,password
```

Extracts from:
```yaml
spec:
  encryptedData:
    password: AgBX...
```

### Values Key Paths (Dot-Notation)

Navigate values YAML with dots:

```
mysql.auth.password
```

or for nested deeper:

```
app.config.database.credentials.password
```

## Code Examples

### Use in Custom Code

```php
use App\Libraries\K8sDeployment\SecretKeyMapper;

// Get instance from DeploymentManager
$deploymentManager = new \App\Libraries\K8sDeployment\DeploymentManager();
$mapper = $deploymentManager->getSecretKeyMapper();

// Get all mappings for a service
$mappings = $mapper->getMappingsForService($serviceUuid);

// Get stats
$stats = $mapper->getMappingStats($serviceUuid);
print_r($stats);
/*
Array
(
    [total_mappings] => 2
    [unique_secret_templates] => 1
    [unique_values_templates] => 1
    [mappings] => Array(...)
)
*/
```

### Save Mappings Programmatically

```php
$result = $mapper->saveMappings(
    $serviceUuid,
    ['secret-template-uuid-1', 'secret-template-uuid-2'],
    'values-template-uuid',
    [
        'secret-template-uuid-1' => ['spec,encryptedData,key1'],
        'secret-template-uuid-2' => ['spec,encryptedData,key2']
    ],
    [
        'values-template-uuid/secret-template-uuid-1' => ['path.to.key1'],
        'values-template-uuid/secret-template-uuid-2' => ['path.to.key2']
    ]
);

if ($result['success']) {
    echo "Saved {$result['count']} mappings";
} else {
    print_r($result['errors']);
}
```

## Database Schema

```sql
SELECT * FROM service__secret_value_template__key
WHERE service_id = 'your-service-uuid';
```

**Columns:**
- `service_id`: Which service
- `secret_temp_id`: Which secret template (source)
- `secret_key`: Path to extract from (e.g., `spec,encryptedData,password`)
- `values_temp_id`: Which values template (target)
- `values_key`: Path to inject into (e.g., `mysql.auth.password`)

## Troubleshooting

### Issue: Mapping not being applied

**Check:**
1. Verify mapping exists in database:
   ```sql
   SELECT * FROM service__secret_value_template__key
   WHERE service_id = 'uuid';
   ```

2. Check deployment logs:
   ```php
   $logger = new \App\Libraries\K8sDeployment\DeploymentLogger();
   $logs = $logger->getDeploymentLogs($deploymentUuid);
   ```

3. Verify paths are correct:
   - Secret key path should match actual sealed secret structure
   - Values key path should match where you want the value injected

### Issue: "Secret key not found in sealed secret"

**Cause:** The path in "Take keys from" doesn't match the sealed secret structure.

**Fix:**
1. Check the actual sealed secret file in `ci4/writable/secret/`
2. Verify the YAML structure matches your path
3. Remember: Use commas for path separation (e.g., `spec,encryptedData,key`)

### Issue: Value not appearing in values file

**Cause:** The mapping might not be loading the sealed secret value.

**Debug:**
```php
$stats = $mapper->getMappingStats($serviceUuid);
print_r($stats);  // Check if mappings are configured

$mappings = $mapper->getMappingsForService($serviceUuid);
foreach ($mappings as $mapping) {
    echo "Secret Template: {$mapping['secret_temp_id']}\n";
    echo "Secret Key: {$mapping['secret_key']}\n";
    echo "Values Key: {$mapping['values_key']}\n";
}
```

## Common Sealed Secret Paths

```
# Standard Bitnami sealed secret structure
spec,encryptedData,KEY_NAME

# Common keys:
spec,encryptedData,password
spec,encryptedData,username
spec,encryptedData,hostname
spec,encryptedData,port
spec,encryptedData,database
spec,encryptedData,apiKey
spec,encryptedData,env
```

## Common Values Paths

```
# MySQL
mysql.auth.password
mysql.auth.rootPassword
mysql.auth.username

# PostgreSQL
postgresql.auth.password
postgresql.auth.postgresPassword
postgresql.auth.username

# Application config
config.database.password
config.api.key
config.api.secret

# Sealed secrets (for kubeseal-generated files)
sealedSecrets.env
sealedSecrets.config
```

## Files Reference

- **SecretKeyMapper**: `ci4/app/Libraries/K8sDeployment/SecretKeyMapper.php`
- **DeploymentManager**: `ci4/app/Libraries/K8sDeployment/DeploymentManager.php`
- **Services Controller**: `ci4/app/Controllers/Services.php` (lines 222-243)
- **UI Form**: `ci4/app/Views/services/edit.php` (lines 1746-1798)
- **Database Migration**: `ci4/app/Database/Migrations/2024-07-29-112638_CreateServiceSecretTemplateKey.php`

## Full Documentation

See [SECRET_KEY_MAPPING_SYSTEM.md](SECRET_KEY_MAPPING_SYSTEM.md) for complete documentation including:
- Architecture details
- API reference
- Integration with deployment pipeline
- Migration from legacy system
- Testing examples

---

**Status**: ✅ Production Ready

**Last Updated**: 2025-01-17

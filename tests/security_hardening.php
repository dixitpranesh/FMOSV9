<?php

declare(strict_types=1);

/**
 * Security hardening regression tests (tenant FK, bootstrap gate, crypto, policy).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Core\Auth;
use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Core\FieldCrypto;
use Fmos\Core\TenantGuard;
use Fmos\Domains\Identity\PasswordPolicy;
use Fmos\Domains\Project\ProjectService;
use Fmos\Domains\Tenant\TenantService;

function ok(string $msg): void
{
    echo "  OK  $msg\n";
}

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('ASSERT: ' . $msg);
    }
    ok($msg);
}

Env::load(dirname(__DIR__) . '/.env');

echo "Security hardening tests\n";

assertTrue(PasswordPolicy::validate('Fmos-Demo-Owner1!')['ok'] === true, 'strong demo password accepted');
assertTrue(PasswordPolicy::validate('Password123!')['ok'] === false, 'legacy demo password rejected by policy');
assertTrue(PasswordPolicy::validate('onlylower1!')['ok'] === false, 'missing uppercase rejected');
assertTrue(PasswordPolicy::validate('ONLYUPPER1!')['ok'] === false, 'missing lowercase rejected');
assertTrue(PasswordPolicy::validate('NoSpecial12')['ok'] === false, 'missing special rejected');

Env::set('APP_KEY', 'test-app-key-for-security-suite-32chars!!');
$enc = FieldCrypto::encrypt('ABCDE1234F');
assertTrue(is_string($enc) && str_starts_with($enc, 'enc:v1:'), 'PAN encrypts');
assertTrue(FieldCrypto::decrypt($enc) === 'ABCDE1234F', 'PAN decrypts');
assertTrue(FieldCrypto::decrypt('PLAIN') === 'PLAIN', 'legacy plaintext passthrough');

$pdo = Database::connection();

// Two tenants via service (internal, not HTTP bootstrap)
$svc = new TenantService();
$suffix = bin2hex(random_bytes(3));
$a = $svc->createTenant('TA' . $suffix, 'Tenant A ' . $suffix, "owner_a_{$suffix}@example.test", 'Owner A', 'Str0ng-Enough!');
$b = $svc->createTenant('TB' . $suffix, 'Tenant B ' . $suffix, "owner_b_{$suffix}@example.test", 'Owner B', 'Str0ng-Enough!');
assertTrue(!empty($a['tenant_id']) && !empty($b['tenant_id']), 'two tenants created');
assertTrue((int) $a['tenant_id'] !== (int) $b['tenant_id'], 'tenant ids differ');

$orgA = (int) $a['organization_id'];
$orgB = (int) $b['organization_id'];

$proj = new ProjectService();
$clientA = $proj->createClient((int) $a['tenant_id'], ['name' => 'Client A', 'email' => "ca_{$suffix}@ex.test"]);
$clientB = $proj->createClient((int) $b['tenant_id'], ['name' => 'Client B', 'email' => "cb_{$suffix}@ex.test"]);

try {
    TenantGuard::assertOwned('organizations', $orgB, (int) $a['tenant_id']);
    assertTrue(false, 'cross-tenant org should fail');
} catch (RuntimeException $e) {
    assertTrue($e->getMessage() === 'RESOURCE_NOT_FOUND', 'cross-tenant org blocked');
}

try {
    $proj->createProject((int) $a['tenant_id'], [
        'organization_id' => $orgB,
        'client_id' => (int) $clientA['id'],
        'name' => 'Evil link',
    ]);
    assertTrue(false, 'cross-tenant project create should fail');
} catch (RuntimeException $e) {
    assertTrue($e->getMessage() === 'RESOURCE_NOT_FOUND', 'cross-tenant project FK blocked');
}

$okProject = $proj->createProject((int) $a['tenant_id'], [
    'organization_id' => $orgA,
    'client_id' => (int) $clientA['id'],
    'name' => 'Good project',
]);
assertTrue(!empty($okProject['id']), 'same-tenant project create works');

// Suspended user loses Auth::user access
$userId = (int) $a['user_id'];
Auth::attempt("owner_a_{$suffix}@example.test", 'Str0ng-Enough!');
assertTrue(Auth::user() !== null, 'usable user authenticates');
$pdo->prepare("UPDATE users SET status='SUSPENDED' WHERE id=?")->execute([$userId]);
$ref = new ReflectionClass(Auth::class);
$prop = $ref->getProperty('user');
$prop->setAccessible(true);
$prop->setValue(null, null);
$_SESSION['user_id'] = $userId;
assertTrue(Auth::user() === null, 'suspended session user rejected');

echo "Security hardening tests done\n";

<?php

declare(strict_types=1);

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Identity\RbacSeeder;
use Fmos\Domains\Tenant\TenantService;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

RbacSeeder::seed();

$pdo = Database::connection();
$exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$exists->execute(['owner@demo.fmos']);
if ($exists->fetch()) {
    echo "Demo tenant already seeded.\n";
    exit(0);
}

// Platform super admin
$hash = password_hash('Password123!', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (tenant_id, email, name, password_hash, is_platform_user, status, email_verified_at, created_at, updated_at) VALUES (NULL, ?, ?, ?, 1, ?, NOW(), NOW(), NOW())');
$stmt->execute(['platform@fmos.local', 'Platform Super Admin', $hash, 'ACTIVE']);
$platformId = (int) $pdo->lastInsertId();
$roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'PLATFORM_SUPER_ADMIN'")->fetchColumn();
$pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$platformId, $roleId]);

$supportHash = password_hash('Password123!', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (tenant_id, email, name, password_hash, is_platform_user, status, email_verified_at, created_at, updated_at) VALUES (NULL, ?, ?, ?, 1, ?, NOW(), NOW(), NOW())');
$stmt->execute(['support@fmos.local', 'Support Admin', $supportHash, 'ACTIVE']);
$supportId = (int) $pdo->lastInsertId();
$supportRole = (int) $pdo->query("SELECT id FROM roles WHERE code = 'SUPPORT'")->fetchColumn();
$pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$supportId, $supportRole]);

$service = new TenantService();
$result = $service->createTenant('DEMO', 'Demo Interiors', 'owner@demo.fmos', 'Demo Owner', 'Password123!');

echo "Seed complete.\n";
echo "Platform: platform@fmos.local / Password123!\n";
echo "Support:  support@fmos.local / Password123!\n";
echo "Tenant:   owner@demo.fmos / Password123!\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Audit;
use Fmos\Core\Auth;
use Fmos\Core\Database;
use PDO;

final class RbacSeeder
{
    /** @return list<string> */
    public static function roleCodes(): array
    {
        return [
            'PLATFORM_SUPER_ADMIN',
            'SUPPORT',
            'TENANT_OWNER',
            'TENANT_ADMIN',
            'OPERATIONS_MANAGER',
            'PROJECT_MANAGER',
            'SENIOR_DESIGNER',
            'DESIGNER',
            'DESIGN_REVIEWER',
            'ENGINEER',
            'ESTIMATOR',
            'SALES_MANAGER',
            'SALES_USER',
            'MANUFACTURING_MANAGER',
            'PRODUCTION_MANAGER',
            'PRODUCTION_SUPERVISOR',
            'MACHINE_OPERATOR',
            'QC_MANAGER',
            'QC_INSPECTOR',
            'WAREHOUSE_OPERATOR',
            'PACKING_OPERATOR',
            'DISPATCH_MANAGER',
            'INSTALLATION_MANAGER',
            'INSTALLATION_USER',
            'CLIENT_ADMIN',
            'CLIENT_USER',
            'VIEWER',
        ];
    }

    /** @return list<array{0:string,1:string,2:string}> */
    public static function permissions(): array
    {
        return [
            ['*', 'platform', 'All permissions'],
            ['platform.support.impersonate', 'platform', 'Impersonate tenant users'],
            ['tenant.view', 'tenant', 'View tenant'],
            ['tenant.manage', 'tenant', 'Manage tenant settings'],
            ['organization.view', 'tenant', 'View organizations'],
            ['organization.create', 'tenant', 'Create organizations'],
            ['organization.update', 'tenant', 'Update organizations'],
            ['user.view', 'identity', 'View users'],
            ['user.create', 'identity', 'Create users'],
            ['user.update', 'identity', 'Update users'],
            ['role.view', 'identity', 'View roles'],
            ['role.manage', 'identity', 'Manage roles'],
            ['client.view', 'crm', 'View clients'],
            ['client.create', 'crm', 'Create clients'],
            ['client.update', 'crm', 'Update clients'],
            ['project.view', 'project', 'View projects'],
            ['project.create', 'project', 'Create projects'],
            ['project.update', 'project', 'Update projects'],
            ['project.delete', 'project', 'Delete projects'],
            ['design.view', 'architecture', 'View design'],
            ['design.create', 'architecture', 'Create design'],
            ['design.update', 'architecture', 'Update design'],
            ['furniture.view', 'furniture', 'View furniture'],
            ['furniture.create', 'furniture', 'Create furniture'],
            ['furniture.update', 'furniture', 'Update furniture'],
            ['catalog.view', 'catalog', 'View catalog'],
            ['catalog.manage', 'catalog', 'Manage catalog'],
            ['bom.view', 'bom', 'View BOM'],
            ['bom.generate', 'bom', 'Generate BOM'],
            ['boq.view', 'boq', 'View BOQ'],
            ['boq.edit', 'boq', 'Edit BOQ'],
            ['pricing.view', 'pricing', 'View pricing'],
            ['pricing.edit', 'pricing', 'Edit pricing'],
            ['quote.view', 'pricing', 'View quotes'],
            ['quote.create', 'pricing', 'Create quotes'],
            ['quote.approve', 'pricing', 'Approve quotes'],
            ['manufacturing.view', 'manufacturing', 'View manufacturing'],
            ['manufacturing.generate', 'manufacturing', 'Generate manufacturing'],
            ['manufacturing.release', 'manufacturing', 'Release manufacturing'],
            ['nesting.view', 'manufacturing', 'View nesting'],
            ['nesting.generate', 'manufacturing', 'Generate nesting'],
            ['production.view', 'mes', 'View production'],
            ['production.update', 'mes', 'Update production'],
            ['qc.view', 'mes', 'View QC'],
            ['qc.update', 'mes', 'Update QC'],
        ];
    }

    public static function seed(): void
    {
        $pdo = Database::connection();

        foreach (self::permissions() as [$code, $module, $desc]) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO permissions (code, module, description) VALUES (?, ?, ?)');
            $stmt->execute([$code, $module, $desc]);
        }

        foreach (self::roleCodes() as $code) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO roles (tenant_id, code, name, is_system, created_at) VALUES (NULL, ?, ?, 1, NOW())');
            $stmt->execute([$code, str_replace('_', ' ', $code)]);
        }

        $permIds = $pdo->query('SELECT id, code FROM permissions')->fetchAll(PDO::FETCH_KEY_PAIR);
        // FETCH_KEY_PAIR is id=>code; invert
        $byCode = [];
        foreach ($pdo->query('SELECT id, code FROM permissions') as $row) {
            $byCode[$row['code']] = (int) $row['id'];
        }
        $roleIds = [];
        foreach ($pdo->query('SELECT id, code FROM roles WHERE tenant_id IS NULL') as $row) {
            $roleIds[$row['code']] = (int) $row['id'];
        }

        $map = [
            'PLATFORM_SUPER_ADMIN' => ['*'],
            'SUPPORT' => ['platform.support.impersonate', 'tenant.view', 'user.view', 'project.view'],
            'TENANT_OWNER' => array_values(array_filter(array_keys($byCode), static fn ($c) => $c !== '*' && !str_starts_with($c, 'platform.'))),
            'TENANT_ADMIN' => ['tenant.view','tenant.manage','organization.view','organization.create','organization.update','user.view','user.create','user.update','role.view','client.view','client.create','project.view','project.create','catalog.view','catalog.manage'],
            'MANUFACTURING_MANAGER' => ['manufacturing.view','manufacturing.generate','manufacturing.release','nesting.view','nesting.generate','bom.view','project.view','furniture.view'],
            'ENGINEER' => ['manufacturing.view','manufacturing.generate','furniture.view','furniture.update','design.view','project.view','bom.view'],
            'ESTIMATOR' => ['bom.view','bom.generate','boq.view','boq.edit','pricing.view','pricing.edit','quote.view','quote.create','project.view','catalog.view'],
            'DESIGNER' => ['design.view','design.create','design.update','furniture.view','furniture.create','furniture.update','project.view','catalog.view'],
            'SENIOR_DESIGNER' => ['design.view','design.create','design.update','furniture.view','furniture.create','furniture.update','project.view','catalog.view','bom.view'],
            'SALES_MANAGER' => ['client.view','client.create','client.update','quote.view','quote.create','quote.approve','project.view','project.create'],
            'SALES_USER' => ['client.view','client.create','quote.view','quote.create','project.view'],
            'PROJECT_MANAGER' => ['project.view','project.create','project.update','design.view','furniture.view','bom.view','boq.view','quote.view'],
            'VIEWER' => ['project.view','design.view','furniture.view','bom.view','catalog.view'],
            'CLIENT_ADMIN' => ['project.view','quote.view','quote.approve'],
            'CLIENT_USER' => ['project.view','quote.view'],
        ];

        foreach ($map as $role => $perms) {
            if (!isset($roleIds[$role])) {
                continue;
            }
            foreach ($perms as $p) {
                if (!isset($byCode[$p])) {
                    continue;
                }
                $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
                $stmt->execute([$roleIds[$role], $byCode[$p]]);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Fmos\Domains\Tenant;

use Fmos\Core\Audit;
use Fmos\Core\Database;
use Fmos\Core\FieldCrypto;
use Fmos\Domains\Identity\IndiaBusinessValidators;
use Fmos\Domains\Identity\RbacSeeder;

final class TenantService
{
    public function createTenant(string $code, string $name, string $ownerEmail, string $ownerName, string $password): array
    {
        return $this->createTenantForRegistration([
            'tenant_code' => $code,
            'tenant_name' => $name,
            'email' => $ownerEmail,
            'password' => $password,
            'name' => $ownerName,
            'display_name' => $ownerName,
            'registration_type' => null,
            'status' => 'ACTIVE',
            'email_verified' => true,
            'organization' => ['legal_name' => 'Main Organization', 'trade_name' => 'Main Organization'],
            'address' => [],
            'terms_version' => null,
            'privacy_version' => null,
            'marketing_email_consent' => false,
        ]);
    }

    /**
     * Transactional tenant + org + owner used by bootstrap and public registration.
     *
     * @param array<string,mixed> $data
     * @return array{tenant_id:int,organization_id:int,user_id:int}
     */
    public function createTenantForRegistration(array $data): array
    {
        $pdo = Database::connection();
        $ownsTx = !$pdo->inTransaction();
        if ($ownsTx) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO tenants (code, name, currency, measurement_system, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                (string) $data['tenant_code'],
                (string) $data['tenant_name'],
                'INR',
                'metric',
                'ACTIVE',
            ]);
            $tenantId = (int) $pdo->lastInsertId();

            RbacSeeder::seed();

            $hash = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            $status = (string) ($data['status'] ?? 'ACTIVE');
            $verified = !empty($data['email_verified']) || ($status === 'ACTIVE' && empty($data['registration_type']));
            $emailVerifiedAt = $verified ? date('Y-m-d H:i:s') : null;
            $termsVersion = $data['terms_version'] ?? null;
            $privacyVersion = $data['privacy_version'] ?? null;
            $termsAt = $termsVersion ? date('Y-m-d H:i:s') : null;
            $privacyAt = $privacyVersion ? date('Y-m-d H:i:s') : null;

            $display = (string) ($data['display_name'] ?? $data['name'] ?? '');
            $stmt = $pdo->prepare(
                'INSERT INTO users (
                    tenant_id, email, name, first_name, last_name, display_name,
                    mobile_country_code, mobile, designation, registration_type,
                    email_verified_at, terms_accepted_at, terms_version,
                    privacy_acknowledged_at, privacy_version, marketing_email_consent,
                    password_hash, is_platform_user, status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, 0, ?, NOW(), NOW()
                )'
            );
            $stmt->execute([
                $tenantId,
                (string) $data['email'],
                $display !== '' ? $display : (string) $data['email'],
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $display !== '' ? $display : null,
                $data['mobile_country_code'] ?? null,
                $data['mobile'] ?? null,
                $data['designation'] ?? null,
                $data['registration_type'] ?? null,
                $emailVerifiedAt,
                $termsAt,
                $termsVersion,
                $privacyAt,
                $privacyVersion,
                !empty($data['marketing_email_consent']) ? 1 : 0,
                $hash,
                $status,
            ]);
            $userId = (int) $pdo->lastInsertId();

            $roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'TENANT_OWNER' AND tenant_id IS NULL")->fetchColumn();
            $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);

            $org = is_array($data['organization'] ?? null) ? $data['organization'] : [];
            $legal = trim((string) ($org['legal_name'] ?? $data['tenant_name'] ?? 'Main Organization'));
            $trade = trim((string) ($org['trade_name'] ?? $legal));
            $gstReg = strtoupper(trim((string) ($org['gst_registered'] ?? 'NOT_APPLICABLE')));
            if ($gstReg === 'NA' || $gstReg === '') {
                $gstReg = 'NOT_APPLICABLE';
            }
            $gstin = IndiaBusinessValidators::normalizeGstin((string) ($org['gstin'] ?? ''));
            $gstStatus = 'NOT_CHECKED';
            if ($gstReg === 'YES' && $gstin !== '') {
                $gstStatus = IndiaBusinessValidators::validateGstin($gstin)['ok'] ? 'FORMAT_VALID' : 'NOT_CHECKED';
            }
            $pan = IndiaBusinessValidators::normalizePan((string) ($org['pan'] ?? ''));
            $constitution = strtoupper(trim((string) ($org['constitution'] ?? ''))) ?: null;
            $profile = $org['profile'] ?? $org['profile_json'] ?? null;
            if (is_array($profile)) {
                $profile = json_encode($profile);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO organizations (
                    tenant_id, owner_user_id, code, name, legal_name, trade_name, constitution,
                    pan, gst_registered, gstin, gst_verification_status,
                    business_email, business_phone, website, year_established, manufacturing_locations_count,
                    profile_json, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $tenantId,
                $userId,
                'MAIN',
                $trade !== '' ? $trade : $legal,
                $legal,
                $trade !== '' ? $trade : null,
                $constitution,
                $pan !== '' ? FieldCrypto::encrypt($pan) : null,
                $gstReg,
                $gstin !== '' ? FieldCrypto::encrypt($gstin) : null,
                $gstStatus,
                $org['business_email'] ?? null,
                $org['business_phone'] ?? null,
                $org['website'] ?? null,
                isset($org['year_established']) && $org['year_established'] !== '' ? (int) $org['year_established'] : null,
                isset($org['manufacturing_locations_count']) && $org['manufacturing_locations_count'] !== ''
                    ? (int) $org['manufacturing_locations_count'] : null,
                $profile,
                'ACTIVE',
            ]);
            $orgId = (int) $pdo->lastInsertId();

            $address = is_array($data['address'] ?? null) ? $data['address'] : [];
            if (!empty($address['line1'])) {
                $pdo->prepare(
                    'INSERT INTO organization_addresses (
                        organization_id, address_type, is_principal, line1, line2, premises, locality,
                        city, district, state, pin_code, country, created_at, updated_at
                    ) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                )->execute([
                    $orgId,
                    (string) ($address['address_type'] ?? 'PRINCIPAL'),
                    (string) $address['line1'],
                    $address['line2'] ?? null,
                    $address['premises'] ?? null,
                    $address['locality'] ?? null,
                    $address['city'] ?? null,
                    $address['district'] ?? null,
                    (string) ($address['state'] ?? ''),
                    (string) ($address['pin_code'] ?? $address['pincode'] ?? ''),
                    (string) ($address['country'] ?? 'IN'),
                ]);
            }

            if ($ownsTx) {
                $pdo->commit();
            }
            Audit::record('CREATE', 'tenant', $tenantId, null, ['code' => $data['tenant_code'], 'name' => $data['tenant_name']]);

            return [
                'tenant_id' => $tenantId,
                'organization_id' => $orgId,
                'user_id' => $userId,
            ];
        } catch (\Throwable $e) {
            if ($ownsTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listOrganizations(int $tenantId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, tenant_id, code, name, legal_name, trade_name, status, gst_registered, gst_verification_status, created_at
             FROM organizations WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY id'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function createOrganization(int $tenantId, string $code, string $name): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO organizations (tenant_id, code, name, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenantId, $code, $name, 'ACTIVE']);
        $id = (int) $pdo->lastInsertId();
        Audit::record('CREATE', 'organization', $id, null, ['code' => $code, 'name' => $name]);
        $stmt = $pdo->prepare('SELECT * FROM organizations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

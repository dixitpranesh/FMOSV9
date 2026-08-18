<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Identity\EmailVerificationService;
use Fmos\Domains\Identity\IndiaBusinessValidators;
use Fmos\Domains\Identity\PasswordPolicy;
use Fmos\Domains\Identity\PasswordResetService;
use Fmos\Domains\Identity\RegistrationService;
use Fmos\Core\Auth;
use Fmos\Core\AuthException;

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
Env::set('MAIL_EXPOSE_TOKENS', 'true');
Env::set('APP_ENV', 'local');
Env::set('APP_DEBUG', 'true');

echo "Auth registration / verification / reset tests\n";

// --- Validators (no DB) ---
assertTrue(PasswordPolicy::validate('short')['ok'] === false, 'weak short password rejected');
assertTrue(PasswordPolicy::validate('password123')['ok'] === false, 'common password rejected');
assertTrue(PasswordPolicy::validate('Str0ng-Enough!')['ok'] === true, 'strong password accepted');

assertTrue(IndiaBusinessValidators::validatePin('560001')['ok'] === true, 'valid PIN');
assertTrue(IndiaBusinessValidators::validatePin('056001')['ok'] === false, 'invalid PIN');
assertTrue(IndiaBusinessValidators::validatePan('ABCDE1234F')['ok'] === true, 'valid PAN');
assertTrue(IndiaBusinessValidators::validatePan('BAD')['ok'] === false, 'invalid PAN');
assertTrue(IndiaBusinessValidators::validateMobile('9876543210')['ok'] === true, 'valid mobile');
assertTrue(IndiaBusinessValidators::validateMobile('1876543210')['ok'] === false, 'invalid mobile');

// Build a checksum-valid GSTIN: state 29 + PAN ABCDE1234F + entity 1 + Z + check
$base14 = '29ABCDE1234F1Z';
$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$factor = 1;
$sum = 0;
for ($i = 0; $i < 14; $i++) {
    $codePoint = strpos($chars, $base14[$i]);
    $product = $codePoint * $factor;
    $sum += intdiv($product, 36) + ($product % 36);
    $factor = $factor === 1 ? 2 : 1;
}
$check = (36 - ($sum % 36)) % 36;
$validGstin = $base14 . $chars[$check];
assertTrue(IndiaBusinessValidators::validateGstin($validGstin)['ok'] === true, 'valid GSTIN checksum');
assertTrue(IndiaBusinessValidators::validateGstin('29ABCDE1234F1Z0')['ok'] === false, 'bad GSTIN rejected');
assertTrue(IndiaBusinessValidators::maskPan('ABCDE1234F') === '******234F', 'PAN masked');

$pdo = Database::connection();

// Ensure schema columns exist
$cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified_at'")->fetch();
assertTrue((bool) $cols, 'email_verified_at column present');

$suffix = bin2hex(random_bytes(3));
$emailInd = "designer_{$suffix}@example.test";
$emailFac = "factory_{$suffix}@example.test";

$reg = new RegistrationService();

$ind = $reg->register([
    'registration_type' => 'INDEPENDENT_DESIGNER',
    'first_name' => 'Ada',
    'last_name' => 'Designer',
    'email' => $emailInd,
    'password' => 'Str0ng-Enough!',
    'password_confirm' => 'Str0ng-Enough!',
    'terms_accepted' => true,
    'privacy_acknowledged' => true,
    'marketing_email_consent' => false,
]);
assertTrue($ind['ok'] === true, 'independent designer registers');
assertTrue(($ind['email_sent'] ?? null) === true, 'verification email marked sent under log driver');
assertTrue(!empty($ind['registration_id']), 'registration_id returned');
assertTrue(!empty($ind['debug_verify_token']), 'verify token available under log mailer');
$userId = (int) $ind['user_id'];
$row = $pdo->prepare('SELECT status, email_verified_at FROM users WHERE id = ?');
$row->execute([$userId]);
$u = $row->fetch();
assertTrue(($u['status'] ?? '') === 'PENDING_EMAIL_VERIFICATION', 'pending verification status');
assertTrue(empty($u['email_verified_at']), 'email not verified yet');

try {
    Auth::attempt($emailInd, 'Str0ng-Enough!');
    assertTrue(false, 'unverified login should throw');
} catch (AuthException $e) {
    assertTrue($e->errorCode === 'EMAIL_NOT_VERIFIED', 'unverified login blocked');
}

$verify = (new EmailVerificationService())->verify((string) $ind['debug_verify_token']);
assertTrue($verify['ok'] === true, 'email verification succeeds');
$row->execute([$userId]);
$u = $row->fetch();
assertTrue(!empty($u['email_verified_at']), 'email_verified_at set');
assertTrue(($u['status'] ?? '') === 'ACTIVE', 'status ACTIVE after verify');

$used = (new EmailVerificationService())->verify((string) $ind['debug_verify_token']);
assertTrue($used['ok'] === false, 'token single-use');

$login = Auth::attempt($emailInd, 'Str0ng-Enough!');
assertTrue($login !== null && ($login['email'] ?? '') === $emailInd, 'verified user can login');
Auth::logout();

$fac = $reg->register([
    'registration_type' => 'FACTORY_OWNER',
    'first_name' => 'Fab',
    'last_name' => 'Owner',
    'email' => $emailFac,
    'mobile' => '9876543210',
    'designation' => 'Proprietor',
    'password' => 'Str0ng-Enough!',
    'password_confirm' => 'Str0ng-Enough!',
    'terms_accepted' => true,
    'privacy_acknowledged' => true,
    'organization' => [
        'legal_name' => 'Fab Modular Pvt Ltd',
        'trade_name' => 'Fab Modular',
        'constitution' => 'PRIVATE_LIMITED',
        'gst_registered' => 'YES',
        'gstin' => $validGstin,
        'pan' => 'ABCDE1234F',
        'business_email' => "ops_{$suffix}@example.test",
        'profile' => ['capabilities' => ['Wardrobe', 'CNC capability']],
    ],
    'address' => [
        'line1' => '12 Industrial Layout',
        'city' => 'Bengaluru',
        'district' => 'Bengaluru Urban',
        'state' => 'Karnataka',
        'pin_code' => '560001',
        'country' => 'IN',
    ],
]);
assertTrue($fac['ok'] === true, 'factory owner registers');
$orgId = (int) $fac['organization_id'];
$org = $pdo->prepare('SELECT gstin, gst_verification_status, owner_user_id FROM organizations WHERE id = ?');
$org->execute([$orgId]);
$o = $org->fetch();
assertTrue(($o['gst_verification_status'] ?? '') === 'FORMAT_VALID', 'GST format valid not government verified');
assertTrue((int) ($o['owner_user_id'] ?? 0) === (int) $fac['user_id'], 'owner_user_id set');
$addr = $pdo->prepare('SELECT COUNT(*) FROM organization_addresses WHERE organization_id = ? AND is_principal = 1');
$addr->execute([$orgId]);
assertTrue((int) $addr->fetchColumn() === 1, 'principal address stored');

$dup = $reg->register([
    'registration_type' => 'INDEPENDENT_DESIGNER',
    'first_name' => 'Dup',
    'last_name' => 'User',
    'email' => $emailInd,
    'password' => 'Str0ng-Enough!',
    'password_confirm' => 'Str0ng-Enough!',
    'terms_accepted' => true,
    'privacy_acknowledged' => true,
]);
assertTrue($dup['ok'] === true, 'duplicate email returns generic success');

$resetSvc = new PasswordResetService();
$msg = $resetSvc->request($emailInd);
assertTrue(str_contains($msg, 'If an account exists'), 'forgot password generic message');
$tok = $pdo->prepare('SELECT token_hash FROM password_resets WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1');
$tok->execute([$userId]);
$hash = (string) $tok->fetchColumn();
assertTrue($hash !== '', 'reset token hash stored');

// Recover raw token from log mail
$mailDir = dirname(__DIR__) . '/storage/mail';
$files = glob($mailDir . '/mail-*.json') ?: [];
usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
$rawReset = null;
$resetFrom = null;
foreach (array_slice($files, 0, 20) as $f) {
    $payload = json_decode((string) file_get_contents($f), true);
    if (!is_array($payload)) {
        continue;
    }
    if (!str_contains((string) ($payload['subject'] ?? ''), 'Reset')) {
        continue;
    }
    $resetFrom = $payload['from'] ?? null;
    if (preg_match('/token=([a-f0-9]+)/i', (string) ($payload['html'] ?? ''), $m)) {
        $rawReset = $m[1];
        break;
    }
}
assertTrue($rawReset !== null, 'reset token found in log mail');
assertTrue($resetFrom === 'accounts@fmos.in', 'reset mail from accounts@fmos.in');
$reset = $resetSvc->reset($rawReset, 'Even-Str0nger!!');
assertTrue($reset['ok'] === true, 'password reset succeeds');
assertTrue(Auth::attempt($emailInd, 'Str0ng-Enough!') === null, 'old password fails');
assertTrue(Auth::attempt($emailInd, 'Even-Str0nger!!') !== null, 'new password works');
Auth::logout();

$reuse = $resetSvc->reset($rawReset, 'Another-Str0ng!!');
assertTrue($reuse['ok'] === false, 'reset token single-use');

$unknown = $resetSvc->request('nobody_' . $suffix . '@example.test');
assertTrue(str_contains($unknown, 'If an account exists'), 'unknown email generic');

echo "Auth registration / verification / reset tests done\n";

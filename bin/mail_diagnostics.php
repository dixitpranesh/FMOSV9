<?php

declare(strict_types=1);

/**
 * Safe mail configuration diagnostics for production.
 *
 * Usage (on the server, from app root):
 *   php bin/mail_diagnostics.php
 *
 * Never prints passwords or tokens.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Core\Env;
use Fmos\Core\Logger;
use Fmos\Core\Mail\MailAddresses;

$root = dirname(__DIR__);
$envPath = $root . '/.env';

echo "FMOS mail diagnostics\n";
echo "=====================\n";
echo 'app_root: ' . $root . "\n";
echo 'env_file: ' . $envPath . (is_file($envPath) ? ' (exists)' : ' (MISSING)') . "\n";

if (!is_file($envPath)) {
    fwrite(STDERR, "ERROR: .env not found. Create it next to public/ and src/.\n");
    exit(1);
}

// Show raw MAIL_DRIVER line shape without dumping secrets.
$rawLines = file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
$mailDriverRaw = null;
$mailDriverLineNo = null;
foreach ($rawLines as $i => $line) {
    if (preg_match('/^\s*MAIL_DRIVER\s*=\s*(.*)$/', $line, $m)) {
        $mailDriverRaw = $m[1];
        $mailDriverLineNo = $i + 1;
    }
}

if ($mailDriverRaw === null) {
    echo "MAIL_DRIVER line: NOT FOUND in .env (app defaults to log)\n";
} else {
    echo 'MAIL_DRIVER raw (line ' . $mailDriverLineNo . '): [' . $mailDriverRaw . "]\n";
    echo 'MAIL_DRIVER length: ' . strlen($mailDriverRaw) . "\n";
    echo 'MAIL_DRIVER hex: ' . bin2hex($mailDriverRaw) . "\n";
}

Env::load($envPath);
$status = Logger::mailConfigStatus();

echo "\nParsed by application:\n";
foreach ($status as $k => $v) {
    if (is_bool($v)) {
        $v = $v ? 'true' : 'false';
    }
    echo "  {$k}: {$v}\n";
}

$from = MailAddresses::accounts();
echo '  from_accounts: ' . Logger::maskEmail($from) . "\n";

echo "\nVerdict:\n";
if (($status['mail_driver'] ?? '') !== 'smtp') {
    echo "  FAIL — MAIL_DRIVER is '{$status['mail_driver']}'. Emails go to storage/mail only.\n";
    echo "  Fix: set MAIL_DRIVER=smtp in {$envPath} (no quotes), save, then re-run this script.\n";
    echo "  Do NOT expect Gmail delivery until mail_driver_is_smtp is true.\n";
    exit(2);
}

echo "  OK — driver is smtp. A registration should log SMTP_CONNECTION_* events.\n";
echo "  If inbox is still empty after that, inspect SMTP_AUTH_FAILED / connection errors in email-*.log.\n";
exit(0);

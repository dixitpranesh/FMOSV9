<?php

declare(strict_types=1);

/**
 * Report / convert project model_mode LEGACY -> FURNITURE_FIRST (non-destructive).
 *
 * Usage:
 *   php bin/migrate_project_mode.php --report
 *   php bin/migrate_project_mode.php --project=123 --to=FURNITURE_FIRST
 *   php bin/migrate_project_mode.php --backfill-components
 */

use Fmos\Core\Database;
use Fmos\Core\Env;
use Fmos\Domains\Furniture\FurnitureEngine;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$opts = getopt('', ['report', 'project:', 'to:', 'backfill-components']);
$pdo = Database::connection();

if (isset($opts['report'])) {
    $rows = $pdo->query('SELECT model_mode, COUNT(*) AS cnt FROM projects WHERE deleted_at IS NULL GROUP BY model_mode')->fetchAll();
    echo "Projects by model_mode:\n";
    foreach ($rows as $r) {
        echo "  {$r['model_mode']}: {$r['cnt']}\n";
    }
    exit(0);
}

if (isset($opts['backfill-components'])) {
    $engine = new FurnitureEngine();
    $stmt = $pdo->query('SELECT id, tenant_id FROM furniture_instances WHERE deleted_at IS NULL');
    $n = 0;
    while ($row = $stmt->fetch()) {
        $engine->refreshComponents((int) $row['tenant_id'], (int) $row['id']);
        $n++;
    }
    echo "Backfilled components for {$n} furniture instances\n";
    exit(0);
}

if (isset($opts['project'], $opts['to'])) {
    $to = strtoupper((string) $opts['to']);
    if (!in_array($to, ['LEGACY', 'FURNITURE_FIRST'], true)) {
        fwrite(STDERR, "Invalid --to\n");
        exit(1);
    }
    $id = (int) $opts['project'];
    $pdo->prepare('UPDATE projects SET model_mode=?, updated_at=NOW() WHERE id=?')->execute([$to, $id]);
    echo "Project {$id} set to {$to}\n";
    exit(0);
}

fwrite(STDERR, "Usage: --report | --project=ID --to=FURNITURE_FIRST|LEGACY | --backfill-components\n");
exit(1);

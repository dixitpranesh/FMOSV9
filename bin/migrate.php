<?php

declare(strict_types=1);

use Fmos\Core\Env;
use Fmos\Core\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

$migrator = new Migrator(dirname(__DIR__) . '/database/migrations');
$ran = $migrator->migrate();

echo "Migrations complete.\n";
if ($ran === []) {
    echo "No new migrations.\n";
} else {
    foreach ($ran as $name) {
        echo "  applied: {$name}\n";
    }
}

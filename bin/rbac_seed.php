<?php

declare(strict_types=1);

/**
 * CLI-only RBAC seed (replaces public POST /api/v1/rbac/seed).
 *
 * Usage: php bin/rbac_seed.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Core\Env;
use Fmos\Domains\Identity\RbacSeeder;

Env::load(dirname(__DIR__) . '/.env');

RbacSeeder::seed();
echo 'RBAC seeded. Roles: ' . count(RbacSeeder::roleCodes()) . PHP_EOL;

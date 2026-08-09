# FMOS Platform

Design-to-Manufacturing Operating System (Phases 0–10 MVP).

## Stack

- PHP 8.x
- MySQL 8.x
- Vanilla JS ES6 + HTML5 + CSS + Three.js
- REST `/api/v1`

## Setup

```bash
cp .env.example .env
# edit DB credentials
composer dump-autoload
php bin/migrate.php
php bin/seed.php
php -S localhost:8080 -t public public/router.php
```

Open http://localhost:8080

Default demo user (after seed): `owner@demo.fmos` / `Password123!`

## Docs

- Requirements: `/REQ`
- Analysis: `/REQ_ANALYSIS`
- ADRs: `/docs/adr`

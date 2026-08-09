<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Migrator
{
    public function __construct(private string $migrationPath)
    {
    }

    public function migrate(): array
    {
        $pdo = Database::connection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $cols = $pdo->query('SHOW COLUMNS FROM schema_migrations')->fetchAll(\PDO::FETCH_COLUMN);
        $migrationColumn = (in_array('version', $cols, true) && !in_array('migration', $cols, true))
            ? 'version'
            : 'migration';

        $applied = $pdo->query("SELECT {$migrationColumn} FROM schema_migrations")->fetchAll(\PDO::FETCH_COLUMN);
        $files = glob($this->migrationPath . '/*.sql') ?: [];
        sort($files);
        $ran = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException("Cannot read migration {$name}");
            }

            try {
                // MySQL DDL is often auto-committed; do not wrap in transactions.
                foreach ($this->splitStatements($sql) as $statement) {
                    $pdo->exec($statement);
                }
                $stmt = $pdo->prepare("INSERT INTO schema_migrations ({$migrationColumn}, applied_at) VALUES (?, NOW())");
                $stmt->execute([$name]);
                $ran[] = $name;
            } catch (\Throwable $e) {
                throw new \RuntimeException("Migration failed: {$name} — " . $e->getMessage(), 0, $e);
            }
        }

        return $ran;
    }

    /** @return list<string> */
    private function splitStatements(string $sql): array
    {
        $parts = preg_split('/;\s*[\r\n]+/', $sql) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || str_starts_with($part, '--')) {
                // skip pure comment blocks; keep statements that contain SQL after comments
            }
            // Remove leading comment lines
            $lines = preg_split('/\r\n|\n|\r/', $part) ?: [];
            $filtered = [];
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '--')) {
                    continue;
                }
                $filtered[] = $line;
            }
            $clean = trim(implode("\n", $filtered));
            if ($clean !== '') {
                $out[] = $clean;
            }
        }
        return $out;
    }
}

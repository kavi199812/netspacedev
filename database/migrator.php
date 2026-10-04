<?php
// NetSpace Dev - Database Migrator Engine
// Handles versioned SQL migrations for both local XAMPP and production Hostinger

class NetSpaceMigrator {
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo, ?string $migrationsDir = null) {
        $this->pdo = $pdo;
        $this->migrationsDir = $migrationsDir ?: __DIR__ . '/migrations';
        $this->ensureMigrationsTable();
    }

    /**
     * Ensure the tracking table exists
     */
    private function ensureMigrationsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `batch` INT NOT NULL DEFAULT 1,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->pdo->exec($sql);
    }

    /**
     * Get all .sql files in migrations directory
     */
    public function getMigrationFiles(): array {
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }
        $files = glob($this->migrationsDir . '/*.sql');
        if (!$files) {
            return [];
        }
        $list = array_map('basename', $files);
        sort($list, SORT_NATURAL);
        return $list;
    }

    /**
     * Get list of already applied migrations
     */
    public function getAppliedMigrations(): array {
        try {
            $stmt = $this->pdo->query("SELECT migration, batch, applied_at FROM `schema_migrations` ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get array of pending migration filenames
     */
    public function getPendingMigrations(): array {
        $all = $this->getMigrationFiles();
        $appliedRows = $this->getAppliedMigrations();
        $appliedMap = array_column($appliedRows, 'migration');
        
        $pending = [];
        foreach ($all as $file) {
            if (!in_array($file, $appliedMap, true)) {
                $pending[] = $file;
            }
        }
        return $pending;
    }

    /**
     * Execute all pending migrations
     */
    public function runPendingMigrations(): array {
        $pending = $this->getPendingMigrations();
        if (empty($pending)) {
            return [
                'success' => true,
                'message' => 'Database is already up to date. No pending migrations.',
                'applied_count' => 0,
                'logs' => ['Nothing to migrate.']
            ];
        }

        // Determine next batch number
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM `schema_migrations`");
        $nextBatch = (int)$stmt->fetchColumn();

        $logs = [];
        $appliedCount = 0;

        foreach ($pending as $migrationFile) {
            $filePath = $this->migrationsDir . '/' . $migrationFile;
            if (!file_exists($filePath)) {
                continue;
            }

            $sqlContent = file_get_contents($filePath);
            $statements = $this->splitSqlStatements($sqlContent);

            try {
                $executedStmtCount = 0;
                foreach ($statements as $query) {
                    $trimmed = trim($query);
                    if (!empty($trimmed)) {
                        $this->pdo->exec($trimmed);
                        $executedStmtCount++;
                    }
                }

                // Record in schema_migrations
                $ins = $this->pdo->prepare("INSERT INTO `schema_migrations` (`migration`, `batch`) VALUES (?, ?)");
                $ins->execute([$migrationFile, $nextBatch]);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }

                $appliedCount++;
                $logs[] = "✓ [Batch #{$nextBatch}] Successfully applied: {$migrationFile} ({$executedStmtCount} SQL statements)";
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $logs[] = "✗ Failed on {$migrationFile}: " . $e->getMessage();
                return [
                    'success' => false,
                    'error' => "Error executing migration '{$migrationFile}': " . $e->getMessage(),
                    'applied_count' => $appliedCount,
                    'logs' => $logs
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Successfully applied {$appliedCount} migration(s)!",
            'applied_count' => $appliedCount,
            'logs' => $logs
        ];
    }

    /**
     * Helper to split multiple SQL statements cleanly
     */
    private function splitSqlStatements(string $sql): array {
        // Strip SQL comments
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $cleanSql = implode("\n", $cleanLines);

        // Split by semicolon, handling statements
        $rawStatements = explode(';', $cleanSql);
        $statements = [];
        foreach ($rawStatements as $stmt) {
            $trimmed = trim($stmt);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
        }
        return $statements;
    }

    /**
     * Get Database & Tables Status Overview
     */
    public function getDatabaseOverview(): array {
        try {
            $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn() ?: 'unknown';
            $tablesStmt = $this->pdo->query("SHOW TABLES");
            $rawTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            $tables = [];
            foreach ($rawTables as $tableName) {
                try {
                    $cnt = $this->pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
                } catch (Exception $e) {
                    $cnt = 0;
                }
                $tables[] = [
                    'name' => $tableName,
                    'rows' => (int)$cnt
                ];
            }

            $applied = $this->getAppliedMigrations();
            $pending = $this->getPendingMigrations();

            return [
                'database' => $dbName,
                'tables' => $tables,
                'total_tables' => count($tables),
                'applied_migrations' => $applied,
                'pending_migrations' => $pending,
                'total_applied' => count($applied),
                'total_pending' => count($pending),
            ];
        } catch (Exception $e) {
            return [
                'database' => 'Error: ' . $e->getMessage(),
                'tables' => [],
                'total_tables' => 0,
                'applied_migrations' => [],
                'pending_migrations' => [],
                'total_applied' => 0,
                'total_pending' => 0,
            ];
        }
    }
}

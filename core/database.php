<?php

/**
 * Database Layer
 * 
 * PDO-based database helpers. No ORM — just clean prepared statements.
 */

function db_connect(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (config('app.debug')) {
                die('Database connection failed: ' . $e->getMessage());
            }
            die('Database connection failed.');
        }
    }

    return $pdo;
}

/**
 * Execute a query and return the PDOStatement.
 */
function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db_connect()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row.
 */
function db_fetch(string $sql, array $params = []): ?array
{
    $result = db_query($sql, $params)->fetch();
    return $result ?: null;
}

/**
 * Fetch all rows.
 */
function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

/**
 * Insert a row and return the last insert ID.
 */
function db_insert(string $table, array $data): string
{
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    db_query($sql, array_values($data));

    return db_connect()->lastInsertId();
}

/**
 * Update rows. $where is an assoc array of column => value for the WHERE clause.
 */
function db_update(string $table, array $data, array $where): int
{
    $setParts = [];
    $params   = [];

    foreach ($data as $col => $val) {
        $setParts[] = "{$col} = ?";
        $params[]   = $val;
    }

    $whereParts = [];
    foreach ($where as $col => $val) {
        $whereParts[] = "{$col} = ?";
        $params[]     = $val;
    }

    $sql = "UPDATE {$table} SET " . implode(', ', $setParts)
         . " WHERE " . implode(' AND ', $whereParts);

    return db_query($sql, $params)->rowCount();
}

/**
 * Delete rows. $where is an assoc array of column => value.
 */
function db_delete(string $table, array $where): int
{
    $whereParts = [];
    $params     = [];

    foreach ($where as $col => $val) {
        $whereParts[] = "{$col} = ?";
        $params[]     = $val;
    }

    $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);
    return db_query($sql, $params)->rowCount();
}

/**
 * Count rows matching conditions.
 */
function db_count(string $table, array $where = []): int
{
    if (empty($where)) {
        return (int) db_fetch("SELECT COUNT(*) as cnt FROM {$table}")['cnt'];
    }

    $whereParts = [];
    $params     = [];
    foreach ($where as $col => $val) {
        $whereParts[] = "{$col} = ?";
        $params[]     = $val;
    }

    $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE " . implode(' AND ', $whereParts);
    return (int) db_fetch($sql, $params)['cnt'];
}

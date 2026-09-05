<?php
/**
 * Database Helper Functions
 * MySQLi Prepared Statements Wrapper
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Automatically determine MySQLi bind types if not specified
 * @param array $params
 * @return string
 */
function db_infer_types($params) {
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } elseif (is_string($param)) {
            $types .= 's';
        } else {
            $types .= 'b'; // blob or null
        }
    }
    return $types;
}

/**
 * Execute a prepared SQL query
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return mysqli_stmt|false
 */
function db_query($sql, $params = [], $types = '') {
    $conn = get_db_connection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("MySQL prepare error: " . $conn->error . " in query: " . $sql);
        return false;
    }

    if (!empty($params)) {
        if (empty($types)) {
            $types = db_infer_types($params);
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("MySQL execute error: " . $stmt->error . " in query: " . $sql);
        $stmt->close();
        return false;
    }

    return $stmt;
}

/**
 * Fetch all matching rows as associative array
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array
 */
function db_fetch_all($sql, $params = [], $types = '') {
    $stmt = db_query($sql, $params, $types);
    if (!$stmt) {
        return [];
    }

    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
    $stmt->close();
    return $rows;
}

/**
 * Fetch single matching row as associative array
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array|null
 */
function db_fetch_one($sql, $params = [], $types = '') {
    $stmt = db_query($sql, $params, $types);
    if (!$stmt) {
        return null;
    }

    $result = $stmt->get_result();
    $row = null;
    if ($result) {
        $row = $result->fetch_assoc();
        $result->free();
    }
    $stmt->close();
    return $row;
}

/**
 * Insert record into table with prepared statement
 * @param string $table
 * @param array $data Associative array ['column' => 'value']
 * @return int|false Insert ID on success, false on error
 */
function db_insert($table, $data) {
    $conn = get_db_connection();
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    $values = array_values($data);

    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    $types = db_infer_types($values);

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Insert prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param($types, ...$values);
    $executed = $stmt->execute();

    if ($executed) {
        $insertId = $conn->insert_id;
        $stmt->close();
        return $insertId;
    } else {
        error_log("Insert execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Update record(s) in table with prepared statement
 * @param string $table
 * @param array $data
 * @param string $whereClause e.g. "id = ?"
 * @param array $whereParams
 * @return int Number of affected rows
 */
function db_update($table, $data, $whereClause, $whereParams = []) {
    $conn = get_db_connection();
    $setParts = [];
    $values = [];

    foreach ($data as $col => $val) {
        $setParts[] = "`{$col}` = ?";
        $values[] = $val;
    }

    $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$whereClause}";
    $allParams = array_merge($values, $whereParams);
    $types = db_infer_types($allParams);

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Update prepare failed: " . $conn->error);
        return 0;
    }

    $stmt->bind_param($types, ...$allParams);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

/**
 * Delete record(s) from table with prepared statement
 * @param string $table
 * @param string $whereClause e.g. "id = ?"
 * @param array $whereParams
 * @return int
 */
function db_delete($table, $whereClause, $whereParams = []) {
    $conn = get_db_connection();
    $sql = "DELETE FROM `{$table}` WHERE {$whereClause}";
    $types = db_infer_types($whereParams);

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Delete prepare failed: " . $conn->error);
        return 0;
    }

    if (!empty($whereParams)) {
        $stmt->bind_param($types, ...$whereParams);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

/**
 * Helper to get a scalar value (e.g. COUNT(*))
 * @param string $sql
 * @param array $params
 * @return mixed
 */
function db_scalar($sql, $params = []) {
    $row = db_fetch_one($sql, $params);
    if ($row) {
        return reset($row);
    }
    return 0;
}

/**
 * Automatically ensure customer authentication columns exist
 */
function ensure_customer_auth_columns() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $conn = get_db_connection();
        $res = $conn->query("SHOW COLUMNS FROM `customers` LIKE 'password_hash'");
        if ($res && $res->num_rows === 0) {
            $conn->query("ALTER TABLE `customers` ADD COLUMN `password_hash` VARCHAR(255) DEFAULT NULL AFTER `email`, ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notes`");
        }
    } catch (Exception $e) {
        // Table may not exist yet during initial fresh installation
    }
}

// Check on boot
ensure_customer_auth_columns();


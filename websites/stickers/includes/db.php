<?php
require_once 'config.php';

/**
 * Get database connection
 * 
 * @return SQLite3 Database connection instance
 */
function getDb() {
    static $db = null;
    
    if ($db === null) {
        try {
            // Create database directory if it doesn't exist
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $db = new SQLite3(DB_PATH);
            $db->enableExceptions(true);
            $db->exec('PRAGMA foreign_keys = ON');
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }
    
    return $db;
}

/**
 * Execute a query and return results as an associative array
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Results as associative array
 */
function dbQuery($query, $params = []) {
    $db = getDb();
    $stmt = $db->prepare($query);
    
    if ($stmt) {
        $i = 1;
        foreach ($params as $param) {
            $paramType = is_int($param) ? SQLITE3_INTEGER : (is_float($param) ? SQLITE3_FLOAT : SQLITE3_TEXT);
            $stmt->bindValue($i++, $param, $paramType);
        }
        
        $result = $stmt->execute();
        $rows = [];
        
        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    return [];
}

/**
 * Execute an insert/update/delete query and return the number of affected rows
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return int Number of affected rows or last insert ID
 */
function dbExecute($query, $params = []) {
    $db = getDb();
    $stmt = $db->prepare($query);
    
    if ($stmt) {
        $i = 1;
        foreach ($params as $param) {
            $paramType = is_int($param) ? SQLITE3_INTEGER : (is_float($param) ? SQLITE3_FLOAT : SQLITE3_TEXT);
            $stmt->bindValue($i++, $param, $paramType);
        }
        
        $result = $stmt->execute();
        
        if (stripos($query, 'INSERT') === 0) {
            return $db->lastInsertRowID();
        } else {
            return $db->changes();
        }
    }
    
    return 0;
}

/**
 * Get a setting value from the database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    $result = dbQuery('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
    
    if (!empty($result)) {
        return $result[0]['setting_value'];
    }
    
    return $default;
}
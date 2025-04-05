<?php
/**
 * Database Test Script
 * 
 * This script allows you to:
 * - Run SQL commands directly against the database
 * - View query output in formatted tables
 * - Execute predefined initialization queries
 * - Check database structure and records
 * - Test database functionality
 * 
 * IMPORTANT: Remove this file from production environment after use!
 */

// Basic security to prevent unauthorized access
// Change this password before using
define('ACCESS_PASSWORD', 'sticker_admin');

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication
$authenticated = false;
$error = null;
$message = null;

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['db_test_auth']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if already authenticated
if (isset($_SESSION['db_test_auth']) && $_SESSION['db_test_auth'] === true) {
    $authenticated = true;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ACCESS_PASSWORD) {
        $_SESSION['db_test_auth'] = true;
        $authenticated = true;
    } else {
        $error = 'Invalid password';
    }
}

// Database connection function
function connectDb() {
    try {
        $dbPath = dirname(__DIR__) . '/database/stickers.db';
        $dbDir = dirname($dbPath);
        
        // Create database directory if it doesn't exist
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON');
        return $db;
    } catch (Exception $e) {
        die('Database connection error: ' . $e->getMessage());
    }
}

// Initialize database function
function initializeDb() {
    $db = connectDb();
    
    // SQL Schema from the database schema file
    $schemaSQL = <<<'SQL'
-- Stickers table
CREATE TABLE IF NOT EXISTS stickers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    image_path TEXT NOT NULL,
    category TEXT NOT NULL CHECK (category IN ('large', 'medium', 'small', 'custom')),
    active INTEGER DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_code TEXT UNIQUE NOT NULL,
    contact_number TEXT NOT NULL,
    messaging_app TEXT NOT NULL,
    instagram_username TEXT,
    total_amount REAL NOT NULL,
    payment_status TEXT NOT NULL CHECK (payment_status IN ('pending', 'paid')),
    payment_screenshot TEXT,
    order_status TEXT NOT NULL CHECK (order_status IN ('new', 'processing', 'shipped', 'delivered', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    sticker_id INTEGER,
    custom_design_path TEXT,
    quantity INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sticker_id) REFERENCES stickers(id) ON DELETE SET NULL
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    last_login TIMESTAMP
);

-- Settings table for site configuration
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;
    
    // Execute schema SQL
    $result = $db->exec($schemaSQL);
    
    // Insert default admin user if not exists
    $checkAdmin = $db->query("SELECT COUNT(*) as count FROM admin_users");
    $adminCount = $checkAdmin->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($adminCount == 0) {
        $defaultUsername = 'admin';
        $defaultPassword = 'adminpass'; // Change this before going live
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->bindValue(1, $defaultUsername, SQLITE3_TEXT);
        $stmt->bindValue(2, $passwordHash, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // Insert default settings if not exists
    $checkSettings = $db->query("SELECT COUNT(*) as count FROM settings");
    $settingsCount = $checkSettings->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($settingsCount == 0) {
        $defaultSettings = [
            ['base_price', '10'],
            ['min_order_quantity', '10'],
            ['custom_sticker_ratio', '3:9'],
            ['bulk_discount_threshold', '12'],
            ['bulk_discount_free_stickers', '2']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bindValue(1, $setting[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $setting[1], SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    
    return true;
}

// Run a query and return results as array
function runQuery($sql, $params = []) {
    $db = connectDb();
    $results = [];
    $error = null;
    
    try {
        $stmt = $db->prepare($sql);
        
        // Bind parameters if any
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $paramType = is_int($param) ? SQLITE3_INTEGER : (is_float($param) ? SQLITE3_FLOAT : SQLITE3_TEXT);
                // SQLite3 parameters are 1-indexed
                $stmt->bindValue($key + 1, $param, $paramType);
            }
        }
        
        $result = $stmt->execute();
        
        // Check if it's a SELECT query by looking for results
        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
            }
        }
        
        // Check for INSERT, UPDATE, DELETE
        $changes = $db->changes();
        if ($changes > 0) {
            $results['affected_rows'] = $changes;
        }
        
        // Check for lastInsertRowID for INSERT queries
        if (stripos(trim($sql), 'INSERT') === 0) {
            $results['last_insert_id'] = $db->lastInsertRowID();
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    
    return ['results' => $results, 'error' => $error];
}

// Get table structure
function getTableStructure($table) {
    $db = connectDb();
    $result = $db->query("PRAGMA table_info({$table})");
    $columns = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row;
    }
    
    return $columns;
}

// Get list of tables
function getTables() {
    $db = connectDb();
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    $tables = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $row['name'];
    }
    
    return $tables;
}

// Handle form submissions if authenticated
if ($authenticated) {
    // Handle initialize database
    if (isset($_POST['initialize_db'])) {
        try {
            initializeDb();
            $message = 'Database initialized successfully';
        } catch (Exception $e) {
            $error = 'Error initializing database: ' . $e->getMessage();
        }
    }
    
    // Handle SQL execution
    if (isset($_POST['run_sql'])) {
        $sql = $_POST['sql_query'] ?? '';
        $params = [];
        
        // Parse parameters if provided
        if (!empty($_POST['params'])) {
            $paramLines = explode("\n", $_POST['params']);
            foreach ($paramLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $params[] = $line;
                }
            }
        }
        
        $queryResult = runQuery($sql, $params);
    }
    
    // Handle table viewing
    if (isset($_GET['view_table'])) {
        $tableName = $_GET['view_table'];
        $tableData = runQuery("SELECT * FROM {$tableName} LIMIT 100");
        $tableStructure = getTableStructure($tableName);
    }
}

// CSS styles
$styles = <<<'CSS'
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f7f9;
}
h1, h2, h3 {
    color: #2c3e50;
}
.container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.login-container {
    max-width: 400px;
    margin: 100px auto;
}
.error {
    color: #e74c3c;
    background-color: #fadbd8;
    padding: 10px;
    border-radius: 3px;
    margin-bottom: 15px;
}
.success {
    color: #27ae60;
    background-color: #d4efdf;
    padding: 10px;
    border-radius: 3px;
    margin-bottom: 15px;
}
.nav {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.nav a {
    margin-left: 15px;
    text-decoration: none;
    color: #3498db;
}
.nav a:hover {
    text-decoration: underline;
}
form {
    margin-bottom: 20px;
}
label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
input[type="text"],
input[type="password"],
textarea,
select {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 3px;
}
textarea {
    height: 150px;
    font-family: monospace;
}
button, .button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 3px;
    cursor: pointer;
}
button:hover, .button:hover {
    background-color: #2980b9;
}
.danger {
    background-color: #e74c3c;
}
.danger:hover {
    background-color: #c0392b;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background-color: #f2f2f2;
    font-weight: bold;
}
tr:nth-child(even) {
    background-color: #f9f9f9;
}
.tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}
.tab {
    padding: 10px 15px;
    cursor: pointer;
    margin-right: 5px;
    border: 1px solid transparent;
}
.tab.active {
    border: 1px solid #ddd;
    border-bottom-color: #fff;
    border-radius: 3px 3px 0 0;
    margin-bottom: -1px;
    background-color: #fff;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.float-right {
    float: right;
}
.tables-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.table-link {
    display: inline-block;
    padding: 8px 12px;
    background-color: #f2f2f2;
    border-radius: 3px;
    text-decoration: none;
    color: #333;
}
.table-link:hover {
    background-color: #e0e0e0;
}
.sql-result {
    overflow-x: auto;
    margin-top: 20px;
}
CSS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test Tool</title>
    <style><?php echo $styles; ?></style>
</head>
<body>
    <?php if (!$authenticated): ?>
    <!-- Login Form -->
    <div class="login-container container">
        <h1>Database Test Tool</h1>
        <p>Enter the access password to continue.</p>
        
        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
    <?php else: ?>
    <!-- Database Test Tool -->
    <div class="nav">
        <h1>Database Test Tool</h1>
        <div>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Home</a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?logout=1">Logout</a>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <div class="tab <?php echo !isset($_GET['view_table']) ? 'active' : ''; ?>">Database</div>
        <div class="tab <?php echo isset($_GET['view_table']) ? 'active' : ''; ?>">Table Viewer</div>
        <div class="tab">SQL Executor</div>
        <div class="tab">Initialize Database</div>
    </div>
    
    <!-- Database Overview Tab -->
    <div id="database" class="tab-content <?php echo !isset($_GET['view_table']) ? 'active' : ''; ?> container">
        <h2>Database Tables</h2>
        <p>Click on a table name to view its structure and data.</p>
        
        <div class="tables-list">
            <?php 
            $tables = getTables();
            foreach ($tables as $table): 
            ?>
            <a href="<?php echo $_SERVER['PHP_SELF'] . '?view_table=' . urlencode($table); ?>" class="table-link">
                <?php echo htmlspecialchars($table); ?>
            </a>
            <?php endforeach; ?>
            
            <?php if (empty($tables)): ?>
            <p>No tables found in the database. Use the Initialize Database tab to create the schema.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Table Viewer Tab -->
    <div id="table-viewer" class="tab-content <?php echo isset($_GET['view_table']) ? 'active' : ''; ?> container">
        <?php if (isset($tableName)): ?>
        <h2>Table: <?php echo htmlspecialchars($tableName); ?></h2>
        
        <h3>Table Structure</h3>
        <table>
            <thead>
                <tr>
                    <th>CID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>NotNull</th>
                    <th>Default Value</th>
                    <th>Primary Key</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableStructure as $column): ?>
                <tr>
                    <td><?php echo $column['cid']; ?></td>
                    <td><?php echo htmlspecialchars($column['name']); ?></td>
                    <td><?php echo htmlspecialchars($column['type']); ?></td>
                    <td><?php echo $column['notnull'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $column['dflt_value'] !== null ? htmlspecialchars($column['dflt_value']) : 'NULL'; ?></td>
                    <td><?php echo $column['pk'] ? 'Yes' : 'No'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Table Data</h3>
        <?php if (empty($tableData['results'])): ?>
        <p>No data found in this table.</p>
        <?php else: ?>
        <div class="sql-result">
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($tableData['results'][0]) as $column): ?>
                        <th><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData['results'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                        <td><?php echo $value !== null ? htmlspecialchars($value) : '<em>NULL</em>'; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="button">Back to Tables</a>
        <?php else: ?>
        <p>Select a table from the Database tab to view its structure and data.</p>
        <?php endif; ?>
    </div>
    
    <!-- SQL Executor Tab -->
    <div id="sql-executor" class="tab-content container">
        <h2>SQL Executor</h2>
        <p>Enter your SQL query below and click "Run Query" to execute it.</p>
        
        <form method="post" action="">
            <div>
                <label for="sql_query">SQL Query:</label>
                <textarea id="sql_query" name="sql_query" required><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
            </div>
            
            <div>
                <label for="params">Parameters (optional, one per line):</label>
                <textarea id="params" name="params" style="height: 80px;"><?php echo isset($_POST['params']) ? htmlspecialchars($_POST['params']) : ''; ?></textarea>
                <p><small>For prepared statements, add one parameter per line in the order they appear in the query (?)</small></p>
            </div>
            
            <button type="submit" name="run_sql">Run Query</button>
        </form>
        
        <?php if (isset($queryResult)): ?>
        <h3>Query Result</h3>
        
        <?php if ($queryResult['error']): ?>
        <div class="error"><?php echo htmlspecialchars($queryResult['error']); ?></div>
        <?php else: ?>
            <?php if (isset($queryResult['results']['affected_rows'])): ?>
            <div class="success">
                Query executed successfully. Affected rows: <?php echo $queryResult['results']['affected_rows']; ?>
                <?php if (isset($queryResult['results']['last_insert_id'])): ?>
                <br>Last insert ID: <?php echo $queryResult['results']['last_insert_id']; ?>
                <?php endif; ?>
            </div>
            <?php elseif (empty($queryResult['results'])): ?>
            <div class="success">Query executed successfully. No results returned.</div>
            <?php else: ?>
            <div class="sql-result">
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($queryResult['results'][0]) as $column): ?>
                            <th><?php echo htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queryResult['results'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                            <td><?php echo $value !== null ? htmlspecialchars($value) : '<em>NULL</em>'; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Initialize Database Tab -->
    <div id="db-initialize" class="tab-content container">
        <h2>Initialize Database</h2>
        <p>This will create all necessary tables in the database according to the schema and insert default values.</p>
        <p><strong>Warning:</strong> If the tables already exist, this will not drop them, but may add default data if missing.</p>
        
        <form method="post" action="">
            <button type="submit" name="initialize_db" class="danger">Initialize Database</button>
        </form>
        
        <h3>Schema Preview</h3>
        <pre style="background-color: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px;"><?php 
        $schemaSQL = <<<'SQL'
-- Stickers table
CREATE TABLE IF NOT EXISTS stickers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    image_path TEXT NOT NULL,
    category TEXT NOT NULL CHECK (category IN ('large', 'medium', 'small', 'custom')),
    active INTEGER DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_code TEXT UNIQUE NOT NULL,
    contact_number TEXT NOT NULL,
    messaging_app TEXT NOT NULL,
    instagram_username TEXT,
    total_amount REAL NOT NULL,
    payment_status TEXT NOT NULL CHECK (payment_status IN ('pending', 'paid')),
    payment_screenshot TEXT,
    order_status TEXT NOT NULL CHECK (order_status IN ('new', 'processing', 'shipped', 'delivered', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    sticker_id INTEGER,
    custom_design_path TEXT,
    quantity INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sticker_id) REFERENCES stickers(id) ON DELETE SET NULL
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    last_login TIMESTAMP
);

-- Settings table for site configuration
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;
        echo htmlspecialchars($schemaSQL); 
        ?></pre>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Find and activate the corresponding tab button
            let activeTabElement = null;
            document.querySelectorAll('.tab').forEach((tab, index) => {
                if (index === 0 && tabId === 'database') {
                    activeTabElement = tab;
                } else if (index === 1 && tabId === 'table-viewer') {
                    activeTabElement = tab;
                } else if (index === 2 && tabId === 'sql-executor') {
                    activeTabElement = tab;
                } else if (index === 3 && tabId === 'db-initialize') {
                    activeTabElement = tab;
                }
            });
            
            if (activeTabElement) {
                activeTabElement.classList.add('active');
            }
            
            // Update hash for bookmarking
            window.location.hash = tabId;
        }
        
        // Set active tab based on URL hash
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to tab buttons
            document.querySelectorAll('.tab').forEach((tab, index) => {
                tab.addEventListener('click', function() {
                    if (index === 0) switchTab('database');
                    else if (index === 1) switchTab('table-viewer');
                    else if (index === 2) switchTab('sql-executor');
                    else if (index === 3) switchTab('db-initialize');
                });
            });
            
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                switchTab(hash);
            }
            
            // Auto-focus SQL query field in SQL executor tab
            const sqlQueryField = document.getElementById('sql_query');
            if (sqlQueryField && document.getElementById('sql-executor').classList.contains('active')) {
                sqlQueryField.focus();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php
/**
 * =============================================================================
 * Auto Checkout Cron Job
 * =============================================================================
 * 
 * Automatically creates checkout records for employees who forgot to checkout.
 * This script should be scheduled to run via cron job.
 * 
 * FEATURES:
 * - Configurable execution time window (only runs within specified hours)
 * - Safe to run frequently (e.g., every minute) - will exit if outside window
 * - Sets checkout time to a configurable time (default: 1:00 PM)
 * - Only processes employees who have checked in but not checked out
 * 
 * CRON SETUP:
 * Option 1: Run once at specific time
 *   0 19 * * * php /path/to/auto_checkout.php
 * 
 * Option 2: Run every minute (script self-manages execution window)
 *   * * * * * php /path/to/auto_checkout.php
 * 
 * DATABASE REQUIREMENTS:
 * - Table: attendance (staff_staff_id, date, device_id, event_type, event_time)
 * 
 * @author    Contributed by Upendra237
 * @version   1.1.0
 * @license   MIT
 * =============================================================================
 */

// =============================================================================
// CONFIGURATION
// =============================================================================

/** @var string Timezone for date/time operations */
define('TIMEZONE', 'Asia/Kathmandu');

/** 
 * @var string Time window start - script only executes from this time
 * Format: HH:MM (24-hour)
 * Example: '19:00' = 7:00 PM
 */
define('RUN_WINDOW_START', '19:00');

/** 
 * @var string Time window end - script stops executing after this time
 * Format: HH:MM (24-hour)
 * Example: '19:30' = 7:30 PM
 */
define('RUN_WINDOW_END', '19:30');

/** 
 * @var string The checkout time to record for missed checkouts
 * Format: HH:MM:SS (24-hour)
 * Example: '13:00:00' = 1:00 PM
 */
define('AUTO_CHECKOUT_TIME', '13:00:00');

// =============================================================================
// INITIALIZATION
// =============================================================================

// Set timezone
date_default_timezone_set(TIMEZONE);

// Get current time for window check
$currentTime = date('H:i');

// =============================================================================
// EXECUTION WINDOW CHECK
// =============================================================================

/**
 * Check if current time is within the allowed execution window.
 * This allows the cron to run frequently while only executing
 * the actual checkout logic during the specified time window.
 */
if ($currentTime < RUN_WINDOW_START || $currentTime > RUN_WINDOW_END) {
    echo "Outside run window.\n";
    echo "Current: $currentTime\n";
    echo "Window: " . RUN_WINDOW_START . " - " . RUN_WINDOW_END . "\n";
    exit(0);
}

// =============================================================================
// DATABASE CONNECTION
// =============================================================================

try {
    require_once __DIR__ . '/../includes/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// =============================================================================
// SCRIPT EXECUTION
// =============================================================================

// Calculate dates and times
$today = date('Y-m-d');
$autoCheckoutDateTime = $today . ' ' . AUTO_CHECKOUT_TIME;

// Display script header
echo "=== Auto Checkout Script ===\n";
echo "Date: $today\n";
echo "Current time: $currentTime\n";
echo "Auto checkout time: " . AUTO_CHECKOUT_TIME . "\n\n";

// =============================================================================
// FIND EMPLOYEES WITH MISSING CHECKOUTS
// =============================================================================

/**
 * Query to find employees who:
 * 1. Have a check_in record for today
 * 2. Do NOT have a check_out record for today
 * 
 * Uses subquery to exclude employees who already checked out
 */
$query = "SELECT DISTINCT a.staff_staff_id, a.device_id
          FROM attendance a
          WHERE a.date = :date 
          AND a.event_type = 'check_in'
          AND a.staff_staff_id NOT IN (
              SELECT staff_staff_id FROM attendance 
              WHERE date = :date2 AND event_type = 'check_out'
          )";

$stmt = $db->prepare($query);
$stmt->bindParam(':date', $today);
$stmt->bindParam(':date2', $today);
$stmt->execute();

$missedCheckouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = count($missedCheckouts);

echo "Found $count employee(s) who forgot to checkout.\n\n";

// Exit early if no work needed
if ($count === 0) {
    echo "No auto-checkouts needed.\n";
    exit(0);
}

// =============================================================================
// INSERT AUTO CHECKOUT RECORDS
// =============================================================================

/**
 * Insert checkout record for each employee
 * Uses the same device_id as their check-in record
 */
$insertQuery = "INSERT INTO attendance (staff_staff_id, date, device_id, event_type, event_time) 
                VALUES (:staff_id, :date, :device_id, 'check_out', :event_time)";

$insertStmt = $db->prepare($insertQuery);

// Track results
$successCount = 0;
$failedCount = 0;

// Process each employee
foreach ($missedCheckouts as $employee) {
    $insertStmt->bindParam(':staff_id', $employee['staff_staff_id']);
    $insertStmt->bindParam(':date', $today);
    $insertStmt->bindParam(':device_id', $employee['device_id']);
    $insertStmt->bindParam(':event_time', $autoCheckoutDateTime);
    
    if ($insertStmt->execute()) {
        echo "[OK] {$employee['staff_staff_id']} -> checkout at " . AUTO_CHECKOUT_TIME . "\n";
        $successCount++;
    } else {
        echo "[FAIL] {$employee['staff_staff_id']}\n";
        $failedCount++;
    }
}

// =============================================================================
// SUMMARY
// =============================================================================

echo "\n=== Summary ===\n";
echo "Processed: $count\n";
echo "Success: $successCount\n";
echo "Failed: $failedCount\n";

// Exit with error code if any failures occurred
exit($failedCount > 0 ? 1 : 0);

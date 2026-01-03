<?php
/**
 * =============================================================================
 * Hikvision Attendance Webhook
 * =============================================================================
 * 
 * Production-ready webhook receiver for Hikvision DS-K1T320EFWX and similar
 * access control devices. Receives attendance events via HTTP POST and stores
 * them in a MySQL database.
 * 
 * FEATURES:
 * - Configurable check-in and check-out time windows
 * - One check-in and one check-out per employee per day
 * - Device validation via serial number or MAC address
 * - Automatic event type determination based on time
 * 
 * DEVICE SETUP:
 * Configure your Hikvision device to send HTTP POST requests to this webhook URL.
 * The device sends data in the 'event_log' POST parameter as JSON.
 * 
 * DATABASE REQUIREMENTS:
 * - Table: attendance (staff_staff_id, date, device_id, event_type, event_time)
 * - Table: attendance_devices (id, device_name, serial_number, mac_address, is_active)
 * 
 * @author    Contributed by Upendra237
 * @version   1.0.0
 * @license   MIT
 * =============================================================================
 */

// =============================================================================
// CONFIGURATION
// =============================================================================

/** @var string Timezone for date/time operations */
define('TIMEZONE', 'Asia/Kathmandu');

/** @var string Check-in window start time (24-hour format HH:MM) */
define('CHECKIN_START', '09:00');

/** @var string Check-in window end time (24-hour format HH:MM) */
define('CHECKIN_END', '10:30');

/** @var string Check-out window start time (24-hour format HH:MM) */
define('CHECKOUT_START', '15:00');

/** @var string Check-out window end time (24-hour format HH:MM) */
define('CHECKOUT_END', '19:00');

// =============================================================================
// INITIALIZATION
// =============================================================================

// Set response headers
header('Content-Type: application/json');
http_response_code(200);

// Set timezone
date_default_timezone_set(TIMEZONE);

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Send JSON response and terminate script
 * 
 * @param int    $status  HTTP-like status code (200 = success, 202 = accepted but not processed)
 * @param string $message Response message
 * @return void
 */
function respond($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// =============================================================================
// REQUEST VALIDATION
// =============================================================================

// Check for event_log POST parameter
$eventLog = $_POST['event_log'] ?? '';
if (empty($eventLog)) {
    respond(202, 'No data');
}

// Parse JSON payload
$data = json_decode($eventLog);
if (!$data) {
    respond(202, 'Invalid JSON');
}

// =============================================================================
// DATA EXTRACTION
// =============================================================================

// Extract event data from AccessControllerEvent object
$eventData = $data->AccessControllerEvent ?? (object)[];

/** @var int Major event type (5 = access event) */
$majorEventType = $eventData->majorEventType ?? 0;

/** @var string Employee ID from device (employeeNoString field) */
$employeeId = $eventData->employeeNoString ?? '';

/** @var string Device MAC address for identification */
$macAddress = $data->macAddress ?? '';

/** @var string Event timestamp from device */
$eventTime = $data->dateTime ?? '';

/**
 * Device serial number - try multiple possible keys
 * Different Hikvision devices may use different field names
 */
$serialNumber = $data->serialNo ?? $data->serialNumber ?? $data->deviceSerialNo ?? '';

// =============================================================================
// EVENT VALIDATION
// =============================================================================

// Only process access events (majorEventType = 5)
if ($majorEventType != 5) {
    respond(202, 'Invalid event');
}

// Employee ID is required
if (empty($employeeId)) {
    respond(202, 'No employee ID');
}

// Validate event date is today (reject old events)
$eventDate = date('Y-m-d', strtotime($eventTime));
$today = date('Y-m-d');
if ($eventDate !== $today) {
    respond(202, 'Event expired');
}

// =============================================================================
// TIME CALCULATIONS
// =============================================================================

/** @var string Current time in HH:MM format for window comparison */
$currentTime = date('H:i');

/** @var string Current datetime for database storage */
$currentDateTime = date('Y-m-d H:i:s');

// =============================================================================
// DATABASE OPERATIONS
// =============================================================================

try {
    // Connect to database
    require_once __DIR__ . '/../includes/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // -------------------------------------------------------------------------
    // DEVICE LOOKUP
    // -------------------------------------------------------------------------
    
    $device = null;
    
    // Try to find device by serial number first (most reliable)
    if (!empty($serialNumber)) {
        $q = "SELECT id, device_name FROM attendance_devices 
              WHERE serial_number = :val AND is_active = 1 LIMIT 1";
        $stmt = $db->prepare($q);
        $stmt->bindParam(':val', $serialNumber);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Fallback to MAC address lookup
    if (!$device && !empty($macAddress)) {
        $q = "SELECT id, device_name FROM attendance_devices 
              WHERE mac_address = :val AND is_active = 1 LIMIT 1";
        $stmt = $db->prepare($q);
        $stmt->bindParam(':val', $macAddress);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Reject if device not registered
    if (!$device) {
        respond(202, 'Device not registered');
    }
    
    $deviceId = $device['id'];
    
    // -------------------------------------------------------------------------
    // CHECK TODAY'S ATTENDANCE RECORDS
    // -------------------------------------------------------------------------
    
    $q = "SELECT id, event_type FROM attendance 
          WHERE staff_staff_id = :staff_id AND date = :date";
    $stmt = $db->prepare($q);
    $stmt->bindParam(':staff_id', $employeeId);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $todayRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine existing attendance status
    $hasCheckin = false;
    $hasCheckout = false;
    foreach ($todayRecords as $rec) {
        if ($rec['event_type'] === 'check_in') $hasCheckin = true;
        if ($rec['event_type'] === 'check_out') $hasCheckout = true;
    }
    
    // -------------------------------------------------------------------------
    // DETERMINE ACTION BASED ON TIME AND EXISTING RECORDS
    // -------------------------------------------------------------------------
    
    $action = null;
    
    // Check if current time falls within defined windows
    $inCheckinWindow = ($currentTime >= CHECKIN_START && $currentTime <= CHECKIN_END);
    $inCheckoutWindow = ($currentTime >= CHECKOUT_START && $currentTime <= CHECKOUT_END);
    
    // Case 1: Already completed both check-in and check-out
    if ($hasCheckin && $hasCheckout) {
        respond(202, 'Already completed attendance today');
    }
    
    // Case 2: Has checked in, waiting for checkout
    if ($hasCheckin && !$hasCheckout) {
        if ($inCheckoutWindow) {
            $action = 'check_out';
        } else {
            respond(202, 'Already checked in. Checkout: ' . CHECKOUT_START . '-' . CHECKOUT_END);
        }
    }
    // Case 3: Needs to check in first
    elseif (!$hasCheckin) {
        if ($inCheckinWindow) {
            $action = 'check_in';
        } else {
            respond(202, 'Outside checkin hours. Checkin: ' . CHECKIN_START . '-' . CHECKIN_END);
        }
    }
    
    // -------------------------------------------------------------------------
    // INSERT ATTENDANCE RECORD
    // -------------------------------------------------------------------------
    
    $q = "INSERT INTO attendance (staff_staff_id, date, device_id, event_type, event_time) 
          VALUES (:staff_id, :date, :device_id, :event_type, :event_time)";
    $stmt = $db->prepare($q);
    $stmt->bindParam(':staff_id', $employeeId);
    $stmt->bindParam(':date', $today);
    $stmt->bindParam(':device_id', $deviceId, PDO::PARAM_INT);
    $stmt->bindParam(':event_type', $action);
    $stmt->bindParam(':event_time', $currentDateTime);
    
    if ($stmt->execute()) {
        respond(200, 'Success. ' . ucfirst(str_replace('_', ' ', $action)));
    } else {
        respond(202, 'Insert failed');
    }
    
} catch (Exception $e) {
    // Log error in production, return generic message
    respond(202, 'Error');
}

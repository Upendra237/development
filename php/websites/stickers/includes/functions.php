<?php
// includes/functions.php
require_once 'db.php';

/**
 * Generate a random order code
 *
 * @return string Unique order code
 */
function generateOrderCode() {
    $prefix = 'STK';
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    return $prefix . $timestamp . $random;
}

/**
 * Sanitize user input
 *
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate mobile number
 *
 * @param string $number Mobile number
 * @return bool True if valid
 */
function isValidMobile($number) {
    // Adjust pattern for Nepali phone numbers
    return preg_match('/^(98|97)\d{8}$/', preg_replace('/\D/', '', $number));
}

/**
 * Validate Instagram username
 *
 * @param string $username Instagram username
 * @return bool True if valid
 */
function isValidInstagramUsername($username) {
    return preg_match('/^[a-zA-Z0-9._]{1,30}$/', $username);
}

/**
 * Calculate order price based on sticker quantity
 *
 * @param int $regularCount Regular sticker count
 * @param int $customCount Custom sticker count
 * @return array Price details
 */
function calculatePrice($regularCount, $customCount) {
    $basePrice = (float) getSetting('base_price', 10);
    $bulkThreshold = (int) getSetting('bulk_discount_threshold', 12);
    $freeStickers = (int) getSetting('bulk_discount_free_stickers', 2);
    
    $totalStickers = $regularCount + $customCount;
    $discountSets = floor($totalStickers / $bulkThreshold);
    $discountedStickers = $discountSets * $freeStickers;
    
    $paidStickers = $totalStickers - $discountedStickers;
    $totalPrice = $paidStickers * $basePrice;
    
    return [
        'total_stickers' => $totalStickers,
        'paid_stickers' => $paidStickers,
        'free_stickers' => $discountedStickers,
        'base_price' => $basePrice,
        'total_price' => $totalPrice
    ];
}

/**
 * Calculate allowable custom stickers based on regular stickers
 *
 * @param int $regularCount Regular sticker count
 * @return int Allowable custom sticker count
 */
function calculateAllowableCustomStickers($regularCount) {
    $ratio = getSetting('custom_sticker_ratio', '3:9');
    list($customRatio, $regularRatio) = explode(':', $ratio);
    
    $customRatio = (int) $customRatio;
    $regularRatio = (int) $regularRatio;
    
    if ($regularRatio <= 0) return 0; // Prevent division by zero
    
    return floor($regularCount / $regularRatio) * $customRatio;
}

/**
 * Upload and process an image file
 *
 * @param array $file $_FILES array element
 * @param string $destination Path to upload directory
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return string|false Path to uploaded file or false on error
 */
function uploadImage($file, $destination, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 2097152) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    // Validate file type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Create destination directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Check if user is an authenticated admin
 *
 * @return bool True if authenticated
 */
function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

/**
 * Redirect to a URL
 *
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Display a flash message
 *
 * @param string $type Message type (success, error, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'time' => time()
    ];
}

/**
 * Get and clear flash message
 *
 * @return array|null Flash message data or null if none exists
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        // Only return if less than 5 seconds old
        if (time() - $message['time'] < 5) {
            return $message;
        }
    }
    
    return null;
}
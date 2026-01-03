# Hikvision Attendance System

A production-ready attendance webhook system for Hikvision DS-K1T320EFWX and similar access control devices.

**Contributed by Upendra237**

---

## 📋 Overview

This system receives attendance events from Hikvision access control devices via HTTP POST and stores them in a MySQL database. It includes:

- **webhook.php** - Receives and processes attendance events from devices
- **auto_checkout.php** - Automatically creates checkout records for employees who forget to checkout

---

## ✨ Features

### Webhook (webhook.php)
- ✅ Configurable check-in time window (default: 9:00 AM - 10:30 AM)
- ✅ Configurable check-out time window (default: 3:00 PM - 7:00 PM)
- ✅ One check-in and one check-out per employee per day
- ✅ Device validation via serial number or MAC address
- ✅ Automatic event type determination based on time
- ✅ Rejects expired events (only processes today's events)

### Auto Checkout (auto_checkout.php)
- ✅ Configurable execution time window
- ✅ Safe to run frequently via cron
- ✅ Sets checkout time to configurable time (default: 1:00 PM)
- ✅ Only processes employees with missing checkouts

---

## 🗄️ Database Requirements

### Table: `attendance`

```sql
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_staff_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `event_type` enum('check_in','check_out') NOT NULL,
  `event_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_staff_date` (`staff_staff_id`, `date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: `attendance_devices`

```sql
CREATE TABLE `attendance_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_serial` (`serial_number`),
  KEY `idx_mac` (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ⚙️ Configuration

### webhook.php

Edit the configuration section at the top of the file:

```php
// Timezone
define('TIMEZONE', 'Asia/Kathmandu');

// Check-in window (24-hour format)
define('CHECKIN_START', '09:00');  // 9:00 AM
define('CHECKIN_END', '10:30');    // 10:30 AM

// Check-out window (24-hour format)
define('CHECKOUT_START', '15:00'); // 3:00 PM
define('CHECKOUT_END', '19:00');   // 7:00 PM
```

### auto_checkout.php

```php
// Timezone
define('TIMEZONE', 'Asia/Kathmandu');

// Script execution window (only runs within this time)
define('RUN_WINDOW_START', '19:00'); // 7:00 PM
define('RUN_WINDOW_END', '19:30');   // 7:30 PM

// Time to record for auto-checkouts
define('AUTO_CHECKOUT_TIME', '13:00:00'); // 1:00 PM
```

---

## 🔧 Installation

### 1. Upload Files

Upload the `hikvision_attendance` folder to your web server.

### 2. Database Setup

Run the SQL commands above to create the required tables.

### 3. Register Your Device

Insert your Hikvision device into the `attendance_devices` table:

```sql
INSERT INTO attendance_devices (device_name, serial_number, mac_address, ip_address, is_active) 
VALUES ('Main Gate', NULL, 'a4:d5:c2:27:93:3e', '192.168.1.73', 1);
```

> **Note:** You can use either serial number OR MAC address for device identification. MAC address is recommended as it's more consistently available.

### 4. Configure Hikvision Device

In your Hikvision device settings:

1. Go to **Network** → **Advanced Settings** → **HTTP Listening**
2. Enable HTTP Listening
3. Set the URL to: `https://yourdomain.com/hikvision_attendance/webhook.php`
4. Set the listening host to your server IP

### 5. Setup Cron Job (for auto-checkout)

Add to your crontab:

```bash
# Option 1: Run once at 7:00 PM
0 19 * * * php /path/to/hikvision_attendance/auto_checkout.php

# Option 2: Run every minute (script self-manages timing)
* * * * * php /path/to/hikvision_attendance/auto_checkout.php
```

---

## 📡 API Response

### Success Response
```json
{
  "status": 200,
  "message": "Success. Check in"
}
```

### Error Responses
```json
{
  "status": 202,
  "message": "Already completed attendance today"
}
```

| Status | Message | Description |
|--------|---------|-------------|
| 200 | Success. Check in | Check-in recorded |
| 200 | Success. Check out | Check-out recorded |
| 202 | No data | Empty POST request |
| 202 | Invalid JSON | Malformed JSON data |
| 202 | Invalid event | Not an access event (majorEventType ≠ 5) |
| 202 | No employee ID | Missing employeeNoString |
| 202 | Event expired | Event date is not today |
| 202 | Device not registered | Device not in database |
| 202 | Already completed attendance today | Both check-in and check-out exist |
| 202 | Already checked in. Checkout: HH:MM-HH:MM | Waiting for checkout window |
| 202 | Outside checkin hours. Checkin: HH:MM-HH:MM | Outside check-in window |

---

## 📦 Expected Device Payload

The Hikvision device sends data in this format:

```json
{
  "ipAddress": "192.168.1.73",
  "portNo": 443,
  "protocol": "HTTPS",
  "macAddress": "a4:d5:c2:27:93:3e",
  "dateTime": "2026-01-04T09:15:32+05:45",
  "eventType": "AccessControllerEvent",
  "AccessControllerEvent": {
    "deviceName": "Access Controller",
    "majorEventType": 5,
    "subEventType": 1,
    "employeeNoString": "KM007",
    "cardNo": "0006987309",
    "name": "Ashok",
    "serialNo": 255
  }
}
```

---

## 🔍 Troubleshooting

### Device not sending data
1. Check device HTTP listening is enabled
2. Verify URL is correct and accessible
3. Check firewall allows incoming connections

### "Device not registered" error
1. Verify device MAC address in database matches device
2. Check `is_active = 1` in database

### Events not recording
1. Check time configuration matches your requirements
2. Verify employee ID exists in device
3. Check database connection credentials

### Auto-checkout not working
1. Verify cron is running: `crontab -l`
2. Check script has execute permissions
3. Verify database path is correct in script

---

## 📁 File Structure

```
hikvision_attendance/
├── webhook.php        # Main webhook receiver
├── auto_checkout.php  # Cron job for missed checkouts
└── README.md          # This documentation
```

---

## 🔒 Security Recommendations

1. **Use HTTPS** - Always use SSL/TLS for webhook URL
2. **IP Whitelist** - Restrict webhook access to device IP only
3. **Database User** - Use a dedicated database user with minimal privileges
4. **Error Logging** - Enable error logging in production (not to browser)

---

## 📝 License

MIT License - Feel free to use and modify.

---

## 👤 Author

**Contributed by Upendra237**

For issues and feature requests, please contact the author.

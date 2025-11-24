<?php
/**
 * Carotu REST API Configuration
 *
 * This is a SEPARATE configuration file from the main Carotu configuration.php
 *
 * WHY SEPARATE CONFIG?
 * - The main Carotu uses configuration.php for web UI authentication
 * - The API has different requirements: API keys, CORS, error logging
 * - Keeps API authentication independent from web UI authentication
 * - Allows API to be deployed separately or alongside the main app
 * - Security: API keys are not stored in the same config as web UI credentials
 */

// Database configuration
// Adjust the path based on your Carotu installation
define('DB_PATH', __DIR__ . '/../storage/101f3db57a6da54b248f09f2986ba794.sqlite');

// API Configuration
define('API_VERSION', '1.0');
define('API_KEY_HEADER', 'X-API-Key');

// Valid API keys
// Generate secure keys with: openssl rand -hex 32
$VALID_API_KEYS = [
    'your-secure-api-key-here',  // Replace with your actual API key
    // Add more API keys as needed for different clients
];

// CORS Configuration
// For production, change '*' to your specific domain
define('CORS_ALLOWED_ORIGINS', '*');
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-API-Key');

// Timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');

<?php
/**
 * Carotu REST API
 *
 * Standalone REST API for Carotu server inventory management
 *
 * Endpoints:
 * - GET    /api/machines       - List all machines
 * - GET    /api/machines/:id   - Get machine by ID
 * - POST   /api/machines       - Create new machine
 * - PUT    /api/machines/:id   - Update machine
 * - DELETE /api/machines/:id   - Delete machine
 * - GET    /api/providers      - List all providers
 * - GET    /api/payment-cycles - List payment cycles
 */

require_once 'config.php';

// CORS headers
header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendError(500, 'Database connection failed: ' . $e->getMessage());
}

// Authentication
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!in_array($apiKey, $GLOBALS['VALID_API_KEYS'])) {
    sendError(401, 'Invalid or missing API key');
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$segments = explode('/', $path);

// Remove 'api' prefix if present
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
}

$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// Get request body for POST/PUT
$input = json_decode(file_get_contents('php://input'), true);

// Route requests
switch ($resource) {
    case 'machines':
        handleMachines($method, $id, $input, $db);
        break;

    case 'providers':
        handleProviders($method, $id, $input, $db);
        break;

    case 'payment-cycles':
        handlePaymentCycles($method, $db);
        break;

    case 'countries':
        handleCountries($method, $db);
        break;

    case 'stats':
        handleStats($db);
        break;

    default:
        sendError(404, 'Resource not found');
}

/**
 * Handle /machines endpoints
 */
function handleMachines($method, $id, $input, $db) {
    switch ($method) {
        case 'GET':
            if ($id) {
                getMachine($id, $db);
            } else {
                listMachines($db);
            }
            break;

        case 'POST':
            createMachine($input, $db);
            break;

        case 'PUT':
            if (!$id) {
                sendError(400, 'Machine ID required');
            }
            updateMachine($id, $input, $db);
            break;

        case 'DELETE':
            if (!$id) {
                sendError(400, 'Machine ID required');
            }
            deleteMachine($id, $db);
            break;

        default:
            sendError(405, 'Method not allowed');
    }
}

/**
 * List all machines
 */
function listMachines($db) {
    $hidden = $_GET['include_hidden'] ?? 0;

    $sql = "SELECT m.*, p.name as provider_name, pc.name as payment_cycle_name, c.country_name
            FROM machine m
            LEFT JOIN provider p ON m.provider_id = p.provider_id
            LEFT JOIN payment_cycle pc ON m.payment_cycle_id = pc.payment_cycle_id
            LEFT JOIN country c ON m.country_code = c.country_code";

    if (!$hidden) {
        $sql .= " WHERE m.is_hidden = 0";
    }

    $sql .= " ORDER BY m.label ASC";

    $stmt = $db->query($sql);
    $machines = $stmt->fetchAll();

    sendSuccess(['machines' => $machines, 'count' => count($machines)]);
}

/**
 * Get single machine
 */
function getMachine($id, $db) {
    $stmt = $db->prepare("
        SELECT m.*, p.name as provider_name, p.website as provider_website,
               pc.name as payment_cycle_name, c.country_name
        FROM machine m
        LEFT JOIN provider p ON m.provider_id = p.provider_id
        LEFT JOIN payment_cycle pc ON m.payment_cycle_id = pc.payment_cycle_id
        LEFT JOIN country c ON m.country_code = c.country_code
        WHERE m.machine_id = ?
    ");

    $stmt->execute([$id]);
    $machine = $stmt->fetch();

    if (!$machine) {
        sendError(404, 'Machine not found');
    }

    sendSuccess(['machine' => $machine]);
}

/**
 * Create new machine
 */
function createMachine($data, $db) {
    // Validate required fields
    $required = ['label', 'ip_address', 'provider_id'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError(400, "Missing required field: $field");
        }
    }

    // Validate and sanitize all data
    $data = validateMachineData($data, $db, false);

    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO machine (
        is_hidden, is_nat, label, virtualization, cpu_speed, cpu_core,
        memory, swap, disk_type, disk_space, bandwidth, ip_address,
        country_code, city_name, price, currency_code, payment_cycle_id,
        due_date, notes, date_created, date_modified, provider_id
    ) VALUES (
        :is_hidden, :is_nat, :label, :virtualization, :cpu_speed, :cpu_core,
        :memory, :swap, :disk_type, :disk_space, :bandwidth, :ip_address,
        :country_code, :city_name, :price, :currency_code, :payment_cycle_id,
        :due_date, :notes, :date_created, :date_modified, :provider_id
    )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':is_hidden' => $data['is_hidden'] ?? 0,
        ':is_nat' => $data['is_nat'] ?? 0,
        ':label' => $data['label'],
        ':virtualization' => $data['virtualization'] ?? '',
        ':cpu_speed' => $data['cpu_speed'] ?? 0,
        ':cpu_core' => $data['cpu_core'] ?? 0,
        ':memory' => $data['memory'] ?? 0,
        ':swap' => $data['swap'] ?? 0,
        ':disk_type' => $data['disk_type'] ?? '',
        ':disk_space' => $data['disk_space'] ?? 0,
        ':bandwidth' => $data['bandwidth'] ?? 0,
        ':ip_address' => $data['ip_address'],
        ':country_code' => $data['country_code'] ?? '',
        ':city_name' => $data['city_name'] ?? '',
        ':price' => $data['price'] ?? 0,
        ':currency_code' => $data['currency_code'] ?? 'USD',
        ':payment_cycle_id' => $data['payment_cycle_id'] ?? 1,
        ':due_date' => $data['due_date'] ?? '',
        ':notes' => $data['notes'] ?? '',
        ':date_created' => $now,
        ':date_modified' => $now,
        ':provider_id' => $data['provider_id']
    ]);

    $machineId = $db->lastInsertId();

    sendSuccess([
        'message' => 'Machine created successfully',
        'machine_id' => $machineId
    ], 201);
}

/**
 * Update machine
 */
function updateMachine($id, $data, $db) {
    // Check if machine exists
    $stmt = $db->prepare("SELECT machine_id FROM machine WHERE machine_id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendError(404, 'Machine not found');
    }

    // Validate and sanitize all data
    $data = validateMachineData($data, $db, true);

    $now = date('Y-m-d H:i:s');

    // Build UPDATE query dynamically based on provided fields
    $fields = [];
    $values = [];

    $allowedFields = [
        'is_hidden', 'is_nat', 'label', 'virtualization', 'cpu_speed', 'cpu_core',
        'memory', 'swap', 'disk_type', 'disk_space', 'bandwidth', 'ip_address',
        'country_code', 'city_name', 'price', 'currency_code', 'payment_cycle_id',
        'due_date', 'notes', 'provider_id'
    ];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }

    if (empty($fields)) {
        sendError(400, 'No fields to update');
    }

    $fields[] = "date_modified = ?";
    $values[] = $now;
    $values[] = $id;

    $sql = "UPDATE machine SET " . implode(', ', $fields) . " WHERE machine_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    sendSuccess(['message' => 'Machine updated successfully']);
}

/**
 * Delete machine
 */
function deleteMachine($id, $db) {
    $stmt = $db->prepare("DELETE FROM machine WHERE machine_id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError(404, 'Machine not found');
    }

    sendSuccess(['message' => 'Machine deleted successfully']);
}

/**
 * Handle /providers endpoints
 */
function handleProviders($method, $id, $input, $db) {
    if ($method !== 'GET') {
        sendError(405, 'Method not allowed');
    }

    if ($id) {
        $stmt = $db->prepare("SELECT * FROM provider WHERE provider_id = ?");
        $stmt->execute([$id]);
        $provider = $stmt->fetch();

        if (!$provider) {
            sendError(404, 'Provider not found');
        }

        sendSuccess(['provider' => $provider]);
    } else {
        $stmt = $db->query("SELECT * FROM provider ORDER BY name ASC");
        $providers = $stmt->fetchAll();

        sendSuccess(['providers' => $providers, 'count' => count($providers)]);
    }
}

/**
 * Handle /payment-cycles endpoints
 */
function handlePaymentCycles($method, $db) {
    if ($method !== 'GET') {
        sendError(405, 'Method not allowed');
    }

    $stmt = $db->query("SELECT * FROM payment_cycle ORDER BY month ASC");
    $cycles = $stmt->fetchAll();

    sendSuccess(['payment_cycles' => $cycles, 'count' => count($cycles)]);
}

/**
 * Handle /countries endpoints
 */
function handleCountries($method, $db) {
    if ($method !== 'GET') {
        sendError(405, 'Method not allowed');
    }

    $stmt = $db->query("SELECT * FROM country ORDER BY country_name ASC");
    $countries = $stmt->fetchAll();

    sendSuccess(['countries' => $countries, 'count' => count($countries)]);
}

/**
 * Handle /stats endpoint
 */
function handleStats($db) {
    // Get target currency from request (default: USD)
    $requestedCurrency = $_GET['currency'] ?? 'USD';

    // Get exchange rate for target currency (rate stored as 4-digit integer)
    $stmt = $db->prepare("SELECT rate FROM currency_rate WHERE currency_code = ?");
    $stmt->execute([$requestedCurrency]);
    $currencyRate = $stmt->fetch();

    // If currency not found, fall back to USD
    if ($currencyRate) {
        $targetRate = $currencyRate['rate'];
        $targetCurrency = $requestedCurrency;
    } else {
        $targetRate = 10000;
        $targetCurrency = 'USD';
    }

    $stats = [];
    $stats['currency'] = $targetCurrency;

    // Total machines
    $stmt = $db->query("SELECT COUNT(*) as count FROM machine WHERE is_hidden = 0");
    $stats['total_machines'] = $stmt->fetch()['count'];
    
    // Total cost per month with currency conversion
    $stmt = $db->prepare("
        SELECT 
            SUM((CAST(m.price AS REAL) / pc.month / cr.rate) * ?) as total
        FROM machine m
        JOIN payment_cycle pc ON m.payment_cycle_id = pc.payment_cycle_id
        JOIN currency_rate cr ON m.currency_code = cr.currency_code
        WHERE m.is_hidden = 0
    ");
    $stmt->execute([$targetRate]);

    $stats['monthly_cost'] = round($stmt->fetch()['total'] ?? 0);

    // Machines by provider
    $stmt = $db->query("
        SELECT p.name, COUNT(m.machine_id) as count
        FROM machine m
        JOIN provider p ON m.provider_id = p.provider_id
        WHERE m.is_hidden = 0
        GROUP BY p.provider_id
        ORDER BY count DESC
    ");
    $stats['by_provider'] = $stmt->fetchAll();

    // Machines by country
    $stmt = $db->query("
        SELECT c.country_name, COUNT(m.machine_id) as count
        FROM machine m
        JOIN country c ON m.country_code = c.country_code
        WHERE m.is_hidden = 0
        GROUP BY m.country_code
        ORDER BY count DESC
    ");
    $stats['by_country'] = $stmt->fetchAll();

    sendSuccess(['stats' => $stats]);
}

/**
 * Send success response
 */
function sendSuccess($data, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit();
}

/**
 * Send error response
 */
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ], JSON_PRETTY_PRINT);
    exit();
}

/**
 * Validate machine data
 *
 * @param array $data Machine data to validate
 * @param PDO $db Database connection
 * @param bool $isUpdate Whether this is an update operation
 * @return array Validated and sanitized data
 */
function validateMachineData($data, $db, $isUpdate = false) {
    $errors = [];

    // 1. Validate provider_id (if provided or required for create)
    if (isset($data['provider_id']) || !$isUpdate) {
        if (!isset($data['provider_id']) && !$isUpdate) {
            $errors[] = "Missing required field: provider_id";
        } elseif (isset($data['provider_id'])) {
            if (!is_numeric($data['provider_id']) || $data['provider_id'] <= 0) {
                $errors[] = "provider_id must be a positive integer";
            } else {
                // Check if provider exists
                $stmt = $db->prepare("SELECT provider_id FROM provider WHERE provider_id = ?");
                $stmt->execute([$data['provider_id']]);
                if (!$stmt->fetch()) {
                    $errors[] = "Invalid provider_id: provider does not exist";
                }
            }
        }
    }

    // 2. Validate payment_cycle_id (if provided)
    if (isset($data['payment_cycle_id'])) {
        if (!is_numeric($data['payment_cycle_id']) || $data['payment_cycle_id'] <= 0) {
            $errors[] = "payment_cycle_id must be a positive integer";
        } else {
            $stmt = $db->prepare("SELECT payment_cycle_id FROM payment_cycle WHERE payment_cycle_id = ?");
            $stmt->execute([$data['payment_cycle_id']]);
            if (!$stmt->fetch()) {
                $errors[] = "Invalid payment_cycle_id: payment cycle does not exist";
            }
        }
    }

    // 3. Validate country_code (if provided and not empty)
    if (isset($data['country_code']) && $data['country_code'] !== '') {
        if (!preg_match('/^[A-Z]{2}$/i', $data['country_code'])) {
            $errors[] = "country_code must be a 2-letter ISO code";
        } else {
            $stmt = $db->prepare("SELECT country_code FROM country WHERE country_code = ?");
            $stmt->execute([strtoupper($data['country_code'])]);
            if (!$stmt->fetch()) {
                $errors[] = "Invalid country_code: country does not exist";
            }
            // Normalize to uppercase
            $data['country_code'] = strtoupper($data['country_code']);
        }
    }

    // 4. Validate numeric fields (must be non-negative integers)
    $numericFields = [
        'cpu_speed' => 'CPU speed',
        'cpu_core' => 'CPU cores',
        'memory' => 'Memory',
        'swap' => 'Swap',
        'disk_space' => 'Disk space',
        'bandwidth' => 'Bandwidth'
    ];

    foreach ($numericFields as $field => $label) {
        if (isset($data[$field])) {
            if (!is_numeric($data[$field])) {
                $errors[] = "$label must be a number";
            } elseif ($data[$field] < 0) {
                $errors[] = "$label cannot be negative";
            } else {
                // Cast to integer
                $data[$field] = (int)$data[$field];
            }
        }
    }

    // 5. Validate price (must be non-negative numeric)
    if (isset($data['price'])) {
        if (!is_numeric($data['price'])) {
            $errors[] = "Price must be a number";
        } elseif ($data['price'] < 0) {
            $errors[] = "Price cannot be negative";
        } else {
            // Cast to float for decimal prices
            $data['price'] = (float)$data['price'];
        }
    }

    // 6. Validate boolean fields
    $booleanFields = ['is_hidden', 'is_nat'];
    foreach ($booleanFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = (int)(bool)$data[$field];
        }
    }

    // 7. Validate due_date format (if provided and not empty)
    if (isset($data['due_date']) && $data['due_date'] !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
            $errors[] = "due_date must be in YYYY-MM-DD format";
        } else {
            // Validate it's a real date
            $parts = explode('-', $data['due_date']);
            if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                $errors[] = "due_date is not a valid date";
            }
        }
    }

    // 8. Validate currency_code (if provided)
    if (isset($data['currency_code']) && $data['currency_code'] !== '') {
        if (!preg_match('/^[A-Z]{3}$/i', $data['currency_code'])) {
            $errors[] = "currency_code must be a 3-letter currency code (e.g., USD, EUR)";
        } else {
            $data['currency_code'] = strtoupper($data['currency_code']);
        }
    }

    // If there are validation errors, send them
    if (!empty($errors)) {
        sendError(400, implode('; ', $errors));
    }

    return $data;
}

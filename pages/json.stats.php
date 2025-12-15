<?php
/**
 * @var \Cookie\Cookie   $cookie
 * @var \Http\Request    $request
 * @var \Security\Form   $form
 * @var \Session\Session $session
 * @var \SQLite\Database $db
 */
defined("INDEX") or exit("Access is denied.");

header("Content-Type: application/json");

if (!$session->get("user")) {
    exit(
        json_encode([
            "status" => 403,
            "message" => "Access denied.",
        ])
    );
}

// Get target currency from request (default: USD)
$requestedCurrency = $request->get("currency", "USD");

// Get exchange rate for target currency (rate stored as 4-digit integer)
$stmt = $db->prepare("SELECT rate FROM currency_rate WHERE currency_code = ?");
$stmt->execute([$requestedCurrency]);
$currencyRate = $stmt->fetch();

// If currency not found, fall back to USD
if ($currencyRate) {
    $targetRate = $currencyRate["rate"];
    $targetCurrency = $requestedCurrency;
} else {
    $targetRate = 10000;
    $targetCurrency = "USD";
}

$stats = [];
$stats["currency"] = $targetCurrency;

// Total machines
$stmt = $db->query("SELECT COUNT(*) as count FROM machine WHERE is_hidden = 0");
$stats["total_machines"] = $stmt->fetch()["count"];

// Total cost per month with currency conversion (calculated in DB)
$stmt = $db->prepare("
		SELECT 
			SUM((CAST(m.price AS REAL) / pc.month / cr.rate) * ?) as total
		FROM machine m
		JOIN payment_cycle pc ON m.payment_cycle_id = pc.payment_cycle_id
		JOIN currency_rate cr ON m.currency_code = cr.currency_code
		WHERE m.is_hidden = 0
	");
$stmt->execute([$targetRate]);

$totalRaw = $stmt->fetch()["total"] ?? 0;
// Convert from 4-digit format to decimal with 2 places
$stats["monthly_cost"] = number_format($totalRaw / 100, 2, ".", "");
$stats["yearly_cost"] = number_format(($totalRaw * 12) / 100, 2, ".", "");

// Machines by provider
$stmt = $db->query("
		SELECT p.name, COUNT(m.machine_id) as count
		FROM machine m
		JOIN provider p ON m.provider_id = p.provider_id
		WHERE m.is_hidden = 0
		GROUP BY p.provider_id
		ORDER BY count DESC
	");
$stats["by_provider"] = $stmt->fetchAll();

// Cost by provider
$stmt = $db->prepare("
		SELECT 
			p.name,
			SUM((CAST(m.price AS REAL) / pc.month / cr.rate) * ?) as cost
		FROM machine m
		JOIN payment_cycle pc ON m.payment_cycle_id = pc.payment_cycle_id
		JOIN currency_rate cr ON m.currency_code = cr.currency_code
		JOIN provider p ON m.provider_id = p.provider_id
		WHERE m.is_hidden = 0
		GROUP BY p.provider_id
		ORDER BY cost DESC
	");
$stmt->execute([$targetRate]);
$costByProvider = $stmt->fetchAll();

// Merge cost data into by_provider
foreach ($stats["by_provider"] as &$provider) {
    $provider["cost"] = "0.00";
    foreach ($costByProvider as $cost) {
        if ($cost["name"] === $provider["name"]) {
            $provider["cost"] = number_format($cost["cost"] / 100, 2, ".", "");
            break;
        }
    }
}
unset($provider);

// Machines by country
$stmt = $db->query("
		SELECT c.country_name, c.country_code, COUNT(m.machine_id) as count
		FROM machine m
		JOIN country c ON m.country_code = c.country_code
		WHERE m.is_hidden = 0
		GROUP BY m.country_code
		ORDER BY count DESC
	");
$stats["by_country"] = $stmt->fetchAll();

exit(
    json_encode([
        "status" => 200,
        "data" => $stats,
    ])
);
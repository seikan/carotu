<?php
/**
 * @var \Cookie\Cookie   $cookie
 * @var \Http\Request    $request
 * @var \Security\Form   $form
 * @var \Session\Session $session
 * @var \SQLite\Database $db
 */
defined('INDEX') or exit('Access is denied.');

header('Content-Type: application/json');

if (!$session->get('user')) {
	exit(json_encode([
		'status'  => 403,
		'message' => 'Access denied.',
	]));
}

if ($request->isPost()) {
	$results = [
		'errors' => [],
		'csrf'   => [
			'name'  => $form->getInputName(),
			'value' => $form->getInputValue(),
		],
	];

	// CSRF validation
	$csrf = $form->validate();

	// Get the latest CSRF token
	$results['csrf'] = [
		'name'  => $form->getInputName(),
		'value' => $form->getInputValue(),
	];

	// CSRF token is invalid
	if (!$csrf) {
		$results['errors']['name'] = 'Security validation failed.';
		exit(json_encode($results));
	}

	switch ($request->post('action')) {
		case 'delete':
			$currencyCode = strtoupper($request->post('code'));

			$db->delete('currency_rate', '`currency_code` = :currencyCode', [
				':currencyCode' => $currencyCode,
			]);

			break;

		default:
			$newCurrencyCode = strtoupper($request->post('new'));
			$currencyCode = strtoupper($request->post('code'));
			$rate = $request->post('rate');

			if ($newCurrencyCode == 'USD' || $currencyCode == 'USD') {
				$results['errors']['code'] = 'Base currency is read-only.';
			}

			if (!empty($currencyCode) && !preg_match('/^[A-Z]{3}$/', $currencyCode)) {
				$results['errors']['code'] = 'Currency code is invalid.';
			}

			if (!preg_match('/^[A-Z]{3}$/', $newCurrencyCode)) {
				$results['errors']['code'] = 'Currency code is invalid.';
			}

			if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $rate)) {
				$results['errors']['rate'] = 'Rate is invalid.';
			}

			if (!empty($results['errors'])) {
				exit(json_encode($results));
			}

			if ($newCurrencyCode) {
				$db->delete('currency_rate', '`currency_code` = :currencyCode', [
					':currencyCode' => $currencyCode,
				]);

				$db->insert('currency_rate', [
					'currency_code' => $newCurrencyCode,
					'rate'          => $rate * 10000,
				]);
			} else {
				$db->insert('currency_rate', [
					'currency_code' => $newCurrencyCode,
					'rate'          => $rate * 10000,
				]);
			}
	}

	exit(json_encode($results));
} else {
	$results = $db->select('currency_rate', '1 = 1 ORDER BY `currency_code`');

	if (!empty($results)) {
		foreach ($results as $key => $result) {
			$results[$key]['rate'] = number_format($results[$key]['rate'] / 10000, 4, '.');
		}
	}

	echo json_encode($results);
}

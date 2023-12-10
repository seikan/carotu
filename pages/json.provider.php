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
			$providerId = $request->post('id');

			$db->delete('provider', '`provider_id` = :providerId', [
				':providerId' => $providerId,
			]);

			// Orphan the machines
			$db->update('machine', [
				'provider_id' => 0,
			], '`provider_id` = :providerId', [
				':providerId' => $providerId,
			]);

			break;

		default:
			$providerId = $request->post('id');
			$name = sanitize($request->post('name'));
			$website = sanitize(rtrim($request->post('website'), '/'));
			$controlPanel = sanitize($request->post('cp'));
			$controlPanelUrl = sanitize(rtrim($request->post('cpUrl'), '/'));

			if (empty($name)) {
				$results['errors']['name'] = 'This field is required.';
			}

			if (!filter_var($website, FILTER_VALIDATE_URL)) {
				$results['errors']['website'] = 'URL is invalid.';
			}

			if (empty($controlPanel)) {
				$results['errors']['cp'] = 'This field is required.';
			}

			if (!filter_var($controlPanelUrl, FILTER_VALIDATE_URL)) {
				$results['errors']['cpUrl'] = 'URL is invalid.';
			}

			if (!empty($results['errors'])) {
				exit(json_encode($results));
			}

			if ($providerId) {
				$db->update('provider', [
					'name'               => $name,
					'website'            => $website,
					'control_panel_name' => $controlPanel,
					'control_panel_url'  => $controlPanelUrl,
					'date_modified'      => gmdate('Y-m-d H:i:s'),
				], 'provider_id = :providerId', [
					':providerId' => $providerId,
				]);
			} else {
				$db->insert('provider', [
					'name'               => $name,
					'website'            => $website,
					'control_panel_name' => $controlPanel,
					'control_panel_url'  => $controlPanelUrl,
					'date_created'       => gmdate('Y-m-d H:i:s'),
				]);
			}
	}

	exit(json_encode($results));
} else {
	$results = [];

	switch ($request->get('action')) {
		case 'name':
			$rows = $db->select('provider', '1=1 ORDER BY `name`');

			if (!empty($rows)) {
				foreach ($rows as $row) {
					$results[$row['provider_id']] = $row['name'];
				}
			}

			exit(json_encode($results));

		case 'control-panel':
			$rows = $db->execute('SELECT DISTINCT `control_panel_name` FROM `provider` ORDER BY `control_panel_name`');

			if (!empty($rows)) {
				foreach ($rows as $row) {
					$results[] = $row['control_panel_name'];
				}
			}

			exit(json_encode($results));

		default:
			exit(json_encode($db->execute('SELECT *, (SELECT COUNT(*) FROM `machine` WHERE `provider_id` = p.`provider_id`) AS `total_machine` FROM `provider` p ORDER BY p.`name`')));
	}
}

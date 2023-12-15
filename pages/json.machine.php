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
		case 'renew':
			$machineId = $request->post('id', '', true);

			if (!is_array($machineId)) {
				$machineId = [$machineId];
			}

			$results['labels'] = [];
			$results['due_dates'] = [];

			foreach ($machineId as $id) {
				$row = $db->execute('SELECT * FROM `machine` m JOIN `payment_cycle` p ON p.`payment_cycle_id` = m.`payment_cycle_id` WHERE m.`machine_id` = :machineId', [
					':machineId' => $id,
				], true);

				if ($row) {
					$nextDueDate = date('Y-m-d', strtotime('+' . $row['month'] . ' month', strtotime($row['due_date'])));

					$db->update('machine', [
						'due_date'      => $nextDueDate,
						'date_modified' => date('Y-m-d H:i:s'),
					], '`machine_id` = :machineId', [
						':machineId' => $id,
					]);

					$results['labels'][] = $row['label'];
					$results['due_dates'][] = $nextDueDate;
				}
			}

			exit(json_encode($results));

		case 'delete':
			$machineId = $request->post('id', '', true);

			if (!is_array($machineId)) {
				$machineId = [$machineId];
			}

			$results['labels'] = [];

			foreach ($machineId as $id) {
				$row = $db->select('machine', '`machine_id` = :machineId', [
					':machineId' => $id,
				], true);

				if ($row) {
					$db->delete('machine', '`machine_id` = :machineId', [
						':machineId' => $id,
					]);

					$results['labels'][] = $row['label'];
				}
			}

			exit(json_encode($results));

		case 'hide':
		case 'unhide':
			$machineId = $request->post('id', '', true);

			if (!is_array($machineId)) {
				$machineId = [$machineId];
			}

			$results['labels'] = [];

			foreach ($machineId as $id) {
				$row = $db->select('machine', '`machine_id` = :machineId', [
					':machineId' => $id,
				], true);

				if ($row) {
					$db->update('machine', [
						'is_hidden' => ($request->post('action') == 'hide') ? 1 : 0,
					], '`machine_id` = :machineId', [
						':machineId' => $id,
					]);

					$results['labels'][] = $row['label'];
				}
			}

			exit(json_encode($results));

		default:
			$machineId = $request->post('id');
			$isHidden = ($request->post('is-hidden')) ? 1 : 0;
			$label = sanitize($request->post('label'));
			$providerId = $request->post('provider');
			$virtualization = $request->post('virtualization');
			$ipAddresses = array_unique(explode(',', $request->post('ip')));
			$isNat = ($request->post('is-nat')) ? 1 : 0;
			$bandwidth = $request->post('bandwidth');
			$bandwidthUnit = $request->post('bandwidth-unit');
			$country = $request->post('country');
			$city = sanitize($request->post('city'));
			$cpuSpeed = $request->post('speed');
			$cpuCore = $request->post('core');
			$memory = $request->post('memory');
			$memoryUnit = $request->post('memory-unit');
			$swap = $request->post('swap');
			$swapUnit = $request->post('swap-unit');
			$diskType = $request->post('disk-type');
			$diskSpace = $request->post('disk-space');
			$diskSpaceUnit = $request->post('disk-space-unit');
			$currency = $request->post('currency');
			$price = $request->post('price');
			$cycle = $request->post('cycle');
			$dueDate = $request->post('due-date');
			$notes = $request->post('notes');

			if (empty($label)) {
				$results['errors']['label'] = 'This field is required.';
			}

			if (empty($virtualization)) {
				$results['errors']['virtualization'] = 'This field is required.';
			}

			if (empty($ipAddresses)) {
				$results['errors']['ip-address'] = 'This field is required.';
			} else {
				foreach ($ipAddresses as $ipAddress) {
					if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
						$results['errors']['ip-address'] = '"' . $ipAddress . '" is not a valid IP address.';
						break;
					}
				}
			}

			if (!preg_match('/^0|[1-9][0-9]*(\.[0-9]+)?$/', $bandwidth)) {
				$results['errors']['bandwidth'] = 'Invalid value.';
			}

			if (!in_array($bandwidthUnit, ['GB', 'TB'])) {
				$results['errors']['bandwidth-unit'] = 'Invalid unit.';
			}

			if (!preg_match('/^[A-Z]{2}$/', $country)) {
				$results['errors']['country'] = 'Invalid country selected.';
			} else {
				// Verify country code with database
				$row = $db->select('country', '`country_code` = :countryCode', [
					':countryCode' => $country,
				]);

				if (empty($row)) {
					$results['errors']['country'] = 'Invalid country selected.';
				}
			}

			if (empty($city)) {
				$results['errors']['city'] = 'This field is required.';
			}

			if (!preg_match('/^[1-9][0-9]*(\.[0-9]+)?$/', $cpuSpeed)) {
				$results['errors']['speed'] = 'Invalid value.';
			}

			if (!preg_match('/^[1-9][0-9]*$/', $cpuCore)) {
				$results['errors']['core'] = 'Invalid value.';
			}

			if (!preg_match('/^[1-9][0-9]*(\.[0-9]+)?$/', $memory)) {
				$results['errors']['memory'] = 'Invalid value.';
			}

			if (!in_array($memoryUnit, ['MB', 'GB'])) {
				$results['errors']['memory-unit'] = 'Invalid unit.';
			}

			if (!preg_match('/^0|[1-9][0-9]*(\.[0-9]+)?$/', $swap)) {
				$results['errors']['swap'] = 'Invalid value.';
			}

			if (!in_array($swapUnit, ['MB', 'GB'])) {
				$results['errors']['swap-unit'] = 'Invalid unit.';
			}

			if (empty($diskType)) {
				$results['errors']['disk-type'] = 'This field is required.';
			}

			if (!preg_match('/^[1-9][0-9]*$/', $diskSpace)) {
				$results['errors']['disk-space'] = 'Invalid value.';
			}

			if (!in_array($diskSpaceUnit, ['GB', 'TB'])) {
				$results['errors']['disk-space-unit'] = 'Invalid unit.';
			}

			if (!preg_match('/^[A-Z]{3}$/', $currency)) {
				$results['errors']['currency'] = 'This field is required.';
			} else {
				// Verify currency code with database
				$row = $db->select('currency_rate', '`currency_code` = :currencyCode', [
					':currencyCode' => $currency,
				]);

				if (empty($row)) {
					$results['errors']['currency'] = 'Invalid currency selected.';
				}
			}

			if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $price)) {
				$results['errors']['price'] = 'Invalid value.';
			}

			if (empty($cycle)) {
				$results['errors']['cycle'] = 'This field is required.';
			}

			if (!preg_match('/^2[0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $dueDate)) {
				$results['errors']['due-date'] = 'Invalid date.';
			}

			if (!empty($results['errors'])) {
				exit(json_encode($results));
			}

			// Store memory, swap, disk space, bandwidth as MB unit to reduce decimal length
			$memory = ($memoryUnit == 'MB') ? $memory : ($memory * 1024);
			$swap = ($swapUnit == 'MB') ? $swap : ($swap * 1024);
			$diskSpace = ($diskSpaceUnit == 'GB') ? ($diskSpace * 1024) : ($diskSpace * 1024 * 1024);
			$bandwidth = ($bandwidthUnit == 'GB') ? ($bandwidth * 1024) : ($bandwidth * 1024 * 1024);

			// Store price in hundred
			$price *= 100;

			if ($machineId) {
				// Update existing machine
				$db->update('machine', [
					'is_hidden'        => $isHidden,
					'is_nat'           => $isNat,
					'label'            => $label,
					'virtualization'   => $virtualization,
					'cpu_speed'        => $cpuSpeed,
					'cpu_core'         => $cpuCore,
					'memory'           => $memory,
					'swap'             => $swap,
					'disk_type'        => $diskType,
					'disk_space'       => $diskSpace,
					'bandwidth'        => $bandwidth,
					'ip_address'       => implode(',', $ipAddresses),
					'country_code'     => $country,
					'city_name'        => $city,
					'price'            => $price,
					'currency_code'    => $currency,
					'payment_cycle_id' => $cycle,
					'due_date'         => $dueDate,
					'notes'            => $notes,
					'date_modified'    => gmdate('Y-m-d H:i:s'),
					'provider_id'      => $providerId,
				], '`machine_id` = :machineId', [
					':machineId' => $machineId,
				]);
			} else {
				// Insert new machine
				$db->insert('machine', [
					'is_hidden'        => $isHidden,
					'is_nat'           => $isNat,
					'label'            => $label,
					'virtualization'   => $virtualization,
					'cpu_speed'        => $cpuSpeed,
					'cpu_core'         => $cpuCore,
					'memory'           => $memory,
					'swap'             => $swap,
					'disk_type'        => $diskType,
					'disk_space'       => $diskSpace,
					'bandwidth'        => $bandwidth,
					'ip_address'       => implode(',', $ipAddresses),
					'country_code'     => $country,
					'city_name'        => $city,
					'price'            => $price,
					'currency_code'    => $currency,
					'payment_cycle_id' => $cycle,
					'due_date'         => $dueDate,
					'notes'            => $notes,
					'date_created'     => gmdate('Y-m-d H:i:s'),
					'provider_id'      => $providerId,
				]);
			}
	}

	exit(json_encode($results));
} else {
	switch ($request->get('action')) {
		case 'city':
			$results = [];

			$rows = $db->execute('SELECT DISTINCT `city_name` FROM `machine` ORDER BY `city_name`');

			if (!empty($rows)) {
				foreach ($rows as $row) {
					$results[] = $row['city_name'];
				}
			}

			exit(json_encode($results));

		case 'single':
			$machineId = $request->get('id');

			$row = $db->select('machine', '`machine_id` = :machineId', [
				':machineId' => $machineId,
			], true);

			if (!empty($row)) {
				list($row['bandwidth'], $row['bandwidth_unit']) = explode(' ', formatBytes($row['bandwidth'] * 1024 * 1024));
				list($row['memory'], $row['memory_unit']) = explode(' ', formatBytes($row['memory'] * 1024 * 1024));
				list($row['swap'], $row['swap_unit']) = explode(' ', formatBytes($row['swap'] * 1024 * 1024));
				list($row['disk_space'], $row['disk_space_unit']) = explode(' ', formatBytes($row['disk_space'] * 1024 * 1024));
				// $row['price'] /= 100;

				$row['bandwidth_unit'] = (empty($row['bandwidth'])) ? 'GB' : $row['bandwidth_unit'];
				$row['swap_unit'] = (empty($row['swap'])) ? 'MB' : $row['swap_unit'];

				exit(json_encode($row));
			}

			exit('[]');

		default:
			$results = [
				'draw'            => (int) $request->get('draw'),
				'recordsTotal'    => 0,
				'recordsFiltered' => 0,
				'data'            => [],
			];

			$filters = [
				'Bandwidth'      => ['=', '<', '>'],
				'City'           => ['=', ':'],
				'Country'        => ['=', ':'],
				'CPU Core'       => ['=', '<', '>'],
				'CPU Speed'      => ['=', '<', '>'],
				'Disk Type'      => ['='],
				'Disk Space'     => ['=', '<', '>'],
				'IP Address'     => ['=', ':'],
				'Label'          => ['=', ':'],
				'NAT'            => ['='],
				'Provider'       => ['=', ':'],
				'RAM'            => ['=', '<', '>'],
				'Search'         => [':'],
				'Virtualization' => ['='],
				'Visibility'     => ['='],
			];

			$orderColumns = [
				'm.`label`',
				'm.`city_name`',
				'm.`memory`',
				'm.`disk_space`',
				'p.`name`',
				'(m.`price` / pc.`month`)',
				'm.`due_date`',
			];

			$length = (in_array($request->get('length'), [10, 25, 50, 100])) ? (int) $request->get('length') : 50;
			$order = $request->get('order', null, true);
			$orderColumn = $order[0]['column'] ?? 1;
			$orderBy = (isset($order[0]['dir']) && $order[0]['dir'] == 'asc') ? 'ASC' : 'DESC';
			$keyword = (isset($request->get('search', null, true)['value'])) ? $request->get('search', null, true)['value'] : '';
			$start = $request->get('start', 0);

			// Get search criteria
			$searches = explode(';', $keyword);

			$conditions = [
				'1 = 1',
			];
			$binds = [];

			$visibility = false;

			// Get search conditions
			$searches = explode(';', $keyword);

			foreach ($searches as $search) {
				// Separate column, operator and condition
				if (!preg_match('/^([a-zA-Z ]+)([:=<>])([a-zA-Z0-9:. ]+)$/', $search, $matches)) {
					continue;
				}

				// Make sure filter is exist
				if (!isset($filters[$matches[1]])) {
					continue;
				}

				// Make sure operator is valid
				if (!in_array($matches[2], $filters[$matches[1]])) {
					continue;
				}

				// Rewrite ":" operator
				if ($matches[2] == ':') {
					$matches[2] = 'LIKE';
					$matches[3] = '%' . $matches[3] . '%';
				}

				// Build condition
				switch ($matches[1]) {
					case 'Bandwidth':
						if (preg_match('/^[0-9]+$/', $matches[3])) {
							$conditions[] = 'm.`bandwidth` ' . $matches[2] . ' :bandwidth';
							$binds[':bandwidth'] = $matches[3] * 1024;
						}
						break;

					case 'City':
						$conditions[] = 'm.`city_name` ' . $matches[2] . ' :city';
						$binds[':city'] = $matches[3];
						break;

					case 'Country':
						if (preg_match('/^[A-Z]{2}$/', $matches[3])) {
							$conditions[] = 'm.`country_code` ' . $matches[2] . ' :country';
							$binds[':country'] = $matches[3];
						} else {
							$conditions[] = 'c.`country_name` ' . $matches[2] . ' :country';
							$binds[':country'] = $matches[3];
						}

						break;

					case 'CPU Core':
						if (preg_match('/[0-9]+/', $matches[3])) {
							$conditions[] = 'm.`cpu_core` ' . $matches[2] . ' :cpuCore';
							$binds[':cpuCore'] = $matches[3];
						}
						break;

					case 'CPU Speed':
						if (preg_match('/[0-9]+(\.[0-9]+)?/', $matches[3])) {
							$conditions[] = 'm.`cpu_speed` ' . $matches[2] . ' :cpuSpeed';
							$binds[':cpuSpeed'] = $matches[3];
						}
						break;

					case 'Disk Type':
						if (in_array($matches[3], ['HDD', 'NVMe', 'SSD'])) {
							$conditions[] = 'm.`disk_type` ' . $matches[2] . ' :diskType';
							$binds[':diskType'] = $matches[3];
						}
						break;

					case 'Disk Space':
						if (preg_match('/^[0-9]+$/', $matches[3])) {
							$conditions[] = 'm.`disk_space` ' . $matches[2] . ' :diskSpace';
							$binds[':diskSpace'] = $matches[3] * 1024;
						}
						break;

					case 'IP Address':
						$conditions[] = 'm.`ip_address` ' . $matches[2] . ' :ipAddress';
						$binds[':ipAddress'] = $matches[3];
						break;

					case 'Label':
						$conditions[] = 'm.`label` ' . $matches[2] . ' :label';
						$binds[':label'] = $matches[3];
						break;

					case 'NAT':
						$conditions[] = 'm.`nat` ' . $matches[2] . ' :nat';
						$binds[':nat'] = ($matches[3] == 'Yes') ? 1 : 0;
						break;

					case 'Provider':
						$conditions[] = 'p.`name` ' . $matches[2] . ' :provider';
						$binds[':provider'] = $matches[3];
						break;

					case 'RAM':
						if (preg_match('/[0-9]+(\.[0-9]+)?/', $matches[3])) {
							$conditions[] = 'm.`memory` ' . $matches[2] . ' :memory';
							$binds[':memory'] = $matches[3];
						}
						break;

					case 'Search':
						$conditions[] = '(m.`label` ' . $matches[2] . ' :keyword OR m.`ip_address` ' . $matches[2] . ' :keyword OR m.`city_name` ' . $matches[2] . ' :keyword OR c.`country_name` ' . $matches[2] . ' :keyword OR p.`name` ' . $matches[2] . ' :keyword OR m.`notes` ' . $matches[2] . ' :keyword)';
						$binds[':keyword'] = $matches[3];
						break;

					case 'Virtualization':
						if (in_array($matches[3], ['Dedicated', 'OpenStack', 'OpenVZ', 'HyperV', 'KVM', 'LXD', 'VMWare', 'XEN'])) {
							$conditions[] = 'm.`virtualization` ' . $matches[2] . ' :virtualization';
							$binds[':virtualization'] = $matches[3];
						}
						break;

					case 'Visibility':
						$visibility = true;

						if ($matches[3] != 'All') {
							$conditions[] = 'm.`is_hidden` ' . $matches[2] . ' :isHidden';
							$binds[':isHidden'] = ($matches[3] == 'Hidden') ? 1 : 0;
						}

						break;
				}
			}

			// If no visibility filter applied, hide hidden machines by default
			if (!$visibility) {
				$conditions[] = 'm.`is_hidden` = 0';
			}

			// Calculate total records
			$results['recordsTotal'] = $db->execute('SELECT COUNT(*) AS `total` FROM `machine`', [], true)['total'];

			// Get found records
			$results['recordsFiltered'] = $db->execute('SELECT COUNT(*) AS `total` FROM (SELECT COUNT(*) FROM `machine` m LEFT JOIN `provider` p ON p.`provider_id` = m.`provider_id` LEFT JOIN `country` c ON c.`country_code` = m.`country_code` WHERE ' . implode(' AND ', $conditions) . ' GROUP BY m.`machine_id`) t', $binds, true)['total'];

			// Get involved records
			$rows = $db->execute('SELECT *, (SELECT CASE WHEN COUNT(1) > 0 THEN 0 ELSE 1 END FROM `machine` WHERE `ip_address` LIKE "%" || m.`ip_address` || "%" AND `machine_id` != m.`machine_id`) AS `is_unique_ip`, p.`name` AS `provider_name` FROM `machine` m LEFT JOIN `payment_cycle` pc ON pc.`payment_cycle_id` = m.`payment_cycle_id` LEFT JOIN `provider` p ON p.`provider_id` = m.`provider_id` LEFT JOIN `country` c ON c.`country_code` = m.`country_code` WHERE ' . implode(' AND ', $conditions) . ' GROUP BY m.`machine_id` ORDER BY ' . $orderColumns[$orderColumn - 1] . ' ' . $orderBy . ' LIMIT ' . $start . ', ' . $length, $binds);

			if (empty($rows)) {
				exit(json_encode($results));
			}

			foreach ($rows as $row) {
				$results['data'][] = [
					$row['machine_id'] . ';' . $row['label'],
					$row['label'] . ';' . $row['virtualization'] . ';' . $row['is_nat'] . ';' . $row['is_hidden'],
					$row['city_name'] . ';' . $row['country_code'] . ';' . $row['country_name'] . ';' . $row['ip_address'] . ';' . $row['is_unique_ip'],
					$row['memory'],
					$row['disk_space'] . ';' . $row['disk_type'],
					$row['provider_name'] . ';' . $row['website'] . ';' . $row['control_panel_url'],
					$row['currency_code'] . ';' . $row['price'] . ';' . $row['payment_cycle_id'],
					$row['machine_id'] . ';' . $row['due_date'],
					$row['machine_id'] . ';' . $row['label'],
				];
			}

			exit(json_encode($results));
	}
}

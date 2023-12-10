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

$results = [
	'errors' => [],
	'csrf'   => [
		'name'  => $form->getInputName(),
		'value' => $form->getInputValue(),
	],
];

$username = $request->post('username');
$password = $request->post('password');
$remember = $request->post('remember');

// CSRF validation
$csrf = $form->validate();

// Get the latest CSRF token
$results['csrf'] = [
	'name'  => $form->getInputName(),
	'value' => $form->getInputValue(),
];

// CSRF token is invalid
if (!$csrf) {
	$results['errors']['password'] = 'Security validation failed.';
	exit(json_encode($results));
}

// Username is not in a proper format
if (!preg_match('/^[a-z0-9]{6,20}$/', $username)) {
	$results['errors']['username'] = 'Invalid username.';
}

// Password is not in a proper format
if (mb_strlen($password) < 8 || !preg_match('@[A-Z]@', $password) || !preg_match('@[a-z]@', $password) || !preg_match('@[0-9]@', $password) || !preg_match('@[^\w]@', $password)) {
	$results['errors']['password'] = 'Invalid password.';
}

// Return errors
if (!empty($results['errors'])) {
	exit(json_encode($results));
}

// Match the username and password
if ($username == $config['username'] && $password == $config['password']) {
	$session->set('user', 'yes');

	// Store authentication details in cookie to avoid sign in again
	if ($remember) {
		$cookie->set('auth', $username . ';' . hash('sha256', $config['privateKey'] . $password . $config['privateKey']));
	}

	$results['url'] = './';

	exit(json_encode($results));
}

$results['errors']['password'] = 'Invalid username or password.';

echo json_encode($results);

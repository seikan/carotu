<?php
/**
 * @var \DB\Database     $db
 * @var \Http\Request    $request
 * @var \Session\Session $session
 */

// Preset PHP settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

// Define this as parent file
define('INDEX', 1);

// Define root directory
define('DS', DIRECTORY_SEPARATOR);
define('SETUP_ROOT', __DIR__ . DS);

$parts = explode(DS, __DIR__);
array_pop($parts);
define('ROOT', implode(DS, $parts) . DS);

// Define directories
define('INCLUDES', ROOT . 'includes' . DS);
define('LIBRARIES', ROOT . 'libraries' . DS);
define('LOGS', ROOT . 'logs' . DS);
define('PAGES', ROOT . 'pages' . DS);
define('STORAGE', ROOT . 'storage' . DS);

// Core functions
require INCLUDES . 'functions.php';

// Load libraries
require LIBRARIES . 'autoload.php';

// Error Handler
new \Error\Handler(function ($file, $line, $error) {
	// Log error
	file_put_contents(LOGS . 'error.log', json_encode([
		'date'  => date('Y-m-d H:i:s', strtotime('+8 hours')),
		'error' => $error,
		'file'  => $file . ': ' . $line,
		'ip'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
		'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? '-',
		'url'   => $_SERVER['REQUEST_URI'] ?? '-',
	]) . "\n", FILE_APPEND);

	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
	include ROOT . '50x.html';
	exit;
});

// Page Request
$request = new \Http\Request();

$requirements = [
	'PHP Version (≥ 8.0.0)' => [
		'value'  => PHP_VERSION,
		'passed' => (PHP_VERSION > 8),
	],
	'PDO\\SQLite Extension' => [
		'value'  => extension_loaded('pdo_sqlite') ? 'Enabled' : 'Not enabled',
		'passed' => extension_loaded('pdo_sqlite'),
	],
	'./configuration.php' => [
		'value'  => file_exists(ROOT . 'configuration.php') ? 'Found' : 'Not found',
		'passed' => file_exists(ROOT . 'configuration.php'),
	],
	'./configuration.php Permission' => [
		'value'  => is_writable(ROOT . 'configuration.php') ? 'Writable' : 'Not writable',
		'passed' => is_writable(ROOT . 'configuration.php'),
	],
	'./logs Permission' => [
		'value'  => is_writable(LOGS) ? 'Writable' : 'Not writable',
		'passed' => is_writable(LOGS),
	],
	'./storage Permission' => [
		'value'  => is_writable(STORAGE) ? 'Writable' : 'Not writable',
		'passed' => is_writable(STORAGE),
	],
];

require ROOT . 'configuration.php';

$requirementsFulfilled = true;
$requirementsTable = [];

foreach ($requirements as $title => $values) {
	$requirementsTable[] = '
	<tr>
		<th>' . $title . '</th>
		<td class="text-end">' . $values['value'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . (($values['passed']) ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') . '</td>
	</tr>';

	if (!$values['passed']) {
		$requirementsFulfilled = false;
	}
}

switch ((int) $request->get('step', 1)) {
	case 2:
		// Installation is completed
		if (isset($config['privateKey']) && file_exists(STORAGE . substr(hash('sha256', $config['privateKey']), 0, 32) . '.sqlite')) {
			header('Location: ./?step=3');
			exit;
		}

		// Make sure requirements fulfilled
		if (!$requirementsFulfilled) {
			header('Location: ./?step=1');
			exit;
		}

		$errors = [];
		$username = $request->post('username');
		$password = $request->post('password');
		$confirmPassword = $request->post('confirmPassword');

		if ($request->isPost()) {
			if (!preg_match('/^[a-z0-9]{6,20}$/', $username)) {
				$errors['username'] = 'Only lower case letters and numbers between 6 and 20 characters.';
			}

			if (mb_strlen($password) < 8 || !preg_match('@[A-Z]@', $password) || !preg_match('@[a-z]@', $password) || !preg_match('@[0-9]@', $password) || !preg_match('@[^\w]@', $password)) {
				$errors['password'] = 'A password must be at least 8 characters in length, include at least one upper case letter, one number, and one special character.';
			}

			if ($confirmPassword != $password) {
				$errors['confirmPassword'] = 'Password not match.';
			}

			if (empty($errors)) {
				// Generate a random private key
				$privateKey = hash('sha256', microtime(true) . mt_rand(10000, 99999));

				// Write configuration file
				file_put_contents(ROOT . 'configuration.php', implode("\n", [
					'<?php',
					'$config = [',
					'	\'username\'   => \'' . $username . '\',',
					'	\'password\'   => \'' . str_replace('\'', '\\\'', $password) . '\',',
					'	\'privateKey\' => \'' . $privateKey . '\',',
					'];',
				]));

				// Create SQLite tables

				try {
					$db = new \SQLite\Database(STORAGE . substr(hash('sha256', $privateKey), 0, 32) . '.sqlite');
					$db->saveErrorLog(LOGS . 'sqlite-error.log');

					$queries = array_filter(explode(';', file_get_contents(SETUP_ROOT . 'tables.sql')));

					foreach ($queries as $query) {
						$db->execute($query);
					}
				} catch (Exception $e) {
					echo $e->getMessage();
					exit;
				}

				header('Location: ./?step=3');
				exit;
			}
		}

		$content = '
		<h4>Credentials (Step 2 of 2)</h4>

		<form method="post">
			<div class="form-floating mb-3">
				<input type="text" class="form-control' . ((isset($errors['username'])) ? ' is-invalid' : '') . '" id="username" name="username" value="' . $request->input($username) . '" placeholder="">
				' . ((isset($errors['username'])) ? '<div class="invalid-feedback">' . $errors['username'] . '</div>' : '') . '
				<label for="username">Username</label>
			</div>
			<div class="form-floating mb-3">
				<input type="password" class="form-control' . ((isset($errors['password'])) ? ' is-invalid' : '') . '" id="password" name="password" value="" placeholder="">
				' . ((isset($errors['password'])) ? '<div class="invalid-feedback">' . $errors['password'] . '</div>' : '') . '
				<label for="password">Password</label>
			</div>
			<div class="form-floating mb-3">
				<input type="password" class="form-control' . ((isset($errors['confirmPassword'])) ? ' is-invalid' : '') . '" id="confirmPassword" name="confirmPassword" value="" placeholder="">
				' . ((isset($errors['confirmPassword'])) ? '<div class="invalid-feedback">' . $errors['confirmPassword'] . '</div>' : '') . '
				<label for="confirmPassword">ConfirmPassword</label>
			</div>
			<div class="text-end">
				<form>
					<button type="submit" class="btn btn-success"><i class="bi bi-arrow-right-circle-fill"></i> Submit</button>
				</form>
			</div>
		</form>';
		break;

	case 3:
		// Make sure requirements fulfilled
		if (!$requirementsFulfilled) {
			header('Location: ./?step=1');
			exit;
		}

		// Make sure private key is created
		if (!preg_match('/privateKey\' => \'([^\']+)/', file_get_contents(ROOT . 'configuration.php'), $matches)) {
			header('Location: ./?step=2');
			exit;
		}

		// Make sure SQLite database is created
		if (!file_exists(STORAGE . substr(hash('sha256', $matches[1]), 0, 32) . '.sqlite')) {
			header('Location: ./?step=2');
			exit;
		}

		$content = '
		<div class="alert alert-success">
			<strong>Installation completed.</strong> Please remove the <code>install</code> folder.
		</div>';
		break;

	case 1:
	default:
		// Installation is completed
		if (isset($config['privateKey']) && file_exists(STORAGE . substr(hash('sha256', $config['privateKey']), 0, 32) . '.sqlite')) {
			header('Location: ./?step=3');
			exit;
		}

		$content = '
		<h4>Requirements (Step 1 of 2)</h4>

		<table class="table table-hover table-striped">
			' . implode('', $requirementsTable) . '
		</table>

		<div class="text-end">
			<form>
				<input type="hidden" name="step" value="2">
				<button type="button" class="btn btn btn-primary" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
				<button type="submit" class="btn btn-success' . ((!$requirementsFulfilled) ? ' disabled' : '') . '"><i class="bi bi-arrow-right-circle-fill"></i> Continue</button>
			</form>
		</div>';
		break;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Carotu Installation</title>

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.2/flatly/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="../assets/css/style.min.css">
</head>

<body class="d-flex flex-column h-100">
	<div class="container mt-5">
		<div class="row mb-5">
			<div class="col-lg-4 offset-lg-4">
				<div class="logo">
					<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj48c3ZnIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHZpZXdCb3g9IjAgMCAxMDI0IDEwMjQiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM6c2VyaWY9Imh0dHA6Ly93d3cuc2VyaWYuY29tLyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoyOyI+PGcgaWQ9ImxvZ28tbG9nbyI+PGc+PGc+PHBhdGggZD0iTTcxNi44MjMsNjQ0LjgxM2wtNTMuODM4LC0wYy04LjI0MywtMCAtMTQuOTQzLDYuNzA3IC0xNC45NDMsMTQuOTQzYy0wLDguMTk5IDYuNywxNC44OTIgMTQuOTQzLDE0Ljg5Mmw1My44MzgsLTBsMCwyLjg1NmMwLDg0Ljg5NyAtMTM4LjEzNCwxNjkuNDgzIC0yMDQuODIzLDIwMS44MjZjLTI0LjI1NSwtMTEuNzM4IC01Ny44NjcsLTMwLjQxNCAtOTAuOTQ0LC01My41MDRsMjUuNDU3LC0wYzUuNDkxLC0wIDkuODk4LC00LjQgOS44OTgsLTkuODgzYy0wLC01LjQ5MSAtNC40MDcsLTkuOTM1IC05Ljg5OCwtOS45MzVsLTUyLjE5MSwtMGMtNDcuMjQyLC0zNy4zNjYgLTg3LjE1MywtODIuODk0IC04Ny4xNTMsLTEyOC41MDRsMCwtMTkuMzg4bDU0LjY2MiwwYzcuMzIzLDAgMTMuMjM3LC01LjkyOCAxMy4yMzcsLTEzLjMwM2MtMCwtNy4zMTYgLTUuOTE0LC0xMy4yMyAtMTMuMjM3LC0xMy4yM2wtNTQuNjYyLDBsMCwtNzkuNTQ3YzEuNywwLjQzOCAzLjQ5NSwwLjc3MSA1LjMzNSwwLjc3MWw2OC42MzMsMGMxMS4xMDgsMCAyMC4xMywtOS4wMTUgMjAuMTMsLTIwLjExNWMwLC0xMS4xMTQgLTkuMDIyLC0yMC4xMjkgLTIwLjEzLC0yMC4xMjlsLTY4LjYzMywtMGMtMS43OTUsLTAgLTMuNDk0LDAuMjg5IC01LjE0OSwwLjcxOWM0LjYxNSwtMTA0LjEzNiA4Ny4yNjQsLTE4OC4yOTIgMTkwLjc3LC0xOTUuMjI5bDAuMDQ1LDU2Ljc0NmMtMTcuNjA4LDUuODMyIC0zMC4zMSwyMi4zNjQgLTMwLjMxLDQxLjkxNWwtMCw0OC4yNThjLTAsMjQuNDA0IDE5LjczNiw0NC4xNCA0NC4xNCw0NC4xNGMyNC4zNDQsMCA0NC4wODEsLTE5LjczNiA0NC4wODEsLTQ0LjE0bC0wLC00OC4yNThjLTAsLTE5LjU1MSAtMTIuNzEsLTM2LjA4MyAtMzAuMzAyLC00MS45MTVsMC4wODksLTU2Ljc0NmMxMDMuMjY4LDYuOTM3IDE4NS43NjksOTAuNzA3IDE5MC43MjUsMTk0LjUxbC00MS42NzcsLTBjLTguNzI1LC0wIC0xNS44MTEsNy4wNzEgLTE1LjgxMSwxNS44MTFjLTAsOC43NjMgNy4wODYsMTUuODQ5IDE1LjgxMSwxNS44NDlsNDEuOTA3LC0wbDAsMTAwLjU5Wm0tMjA0LjgyMywtMzg1LjQ5NWMtMTQ1LjAyLC0wIC0yNjMuMDM5LDExOC4wMDQgLTI2My4wMzksMjYzLjAzOWwwLDE1NS4xNDdjMCw2My40NDcgNDMuNDEzLDEyNy4wMzQgMTI5LjAyMywxODkuMDM0YzYwLjE4OSw0My41NDcgMTM0LjAxNiw3Ni44MTcgMTM0LjAxNiw3Ni44MTdjLTAsMCA3My44MTksLTMzLjI3IDEzNC4wMTYsLTc2LjgxN2M4NS42MDIsLTYyIDEyOS4wMjMsLTEyNS41ODcgMTI5LjAyMywtMTg5LjAzNGwtMCwtMTU1LjE0N2MtMCwtMTQ1LjAzNSAtMTE4LjAyNywtMjYzLjAzOSAtMjYzLjAzOSwtMjYzLjAzOSIgc3R5bGU9ImZpbGw6I2ZmNTc1ODtmaWxsLXJ1bGU6bm9uemVybzsiLz48L2c+PGc+PHBhdGggZD0iTTU0Ni4xOTEsMjM4LjgzYzAsMCAxOTUuODY4LDI1LjIyIDIwNS44ODUsLTE1My40ODVjLTAsLTAgLTIyMC4xODIsLTQyLjY5NCAtMjM2LjE3MiwxMjkuMjc0Yy0wLDAgNzEuMjgyLC03OS44MzYgMTcwLjE1LC04My42NWMwLC0wIC0xMzMuMTAzLDUxLjAzMyAtMTM5Ljg2MywxMDcuODYxIiBzdHlsZT0iZmlsbDojN2ZkODU4O2ZpbGwtcnVsZTpub256ZXJvOyIvPjwvZz48Zz48cGF0aCBkPSJNNDc5Ljc5OSwyMzkuMDA4YzAsMCAtMTY3LjE3NSwyMS41MjUgLTE3NS43MjIsLTEzMS4wMDNjLTAsLTAgMTg3LjkyOCwtMzYuNDMyIDIwMS41NjYsMTEwLjMzOWMtMCwwIC02MC44MzUsLTY4LjE0MyAtMTQ1LjIxMywtNzEuMzkzYy0wLDAgMTEzLjU5Nyw0My41NDcgMTE5LjM2OSw5Mi4wNTciIHN0eWxlPSJmaWxsOiM3ZmQ4NTg7ZmlsbC1ydWxlOm5vbnplcm87Ii8+PC9nPjwvZz48L2c+PC9zdmc+" width="64" height="64" class="img-fluid float-start">
					<h3 class="float-end mt-1">
						Carotu <small>A list of VMs</small>
					</h3>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-4 offset-lg-4">
				<?php echo $content; ?>
			</div>
		</div>
	</div>

	<footer class="footer mt-auto py-3 bg-light">
		<div class="container d-flex justify-content-between">
			<small class="text-muted">
				<a href="https://github.com/seikan/carotu" target="_blank" class="text-secondary text-decoration-none"><i class="bi bi-github"></i> Star this project on Github</a>
			</small>

			<small class="text-muted">Carotu <?php echo getVersion(); ?></small>
		</div>
	</footer>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
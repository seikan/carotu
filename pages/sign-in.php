<?php
/**
 * @var \Http\Request    $request
 * @var \Session\Session $session
 */
defined('INDEX') or exit('Access is denied.');

// Sign out
if ($request->get('action') == 'sign-out') {
	$session->delete('user');
	$cookie->delete('auth');
}

// Redirect to home page if user is already signed in
if ($session->get('user')) {
	exit(header('Location: ./'));
}

$username = $request->post('username');
$password = $request->post('password');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>Sign In | Carotu</title>

	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.2/flatly/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/loaders.css/0.1.2/loaders.min.css" rel="stylesheet">
	<link href="../assets/css/style.min.css" rel="stylesheet">
</head>

<body class="mt-5">
	<div class="container">
		<div class="row mb-5">
			<div class="col-lg-4 offset-lg-4">
				<div class="logo">
					<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj48c3ZnIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHZpZXdCb3g9IjAgMCAxMDI0IDEwMjQiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM6c2VyaWY9Imh0dHA6Ly93d3cuc2VyaWYuY29tLyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoyOyI+PGcgaWQ9ImxvZ28tbG9nbyI+PGc+PGc+PHBhdGggZD0iTTcxNi44MjMsNjQ0LjgxM2wtNTMuODM4LC0wYy04LjI0MywtMCAtMTQuOTQzLDYuNzA3IC0xNC45NDMsMTQuOTQzYy0wLDguMTk5IDYuNywxNC44OTIgMTQuOTQzLDE0Ljg5Mmw1My44MzgsLTBsMCwyLjg1NmMwLDg0Ljg5NyAtMTM4LjEzNCwxNjkuNDgzIC0yMDQuODIzLDIwMS44MjZjLTI0LjI1NSwtMTEuNzM4IC01Ny44NjcsLTMwLjQxNCAtOTAuOTQ0LC01My41MDRsMjUuNDU3LC0wYzUuNDkxLC0wIDkuODk4LC00LjQgOS44OTgsLTkuODgzYy0wLC01LjQ5MSAtNC40MDcsLTkuOTM1IC05Ljg5OCwtOS45MzVsLTUyLjE5MSwtMGMtNDcuMjQyLC0zNy4zNjYgLTg3LjE1MywtODIuODk0IC04Ny4xNTMsLTEyOC41MDRsMCwtMTkuMzg4bDU0LjY2MiwwYzcuMzIzLDAgMTMuMjM3LC01LjkyOCAxMy4yMzcsLTEzLjMwM2MtMCwtNy4zMTYgLTUuOTE0LC0xMy4yMyAtMTMuMjM3LC0xMy4yM2wtNTQuNjYyLDBsMCwtNzkuNTQ3YzEuNywwLjQzOCAzLjQ5NSwwLjc3MSA1LjMzNSwwLjc3MWw2OC42MzMsMGMxMS4xMDgsMCAyMC4xMywtOS4wMTUgMjAuMTMsLTIwLjExNWMwLC0xMS4xMTQgLTkuMDIyLC0yMC4xMjkgLTIwLjEzLC0yMC4xMjlsLTY4LjYzMywtMGMtMS43OTUsLTAgLTMuNDk0LDAuMjg5IC01LjE0OSwwLjcxOWM0LjYxNSwtMTA0LjEzNiA4Ny4yNjQsLTE4OC4yOTIgMTkwLjc3LC0xOTUuMjI5bDAuMDQ1LDU2Ljc0NmMtMTcuNjA4LDUuODMyIC0zMC4zMSwyMi4zNjQgLTMwLjMxLDQxLjkxNWwtMCw0OC4yNThjLTAsMjQuNDA0IDE5LjczNiw0NC4xNCA0NC4xNCw0NC4xNGMyNC4zNDQsMCA0NC4wODEsLTE5LjczNiA0NC4wODEsLTQ0LjE0bC0wLC00OC4yNThjLTAsLTE5LjU1MSAtMTIuNzEsLTM2LjA4MyAtMzAuMzAyLC00MS45MTVsMC4wODksLTU2Ljc0NmMxMDMuMjY4LDYuOTM3IDE4NS43NjksOTAuNzA3IDE5MC43MjUsMTk0LjUxbC00MS42NzcsLTBjLTguNzI1LC0wIC0xNS44MTEsNy4wNzEgLTE1LjgxMSwxNS44MTFjLTAsOC43NjMgNy4wODYsMTUuODQ5IDE1LjgxMSwxNS44NDlsNDEuOTA3LC0wbDAsMTAwLjU5Wm0tMjA0LjgyMywtMzg1LjQ5NWMtMTQ1LjAyLC0wIC0yNjMuMDM5LDExOC4wMDQgLTI2My4wMzksMjYzLjAzOWwwLDE1NS4xNDdjMCw2My40NDcgNDMuNDEzLDEyNy4wMzQgMTI5LjAyMywxODkuMDM0YzYwLjE4OSw0My41NDcgMTM0LjAxNiw3Ni44MTcgMTM0LjAxNiw3Ni44MTdjLTAsMCA3My44MTksLTMzLjI3IDEzNC4wMTYsLTc2LjgxN2M4NS42MDIsLTYyIDEyOS4wMjMsLTEyNS41ODcgMTI5LjAyMywtMTg5LjAzNGwtMCwtMTU1LjE0N2MtMCwtMTQ1LjAzNSAtMTE4LjAyNywtMjYzLjAzOSAtMjYzLjAzOSwtMjYzLjAzOSIgc3R5bGU9ImZpbGw6I2ZmNTc1ODtmaWxsLXJ1bGU6bm9uemVybzsiLz48L2c+PGc+PHBhdGggZD0iTTU0Ni4xOTEsMjM4LjgzYzAsMCAxOTUuODY4LDI1LjIyIDIwNS44ODUsLTE1My40ODVjLTAsLTAgLTIyMC4xODIsLTQyLjY5NCAtMjM2LjE3MiwxMjkuMjc0Yy0wLDAgNzEuMjgyLC03OS44MzYgMTcwLjE1LC04My42NWMwLC0wIC0xMzMuMTAzLDUxLjAzMyAtMTM5Ljg2MywxMDcuODYxIiBzdHlsZT0iZmlsbDojN2ZkODU4O2ZpbGwtcnVsZTpub256ZXJvOyIvPjwvZz48Zz48cGF0aCBkPSJNNDc5Ljc5OSwyMzkuMDA4YzAsMCAtMTY3LjE3NSwyMS41MjUgLTE3NS43MjIsLTEzMS4wMDNjLTAsLTAgMTg3LjkyOCwtMzYuNDMyIDIwMS41NjYsMTEwLjMzOWMtMCwwIC02MC44MzUsLTY4LjE0MyAtMTQ1LjIxMywtNzEuMzkzYy0wLDAgMTEzLjU5Nyw0My41NDcgMTE5LjM2OSw5Mi4wNTciIHN0eWxlPSJmaWxsOiM3ZmQ4NTg7ZmlsbC1ydWxlOm5vbnplcm87Ii8+PC9nPjwvZz48L2c+PC9zdmc+" width="64" height="64" class="img-fluid float-start">
					<h3 class="float-end text-end mt-1">
						Carotu <small>A list of VMs</small>
					</h3>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-4 offset-lg-4">
				<div class="panel card w-100">
					<div class="card-body">
						<p class="card-text">
							<?php if ($request->get('action') == 'sign-out'): ?>
							<div class="alert alert-success alert-dismissible" role="alert">
								<strong>Horray!</strong> You have signed out successfully.
								<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							</div>
							<?php endif; ?>

							<form method="post" class="p-1">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="username" name="username" value="<?php echo $request->input($username); ?>" placeholder="" autocapitalize="off" required>
									<label for="username">Username</label>
									<div id="username-feedback" class="invalid-feedback"></div>
								</div>
								<div class="input-group mb-3">
									<div class="form-floating">
										<input type="password" class="form-control" id="password" name="password" value="<?php echo $request->input($password); ?>" placeholder="" autocapitalize="off" autocomplete="off" required>
										<label for="password">Password</label>
									</div>
									<button class="btn btn-end btn-outline-secondary" type="button" id="toggle-password"><i class="bi bi-eye-slash"></i></button>
									<div id="password-feedback" class="invalid-feedback"></div>
								</div>

								<div class="form-check mb-5">
									<input class="form-check-input" type="checkbox" value="1" name="remember" id="remember">
									<label class="form-check-label" for="remember">Remember Me</label>
								</div>

								<button type="submit" class="btn btn-primary w-100 disabled"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>

								<?php $form->input(); ?>
							</form>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
	<script src="./assets/js/sign-in.min.js"></script>
</body>
</html>
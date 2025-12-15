<?php

/**
 * @var \Cookie\Cookie   $cookie
 * @var \Http\Request    $request
 * @var \Security\Form   $form
 * @var \Session\Session $session
 * @var \SQLite\Database $db
 */
defined('INDEX') or exit('Access is denied.');

// Redirect to sign in page
if (!$session->get('user')) {
        header('Location: ./sign-in');
        exit;
}

$countryOptions = [];
$currencyOptions = [];

// Get all countries
$countries = $db->select('country', '1 = 1 ORDER BY `country_name`');

if (!empty($countries)) {
	foreach ($countries as $country) {
		$countryOptions[] = '<option value="' . $country['country_code'] . '"> ' . $country['country_name'] . '</option>';
	}
}

// Get all currencies
$currencies = $db->select('currency_rate', '1 = 1 ORDER BY `currency_code`');

if (!empty($currencies)) {
	foreach ($currencies as $currency) {
		$currencyOptions[] = '<option value="' . $currency['currency_code'] . '" data-rate="' . $currency['rate'] . '">' . $currency['currency_code'] . '</option>';
	}
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100"<?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark'): echo ' data-bs-theme="dark"'; endif; ?>>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Carotu</title>

	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.8/flatly/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/loaders.css/0.1.2/loaders.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.5.0/css/flag-icons.min.css" rel="stylesheet">
	<link href="https://cdn.datatables.net/1.13.11/css/dataTables.bootstrap5.min.css" rel="stylesheet">

	<link href="./assets/css/select-search.min.css" rel="stylesheet">
	<link href="./assets/css/style.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">
	<header class="border-bottom">
		<div class="container py-3">
			<div class="d-flex flex-column flex-md-row align-items-center">
				<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj48c3ZnIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHZpZXdCb3g9IjAgMCAxMDI0IDEwMjQiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM6c2VyaWY9Imh0dHA6Ly93d3cuc2VyaWYuY29tLyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoyOyI+PGcgaWQ9ImxvZ28tbG9nbyI+PGc+PGc+PHBhdGggZD0iTTcxNi44MjMsNjQ0LjgxM2wtNTMuODM4LC0wYy04LjI0MywtMCAtMTQuOTQzLDYuNzA3IC0xNC45NDMsMTQuOTQzYy0wLDguMTk5IDYuNywxNC44OTIgMTQuOTQzLDE0Ljg5Mmw1My44MzgsLTBsMCwyLjg1NmMwLDg0Ljg5NyAtMTM4LjEzNCwxNjkuNDgzIC0yMDQuODIzLDIwMS44MjZjLTI0LjI1NSwtMTEuNzM4IC01Ny44NjcsLTMwLjQxNCAtOTAuOTQ0LC01My41MDRsMjUuNDU3LC0wYzUuNDkxLC0wIDkuODk4LC00LjQgOS44OTgsLTkuODgzYy0wLC01LjQ5MSAtNC40MDcsLTkuOTM1IC05Ljg5OCwtOS45MzVsLTUyLjE5MSwtMGMtNDcuMjQyLC0zNy4zNjYgLTg3LjE1MywtODIuODk0IC04Ny4xNTMsLTEyOC41MDRsMCwtMTkuMzg4bDU0LjY2MiwwYzcuMzIzLDAgMTMuMjM3LC01LjkyOCAxMy4yMzcsLTEzLjMwM2MtMCwtNy4zMTYgLTUuOTE0LC0xMy4yMyAtMTMuMjM3LC0xMy4yM2wtNTQuNjYyLDBsMCwtNzkuNTQ3YzEuNywwLjQzOCAzLjQ5NSwwLjc3MSA1LjMzNSwwLjc3MWw2OC42MzMsMGMxMS4xMDgsMCAyMC4xMywtOS4wMTUgMjAuMTMsLTIwLjExNWMwLC0xMS4xMTQgLTkuMDIyLC0yMC4xMjkgLTIwLjEzLC0yMC4xMjlsLTY4LjYzMywtMGMtMS43OTUsLTAgLTMuNDk0LDAuMjg5IC01LjE0OSwwLjcxOWM0LjYxNSwtMTA0LjEzNiA4Ny4yNjQsLTE4OC4yOTIgMTkwLjc3LC0xOTUuMjI5bDAuMDQ1LDU2Ljc0NmMtMTcuNjA4LDUuODMyIC0zMC4zMSwyMi4zNjQgLTMwLjMxLDQxLjkxNWwtMCw0OC4yNThjLTAsMjQuNDA0IDE5LjczNiw0NC4xNCA0NC4xNCw0NC4xNGMyNC4zNDQsMCA0NC4wODEsLTE5LjczNiA0NC4wODEsLTQ0LjE0bC0wLC00OC4yNThjLTAsLTE5LjU1MSAtMTIuNzEsLTM2LjA4MyAtMzAuMzAyLC00MS45MTVsMC4wODksLTU2Ljc0NmMxMDMuMjY4LDYuOTM3IDE4NS43NjksOTAuNzA3IDE5MC43MjUsMTk0LjUxbC00MS42NzcsLTBjLTguNzI1LC0wIC0xNS44MTEsNy4wNzEgLTE1LjgxMSwxNS44MTFjLTAsOC43NjMgNy4wODYsMTUuODQ5IDE1LjgxMSwxNS44NDlsNDEuOTA3LC0wbDAsMTAwLjU5Wm0tMjA0LjgyMywtMzg1LjQ5NWMtMTQ1LjAyLC0wIC0yNjMuMDM5LDExOC4wMDQgLTI2My4wMzksMjYzLjAzOWwwLDE1NS4xNDdjMCw2My40NDcgNDMuNDEzLDEyNy4wMzQgMTI5LjAyMywxODkuMDM0YzYwLjE4OSw0My41NDcgMTM0LjAxNiw3Ni44MTcgMTM0LjAxNiw3Ni44MTdjLTAsMCA3My44MTksLTMzLjI3IDEzNC4wMTYsLTc2LjgxN2M4NS42MDIsLTYyIDEyOS4wMjMsLTEyNS41ODcgMTI5LjAyMywtMTg5LjAzNGwtMCwtMTU1LjE0N2MtMCwtMTQ1LjAzNSAtMTE4LjAyNywtMjYzLjAzOSAtMjYzLjAzOSwtMjYzLjAzOSIgc3R5bGU9ImZpbGw6I2ZmNTc1ODtmaWxsLXJ1bGU6bm9uemVybzsiLz48L2c+PGc+PHBhdGggZD0iTTU0Ni4xOTEsMjM4LjgzYzAsMCAxOTUuODY4LDI1LjIyIDIwNS44ODUsLTE1My40ODVjLTAsLTAgLTIyMC4xODIsLTQyLjY5NCAtMjM2LjE3MiwxMjkuMjc0Yy0wLDAgNzEuMjgyLC03OS44MzYgMTcwLjE1LC04My42NWMwLC0wIC0xMzMuMTAzLDUxLjAzMyAtMTM5Ljg2MywxMDcuODYxIiBzdHlsZT0iZmlsbDojN2ZkODU4O2ZpbGwtcnVsZTpub256ZXJvOyIvPjwvZz48Zz48cGF0aCBkPSJNNDc5Ljc5OSwyMzkuMDA4YzAsMCAtMTY3LjE3NSwyMS41MjUgLTE3NS43MjIsLTEzMS4wMDNjLTAsLTAgMTg3LjkyOCwtMzYuNDMyIDIwMS41NjYsMTEwLjMzOWMtMCwwIC02MC44MzUsLTY4LjE0MyAtMTQ1LjIxMywtNzEuMzkzYy0wLDAgMTEzLjU5Nyw0My41NDcgMTE5LjM2OSw5Mi4wNTciIHN0eWxlPSJmaWxsOiM3ZmQ4NTg7ZmlsbC1ydWxlOm5vbnplcm87Ii8+PC9nPjwvZz48L2c+PC9zdmc+" width="48" height="48">
				<h5 class="my-auto">Carotu</h5>

				<div class="dropdown ms-auto">
					<button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i></button>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="#" id="menu-provider"><i class="bi bi-person-workspace"></i> Providers</a></li>
						<li><a class="dropdown-item" href="#" id="menu-stats"><i class="bi bi-bar-chart-fill"></i> Statistics</a></li>
						<li><a class="dropdown-item" href="#" id="menu-settings"><i class="bi bi-gear"></i> Settings</a></li>
						<li>
							<hr class="dropdown-divider">
						</li>
						<li><a class="dropdown-item" href="./sign-in?action=sign-out"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
					</ul>
				</div>
			</div>
		</div>
	</header>

    <main>
		<div class="container py-3">
			<div class="row mb-2">
				<div class="col-12">
					<div class="text-end">
						<div class="btn-group">
							<button class="btn btn-outline-dark btn-sm dropdown-toggle disabled" id="btn-action" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-ui-checks"></i><span class="d-none d-md-inline"> Actions</span></button>
							<ul class="dropdown-menu">
								<li><a class="dropdown-item" href="#" id="action-hide"><i class="bi bi-eye-slash-fill"></i> Hide</a></li>
								<li><a class="dropdown-item" href="#" id="action-unhide"><i class="bi bi-eye-fill"></i> Unhide</a></li>
								<li><a class="dropdown-item" href="#" id="action-delete"><i class="bi bi-trash"></i> Delete</a></li>
								<li><a class="dropdown-item" href="#" id="action-renew"><i class="bi bi-arrow-repeat"></i> Renew</a></li>
							</ul>
						</div>

						<button id="btn-new-machine" type="button" class="btn btn-sm btn-danger"><i class="bi bi-plus-square-fill"></i><span class="d-none d-md-inline"> New Machine</span></button>
					</div>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-12 col-md-6">
					<input type="text" id="search" class="form-control">
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<div class="table-responsive">
						<table id="table-machine" class="table">
							<thead>
								<tr>
									<th>
										<div class="form-check"><input class="form-check-input" type="checkbox" disabled></div>
									</th>
									<th>Label</th>
									<th>Location</th>
									<th>Memory</th>
									<th>Disk</th>
									<th>Provider</th>
									<th>Price</th>
									<th>Due</th>
									<th>&nbsp;</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</main>

	<footer class="footer mt-auto py-3 bg-light">
		<div class="container d-flex justify-content-between">
			<small class="text-muted">
				<a href="https://github.com/seikan/carotu" target="_blank" class="text-secondary text-decoration-none"><i class="bi bi-github"></i> Star this project on Github</a>
			</small>

			<small class="text-muted">Carotu <?php echo getVersion(); ?></small>
		</div>
	</footer>

	<div class="modal" id="modal-machine" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modal-machine" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5">New Machine</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form>
						<div class="row mb-3">
							<div class="col-12">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">General</h6>

										<div class="row">
											<div class="col-12">
												<div class="form-check form-switch my-1">
													<input class="form-check-input" type="checkbox" role="switch" name="is-hidden" id="is-hidden">
													<label class="form-check-label" for="is-hidden">Hidden</label>
												</div>
												<div class="form-floating mb-3">
													<input type="text" class="form-control" name="label" placeholder="">
													<label>Label</label>
												</div>
											</div>
										</div>

										<div class="row">
											<div class="col-12 col-lg-8">
												<div class="form-floating">
													<select class="form-select select-search" name="provider" aria-label="Provider" data-search="true">
														<option selected disabled></option>
													</select>
													<label>Provider</label>
												</div>
											</div>
											<div class="col-12 col-lg-4">
												<div class="form-floating">
													<select class="form-select select-search" name="virtualization" aria-label="Virtualization" data-search="true">
														<option selected disabled></option>
													</select>
													<label>Virtualization</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-12">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">Network</h6>

										<div class="row">
											<div class="col-12 col-md-6">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control" name="ip-address" placeholder="">
														<input type="hidden" name="ip">
														<label>IP Address</label>
													</div>
													<button class="btn btn-outline-secondary disabled" type="button" id="btn-add-ip"><i class="bi bi-plus"></i></button>
												</div>
												<div class="overflow-y-scroll overflow-x-hidden"></div>

												<div class="form-check mt-1">
													<input class="form-check-input" type="checkbox" value="1" name="is-nat" id="is-nat">
													<label class="form-check-label" for="is-nat">NAT Network</label>
												</div>
											</div>
											<div class="col-12 col-md-6">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control float" name="bandwidth" placeholder="Bandwidth" maxlength="4">
														<label>Bandwidth</label>
													</div>
													<select class="form-select" name="bandwidth-unit" style="max-width: 80px;">
														<option value="GB" selected> GB</option>
														<option value="TB"> TB</option>
													</select>
												</div>
												<div class="form-floating mb-3">
													<select class="form-select select-search" name="country" aria-label="Country" data-search="true">
														<option selected disabled></option>
														<?php echo implode('', $countryOptions); ?>
													</select>
													<label>Country</label>
												</div>
												<div class="form-floating mb-3">
													<input type="text" class="form-control" name="city" placeholder="">
													<label>City</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-12 col-lg-4">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">CPU</h6>
										<div class="row">
											<div class="col-12">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control float" name="speed" placeholder="Speed" maxlength="7">
														<label>Speed</label>
													</div>
													<span class="input-group-text">MHz</span>
												</div>
											</div>
											<div class="col-12">
												<div class="form-floating mb-3">
													<input type="text" class="form-control number" name="core" placeholder="Core" maxlength="2">
													<label>Core</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-12 col-lg-4">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">RAM</h6>

										<div class="row">
											<div class="col-12">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control float" name="memory" placeholder="Memory" maxlength="4">
														<label>Memory</label>
													</div>
													<select class="form-select" name="memory-unit" style="max-width: 80px;">
														<option value="MB" selected> MB</option>
														<option value="GB"> GB</option>
													</select>
												</div>
											</div>
											<div class="col-12">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control float" name="swap" placeholder="Swap" maxlength="4">
														<label>Swap</label>
													</div>
													<select class="form-select" name="swap-unit" style="max-width: 80px;">
														<option value="MB" selected> MB</option>
														<option value="GB"> GB</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-12 col-lg-4">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">Disk</h6>

										<div class="row">
											<div class="col-12">
												<div class="form-floating mb-3">
													<select class="form-select" name="disk-type" aria-label="Type">
														<option selected disabled></option>
													</select>
													<label>Type</label>
												</div>
											</div>
											<div class="col-12">
												<div class="input-group mb-3">
													<div class="form-floating">
														<input type="text" class="form-control number" name="disk-space" placeholder="Space" maxlength="4">
														<label>Space</label>
													</div>
													<select class="form-select" name="disk-space-unit" style="max-width: 80px;">
														<option value="GB" selected> GB</option>
														<option value="TB"> TB</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-12">
								<div class="card w-100">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary">Billing</h6>

										<div class="row">
											<div class="col-12 col-lg-6">
												<div class="input-group mb-3">
													<select class="form-select" name="currency" style="max-width: 90px">
														<?php echo implode('', $currencyOptions); ?>
													</select>
													<div class="form-floating">
														<input type="text" class="form-control text-end" name="price" placeholder="Price" maxlength="8" autocomplete="off">
														<label>Price</label>
													</div>
													<select class="form-select" name="cycle" style="max-width: 135px">
														<option value="1" selected> Monthly</option>
														<option value="2"> Quarterly</option>
														<option value="3"> Semi-Yearly</option>
														<option value="4"> Yearly</option>
														<option value="5"> Bi-Yearly</option>
														<option value="6"> Tri-Yearly</option>
													</select>
												</div>
											</div>
											<div class="col-12 col-lg-6">
												<div class="input-group mb-3">
													<span class="input-group-text"><i class="bi bi-calendar3"></i></span>
													<div class="form-floating">
														<input type="text" class="form-control" name="due-date" placeholder="Due Date" maxlength="10">
														<label>Due Date</label>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-12">
								<div class="form-floating">
									<textarea class="form-control" placeholder="Notes" name="notes" style="height: 100px"></textarea>
									<label>Notes</label>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary">Add Machine</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="modal-provider" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modal-provider" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5">Providers</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row mb-2">
						<div id="page-provider" class="col-12">
							<div class="text-end">
								<button id="btn-new-provider" type="button" class="btn btn-sm btn-danger"><i class="bi bi-plus-square-fill"></i><span class="d-none d-md-inline"> New Provider</span></button>
							</div>
							<div class="loader text-center p-5">
								<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
								<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
								<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
							</div>
							<div id="provider-list" class="d-none">
								<input type="input" class="form-control my-2" name="search-provider" placeholder="Search...">
								<div class="mt-3 p-3 overflow-auto" style="max-height: 300px">
									<table class="table" style="table-layout: fixed; min-width: 500px">
										<thead>
											<tr>
												<th>Name</th>
												<th>Website</th>
												<th colspan="2">Control Panel</th>
												<th class="text-end">Owned</th>
												<th>&nbsp;</th>
											</tr>
										</thead>
										<tbody></tbody>
									</table>
								</div>
							</div>
						</div>
						<div id="page-new-provider" class="col-12 d-none">
							<form>
								<div class="form-floating mb-3">
									<input type="text" class="form-control" name="name" placeholder="">
									<label>Provider Name</label>
								</div>
								<div class="form-floating mb-3">
									<input type="text" class="form-control" name="website" placeholder="https://example.com">
									<label>Website</label>
								</div>
								<div class="form-floating mb-3">
									<input type="text" class="form-control" name="cp" placeholder="SolusVM" autocomplete="off" autocapitalize="off">
									<label>Control Panel</label>
								</div>
								<div class="form-floating mb-3">
									<input type="text" class="form-control" name="cpUrl" placeholder="https://example.com">
									<label>Control Panel URL</label>
								</div>
								<div class="text-end">
									<button type="button" class="btn btn-outline-secondary">Discard</button>
									<button type="submit" class="btn btn-primary">Save Provider</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="modal-settings" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modal-settings" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5">Settings</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-12 col-md-6 mb-3">
							<label for="theme" class="form-label">Theme</label>
							<select name="theme" id="theme" class="form-select" aria-label="Theme">
								<option value="light">Light</option>
								<option value="dark">Dark</option>
							</select>
						</div>
						<div class="col-12 col-md-6 mb-3">
							<label for="page-length" class="form-label">Page Length</label>
							<select name="page-length" id="page-length" class="form-select" aria-label="Page Length">
								<option value="10">10 items per page</option>
								<option value="25">25 items per page</option>
								<option value="50">50 items per page</option>
								<option value="100">100 items per page</option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-12 mb-5">
							<label class="form-label">Currencies <a href="#" id="btn-add-currency" class="text-decoration-none text-primary"><i class="bi bi-plus-square-fill"></i></a></label>
							<div id="currency-list" class="overflow-y-scroll overflow-x-hidden px-2" style="min-height: 200px; max-height: 200px;">
								<table id="table-currency" class="table table-sm" style="table-layout: fixed">
									<thead>
										<tr>
											<th>Code</th>
											<th class="text-end">Rate</th>
											<td class="text-end">&nbsp</td>
										</tr>
									</thead>
									<tbody></tbody>
								</table>

								<small><i class="bi bi-info-circle"></i> Currency rates convertion are based on USD.</small>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="modal-stats" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modal-stats" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5">Statistics</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row mb-3">
						<div class="col-12">
							<div class="d-flex justify-content-end align-items-center">
								<label class="me-2 mb-0">Currency:</label>
								<select id="stats-currency" class="form-select form-select-sm" style="width: auto;">
									<?php echo implode("", $currencyOptions); ?>
								</select>
							</div>
						</div>
					</div>

					<div class="loader text-center p-5">
						<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
						<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
						<div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
					</div>

					<div id="stats-content" class="d-none">
						<!-- Cost Overview Cards -->
						<div class="row mb-4">
							<div class="col-12 col-md-4 mb-3">
								<div class="card">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary"><i class="bi bi-hdd-rack"></i> Total Machines</h6>
										<h2 class="card-title mb-0"><span id="total-machines">-</span></h2>
									</div>
								</div>
							</div>
							<div class="col-12 col-md-4 mb-3">
								<div class="card">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary"><i class="bi bi-calendar-month"></i> Monthly Cost</h6>
										<h2 class="card-title mb-0">
											<span id="monthly-cost" class="text-primary">-</span>
											<small class="text-muted" id="monthly-currency"></small>
										</h2>
									</div>
								</div>
							</div>
							<div class="col-12 col-md-4 mb-3">
								<div class="card">
									<div class="card-body">
										<h6 class="card-subtitle mb-2 text-body-secondary"><i class="bi bi-calendar-range"></i> Yearly Cost</h6>
										<h2 class="card-title mb-0">
											<span id="yearly-cost" class="text-danger">-</span>
											<small class="text-muted" id="yearly-currency"></small>
										</h2>
									</div>
								</div>
							</div>
						</div>

						<!-- Machines by Provider -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card">
									<div class="card-body">
										<h5 class="card-title"><i class="bi bi-person-workspace"></i> Machines by Provider</h5>
										<div class="table-responsive">
											<table class="table table-sm">
												<thead>
													<tr>
														<th>Provider</th>
														<th class="text-end">Count</th>
														<th class="text-end">Monthly Cost</th>
														<th style="width: 40%;">Distribution</th>
													</tr>
												</thead>
												<tbody id="provider-tbody">
													<tr>
														<td colspan="4" class="text-center text-muted">No data</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Machines by Country -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card">
									<div class="card-body">
										<h5 class="card-title"><i class="bi bi-globe"></i> Machines by Country</h5>
										<div class="table-responsive">
											<table class="table table-sm">
												<thead>
													<tr>
														<th>Country</th>
														<th class="text-end">Count</th>
														<th style="width: 50%;">Distribution</th>
													</tr>
												</thead>
												<tbody id="country-tbody">
													<tr>
														<td colspan="3" class="text-center text-muted">No data</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="modal-confirmation" tabindex="-1" aria-labelledby="modal-confirmation" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5">Delete Confirmation</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-danger">Confirm</button>
				</div>
			</div>
		</div>
	</div>

	<div class="toast-container position-fixed top-0 end-0 translate-middle-x p-3">
		<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="d-flex">
				<div class="toast-body"></div>
				<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	</div>

	<?php $form->input(); ?>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>

	<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.11/js/dataTables.bootstrap5.min.js"></script>

	<script src="./assets/js/variables.js"></script>
	<script src="./assets/js/ip-address.min.js"></script>
	<script src="./assets/js/search.min.js"></script>
	<script src="./assets/js/select-search.min.js"></script>
	<script src="./assets/js/currency-input.min.js"></script>
	<script src="./assets/js/auto-complete.min.js"></script>

	<script src="./assets/js/machine.min.js"></script>
	<script src="./assets/js/provider.min.js"></script>
	<script src="./assets/js/settings.min.js"></script>
	<script src="./assets/js/stats.min.js"></script>

	<script>
		$(function() {
			new Provider();
			new Machine();
			new Settings();
			new Stats();
		});
	</script>
</body>

</html>

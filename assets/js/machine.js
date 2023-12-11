class Machine {
	constructor() {
		var _this = this;
		var table;

		this.ip = new IpAddress();

		$('#search').search({
			placeholder: 'Search for a machine',
			onFilterChanged: function (filters) {
				var searches = [];

				if (filters.length > 0) {
					$.each(filters, function (i, filter) {
						searches.push(filter.filter + filter.operator + filter.value);
					});
				}

				table.search(searches.join(';')).draw();
			}
		});

		$('#btn-new-machine').on('click', function (e) {
			e.preventDefault();

			$('#modal-machine').modal('show');

			$('#modal-machine .modal-footer .btn-primary')
				.off('click')
				.on('click', function (e) {
					e.preventDefault();

					var $btn = $(this);
					var values = $('#modal-machine form').serialize();

					$btn.data('html', $btn.html())
						.css({
							width: $btn.outerWidth(),
							height: $btn.outerHeight(),
						})
						.prop('disabled', true)
						.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

					$('#modal-machine input, #modal-machine select, #modal-machine textarea, #modal-machine .btn').prop('disabled', true);

					$('#modal-machine .is-invalid').removeClass('is-invalid');

					$.post(
						'./machine.json',
						values + '&__CSRF_TOKEN__=' + $('input[name="__CSRF_TOKEN__"]').val(),
						function (response) {
							if (Object.keys(response.errors).length > 0) {
								$.each(response.errors, function (key) {
									$('#modal-machine [name="' + key + '"]').addClass('is-invalid');
									$('#modal-machine [name="' + key + '"]')
										.parent('.form-floating')
										.addClass('is-invalid');
								});

								$('#modal-machine').scrollTop(0);

								return;
							}

							$('.toast-container').empty();

							var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + $('input[name="label"]').val() + '</strong> is created.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

							$('.toast-container').append($toast);

							$toast.toast('show');

							$('#modal-machine').modal('hide');

							// Refresh city list
							$.get(
								'./machine.json?action=city',
								function (data) {
									$('input[name="city"]')
										.autoComplete({
											source: data,
										})
										.refresh();
								},
								'json'
							);

							// Refresh table
							table.draw();
						},
						'json'
					)
						.fail(function () {
							alert('Connection lost, please try again.');
						})
						.always(function (response) {
							$btn.prop('disabled', false).html($btn.data('html'));
							$('#modal-machine input, #modal-machine select, #modal-machine textarea, #modal-machine .btn').prop('disabled', false);

							$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
						});
				});
		});

		// Reset form inputs
		$('#modal-machine').on('hidden.bs.modal', function () {
			$('#modal-machine .modal-title').html('New Machine');
			$('#modal-machine form')[0].reset();
			$('#modal-machine input[name="ip"]').val('');
			$('#modal-machine select').trigger('change');
			$('#btn-add-ip').closest('div').next('.overflow-y-scroll').empty();
			$('#modal-machine .modal-footer .btn-primary').html('Add Machine');

			$('#modal-machine .is-invalid').removeClass('is-invalid');
		});

		$.each(variables.virtualizations, function (i, virtualization) {
			$('select[name="virtualization"]').append('<option value="' + virtualization + '">' + virtualization + '</option>');
		});

		$.each(variables.diskTypes, function (i, diskType) {
			$('select[name="disk-type"]').append('<option value="' + diskType + '">' + diskType + '</option>');
		});

		$('.select-search').selectSearch();

		$('input[name="ip-address"]')
			.on('input', function () {
				$(this).val($(this).val().trim());

				$(this).toggleClass('is-invalid', !_this.ip.isValid($(this).val()));
				$('#btn-add-ip').toggleClass('disabled', !_this.ip.isValid($(this).val()));
			})
			.on('keypress', function (e) {
				if (e.which == 13) {
					$('#btn-add-ip').trigger('click');
				}
			});

		$('#btn-add-ip').on('click', function (e) {
			e.preventDefault();

			var ip = $('input[name="ip-address"]').val().trim();

			if (!_this.ip.isValid(ip)) {
				return;
			}

			var ipList = $('input[name="ip"]').val().split(',');

			if (ipList.includes(ip)) {
				return;
			}

			ipList.push(ip);

			$('input[name="ip"]').val(ipList.filter((elm) => elm).join(','));

			var $item = $('<div class="badge bg-secondary-subtle text-dark p-2 ps-3 py-3 d-flex w-100 mb-1">');
			var $close = $('<i class="bi bi-x ms-auto pointer">').on('click', function (e) {
				e.preventDefault();

				var ipList = $('input[name="ip"]').val().split(',');
				var index = ipList.indexOf(ip);

				if (index !== -1) {
					ipList.splice(index, 1);
					$('input[name="ip"]').val(ipList.filter((elm) => elm).join(','));
				}

				$item.remove();
			});

			$(this).closest('div').next('.overflow-y-scroll').prepend($item.append(ip).append($close));

			$(this)
				.closest('div')
				.next('.overflow-y-scroll')
				.css({
					maxHeight: $item.outerHeight() * 3,
				});

			$('input[name="ip-address"]').val('');
		});

		$('.float')
			.on('input', function () {
				$(this).val(
					$(this)
						.val()
						.replace(/[^0-9.]/g, '')
				);

				if ($(this).val().indexOf('.') > -1) {
					if ($(this).val().match(/\./g).length > 1) {
						$(this).val($(this).val().slice(0, -1));
					}
				}
			})
			.on('drop paste', function (e) {
				e.preventDefault();
			});

		$('.number').on('input', function () {
			$(this).val($(this).val().replace(/\D/g, ''));
			$(this).val(
				$(this)
					.val()
					.replace(/^(0)[0-9]+/g, '$1')
			);
		});

		$('input[name="price"]')
			.currencyInput()
			.on('input', function () {
				if ($('select[name="currency"]').val() == 'USD') {
					$(this).attr('data-usd-price', $(this).val());
				} else {
					$(this).attr('data-usd-price', ($(this).val() / $('select[name="currency"] option:selected').attr('data-rate')) * 10000);
				}
			});

		$('select[name="currency"]').on('change', function () {
			if ($('input[name="price"]').val().length > 0) {
				$('input[name="price"]').val((($('input[name="price"]').attr('data-usd-price') * $('select[name="currency"] option:selected').attr('data-rate')) / 10000).toFixed(2));
			}
		});

		$('select[name="cycle"]').on('change', function () {
			var now = new Date();

			switch (parseInt($(this).val())) {
				case 2: // Quarterly
					var date = new Date(new Date(now.setMonth(now.getMonth() + 3)).setDate(now.getDate() - 1));
					break;

				case 3: // Semi-Yearly
					var date = new Date(new Date(now.setMonth(now.getMonth() + 6)).setDate(now.getDate() - 1));
					break;

				case 4: // Yearly
					var date = new Date(new Date(now.setFullYear(now.getFullYear() + 1)).setDate(now.getDate() - 1));
					break;

				case 5: // Bi-Yearly
					var date = new Date(new Date(now.setFullYear(now.getFullYear() + 2)).setDate(now.getDate() - 1));
					break;

				case 6: // Tri-Yearly
					var date = new Date(new Date(now.setFullYear(now.getFullYear() + 3)).setDate(now.getDate() - 1));
					break;

				default: // Monthly
					var date = new Date(new Date(now.setMonth(now.getMonth() + 1)).setDate(now.getDate() - 1));
			}

			$('input[name="due-date"]').datepicker('update', date.getFullYear() + '-' + _this.zeroPad(date.getMonth() + 1, 2) + '-' + _this.zeroPad(date.getDate(), 2));
		});

		$('input[name="due-date"]').datepicker({
			todayHighlight: true,
			format: 'yyyy-mm-dd',
			autoclose: true,
		});

		$.get(
			'./machine.json?action=city',
			function (data) {
				$('input[name="city"]')
					.attr({
						autocomplete: 'off',
						autocapitalize: 'off',
					})
					.autoComplete({
						source: data,
					});
			},
			'json'
		);

		table = $('#table-machine')
			.on('processing.dt', function () {
				$('html, body').scrollTop(0);
			})
			.DataTable({
				autoWidth: false,
				responsive: true,
				processing: true,
				serverSide: true,
				pageLength: ($.cookie('page-length') ? $.cookie('page-length') : 10),
				dom: '<"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
				ajax: './machine.json',
				language: {
					lengthMenu: 'Show _MENU_ records',
					zeroRecords: '<i class="bi bi-info-circle"></i> No data available.',
					paginate: {
						first: 'First',
						last: 'Last',
						next: '<i class="bi bi-chevron-right"></i>',
						previous: '<i class="bi bi-chevron-left"></i>',
					},
				},
				order: [1, 'asc'],
				columnDefs: [
					{
						targets: 0,
						className: 'align-middle',
						orderable: false,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							return '<div class="form-check"><input class="form-check-input" type="checkbox" name="machineId[]" value="' + parts[0] + '" data-label="' + parts[1] + '"></div>';
						},
					},
					{
						targets: 1,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							var badgeClass = 'text-bg-primary';

							switch (parts[1]) {
								case 'OpenVZ':
									badgeClass = 'text-bg-success';
									break;

								case 'HyperV':
									badgeClass = 'text-bg-secondary';
									break;

								case 'KVM':
									badgeClass = 'text-bg-info';
									break;

								case 'LXD':
									badgeClass = 'text-bg-light';
									break;

								case 'VMWare':
									badgeClass = 'text-bg-warning';
									break;

								case 'XEN':
									badgeClass = 'text-bg-danger';
									break;
							}

							return parts[0] + '<p><span class="badge badge-sm ' + badgeClass + '">' + parts[1] + '</span>' + (parts[2] == 1 ? '<span class="badge badge-sm border border-1 border-warning text-warning ms-2">NAT</span>' : '') + (parts[3] == 1 ? '<span class="badge text-primary" data-bs-toggle="tooltip" data-bs-title="Hidden"><i class="bi bi-eye-slash"></i></span>' : '') + '</p>';
						},
					},
					{
						targets: 2,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');
							var ipFields = '';

							var addresses = parts[3].split(',');

							if (addresses.length == 1) {
								ipFields = '<span class="text-muted" style="font-size: .75em">' + addresses[0] + ' <a href="javascript:;" class="btn-copy" data-clipboard-text="' + addresses[0] + '"><i class="bi bi-copy"></i></a></span>';
							} else {
								ipFields = '<button class="btn border-0 p-0 dropdown-toggle" data-bs-toggle="dropdown" style="font-size: .75em">' + addresses[0] + '</button><ul class="dropdown-menu">';
								$.each(addresses, function (i, address) {
									ipFields += '<li><a class="dropdown-item btn-copy" href="javascript:;" data-clipboard-text="' + address + '">' + address + '</a></li>';
								});
								ipFields += '</ul>';
							}

							return '<div class="text-nowrap" data-bs-toggle="tooltip" data-bs-title="' + parts[2] + '"><span class="fi fi-' + parts[1].toLowerCase() + ' me-2"></span>' + parts[0] + '</div>' + ((parts[4] == 0) ? '<i class="bi bi-exclamation text-danger" data-bs-toggle="tooltip" data-bs-title="Duplicated"></i>' : '') + ipFields;
						},
					},
					{
						targets: 3,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							return _this.formatBytes(data * 1024 * 1024);
						},
					},
					{
						targets: 4,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							var badgeClass = 'text-bg-primary';

							switch (parts[1]) {
								case 'NVMe':
									badgeClass = 'text-bg-warning';
									break;

								case 'SSD':
									badgeClass = 'text-bg-danger';
									break;
							}

							return _this.formatBytes(parts[0] * 1024 * 1024) + '<p><span class="badge badge-sm ' + badgeClass + '">' + parts[1] + '</span></p>';
						},
					},
					{
						targets: 5,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							return '<button class="btn border-0 p-0 dropdown-toggle" data-bs-toggle="dropdown">' + parts[0] + '</button>' + '<ul class="dropdown-menu">' + '	<li><a class="dropdown-item" href="' + parts[1] + '" target="_blank"><i class="bi bi-globe-americas"></i> Website</a></li>' + '	<li><a class="dropdown-item" href="' + parts[2] + '" target="_blank"><i class="bi bi-menu-button-wide"></i> Control Panel</a></li>' + '</ul>';
						},
					},
					{
						targets: 6,
						className: 'text-end',
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							var cycle = '';
							var badgeClass = 'text-bg-primary';

							switch (parseInt(parts[2])) {
								case 1: // Monthly
									cycle = 'Monthly';
									badgeClass = 'text-bg-warning';
									break;

								case 2: // Quarterly
									cycle = 'Quarterly';
									badgeClass = 'text-bg-info';
									break;

								case 3: // Semi-Yearly
									cycle = 'Semi-Yearly';
									badgeClass = 'text-bg-dark';
									break;

								case 4: // Yearly
									cycle = 'Yearly';
									badgeClass = 'text-bg-success';
									break;

								case 5: // Bi-Yearly
									cycle = 'Bi-Yearly';
									badgeClass = 'text-bg-primary';
									break;

								case 6: // Tri-Yearly
									cycle = 'Tri-Yearly';
									badgeClass = 'text-bg-danger';
									break;
							}

							return '<span class="badge ' + badgeClass + ' d-md-none me-1">' + cycle.slice(0, 1) + '</span><span class="badge text-bg-light">' + parts[0] + '</span> ' + (parts[1] / 100).toFixed(2) + '<p class="d-none d-md-block"><span class="badge ' + badgeClass + '">' + cycle + '</span></p>';
						},
					},
					{
						target: 7,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');
							var duration = _this.duration(parts[1]);
							var badge = '';
							var toolTipClass = 'tooltip-primary';

							if (duration.match(/ago/)) {
								badge = '<i class="bi bi-exclamation-circle-fill text-danger ms-2"></i>';
								toolTipClass = 'tooltip-danger';
							} else if (parseInt(duration.replace(/\D/g, '')) < 7) {
								badge = '<i class="bi bi-exclamation-triangle-fill text-warning ms-2"></i>';
								toolTipClass = 'tooltip-warning';
							}

							return '<button class="btn border-0 p-0 dropdown-toggle" data-bs-toggle="dropdown"><span data-bs-custom-class="' + toolTipClass + '" data-bs-toggle="tooltip" data-bs-title="' + duration + '">' + parts[1] + badge + '</span></button>' + '<ul class="dropdown-menu">' + '	<li><a class="dropdown-item renew" href="#" data-id="' + parts[0] + '"><i class="bi bi-arrow-repeat"></i> Renew</a></li>' + '</ul>';
						},
					},
					{
						targets: 8,
						className: 'align-middle text-end',
						orderable: false,
						render: function (data, type) {
							if (type != 'display') {
								return data;
							}

							var parts = data.split(';');

							return '<button type="button" class="btn btn-outline-dark btn-sm btn-edit mb-1 mb-md-0" data-id="' + parts[0] + '"><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-danger btn-sm btn-delete ms-1" data-id="' + parts[0] + '" data-label="' + parts[1] + '"><i class="bi bi-trash"></i></button>';
						},
					},
				],
				drawCallback: function () {
					var api = this.api();
					var clipboard = new ClipboardJS('.btn-copy');

					clipboard.on('success', function (e) {
						$(e.trigger).parent().fadeOut(100).fadeIn(200);
					});

					$('#btn-action').toggleClass('disabled', true);

					$('#table-machine thead input[type="checkbox"]')
						.prop('indeterminate', false)
						.prop('checked', false)
						.prop('disabled', !api.rows({ page: 'current' }).data().length)
						.off('change')
						.on('change', function () {
							$('#table-machine tbody input[type="checkbox"]').prop('checked', $(this).is(':checked')).trigger('change');
						});

					$('#table-machine tbody input[type="checkbox"]')
						.off('change')
						.on('change', function () {
							var length = $('#table-machine tbody input[type="checkbox"]').length;
							var checked = $('#table-machine tbody input[type="checkbox"]:checked').length;

							if ($(this).is(':checked')) {
								$(this).closest('tr').addClass('table-light');
							} else {
								$(this).closest('tr').removeClass('table-light');
							}

							$('#table-machine thead input[type="checkbox"]')
								.prop('indeterminate', checked > 0 && checked < length)
								.prop('checked', checked == length);

							$('#btn-action').toggleClass('disabled', checked == 0);
						});

					$('[data-bs-toggle="tooltip"]').tooltip();

					$('.dropdown-menu a.renew').on('click', function (e) {
						e.preventDefault();

						var machineId = $(this).data('id');

						$.post(
							'./machine.json',
							{
								__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
								action: 'renew',
								id: machineId,
							},
							function (response) {
								$('.toast-container').empty();

								$.each(response.labels, function (i, label) {
									var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is renewed to ' + response.due_dates[i] + '.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

									$('.toast-container').append($toast);

									$toast.toast('show');
								});

								table.draw(false);
							}
						)
							.fail('Connection lost, please try again.')
							.always(function (response) {
								$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
							});
					});

					$('.btn-edit').on('click', function (e) {
						e.preventDefault();

						var id = $(this).data('id');
						var $btn = $(this);

						$btn.data('html', $btn.html())
							.css({
								width: $btn.outerWidth(),
								height: $btn.outerHeight(),
							})
							.prop('disabled', true)
							.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

						$.get(
							'./machine.json',
							{
								id: $(this).data('id'),
								action: 'single',
							},
							function (response) {
								if (response.length == 0) {
									return;
								}

								$('#modal-machine .modal-title').html('Edit Machine');
								$('#modal-machine .modal-footer .btn-primary').css({width: 'auto'}).html('Save Changes');

								$('input[name="is-hidden"]').prop('checked', response.is_hidden === 1);
								$('input[name="label"]').val(response.label);
								$('select[name="provider"]').val(response.provider_id).trigger('change');
								$('select[name="virtualization"]').val(response.virtualization).trigger('change');
								$('input[name="ip"]').val(response.ip_address);
								$('input[name="is-nat"]').prop('checked', response.is_nat === 1);
								$('input[name="bandwidth"]').val(response.bandwidth);
								$('select[name="bandwidth-unit"]').val(response.bandwidth_unit);
								$('select[name="country"]').val(response.country_code).trigger('change');
								$('input[name="city"]').val(response.city_name);
								$('input[name="speed"]').val(response.cpu_speed);
								$('input[name="core"]').val(response.cpu_core);
								$('input[name="memory"]').val(response.memory);
								$('select[name="memory-unit"]').val(response.memory_unit);
								$('input[name="swap"]').val(response.swap);
								$('select[name="swap-unit"]').val(response.swap_unit);
								$('select[name="disk-type"]').val(response.disk_type);
								$('input[name="disk-space"]').val(response.disk_space);
								$('select[name="disk-space-unit"]').val(response.disk_space_unit);
								$('select[name="currency"]').val(response.currency_code);
								$('input[name="price"]').val(response.price).trigger('input');
								$('select[name="cycle"]').val(response.payment_cycle_id);
								$('input[name="due-date"]').val(response.due_date);
								$('textarea[name="notes"]').val(response.notes);

								var ipList = response.ip_address.split(',');

								if (ipList.length > 0) {
									$.each(ipList, function (i, ip) {
										var $item = $('<div class="badge bg-secondary-subtle text-dark p-2 ps-3 py-3 d-flex w-100 mb-1">');
										var $close = $('<i class="bi bi-x ms-auto pointer">').on('click', function (e) {
											e.preventDefault();

											var ipList = $('input[name="ip"]').val().split(',');
											var index = ipList.indexOf(ip);

											if (index !== -1) {
												ipList.splice(index, 1);
												$('input[name="ip"]').val(ipList.filter((elm) => elm).join(','));
											}

											$item.remove();
										});

										$('#btn-add-ip').closest('div').next('.overflow-y-scroll').prepend($item.append(ip).append($close));
									});
								}

								$('#modal-machine').modal('show');

								$('#btn-add-ip')
									.closest('div')
									.next('.overflow-y-scroll')
									.css({
										maxHeight: $('#btn-add-ip').closest('div').next('.overflow-y-scroll').find('.border:first').outerHeight() * 3,
									});

								$('#modal-machine .modal-footer .btn-primary')
									.off('click')
									.on('click', function (e) {
										e.preventDefault();

										var $btn = $(this);
										var values = $('#modal-machine form').serialize();

										$btn.data('html', $btn.html())
											.css({
												width: $btn.outerWidth(),
												height: $btn.outerHeight(),
											})
											.prop('disabled', true)
											.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

										$('#modal-machine input, #modal-machine select, #modal-machine textarea, #modal-machine .btn').prop('disabled', true);

										$('#modal-machine .is-invalid').removeClass('is-invalid');

										$.post(
											'./machine.json',
											values + '&id=' + id + '&__CSRF_TOKEN__=' + $('input[name="__CSRF_TOKEN__"]').val(),
											function (response) {
												if (Object.keys(response.errors).length > 0) {
													$.each(response.errors, function (key) {
														$('#modal-machine [name="' + key + '"]').addClass('is-invalid');
														$('#modal-machine [name="' + key + '"]')
															.parent('.form-floating')
															.addClass('is-invalid');
													});

													$('#modal-machine').scrollTop(0);

													return;
												}

												$('.toast-container').empty();

												var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + $('input[name="label"]').val() + '</strong> is modified.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

												$('.toast-container').append($toast);

												$toast.toast('show');

												$('#modal-machine').modal('hide');

												// Refresh city list
												$.get(
													'./machine.json?action=city',
													function (data) {
														$('input[name="city"]')
															.autoComplete({
																source: data,
															})
															.refresh();
													},
													'json'
												);

												// Refresh table
												table.draw(false);
											},
											'json'
										)
											.fail(function () {
												alert('Connection lost, please try again.');
											})
											.always(function (response) {
												$btn.prop('disabled', false).html($btn.data('html'));
												$('#modal-machine input, #modal-machine select, #modal-machine textarea, #modal-machine .btn').prop('disabled', false);

												$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
											});
									});
							}
						)
							.fail(function () {
								alert('Connection lost, please try again.');
							})
							.always(function () {
								$btn.prop('disabled', false).html($btn.data('html'));
							});
					});

					$('.btn-delete').on('click', function (e) {
						e.preventDefault();

						var ids = [$(this).data('id')];
						var labels = [$(this).data('label')];

						$('#modal-confirmation .modal-body').html('Are you sure you want to delete the following machine? <ul class="mt-3 ms-3"><li>' + labels.join('</li><li>') + '</li></ul>');

						$('#modal-confirmation .btn-danger')
							.off('click')
							.on('click', function (e) {
								e.preventDefault();

								var $btn = $(this);

								$btn.data('html', $btn.html())
									.css({
										width: $btn.outerWidth(),
										height: $btn.outerHeight(),
									})
									.prop('disabled', true)
									.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

								$.post(
									'./machine.json',
									{
										__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
										action: 'delete',
										id: ids,
									},
									function (response) {
										$('.toast-container').empty();

										$.each(response.labels, function (i, label) {
											var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is deleted.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

											$('.toast-container').append($toast);

											$toast.toast('show');
										});

										$('#modal-confirmation').modal('hide');
										table.draw(false);
									},
									'json'
								)
									.fail(function () {
										alert('Connection lost, please try again.');
									})
									.always(function (response) {
										$btn.prop('disabled', false).html($btn.data('html'));
										$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
									});
							});

						$('#modal-confirmation').modal('show');
					});
				},
			});

		$('#action-hide').on('click', function (e) {
			e.preventDefault();

			var ids = [];

			$('#table-machine tbody input[type="checkbox"]:checked').each(function (i, input) {
				ids.push($(input).val());
			});

			$.post(
				'./machine.json',
				{
					__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
					action: 'hide',
					id: ids,
				},
				function (response) {
					$('.toast-container').empty();

					$.each(response.labels, function (i, label) {
						var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is set to hidden.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

						$('.toast-container').append($toast);

						$toast.toast('show');
					});

					table.draw(false);
				}
			)
				.fail('Connection lost, please try again.')
				.always(function (response) {
					$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
				});
		});

		$('#action-unhide').on('click', function (e) {
			e.preventDefault();

			var ids = [];

			$('#table-machine tbody input[type="checkbox"]:checked').each(function (i, input) {
				ids.push($(input).val());
			});

			$.post(
				'./machine.json',
				{
					__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
					action: 'unhide',
					id: ids,
				},
				function (response) {
					$('.toast-container').empty();

					$.each(response.labels, function (i, label) {
						var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is set to unhide.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

						$('.toast-container').append($toast);

						$toast.toast('show');
					});

					table.draw(false);
				}
			)
				.fail('Connection lost, please try again.')
				.always(function (response) {
					$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
				});
		});

		$('#action-delete').on('click', function (e) {
			e.preventDefault();

			var ids = [];
			var labels = [];

			$('#table-machine tbody input[type="checkbox"]:checked').each(function (i, input) {
				ids.push($(input).val());
				labels.push($(input).data('label'));
			});

			$('#modal-confirmation .modal-body').html('Are you sure you want to delete the following machine' + (ids.length > 1 ? 's' : '') + '? <ul class="mt-3 ms-3"><li>' + labels.join('</li><li>') + '</li></ul>');

			$('#modal-confirmation .btn-danger')
				.off('click')
				.on('click', function (e) {
					e.preventDefault();

					var $btn = $(this);

					$btn.data('html', $btn.html())
						.css({
							width: $btn.outerWidth(),
							height: $btn.outerHeight(),
						})
						.prop('disabled', true)
						.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

					$.post(
						'./machine.json',
						{
							__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
							action: 'delete',
							id: ids,
						},
						function (response) {
							$('.toast-container').empty();

							$.each(response.labels, function (i, label) {
								var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is deleted.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

								$('.toast-container').append($toast);

								$toast.toast('show');
							});

							$('#modal-confirmation').modal('hide');
							table.draw(false);
						},
						'json'
					)
						.fail(function () {
							alert('Connection lost, please try again.');
						})
						.always(function (response) {
							$btn.prop('disabled', false).html($btn.data('html'));
							$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
						});
				});

			$('#modal-confirmation').modal('show');
		});

		$('#action-renew').on('click', function (e) {
			e.preventDefault();

			var ids = [];

			$('#table-machine tbody input[type="checkbox"]:checked').each(function (i, input) {
				ids.push($(input).val());
			});

			$.post(
				'./machine.json',
				{
					__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
					action: 'renew',
					id: ids,
				},
				function (response) {
					$('.toast-container').empty();

					$.each(response.labels, function (i, label) {
						var $toast = $('<div id="toast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">').html('<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill text-success"></i> <strong>' + label + '</strong> is renewed to ' + response.due_dates[i] + '.</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>');

						$('.toast-container').append($toast);

						$toast.toast('show');
					});

					table.draw(false);
				}
			)
				.fail('Connection lost, please try again.')
				.always(function (response) {
					$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
				});
		});
	}

	zeroPad(number, digit) {
		return number.toString().padStart(digit, '0');
	}

	formatBytes(bytes) {
		var i = bytes === 0 ? 0 : Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
	}

	formatNumber(number) {
		return number.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ',');
	}

	duration(date) {
		var parts = date.split('-');
		var duration = Math.floor((new Date() - new Date(parts[0], parts[1] - 1, parts[2])) / (60 * 60 * 24 * 1000));

		if (duration === 0) {
			return 'Now';
		}

		return duration > 0 ? duration + ' day' + (duration > 1 ? 's' : '') + ' ago' : duration * -1 + ' day' + (duration < -1 ? 's' : '') + ' later';
	}
}

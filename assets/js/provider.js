class Provider {
	constructor() {
		let _this = this;

		$('#menu-provider').on('click', function (e) {
			e.preventDefault();
			$('#modal-provider').modal('show');
		});

		$('#btn-new-provider').on('click', function (e) {
			e.preventDefault();
			$('#page-provider').addClass('d-none');
			$('#page-new-provider').removeClass('d-none');

			$.get('./provider.json?action=control-panel', function (names) {
				$('#page-new-provider input[name="cp"]').autoComplete({
					source: names,
				}).refresh();
			});
		});

		$('#page-new-provider .btn-outline-secondary').on('click', function (e) {
			e.preventDefault();
			_this.discardNew();
		});

		$('#page-new-provider .btn-primary').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var values = $('#page-new-provider form').serialize();

			$btn.data('html', $btn.html())
				.css({
					width: $btn.outerWidth(),
					height: $btn.outerHeight(),
				})
				.prop('disabled', true)
				.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

			$('#page-new-provider input').prop('disabled', true);

			$('#page-new-provider .is-invalid').removeClass('is-invalid');
			$('#page-new-provider .invalid-feedback').remove();

			$.post(
				'./provider.json',
				values + '&__CSRF_TOKEN__=' + $('input[name="__CSRF_TOKEN__"]').val(),
				function (response) {
					if (Object.keys(response.errors).length > 0) {
						$.each(response.errors, function (key, value) {
							$('#page-new-provider [name="' + key + '"]').addClass('is-invalid');
							$('#page-new-provider [name="' + key + '"]')
								.parent('.form-floating')
								.addClass('is-invalid');
							$('#page-new-provider [name="' + key + '"]').after('<div class="invalid-feedback">' + value + '</div>');
						});

						return;
					}

					_this.discardNew();
					_this.rebuildList();
					_this.refreshSelectList();
				},
				'json'
			)
				.fail(function () {
					alert('Connection lost, please try again.');
				})
				.always(function (response) {
					$btn.prop('disabled', false).html($btn.data('html'));

					$('#page-new-provider input').prop('disabled', false);

					$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
				});
		});

		$('#modal-provider').on('hidden.bs.modal', function () {
			_this.discardNew();
		});

		$('#modal-provider').on('show.bs.modal', function () {
			_this.rebuildList();

			// Restore provider list overlay
			$('#provider-list .overlay').remove();
			$('#provider-list .overflow-hidden')
				.addClass('overflow-auto')
				.removeClass('overflow-hidden');

			$('#provider-list tr.table-light').removeClass('table-light');
		});

		$('input[name="search-provider"]').on('input', function () {
			var $input = $(this);

			// Restore table rows
			_this.disableEditMode();

			$.each($('#page-provider table tbody tr.pointer'), function (i, tr) {
				$(tr).toggleClass('search-not-match', !$(tr).text().toLowerCase().includes($input.val().toLowerCase()));
			});
		});

		this.refreshSelectList();
	}

	refreshSelectList() {
		$('select[name="provider"]')
			.empty()
			.append('<option selected disabled></option>');


		$.get('./provider.json', function (data) {
			if (data.length > 0) {
				$.each(data, function (key, value) {
					$('select[name="provider"]').append('<option value="' + value.provider_id + '">' + value.name + '</option>');
				});
			}
		});
	}

	discardNew() {
		$('#page-new-provider .is-invalid').removeClass('is-invalid');
		$('#page-new-provider .invalid-feedback').remove();
		$('#page-new-provider input').val('');
		$('#page-provider').removeClass('d-none');
		$('#page-new-provider').addClass('d-none');
	}

	rebuildList() {
		let _this = this;

		$('#provider-list').addClass('d-none');
		$('#page-provider .loader').removeClass('d-none');
		$('#provider-list input[name="search-provider"]').val('');
		$('#provider-list table tbody').empty();

		this.getControlPanelNames(function (controlPanelNames) {
			$.get('./provider.json', function (data) {
				$('#page-provider .loader').addClass('d-none');
				$('#page-provider').find('.alert').remove();

				if (data.length > 0) {
					$('#provider-list').removeClass('d-none');

					$.each(data, function (key, value) {
						var $tr = $('<tr class="pointer">')
							.append('<td class="editable text-truncate">' + value.name + '</td>')
							.append('<td class="editable text-truncate text-muted">' + value.website + '</td>')
							.append('<td class="editable text-truncate text-muted">' + value.control_panel_name + '</td>')
							.append('<td class="editable text-truncate text-muted">' + value.control_panel_url + '</td>')
							.append('<td class="editable text-end text-muted">' + value.total_machine + '</td>');

						var $edit = $('<i class="bi bi-pencil me-1 invisible pointer">').on('click', function (e) {
							e.preventDefault();
							_this.enableEditMode($(this).parent());
						});

						var $delete = $('<i class="bi bi-trash invisible pointer">').on('click', function (e) {
							e.preventDefault();
							var $dropdown = $('<div class="dropdown-menu dropdown-confirm show">');
							var $overlay = $('<div class="overlay position-absolute top-0 start-0 opacity-50 bg-light w-100 h-100">');

							var $content = $('<div class="p-2">')
								.append('<h6>Confirm to delete <span class="text-danger">' + value.name + '</span>?</h6>');

							if (value.total_machine > 0) {
								$content.append('<small class="d-block">' + value.total_machine + ' machine' + ((value.total_machine > 1) ? 's' : '') + ' owned by this provider will be orphaned.</small>');
							}

							var $no = $('<button class="btn btn-sm btn-outline-secondary" style="padding:.1rem 1rem;margin-right:.5rem">')
								.html('No')
								.on('click', function (e) {
									e.preventDefault();
									$overlay.remove();
									$('#provider-list .overflow-hidden')
										.addClass('overflow-auto')
										.removeClass('overflow-hidden');

									$(this).closest('tr').removeClass('table-light');

									$dropdown.remove();
								});

							var $yes = $('<button class="btn btn-sm btn-danger" style="padding:.1rem 1rem">')
								.html('Yes')
								.on('click', function (e) {
									e.preventDefault();
									$overlay.remove();
									$('#provider-list .overflow-hidden')
										.addClass('overflow-auto')
										.removeClass('overflow-hidden');

									$(this).closest('tr').removeClass('table-light');

									$tr.closest('tr').remove();
									$tr.remove();

									$.post('./provider.json', {
										id: value.provider_id,
										__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
										action: 'delete',
									})
										.fail(function() {
											alert('Connection lost, please try again.');
										})
										.always(function (response) {
											$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
										});
								});

							$('.dropdown-confirm').remove();
							$(this).after($dropdown
								.append($content)
								.append($('<div class="mt-1 text-center">').append($no).append($yes)));

							$dropdown.offset({
								top: $(this).offset().top + $(this).outerHeight() - $dropdown.outerHeight(),
								left: $(this).offset().left - $(this).outerWidth() - $dropdown.outerWidth(),
							});

							$('#provider-list .overflow-auto')
								.removeClass('overflow-auto')
								.addClass('overflow-hidden')
								.prepend($overlay);

							$(this).closest('tr').addClass('table-light');
						});

						var $td = $('<td class="text-end" style="width: 60px;">').append($edit).append($delete);

						var $controlPanel = $('<input type="text" name="cp" value="' + value.control_panel_name + '" data-value="' + value.control_panel_name + '" class="form-control form-control-sm" autocomplete="off" autocapitalize="off">');

						var $trEdit = $('<tr class="edit d-none">')
							.append('<td><input type="text" name="name" value="' + value.name + '" data-value="' + value.name + '" class="form-control form-control-sm"></td>')
							.append('<td><input type="text" name="website" value="' + value.website + '" data-value="' + value.website + '" class="form-control form-control-sm"></td>')
							.append($('<td>').append($controlPanel))
							.append('<td><input type="text" name="cpUrl" value="' + value.control_panel_url + '" data-value="' + value.control_panel_url + '" class="form-control form-control-sm"><input type="hidden" name="id" value="' + value.provider_id + '"></td>')
							.append('<td class="text-end">' + value.total_machine + '</td>');

						var $save = $('<i class="bi bi-check me-1 pointer">').on('click', function (e) {
							e.preventDefault();

							// Clear existing errors
							$trEdit.find('.is-invalid').removeClass('is-invalid');

							$.post(
								'./provider.json',
								$trEdit.find(':input').serialize() + '&__CSRF_TOKEN__=' + $('input[name="__CSRF_TOKEN__"]').val(),
								function (response) {
									if (Object.keys(response.errors).length > 0) {
										$.each(response.errors, function (i, error) {
											$trEdit.find('[name="' + i + '"]').addClass('is-invalid');
										});

										return;
									}

									_this.rebuildList();
									_this.refreshSelectList();
								},
								'json'
							)
								.fail(function () {
									alert('Connection lost, please try again.');
								})
								.always(function (response) {
									$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
								});
						});

						var $discard = $('<i class="bi bi-x pointer">').on('click', function (e) {
							e.preventDefault();
							_this.disableEditMode();
						});

						$tr.append($td);

						$tr.find('td.editable').on('click', function (e) {
							e.preventDefault();
							_this.enableEditMode(this);
						});

						$trEdit.find('input[type="text"]').on('keypress', function (e) {
							// Save when enter key pressed
							if (e.which == 13) {
								$save.trigger('click');
							}
						});

						$('#provider-list table tbody')
							.append($tr)
							.append($trEdit.append($('<td class="text-end" style="width: 60px;">').append($save).append($discard)));

						$controlPanel.autoComplete({
							source: controlPanelNames,
						});
					});
				} else {
					$('#provider-list').addClass('d-none').after('<div class="alert alert-light mt-3">No data available.</div>');
				}
			})
				.fail(function () {
					alert('Connection lost, please try again.');
				});
		});
	}

	getControlPanelNames(callback) {
		$.get('./provider.json?action=control-panel', function (data) {
			callback(data);
		});
	}

	disableEditMode() {
		// Restore table rows
		$('#provider-list table tbody tr.d-none').removeClass('d-none');
		$('#provider-list table tbody tr.edit:not(.d-none)').addClass('d-none');
	}

	enableEditMode(td) {
		// Restore table rows
		$('#provider-list table tbody tr.d-none').removeClass('d-none');
		$('#provider-list table tbody tr.edit:not(.d-none)').addClass('d-none');

		// Restore inputs stage
		$('#provider-list table tbody tr').find('.is-invalid').removeClass('is-invalid');

		// Restore inputs value
		$.each($('#provider-list table tbody tr').find('input[type="text"]'), function (i, input) {
			$(input).val($(input).attr('data-value'));
		});

		// Hide display row
		$(td).closest('tr').addClass('d-none');

		// Show edit row
		$(td).closest('tr').next().removeClass('d-none');
	}
}

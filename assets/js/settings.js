class Settings {
	constructor() {
		var _this = this;

		$('#menu-settings').on('click', function (e) {
			e.preventDefault();
			$('#modal-settings').modal('show');
		});

		$('#modal-settings').on('show.bs.modal', function () {
			if ($.cookie('theme')) {
				$('select[name="theme"]').val($.cookie('theme'));
			}

			if ($.cookie('page-length')) {
				$('select[name="page-length"]').val($.cookie('page-length'));
			}

			_this.refreshCurrencyList();
		});

		$('select[name="theme"]').on('change', function () {
			$.cookie('theme', $(this).val());

			window.location.href = window.location.href;
		});

		$('select[name="page-length"]').on('change', function () {
			$.cookie('page-length', $(this).val());

			window.location.href = window.location.href;
		});

		$('#btn-add-currency').on('click', function (e) {
			e.preventDefault();

			_this.refreshCurrencyList();

			var $trNew = $('<tr>').append('<td><input type="text" name="code" value="" class="form-control form-control-sm" maxlength="3"></td>').append('<td><input type="text" name="rate" value="" class="form-control form-control-sm text-end"></td>');

			var $save = $('<i class="bi bi-check pointer me-2">').on('click', function (e) {
				e.preventDefault();

				$trNew.find('.is-invalid').removeClass('is-invalid');

				$.post(
					'./currency.json',
					{
						new: $trNew.find('input[name="code"]').val(),
						rate: $trNew.find('input[name="rate"]').val(),
						__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
					},
					function (response) {
						if (Object.keys(response.errors).length > 0) {
							$.each(response.errors, function (key) {
								$trNew.find('input[name="' + key + '"]').addClass('is-invalid');
							});

							return;
						}

						_this.refreshCurrencyList();
					}
				)
					.fail(function () {
						alert('Connection lost, please try again.');
					})
					.always(function (response) {
						$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
					});
			});

			var $close = $('<i class="bi bi-x pointer">').on('click', function (e) {
				e.preventDefault();
				$trNew.remove();
			});

			$('#table-currency tbody').prepend($trNew.append($('<td class="text-end">').append($save).append($close)));
		});
	}

	refreshCurrencyList() {
		var _this = this;

		$('#table-currency tbody').empty();

		$.get('./currency.json', function (response) {
			if (response.length > 0) {
				$.each(response, function (key, value) {
					var $tr = $('<tr class="display">');
					var $trEdit = $('<tr class="edit d-none">')
						.append('<td><input type="text" name="code" value="' + value.currency_code + '" data-value="' + value.currency_code + '" class="form-control form-control-sm" maxlength="3"></td>')
						.append('<td><input type="text" name="rate" value="' + value.rate + '" data-value="' + value.rate + '" class="form-control form-control-sm text-end"></td>');
					var $edit = $('<i class="bi bi-pencil-square pointer me-2">').on('click', function (e) {
						e.preventDefault();

						$('#table-currency tr.display').toggleClass('d-none', false);
						$('#table-currency tr.edit').toggleClass('d-none', true);

						$tr.toggleClass('d-none', true);
						$trEdit.toggleClass('d-none', false);
					});
					var $delete = $('<i class="bi bi-trash pointer">').on('click', function (e) {
						e.preventDefault();

						var $dropdown = $('<div class="dropdown-menu dropdown-confirm show">');
						var $overlay = $('<div class="overlay position-absolute top-0 start-0 opacity-50 bg-light w-100 h-100">');

						var $content = $('<div class="p-2">').append('<h6>Confirm to delete <span class="text-danger"><strong>' + value.currency_code + '</strong></span>?</h6>');

						var $no = $('<button class="btn btn-sm btn-outline-secondary" style="padding:.1rem 1rem;margin-right:.5rem">')
							.html('No')
							.on('click', function (e) {
								e.preventDefault();
								$overlay.remove();
								$('#currency-list').addClass('overflow-auto').removeClass('overflow-hidden');

								$(this).closest('tr').removeClass('table-light');

								$dropdown.remove();
							});

						var $yes = $('<button class="btn btn-sm btn-danger" style="padding:.1rem 1rem">')
							.html('Yes')
							.on('click', function (e) {
								e.preventDefault();
								$overlay.remove();
								$('#currency-list').addClass('overflow-auto').removeClass('overflow-hidden');

								$(this).closest('tr').removeClass('table-light');

								$tr.closest('tr').remove();
								$tr.remove();

								$.post(
									'./currency.json',
									{
										action: 'delete',
										code: value.currency_code,
										__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
									},
									function () {}
								)
									.fail(function () {
										alert('Connection lost, please try again.');
									})
									.always(function (response) {
										$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
									});
							});

						$('.dropdown-confirm').remove();

						$(this).after($dropdown.append($content).append($('<div class="mt-1 text-center">').append($no).append($yes)));

						$dropdown.offset({
							top: $(this).offset().top + $(this).outerHeight() - $dropdown.outerHeight(),
							left: $(this).offset().left - $(this).outerWidth() - $dropdown.outerWidth(),
						});

						$('#currency-list').removeClass('overflow-auto').addClass('overflow-hidden').prepend($overlay);

						$(this).closest('tr').addClass('table-light');
					});

					var $save = $('<i class="bi bi-check pointer me-2">').on('click', function (e) {
						e.preventDefault();

						$trEdit.find('.is-invalid').removeClass('is-invalid');

						$.post(
							'./currency.json',
							{
								code: value.currency_code,
								new: $trEdit.find('input[name="code"]').val(),
								rate: $trEdit.find('input[name="rate"]').val(),
								__CSRF_TOKEN__: $('input[name="__CSRF_TOKEN__"]').val(),
							},
							function (response) {
								if (Object.keys(response.errors).length > 0) {
									$.each(response.errors, function (key) {
										$trEdit.find('input[name="' + key + '"]').addClass('is-invalid');
									});

									return;
								}

								_this.refreshCurrencyList();
							}
						)
							.fail(function () {
								alert('Connection lost, please try again.');
							})
							.always(function (response) {
								$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
							});
					});

					var $cancel = $('<i class="bi bi-x pointer">').on('click', function (e) {
						e.preventDefault();

						$trEdit.find('input[name="code"]').val($trEdit.find('input[name="code"]').data('value'));
						$trEdit.find('input[name="rate"]').val($trEdit.find('input[name="rate"]').data('value'));
						$trEdit.find('.is-invalid').removeClass('is-invalid');

						$tr.toggleClass('d-none', false);
						$trEdit.toggleClass('d-none', true);
					});

					if (value.currency_code == 'USD') {
						$tr.append('<td class="text-muted"><strong>' + value.currency_code + '</strong></td>')
							.append('<td class="text-end text-muted"><strong>' + value.rate + '</strong></td>')
							.append('<td class="text-end">&nbsp;</td>');

						$('#table-currency tbody').append($tr);
					} else {
						$tr.append('<td>' + value.currency_code + '</td>')
							.append('<td class="text-end">' + value.rate + '</td>')
							.append($('<td class="text-end">').append($edit).append($delete));

						$('#table-currency tbody').append($tr);
						$('#table-currency tbody').append($trEdit.append($('<td class="text-end">').append($save).append($cancel)));
					}
				});
			}
		}).fail(function () {
			alert('Connection lost, please try again.');
		});
	}
}

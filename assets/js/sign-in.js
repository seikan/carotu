$(function () {
	$('#username, #password').on('input', function () {
		$('button[type="submit"]').toggleClass('disabled', !($('#username').val() && $('#password').val()));
	});

	$('#toggle-password').on('click', function () {
		$('#toggle-password i').toggleClass('bi-eye bi-eye-slash');

		$('input[name="password"]').attr('type', function (index, attr) {
			return attr == 'password' ? 'text' : 'password';
		});
	});

	$('button[type="submit"]').on('click', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var values = $('form').serialize();

		$btn.data('html', $btn.html())
			.css({
				width: $btn.outerWidth(),
				height: $btn.outerHeight(),
			})
			.prop('disabled', true)
			.html('<div class="loader-inner ball-beat"><div></div><div></div><div></div></div>');

		$('input')
			.prop('disabled', true);

		$('.is-invalid').removeClass('is-invalid');

		$('.invalid-feedback').html('');

		$.post('./user.json', values, function(response) {
			if (Object.keys(response.errors).length > 0) {
				$.each(response.errors, function (key, value) {
					$('[name="' + key + '"]')
						.addClass('is-invalid');

					$('[name="' + key + '"]').parent('.form-floating')
						.addClass('is-invalid');

					$('#' + key + '-feedback')
						.html(value);
				});

				return;
			}

			window.location.href = './';
		}, 'json')
			.fail(function() {
				alert('Connection lost. Please try again.');
			})
			.always(function(response) {
				$btn.prop('disabled', false).html($btn.data('html'));

				$('input')
					.prop('disabled', false);

				$('input[name="' + response.csrf.name + '"]').val(response.csrf.value);
			});
	});
});

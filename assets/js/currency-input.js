(function ($) {
	$.fn.currencyInput = function (options) {
		var settings = $.extend(
			{
				decimalDigit: 2,
				decimalSymbol: '.',
			},
			options
		);

		$.each(this, function (i, input) {
			// Make sure it's a valid input field
			if (!$(input).is('input')) {
				return;
			}

			$(input).on('input', function () {
				// Accept only numbers and dot
				$(this).val(
					$(this)
						.val()
						.replace(new RegExp('[^0-9' + settings.decimalSymbol + ']', 'g'), '')
				);

				// Allow only one decimal point
				if ($(this).val().indexOf(settings.decimalSymbol) > -1) {
					if (
						$(this)
							.val()
							.match(new RegExp(settings.decimalSymbol.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&'), 'g')).length > 1
					) {
						$(this).val($(this).val().slice(0, -1));
					}
				}

				// Remove decimal symbol for further processing
				$(this).val($(this).val().replace(settings.decimalSymbol, ''));

				if ($(this).val().length > settings.decimalDigit) {
					if ($(this).val().length > settings.decimalDigit + 1) {
						// Remove leading zero
						$(this).val($(this).val().replace(/^0/, ''));
					}

					$(this).val(
						$(this)
							.val()
							.slice(0, settings.decimalDigit * -1) +
							settings.decimalSymbol +
							$(this)
								.val()
								.slice(settings.decimalDigit * -1)
					);
				} else {
					$(this).val('0' + settings.decimalSymbol + '0'.repeat(settings.decimalDigit).slice(0, $(this).val().length * -1) + $(this).val());
				}
			});
		});

		return this;
	};
})(jQuery);

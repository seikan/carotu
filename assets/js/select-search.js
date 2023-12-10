(function ($) {
	$.fn.selectSearch = function (options) {
		$.each(this, function (i, select) {
			var classes = $(select).attr('class').split(' ');
			var currentText = $(select).find('option:selected').text();
			var $dropdown = $('<ul class="dropdown-menu shadow">');
			var $input = $('<input type="text" class="form-control select-search-input" autocomplete="off" autocapitalize="off">');
			var search = typeof $(select).data('search') !== 'undefined';
			var $listContainer = $('<div class="w-100 overflow-x-hidden overflow-y-scroll">');

			// Enable search
			if (search) {
				$input
					.on('input', function () {
						populateOptionItems();
					})
					.on('keypress', function (e) {
						// When enter is pressed
						if (e.which === 13) {
							$listContainer.find('a.active').trigger('click');
						}
					})
					.on('keydown', function (e) {
						// When tab is pressed
						if (e.which === 9) {
							$listContainer.find('a.active').trigger('click');
						}
					});

				$dropdown.append($('<li class="p-2">').append($input));
			}

			$dropdown.append($listContainer);

			classes.push('select-search');

			var $select = $('<div class="' + classes.join(' ') + '">')
				.css({
					height: $(select).outerHeight(),
				})
				.html('<div class="overflow-hidden text-nowrap">' + (currentText.length > 0 ? currentText : '&nbsp;') + '</div>')
				.on('click', function () {
					if ($(this).hasClass('disabled')) {
						return;
					}

					$(this).addClass('focus');

					// Close dropdown menu
					if ($dropdown.hasClass('show')) {
						closeOptionList();
					}

					// Open dropdown menu
					else {
						openOptionList();
						populateOptionItems();
					}
				});

			$(select)
				.addClass('d-none')
				.after($select, $dropdown);

			$(select).on('change', function () {
				$select
					.data('value', $(this).val())
					.find('div')
						.html($(this).find('option:selected').html());
			});

			// On blur event
			$(document).on('mouseup', function (e) {
				if (!$select.is(e.target) && !$input.is(e.target) && !$listContainer.is(e.target)) {
					$select.removeClass('focus');
					$dropdown.removeClass('show');
				}
			});

			$(document).on('keydown', function (e) {
				var items = $listContainer.find('a');

				switch (e.which) {
					// Escape pressed
					case 27:
						closeOptionList();
						break;

					// Up arrow
					case 38:
						if (!$select.hasClass('focus')) {
							e.preventDefault();

							return;
						}

						openOptionList();

						if ($listContainer.find('li a.active').length === 0) {
							$(items[0]).addClass('active');
						} else {
							$.each(items, function (index, item) {
								if ($(item).hasClass('active')) {
									var $activeItem = $(items[(index === 0 ? items.length : index) - 1]);

									$(item).removeClass('active');
									$activeItem.addClass('active');

									var itemTop = $activeItem.position().top + $listContainer.scrollTop() - ($activeItem.outerHeight() * 2);
									var scrollHeight = $listContainer.scrollTop() + $listContainer.outerHeight();

									if (itemTop > scrollHeight || itemTop < $listContainer.scrollTop()) {
										$listContainer.scrollTop($listContainer.scrollTop() + $activeItem.position().top - ($activeItem.outerHeight() * 2));
									}


									return false;
								}
							});
						}
						break;

					// Down arrow
					case 40:
						if (!$select.hasClass('focus')) {
							e.preventDefault();
							break;
						}

						openOptionList();

						// No item selected
						if ($listContainer.find('a.active').length === 0) {
							$(items[0]).addClass('active');
						} else {
							$.each(items, function (index, item) {
								if ($(item).hasClass('active')) {
									var $activeItem = $(items[(index === items.length - 1 ? -1 : index) + 1]);

									$(item).removeClass('active');
									$activeItem.addClass('active');

									var itemTop = $activeItem.position().top + $listContainer.scrollTop() - $activeItem.outerHeight();
									var scrollHeight = $listContainer.scrollTop() + $listContainer.outerHeight();

									if (itemTop > scrollHeight || itemTop < $listContainer.scrollTop()) {
										$listContainer.scrollTop($listContainer.scrollTop() + $activeItem.position().top - ($activeItem.outerHeight() * 2));
									}

									return false;
								}
							});
						}
						break;

					default:
				}
			});

			var observer = new MutationObserver(function(mutations) {
				for (var i=0, mutation; mutation = mutations[i]; i++) {
					// Listen to disabled/enabled events
					if (mutation.attributeName == 'disabled') {
						if (mutation.target.disabled) {
							$select.addClass('disabled');
						} else {
							$select.removeClass('disabled');
						}
					}

					// Listen to class change events
					if (mutation.attributeName == 'class') {
						if ($(mutation.target).hasClass('is-invalid')) {
							$select.addClass('is-invalid');
						} else {
							$select.removeClass('is-invalid');
						}
					}
				};
			});

			observer.observe(select, { attributes: true });

			var openOptionList = function () {
				$dropdown
					.css({
						width: $select.outerWidth(),
					})
					.addClass('show');

				if ($input) {
					$input.val('').focus();
				}

				var maxHeight = $(window).height() - ($select.offset().top + $select.outerHeight() + 100);
				maxHeight = (maxHeight > 300) ? 300 : maxHeight;

				$listContainer.css({
					maxHeight: maxHeight,
				});
			};

			var closeOptionList = function () {
				$dropdown.removeClass('show');
			};

			var populateOptionItems = function () {
				// Clear existing option items
				$listContainer.empty();

				var index = 0;

				$.each($(select).find('option'), function (i, option) {
					if (option.text.toLowerCase().includes($input.val().trim().toLowerCase())) {
						if (option.text.length == 0) {
							index++;
							return;
						}

						var $a = $('<a href="#" class="dropdown-item" data-value="' + option.value + '">')
							.html(option.text)
							.on('click', function (e) {
								e.preventDefault();

								$(select).val(option.value);
								$select
									.data('value', option.value)
									.find('div')
										.html(option.text);

								$dropdown.removeClass('show');
							});

						if ($input.val().length > 0) {
							if (index === 0) {
								$a.addClass('active');
							}
						} else if ($(select).val() === option.value) {
							$a.addClass('active');
						}

						$listContainer.append($('<li class="select-search-item' + ($select.data('value') === option.value ? ' active' : '') + '">').append($a));

						index++;
					}
				});

				if ($listContainer.find('a.active')) {
					var $activeItem = $listContainer.find('a.active');

					if ($activeItem.length > 0) {
						$listContainer.scrollTop($listContainer.scrollTop() + $activeItem.position().top - ($activeItem.outerHeight() * 2));
					}
				}
			};
		});
	};
})(jQuery);

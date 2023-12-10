(function ($) {
	$.fn.autoComplete = function (options) {
		// Container to store input elements
		var inputs = [];

		// Default settings
		var defaults = {
			source: [],
			noResults: 'No result found',
		};

		// User defined settings
		var defines = typeof options === 'object' ? options : {};

		// Merge default and user defined settings
		var settings = $.extend(defaults, defines);

		// Initialize
		var init = function (input) {
			// Make sure it's a valid input field
			if (!$(input).is('input')) {
				return;
			}

			// Make sure source is an array
			if (!Array.isArray(settings.source)) {
				return;
			}

			inputs.push(input);

			var $dropdown = $('<ul class="dropdown-menu shadow auto-complete">');
			var $listContainer = $('<div class="w-100 overflow-x-hidden overflow-y-auto">');

			var openList = function () {
				$dropdown
					.css({
						width: $(input).outerWidth(),
					})
					.addClass('show');

				// Position adjustment only works after displayed
				$dropdown.offset({
					top: $(input).offset().top + $(input).outerHeight(),
				});

				// Make sure the dropdown menu is not out of the viewport
				var maxHeight = $(window).height() - ($(input).offset().top + $(input).outerHeight() + 100);
				maxHeight = maxHeight > 300 ? 300 : maxHeight;

				$listContainer.css({
					maxHeight: maxHeight,
				});
			};

			var closeList = function () {
				$dropdown.removeClass('show');
			};

			var populateListItems = function () {
				// Clear existing option items
				$listContainer.empty();

				var index = 0;

				if (settings.source.length > 0) {
					$.each(settings.source, function (key, value) {
						if (value.toLowerCase().includes($(input).val().trim().toLowerCase())) {
							if (value.length == 0) {
								index++;
								return;
							}

							var $a = $('<a href="#" class="dropdown-item">')
								.html(value.replace(new RegExp('(' + $(input).val().trim() + ')', 'ig'), '<strong>$1</strong>'))
								.on('click', function (e) {
									e.preventDefault();

									$(input).val(value);
									$dropdown.removeClass('show');
								});

							$listContainer.append($('<li>').append($a));

							index++;
						}
					});
				}

				if ($listContainer.is(':empty')) {
					$listContainer.append($('<li>').append('<a href="#" class="dropdown-item disabled">' + settings.noResults + '</a>'));
				}
			};

			$(document).on('keydown', function (e) {
				var items = $listContainer.find('a');

				switch (e.which) {
					// Escape pressed
					case 27:
						closeList();
						break;

					// Up arrow
					case 38:
						if (!$(input).is(':focus')) {
							e.preventDefault();
							break;
						}

						if ($listContainer.find('li a.active').length === 0) {
							$(items[0]).addClass('active');
						} else {
							$.each(items, function (index, item) {
								if ($(item).hasClass('active')) {
									var $activeItem = $(items[(index === 0 ? items.length : index) - 1]);

									$(item).removeClass('active');
									$activeItem.addClass('active');

									if ($activeItem.position().top < $activeItem.outerHeight() * 4) {
										$activeItem[0].scrollIntoView();
									} else if ($activeItem.position().top > $listContainer[0].clientHeight * 2 - $activeItem.outerHeight() * 2) {
										$activeItem[0].scrollIntoView();
									}

									return false;
								}
							});
						}
						break;

					// Down arrow
					case 40:
						if (!$(input).is(':focus')) {
							e.preventDefault();
							break;
						}

						// No item selected
						if ($listContainer.find('a.active').length === 0) {
							$(items[0]).addClass('active');
						} else {
							$.each(items, function (index, item) {
								if ($(item).hasClass('active')) {
									var $activeItem = $(items[(index === items.length - 1 ? -1 : index) + 1]);

									$(item).removeClass('active');
									$activeItem.addClass('active');

									var visibleItemLimit = Math.ceil($listContainer[0].clientHeight / $activeItem.outerHeight());

									if ($activeItem.position().top > $activeItem.outerHeight() * visibleItemLimit) {
										$activeItem[0].scrollIntoView();
									} else if ($activeItem.position().top < 0) {
										$activeItem[0].scrollIntoView();
									}

									return false;
								}
							});
						}
						break;

					default:
				}
			});

			$dropdown.append($listContainer);

			$(input)
				.on('input', function () {
					populateListItems();
					openList();
				})
				.on('blur', function () {
					setTimeout(function () {
						closeList();
					}, 200);
				})
				.on('keypress', function (e) {
					// When enter is pressed
					if (e.which === 13) {
						e.preventDefault();

						$listContainer.find('a.active').trigger('click');
					}
				})
				.on('keydown', function (e) {
					// When tab is pressed
					if (e.which === 9) {
						e.preventDefault();

						$listContainer.find('a.active').trigger('click');
					}
				})
				.after($dropdown);

			$(document).on('wheel', function () {
				// Lost focus on current input when scrolling
				$(input).blur();
			});
		};

		// Destroy the existing input elements
		destroy = function () {
			$.each(inputs, function (i, input) {
				$(input).off('input blur keypress keydown');
				$(input).next('.auto-complete').remove();
			});
		};

		// Re-initialize
		refresh = function () {
			destroy();

			$.each(inputs, function (i, input) {
				init(input);
			});
		};

		this.each(function (i, input) {
			init(input);
		});

		return {
			destroy: destroy,
			refresh: refresh,
		};
	};
})(jQuery);

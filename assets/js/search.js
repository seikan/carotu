(function ($) {
	$.fn.search = function (options) {
		var filterList = [];

		var filters = {
			'Bandwidth': {
				operators: {
					'Equals': '=',
					'Greater Than': '>',
					'Less Than': '<',
				},
				unit: 'GB',
				description: '<code>number</code> <strong>GB</strong>',
			},
			'City': {
				operators: {
					Contains: ':',
					Equals: '=',
				},
				description: '<code>text</code>',
			},
			'Country': {
				operators: {
					Contains: ':',
					Equals: '=',
				},
				description: '<code>ISO 3166-2 code or country name</code>',
			},
			'CPU Core': {
				operators: {
					'Equals': '=',
					'Greater Than': '>',
					'Less Than': '<',
				},
				description: '<code>number</code>',
			},
			'CPU Speed': {
				operators: {
					'Equals': '=',
					'Greater Than': '>',
					'Less Than': '<',
				},
				unit: 'MHz',
				description: '<code>number</code> <strong>MHz</strong>',
			},
			'Disk Type': {
				operators: {
					Equals: '=',
				},
				values: variables.diskTypes,
				description: '',
			},
			'Disk Space': {
				operators: {
					'Equals': '=',
					'Greater Than': '>',
					'Less Than': '<',
				},
				unit: 'GB',
				description: '<code>number</code> <strong>GB</strong>',
			},
			'IP Address': {
				operators: {
					Contains: ':',
					Equals: '=',
				},
				description: '<code>text</code>',
			},
			'Label': {
				operators: {
					Contains: ':',
					Equals: '=',
				},
				description: '<code>text</code>',
			},
			'NAT': {
				operators: {
					Equals: '=',
				},
				values: ['Yes', 'No'],
				description: '',
			},
			'Provider': {
				operators: {
					Contains: ':',
					Equals: '=',
				},
				description: '<code>text</code>',
			},
			'RAM': {
				operators: {
					'Equals': '=',
					'Greater Than': '>',
					'Less Than': '<',
				},
				unit: 'MB',
				description: '<code>number</code> <strong>MB</strong>',
			},
			'Virtualization': {
				operators: {
					Equals: '=',
				},
				values: variables.virtualizations,
				description: '',
			},
			'Visibility': {
				operators: {
					Equals: '=',
				},
				values: ['All', 'Visible', 'Hidden'],
				description: '',
			},
		};

		var settings = $.extend(
			{},
			{
				placeholder: '',
				onFilterChanged: function () {},
			},
			options
		);

		var $dropdown = $('<ul class="dropdown-menu shadow">');

		var $input = $('<input type="text" class="form-control" placeholder="' + settings.placeholder + '" autocomplete="off" autocapitalize="off">')
			.on('focus', function () {
				openDropDownMenu();
				populateMenuItems();
			})
			.on('blur', function () {
				setTimeout(function () {
					if (!$input.is(':focus')) {
						closeDropDownMenu();
					}
				}, 200);
			})
			.on('keypress', function (e) {
				// When enter is pressed
				if (e.which === 13) {
					e.preventDefault();
					expandFilter();
				}
			})
			.on('keydown', function (e) {
				// When tab is pressed
				if (e.which === 9) {
					e.preventDefault();
					expandFilter();
				}
			})
			.on('input', function () {
				$clear.toggleClass('d-none', !$input.val());
				populateMenuItems();
			});

		var $clear = $('<i class="bi bi-x-lg d-none"></i>').on('click', function () {
			$input.val('');
			$(this).addClass('d-none');
		});

		var $filterList = $('<div class="filter-list mt-2">');

		var openDropDownMenu = function () {
			$dropdown.toggleClass('show', true);
		};

		var closeDropDownMenu = function () {
			$dropdown.toggleClass('show', false);
		};

		var populateMenuItems = function () {
			$dropdown
				.toggleClass('show', true) // Make sure dropdown menu is open
				.empty(); // Clear existing items

			if ($input.val().length > 0) {
				var $a = $('<a class="dropdown-item" href="#">')
					.html('<div style="max-width: 300px; white-space: pre-wrap; word-wrap: break-word; word-break: break-word"><strong>Search: </strong>' + $input.val() + '</div>')
					.on('click', function () {
						addFilter('Search', ':', $input.val(), '');

						renderFilterList();

						$input.val('').trigger('input');

						closeDropDownMenu();
					});

				$dropdown.append($('<li class="filter-item filter-search">').append($a));
			}

			$.each(filters, function (name, filter) {
				var skip = false;

				// Filter name not match
				if (!name.toLowerCase().includes($input.val().toLowerCase()) && !$input.val().toLowerCase().includes(name.toLowerCase())) {
					return;
				}

				// Add filter header if not exist
				if ($dropdown.find('.dropdown-header').length == 0) {
					$dropdown.append('<li><h5 class="dropdown-header">Filters</h5></li>');
				}

				// Exact match
				if (name.toLowerCase() == $input.val().toLowerCase()) {
					$.each(filter.operators, function (operator, symbol) {
						var $a = $('<a class="dropdown-item" href="#">')
							.html(name.replace(new RegExp('(' + $input.val() + ')', 'ig'), '<code>$1</code> ' + symbol + '<small class="d-block">' + operator + '</small>'))
							.on('click', function (e) {
								e.preventDefault();

								$input
									.val(name + ' ' + symbol + ' ')
									.trigger('input')
									.focus();
							});

						$dropdown.append($('<li class="filter-item">').append($a));
					});

					return;
				}

				// Check for matching operators
				$.each(filter.operators, function (operator, symbol) {
					if (
						$input
							.val()
							.toLowerCase()
							.includes(name.toLowerCase() + ' ' + symbol)
					) {
						if (typeof filter.values !== 'undefined') {
							$.each(filter.values, function (i, value) {
								var $a = $('<a class="dropdown-item" href="#">')
									.html('<strong>' + name + '</strong> ' + symbol + ' ' + value)
									.on('click', function (e) {
										e.preventDefault();

										$input.val('').trigger('input');

										addFilter(name, symbol, value, (typeof filter.unit !== 'undefined') ? filter.unit : '') ;
										renderFilterList();

										closeDropDownMenu();
									});

								$dropdown.append($('<li class="filter-item">').append($a));
							});
						} else {
							var $a = $('<a class="dropdown-item" href="#">')
								.html('<strong>' + name + '</strong> ' + symbol + ' ' + filter.description)
								.on('click', function (e) {
									$input.focus();
									e.preventDefault();
								});

							$dropdown.append($('<li class="filter-item">').append($a));
						}

						skip = true;

						return false;
					}
				});

				if (skip) {
					return false;
				}

				var $a = $('<a class="dropdown-item" href="#">')
					.html(name.replace(new RegExp('(' + $input.val() + ')', 'ig'), '<code>$1</code>'))
					.on('click', function (e) {
						e.preventDefault();

						$input.val(name).trigger('input').focus();

						populateMenuItems();
					});

				$dropdown.append($('<li class="filter-item">').append($a));
			});
		};

		var addFilter = function (filter, operator, value, unit) {
			var isExist = false;

			// Make sure no delimiter is used
			value = value.replace(/;/g, '').trim();

			if (value.length === 0) {
				return;
			}

			if (Object.keys(filterList).length > 0) {
				$.each(filterList, function (i, list) {
					if (list.filter == filter && list.operator == operator && list.value == value) {
						isExist = true;

						return false;
					}
				});
			}

			if (!isExist) {
				filterList.push({
					filter: filter,
					operator: operator,
					value: value,
					unit: unit,
				});

				settings.onFilterChanged(filterList);
			}
		};

		var removeFilter = function (filter, operator, value) {
			if (Object.keys(filterList).length > 0) {
				$.each(filterList, function (i, list) {
					if (list.filter == filter && list.operator == operator && list.value == value) {
						filterList.splice(i, 1);
						return false;
					}
				});
			}
		};

		var renderFilterList = function () {
			$filterList.empty();

			if (Object.keys(filterList).length > 0) {
				$.each(filterList, function (i, list) {
					var $close = $('<i class="bi bi-x">').on('click', function () {
						removeFilter(list.filter, list.operator, list.value);
						renderFilterList();

						settings.onFilterChanged(filterList);
					});

					$filterList.append(
						$('<span class="badge text-bg-warning me-1">')
							.html(list.filter + ' ' + list.operator + ' ' + list.value + ((typeof list.unit !== 'undefined') ? (' ' + list.unit) : ''))
							.append($close)
					);
				});

				var $clearFilter = $('<button class="btn btn-sm btn-outline-warning p-0 px-1 mt-1 float-end" type="button">')
					.html('<i class="bi bi-x-circle-fill"></i> Clear Filters')
					.on('click', function () {
						filterList = [];
						$input.val('').trigger('input');
						renderFilterList();

						settings.onFilterChanged(filterList);

						closeDropDownMenu();
					});

				$filterList.append($clearFilter).append('<div class="clearfix"></div>');
			}
		};

		var expandFilter = function () {
			// No item is selected
			if ($dropdown.find('li.filter-item a.active').length == 0) {
				// Check if one of the operator exist
				var isFound = false;
				$.each([':', '=', '<', '>'], function (i, operator) {
					if ($input.val().includes(operator)) {
						var parts = $input.val().split(operator);
						var isValidFilter = false;
						var unit = '';

						isFound = true;

						// Check for valid filter
						$.each(filters, function (name, filter) {
							if (parts[0].trim() == name) {
								$.each(filter.operators, function (label, symbol) {
									if (operator == symbol && parts[1].trim().length > 0) {
										isValidFilter = true;
										unit = (typeof filter.unit !== 'undefined') ? filter.unit : '';

										return false;
									}
								});
							}
						});

						if (isValidFilter) {
							addFilter(parts[0].trim(), operator, parts[1].trim(), unit);
						}

						renderFilterList();

						$input.val('').trigger('input');

						closeDropDownMenu();

						return false;
					}
				});

				// Normal search string
				if (!isFound && $input.val().length > 0) {
					addFilter('Search', ':', $input.val(), '');

					renderFilterList();

					$input.val('').trigger('input');

					closeDropDownMenu();
				}
			} else {
				$dropdown.find('li.filter-item a.active').trigger('click');
			}
		};

		this.addClass('d-none').after($('<div class="search">').append('<i class="bi bi-search"></i>').append($input).append($clear).append($dropdown).append($filterList));

		$(document).on('keydown', function (e) {
			switch (e.which) {
				// Escape pressed
				case 27:
					e.preventDefault();
					closeDropDownMenu();
					break;

				// Up arrow
				case 38:
					if ($dropdown.hasClass('show')) {
						e.preventDefault();
					}

					var items = $dropdown.find('li.filter-item a');

					if ($dropdown.find('li.filter-item a.active').length == 0) {
						if (!$(items[0]).text()) {
							$(items[1]).addClass('active');
						} else {
							$(items[0]).addClass('active');
						}
					} else {
						$.each(items, function (index, item) {
							if ($(item).hasClass('active')) {
								$(item).removeClass('active');
								$(items[(index == 0 ? items.length : index) - 1]).addClass('active');

								return false;
							}
						});
					}
					break;

				// Down arrow
				case 40:
					if ($dropdown.hasClass('show')) {
						e.preventDefault();
					}

					var items = $dropdown.find('li.filter-item a');

					if ($dropdown.find('li.filter-item a.active').length == 0) {
						if (!$(items[0]).text()) {
							$(items[1]).addClass('active');
						} else {
							$(items[0]).addClass('active');
						}
					} else {
						$.each(items, function (index, item) {
							if ($(item).hasClass('active')) {
								$(item).removeClass('active');
								$(items[(index == items.length - 1 ? -1 : index) + 1]).addClass('active');

								return false;
							}
						});
					}
					break;
			}
		});
	};
})(jQuery);

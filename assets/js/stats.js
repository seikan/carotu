class Stats {
	constructor() {
		let _this = this;

		// Set default currency based on browser locale
		this.setDefaultCurrency();

		// Open stats modal
		$('#menu-stats').on('click', function (e) {
			e.preventDefault();
			$('#modal-stats').modal('show');
		});

		// Currency change
		$('#stats-currency').on('change', function () {
			_this.loadStats();
		});

		// Load stats when modal is shown
		$('#modal-stats').on('show.bs.modal', function () {
			_this.loadStats();
		});
	}

	setDefaultCurrency() {
		// Get browser locale
		var locale = navigator.language || navigator.userLanguage || 'en-US';
		var currencyMap = {
			'de': 'EUR',
			'fr': 'EUR',
			'es': 'EUR',
			'it': 'EUR',
			'nl': 'EUR',
			'en-GB': 'GBP',
			'en-US': 'USD',
			'ja': 'JPY',
			'zh': 'CNY',
			'ru': 'RUB',
			'pl': 'PLN',
			'tr': 'TRY',
			'br': 'BRL',
			'pt': 'EUR',
			'ch': 'CHF',
			'se': 'SEK',
			'no': 'NOK',
			'dk': 'DKK'
		};

		// Try to match full locale first (e.g., en-GB), then language code (e.g., en)
		var currency = currencyMap[locale] || currencyMap[locale.split('-')[0]] || 'USD';

		// Check if currency exists in dropdown
		if ($('#stats-currency option[value="' + currency + '"]').length > 0) {
			$('#stats-currency').val(currency);
		} else {
			$('#stats-currency').val('USD');
		}
	}

	loadStats() {
		var currency = $('#stats-currency').val() || 'USD';

		// Show loader
		$('#modal-stats .loader').removeClass('d-none');
		$('#stats-content').addClass('d-none');

		$.ajax({
			url: './json.stats?currency=' + currency,
			type: 'GET',
			dataType: 'json',
			success: function (response) {
				if (response.status === 200 && response.data) {
					var data = response.data;

					// Update currency displays
					$('#monthly-currency').text(data.currency);
					$('#yearly-currency').text(data.currency);

					// Update cost cards
					$('#total-machines').text(data.total_machines);
					$('#monthly-cost').text(data.monthly_cost);
					$('#yearly-cost').text(data.yearly_cost);

					// Update provider stats
					if (data.by_provider && data.by_provider.length > 0) {
						var providerHtml = '';
						var totalProviders = 0;

						// Calculate total
						$.each(data.by_provider, function (i, provider) {
							totalProviders += parseInt(provider.count);
						});

						$.each(data.by_provider, function (i, provider) {
							var percentage = (provider.count / totalProviders * 100).toFixed(1);
							providerHtml += '<tr>' +
								'<td>' + (provider.name || 'Unknown') + '</td>' +
								'<td class="text-end">' + provider.count + '</td>' +
								'<td>' +
								'<div class="progress" style="height: 20px;">' +
								'<div class="progress-bar bg-primary" role="progressbar" style="width: ' + percentage + '%" aria-valuenow="' + provider.count + '" aria-valuemin="0" aria-valuemax="' + totalProviders + '">' +
								percentage + '%' +
								'</div>' +
								'</div>' +
								'</td>' +
								'</tr>';
						});
						$('#provider-tbody').html(providerHtml);
					} else {
						$('#provider-tbody').html('<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
					}

					// Update country stats
					if (data.by_country && data.by_country.length > 0) {
						var countryHtml = '';
						var totalCountries = 0;

						// Calculate total
						$.each(data.by_country, function (i, country) {
							totalCountries += parseInt(country.count);
						});

						$.each(data.by_country, function (i, country) {
							var percentage = (country.count / totalCountries * 100).toFixed(1);
							var flagClass = country.country_code ? 'fi fi-' + country.country_code.toLowerCase() : '';
							countryHtml += '<tr>' +
								'<td>' +
								'<span class="' + flagClass + ' me-2"></span>' +
								(country.country_name || 'Unknown') +
								'</td>' +
								'<td class="text-end">' + country.count + '</td>' +
								'<td>' +
								'<div class="progress" style="height: 20px;">' +
								'<div class="progress-bar bg-success" role="progressbar" style="width: ' + percentage + '%" aria-valuenow="' + country.count + '" aria-valuemin="0" aria-valuemax="' + totalCountries + '">' +
								percentage + '%' +
								'</div>' +
								'</div>' +
								'</td>' +
								'</tr>';
						});
						$('#country-tbody').html(countryHtml);
					} else {
						$('#country-tbody').html('<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
					}

					// Hide loader, show content
					$('#modal-stats .loader').addClass('d-none');
					$('#stats-content').removeClass('d-none');
				}
			},
			error: function () {
				$('#modal-stats .loader').addClass('d-none');
				alert('Connection lost, please try again.');
			}
		});
	}
}

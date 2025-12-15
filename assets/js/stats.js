class Stats {
	constructor() {
		let _this = this;

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
						var maxProviderCount = Math.max.apply(Math, data.by_provider.map(function (p) { return p.count; }));

						$.each(data.by_provider, function (i, provider) {
							var percentage = (provider.count / maxProviderCount * 100).toFixed(1);
							providerHtml += '<tr>' +
								'<td>' + (provider.name || 'Unknown') + '</td>' +
								'<td class="text-end">' + provider.count + '</td>' +
								'<td>' +
								'<div class="progress" style="height: 20px;">' +
								'<div class="progress-bar bg-primary" role="progressbar" style="width: ' + percentage + '%" aria-valuenow="' + provider.count + '" aria-valuemin="0" aria-valuemax="' + maxProviderCount + '">' +
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
						var maxCountryCount = Math.max.apply(Math, data.by_country.map(function (c) { return c.count; }));

						$.each(data.by_country, function (i, country) {
							var percentage = (country.count / maxCountryCount * 100).toFixed(1);
							var flagClass = country.country_code ? 'fi fi-' + country.country_code.toLowerCase() : '';
							countryHtml += '<tr>' +
								'<td>' +
								'<span class="' + flagClass + ' me-2"></span>' +
								(country.country_name || 'Unknown') +
								'</td>' +
								'<td class="text-end">' + country.count + '</td>' +
								'<td>' +
								'<div class="progress" style="height: 20px;">' +
								'<div class="progress-bar bg-success" role="progressbar" style="width: ' + percentage + '%" aria-valuenow="' + country.count + '" aria-valuemin="0" aria-valuemax="' + maxCountryCount + '">' +
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

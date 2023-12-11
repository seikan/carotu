class IpAddress {
	constructor() {
		this.address4 = /^(\d{1,3}\.){3}\d{1,3}$/;
		this.address6 = /^([\da-f]{1,4}:){7}[\da-f]{1,4}$/i;
	}

	isAddress4(ip) {
		if (this.address4.test(ip)) {
			return ip.split('.').every((part) => parseInt(part) <= 255);
		}

		return false;
	}

	isAddress6(ip) {
		if (this.address6.test(this.expandAddress6(ip))) {
			return ip.split(':').every((part) => part.length <= 4);
		}

		return false;
	}

	expandAddress6(ip) {
		if (ip.match(/:/g) === null) {
			return ip;
		}

		// Check if groups of 8
		if (ip.match(/:/g).length === 7) {
			return ip;
		}

		var currentGroup = 0;
		var groups = [];

		// Split by empty groups
		var parts = ip.split('::');

		parts.forEach(function (part, i) {
			currentGroup += part.split(':').length;
		});

		groups.push(parts[0]);

		for (var i = 0; i < 8 - currentGroup; i++) {
			groups.push('0000');
		}

		groups.push(parts[1]);

		groups
			.filter((ele) => ele)
			.forEach(function (group, i) {
				// Pad leading zeros
				groups[i] = groups[i].toString().padStart(4, '0');
			});

		return groups.join(':');
	}

	isValid(ip) {
		return this.isAddress4(ip) || this.isAddress6(ip);
	}
}

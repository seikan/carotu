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
		if (this.address6.test(ip)) {
			return ip.split(':').every((part) => part.length <= 4);
		}

		return false;
	}

	isValid(ip) {
		return this.isAddress4(ip) || this.isAddress6(ip);
	}
}

<?php

namespace Http;

class Request
{
	/**
	 * Get client IP address.
	 *
	 * @param bool $detectForwardedIp get client ip from forwarded headers
	 *
	 * @return string
	 */
	public function clientIp($detectForwardedIp = false)
	{
		if ($detectForwardedIp) {
			if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE)) {
				return $_SERVER['HTTP_CF_CONNECTING_IP'];
			}

			if (isset($_SERVER['X-Real-IP']) && filter_var($_SERVER['X-Real-IP'], \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE)) {
				return $_SERVER['X-Real-IP'];
			}

			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

				if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE)) {
					return $ip;
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '';
	}

	/**
	 * Get current page URL.
	 *
	 * @return string
	 */
	public function currentUrl()
	{
		return 'http' . (($this->isHttps()) ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . (($_SERVER['SERVER_PORT'] == '80' || ($_SERVER['SERVER_PORT'] == '443' && $this->isHttps())) ? '' : (':' . $_SERVER['SERVER_PORT'])) . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get client user agent.
	 *
	 * @return string
	 */
	public function userAgent()
	{
		return $_SERVER['HTTP_USER_AGENT'] ?? '';
	}

	/**
	 * Get page referer if available.
	 *
	 * @return string
	 */
	public function referer()
	{
		return $_SERVER['HTTP_REFERER'] ?? '';
	}

	/**
	 * Check if current page is HTTPS.
	 *
	 * @return bool
	 */
	public function isHttps()
	{
		return (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? true : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false);
	}

	/**
	 * Get value from a GET request.
	 *
	 * @param string $key        The query name
	 * @param string $default    Default value if not found
	 * @param bool   $allowArray Allow input as array
	 *
	 * @return array|string
	 */
	public function get($key, $default = '', $allowArray = false)
	{
		return (isset($_GET[$key])) ? $this->sanitize($_GET[$key], $allowArray) : $default;
	}

	/**
	 * Get value from a POST request.
	 *
	 * @param string $key        The query name
	 * @param string $default    Default value if not found
	 * @param bool   $allowArray Allow input as array
	 *
	 * @return array|string
	 */
	public function post($key, $default = '', $allowArray = false)
	{
		return (isset($_POST[$key])) ? $this->sanitize($_POST[$key], $allowArray) : $default;
	}

	/**
	 * Check if this is a form post request.
	 *
	 * @return bool
	 */
	public function isPost()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * Get value from a cookie.
	 *
	 * @param string $key        The query name
	 * @param string $default    Default value if cookie not found
	 * @param bool   $allowArray Allow input as array
	 *
	 * @return array|string
	 */
	public function cookie($key, $default = '', $allowArray = false)
	{
		return (isset($_COOKIE[$key])) ? $this->sanitize($_COOKIE[$key], $allowArray) : $default;
	}

	/**
	 * Sanitize text for display to prevent XSS attacks.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function input($text)
	{
		return htmlspecialchars((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Clean up string to display safely in HTML.
	 *
	 * @param string $text       Input string
	 * @param mixed  $allowArray Allow input as array
	 *
	 * @return string
	 */
	private function sanitize($text, $allowArray = false)
	{
		if (\is_array($text) && !$allowArray) {
			return '';
		}

		if (!\is_array($text) && !\is_object($text)) {
			return htmlspecialchars_decode(trim($text));
		}

		foreach ($text as $key => $value) {
			(\is_array($text)) ? $text[$key] = $this->sanitize($value, $allowArray) : $text->{$key} = $this->sanitize($value, $allowArray);
		}

		return $text;
	}
}

<?php

namespace Cookie;

class Cookie
{
	/**
	 * A private key to encrypt cookie values.
	 *
	 * @var string
	 */
	protected $privateKey;

	/**
	 * Initialize cookie object.
	 *
	 * @param string $privateKey a private key to encrypt cookie values
	 *
	 * @return void
	 */
	public function __construct($privateKey)
	{
		if (empty($privateKey)) {
			throw new \Exception(__CLASS__ . ': Missing private key.');
		}

		$this->privateKey = $privateKey;
	}

	/**
	 * Retrieve a cookie value by key.
	 *
	 * @param string $key a key to identify the cookie
	 *
	 * @return string
	 */
	public function get($key)
	{
		return (isset($_COOKIE[$key])) ? $this->decrypt($_COOKIE[$key]) : '';
	}

	/**
	 * Set a cookie value by key and value.
	 *
	 * @param string $key   a key to identify the cookie
	 * @param string $value value to store in cookie
	 * @param int    $days  cookie expiry in days
	 *
	 * @return void
	 */
	public function set($key, $value = null, $days = 30)
	{
		if ($value == null) {
			setcookie($key, '', -1);
		} else {
			setcookie($key, $this->encrypt($value), time() + (86400 * $days), '/', false, true);
		}
	}

	/**
	 * Delete a cookie by key.
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function delete($key)
	{
		$this->set($key, null);
	}

	/**
	 * Destroy all cookies.
	 *
	 * @return void
	 */
	public function destroy()
	{
		foreach ($_COOKIE as $key) {
			setcookie($key, '', -1);
		}
	}

	/**
	 * Encrypt a string.
	 *
	 * @param string $text string to encrypt
	 *
	 * @return string
	 */
	private function encrypt($text)
	{
		if (!\in_array('aes-256-ctr', openssl_get_cipher_methods())) {
			throw new \Exception(__CLASS__ . ': "aes-256-ctr" cipher is not supported.');
		}

		$nonce = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-ctr'));
		$cipherText = openssl_encrypt($text, 'aes-256-ctr', $this->privateKey, OPENSSL_RAW_DATA, $nonce);

		return base64_encode($nonce . $cipherText);
	}

	/**
	 * Decrypt a string.
	 *
	 * @param string $text string to decrypt
	 *
	 * @return string
	 */
	private function decrypt($text)
	{
		$text = base64_decode($text);

		$nonceSize = openssl_cipher_iv_length('aes-256-ctr');

		return openssl_decrypt(mb_substr($text, $nonceSize, null, '8bit'), 'aes-256-ctr', $this->privateKey, OPENSSL_RAW_DATA, mb_substr($text, 0, $nonceSize, '8bit'));
	}
}

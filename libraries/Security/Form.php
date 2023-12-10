<?php

namespace Security;

class Form
{
	private $inputName = '__CSRF_TOKEN__';
	private $checkIp = false;
	private $timer = 3600;

	/**
	 * Initialize NoCSRF object.
	 *
	 * @param bool $checkIp Make sure client IP is same within the session
	 * @param int  $timer   Define expiration time for CSRF token in seconds
	 */
	public function __construct($checkIp = false, $timer = 3600)
	{
		// Start a session
		if (session_status() == PHP_SESSION_NONE) {
			// Make sure the session Id is valid
			if (isset($_COOKIE[session_name()]) && !preg_match('/^[a-z0-9]{26,32}$/', $_COOKIE[session_name()])) {
				return;
			}

			session_start();
		}

		$this->checkIp = ($checkIp) ? true : false;

		if (\is_int($timer)) {
			$this->timer = $timer;
		}
	}

	/**
	 * Validate a form submission.
	 *
	 * @return int
	 */
	public function validate()
	{
		// No CSRF post field is found
		if (!isset($_POST[$this->inputName])) {
			return false;
		}

		// No CSRF token found in session
		if (!isset($_SESSION[$this->inputName])) {
			return false;
		}

		if (($session = json_decode($_SESSION[$this->inputName])) === null) {
			return false;
		}

		// Client IP does not match
		if ($this->checkIp && $session->client_ip != $this->getClientIp()) {
			return false;
		}

		// Token is expired
		if ($this->timer !== false && $session->time < time()) {
			return false;
		}

		// CSRF token not match
		if ($_POST[$this->inputName] != $session->token) {
			return false;
		}

		// Generate new token
		$this->generateToken();

		return true;
	}

	/**
	 * Generate a new CSRF token.
	 *
	 * @return void
	 */
	public function generateToken()
	{
		$_SESSION[$this->inputName] = json_encode([
			'token'     => bin2hex(random_bytes(35)),
			'time'      => time() + $this->timer,
			'client_ip' => $this->getClientIp(),
		]);
	}

	/**
	 * Print a hidden text field to hold security token.
	 */
	public function input()
	{
		// Generate new token
		if (!isset($_SESSION[$this->inputName]) || $_SERVER['REQUEST_METHOD'] == 'GET') {
			$this->generateToken();
		}

		$session = json_decode((isset($_SESSION[$this->inputName])) ? $_SESSION[$this->inputName] : '');

		echo '<input type="hidden" name="' . $this->inputName . '" value="' . ((isset($session->token)) ? $session->token : '') . '">';
	}

	/**
	 * Get input field name.
	 *
	 * @return string
	 */
	public function getInputName()
	{
		return $this->inputName;
	}

	/**
	 * Get the value of input.
	 *
	 * @return string
	 */
	public function getInputValue()
	{
		$session = json_decode((isset($_SESSION[$this->inputName])) ? $_SESSION[$this->inputName] : '');

		return (isset($session->token)) ? $session->token : '';
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private function getClientIp()
	{
		return (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
	}
}

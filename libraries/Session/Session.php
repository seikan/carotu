<?php

namespace Session;

class Session
{
	/**
	 * Object to hold session variables.
	 *
	 * @var object
	 */
	protected $session;

	/**
	 * An unique ID for the session.
	 *
	 * @var string
	 */
	protected $sessionId;

	/**
	 * Initialize session object.
	 *
	 * @param string $sessionId specify an unique session Id for session container
	 * @param bool   $secure    enable secure mode that will validate client IP and user agent
	 *
	 * @return void
	 */
	public function __construct($sessionId = null, $secure = true)
	{
		if (!session_id()) {
			// Prevent invalid session Id
			if (isset($_COOKIE[session_name()]) && !preg_match('/^[a-z0-9]{26,32}$/', $_COOKIE[session_name()])) {
				return;
			}

			session_start([
				'cookie_samesite' => 'Lax',
			]);
		}

		$this->sessionId = ($sessionId) ? $sessionId : md5($_SERVER['HTTP_HOST'] . '_session');

		// Secure mode
		if ($secure) {
			if (!isset($_SESSION[$this->sessionId . '_clientIp'])) {
				$_SESSION[$this->sessionId . '_clientIp'] = $_SERVER['REMOTE_ADDR'];
			}

			if (!isset($_SESSION[$this->sessionId . '_userAgent'])) {
				$_SESSION[$this->sessionId . '_userAgent'] = $_SERVER['HTTP_USER_AGENT'];
			}

			// Make sure client IP and user agent is same to prevent session hijacking
			if ($_SESSION[$this->sessionId . '_clientIp'] != $_SERVER['REMOTE_ADDR'] || $_SESSION[$this->sessionId . '_userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
				session_unset();
				session_destroy();

				return;
			}
		}

		if (isset($_SESSION[$this->sessionId]) && ($this->session = json_decode($_SESSION[$this->sessionId], true)) === null) {
			return;
		}

		if (!isset($_SESSION[$this->sessionId])) {
			$_SESSION[$this->sessionId] = json_encode([]);
		}

		$this->session = json_decode($_SESSION[$this->sessionId], true);
	}

	/**
	 * Retrieve a session value by key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function get($key)
	{
		return (isset($this->session[$key])) ? $this->session[$key] : '';
	}

	/**
	 * Set a session variable by key and value.
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function set($key, $value = null)
	{
		$this->session[$key] = $value;

		if ($value === null) {
			unset($this->session[$key]);
		}

		$_SESSION[$this->sessionId] = json_encode($this->session);
	}

	/**
	 * Delete a session variable by key.
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
	 * Destroy the entire session variables.
	 *
	 * @return void
	 */
	public function destroy()
	{
		unset($_SESSION[$this->sessionId]);
		$this->session = null;
	}
}

<?php

namespace SQLite;

use PDO;

class Database extends \PDO
{
	/**
	 * Collection of errors.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Store the last error.
	 *
	 * @var string
	 */
	private $lastError;

	/**
	 * Maps for value bindings.
	 *
	 * @var array
	 */
	private $binds = [];

	/**
	 * SQL query.
	 *
	 * @var string
	 */
	private $query = '';

	/**
	 * Path to error log.
	 *
	 * @var string
	 */
	private $errorLog = '';

	/**
	 * Last insert ID.
	 *
	 * @var array
	 */
	private $lastId;

	/**
	 * Initialize PDO object.
	 *
	 * @param string $dsn
	 *
	 * @throws \Exception
	 */
	public function __construct($dsn)
	{
		try {
			parent::__construct('sqlite:' . $dsn, null, null, [
				\PDO::ATTR_EMULATE_PREPARES => false,
				\PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
			]);
		} catch (\PDOException $e) {
			throw new \Exception($e->getMessage());
		} catch (\Exception $e) {
			throw new \Exception('Unable to open database.');
		}
	}

	/**
	 * Set the location to save error log.
	 *
	 * @param string $errorLog
	 *
	 * @throws \Exception
	 */
	public function saveErrorLog($errorLog)
	{
		if (!file_exists($errorLog)) {
			@touch($errorLog);
		}

		if (!is_writable($errorLog)) {
			throw new \Exception('"' . $errorLog . '" is not writable.');
		}
		$this->errorLog = $errorLog;
	}

	/**
	 * Fetch records from database.
	 *
	 * @param string $table
	 * @param string $where
	 * @param array  $binds
	 * @param bool   $single
	 * @param string $fields
	 *
	 * @return array|false
	 */
	public function select($table, $where = '', $binds = '', $single = false, $fields = '*')
	{
		return $this->execute('SELECT ' . $fields . ' FROM `' . $table . '`' . ((!empty($where)) ? ' WHERE ' . $where : '') . ';', $binds, $single);
	}

	/**
	 * Insert record into database.
	 *
	 * @param string $table
	 * @param array  $records
	 *
	 * @return false|int
	 */
	public function insert($table, $records = [])
	{
		$records = (!\is_array($records)) ? [] : $records;

		$columns = array_keys($records);

		foreach ($records as $key => $value) {
			$records[':' . $key] = (string) $value;
			unset($records[$key]);
		}

		return $this->execute('INSERT OR IGNORE INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES(:' . implode(', :', $columns) . ');', $records);
	}

	/**
	 * Delete record from database.
	 *
	 * @param string $table
	 * @param string $where
	 * @param array  $binds
	 *
	 * @return false|int
	 */
	public function delete($table, $where, $binds = '')
	{
		return $this->execute('DELETE FROM `' . $table . '` WHERE ' . $where . ';', $binds);
	}

	/**
	 * Modify existing record.
	 *
	 * @param string $table
	 * @param array  $fields
	 * @param string $where
	 * @param array  $binds
	 *
	 * @return false|int
	 */
	public function update($table, $fields, $where, $binds = '')
	{
		$binds = $this->getBinds($binds);

		$query = 'UPDATE `' . $table . '` SET ';

		foreach ($fields as $column => $value) {
			$query .= '`' . $column . '` = :new_' . $column . ', ';
			$binds[':new_' . $column] = $fields[$column];
		}

		$query = rtrim($query, ', ') . ' WHERE ' . $where . ';';

		return $this->execute($query, $binds);
	}

	/**
	 * Execute SQL query.
	 *
	 * @param string $query
	 * @param array  $binds
	 *
	 * @return array|false|int
	 *
	 * @throws \Exception
	 */
	public function execute($query, $binds = '', $single = false)
	{
		$this->lastId = null;
		$this->lastError = null;
		$this->query = trim($query);
		$this->binds = $this->getBinds($binds);

		try {
			$st = $this->prepare($this->query);

			if (is_array($this->binds)) {
				foreach ($this->binds as $key => $value) {
					$st->bindValue($key, $value, (is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
				}
			}

			if ($st->execute($this->binds) !== false) {
				if (preg_match('/^(UPDATE|DELETE|INSERT)/i', $this->query)) {
					$this->lastId = parent::lastInsertId();
				} else {
					if ($single) {
						$row = $st->fetch(\PDO::FETCH_ASSOC);

						return ($row === false) ? [] : $row;
					}

					$rows = $st->fetchAll(\PDO::FETCH_ASSOC);

					return ($rows === false) ? [] : $rows;
				}
			}
		} catch (\PDOException $e) {
			$this->errors[] = $this->lastError = $e->getMessage();

			if (is_writable($this->errorLog)) {
				@file_put_contents($this->errorLog, implode("\t", [
					gmdate('Y-m-d H:i:s'),
					$_SERVER['REMOTE_ADDR'] ?? '',
					$_SERVER['REQUEST_URI'] ?? '',
					$e->getFile() . ':' . $e->getLine(),
					$e->getMessage(),
					$this->getQuery(),
				]) . "\n", \FILE_APPEND);
			}

			return false;
		}
	}

	/**
	 * Get executed SQL query.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return str_replace(array_keys($this->binds), array_map(function ($s) {
			return "'" . str_replace('\'', '\\\'', $s) . "'";
		}, array_values($this->binds)), $this->query);
	}

	/**
	 * Get the error message of last query.
	 *
	 * @return string|null
	 */
	public function getLastError()
	{
		return $this->lastError;
	}

	/**
	 * Get MySQL errors.
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Get the last insert ID.
	 *
	 * @return int
	 */
	public function getLastId()
	{
		return $this->lastId;
	}

	/**
	 * Rebuild maps of bindings.
	 *
	 * @param array $binds
	 *
	 * @return array
	 */
	private function getBinds($binds)
	{
		if (!\is_array($binds)) {
			return (!empty($binds)) ? [$binds] : [];
		}

		foreach ($binds as $key => $bind) {
			if (\is_array($bind)) {
				$fields = '';
				$index = 1;

				foreach ($bind as $value) {
					if (empty($value)) {
						continue;
					}

					$suffix = mt_rand(10000, 99999);

					$binds[':bind_' . $suffix . $index] = $value;
					$fields .= ':bind_' . $suffix . $index . ', ';

					++$index;
				}

				$this->query = str_replace($key, rtrim($fields, ', '), $this->query);
				unset($binds[$key]);

				$binds = array_filter($binds);
			}
		}

		return $binds;
	}
}

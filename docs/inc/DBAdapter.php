<?php

/**
 * PDO Database Adapter, gives you SQL injection safe CRUD operations.
 * See <http://en.wikipedia.org/wiki/Create,_read,_update_and_delete> 
 * for information about CRUD. 
 *
 * @author Richard Hoogstraaten <richard@hoogstraaten.eu>
 * @version 1.1.3
 * @copyright (c) 2014, Hoogstraaten.eu
 */
class DBAdapter {

	private static $instance = array();
	private $connection = null;

	/**
	 * Return instance or create one first if there is none...
	 */
	public static function getInstance($dsn, $user, $pass) {
		if (!isset(self::$instance[$dsn])) {
			self::$instance[$dsn] = new self($dsn, $user, $pass);
		}
		return self::$instance[$dsn];
	}

	/**
	 * Constructor
	 */
	private function __construct($dsn, $user, $pass) {
		$this->connection = new PDO($dsn, $user, $pass);
	}

	/**
	 * Error
	 * 
	 * @param	object $query	PDO query object
	 * 
	 * TODO: Build error handler instead of dumping DB errors on screen
	 */
	private function handleError($query) {
		var_dump($query->errorInfo());
		echo '<pre>';
		var_dump($query->debugDumpParams());
		echo '</pre>';
		exit;
	}

	/**
	 * Delete statement builder
	 * 
	 * @param	string	$table	Database table
	 * @param	mixed	$id		PK value or array of PK values as criteria
	 * @param	array	$custom	Custom field => value pairs as deletion criteria
	 */
	public function delete($table, $id = null, array $custom = array()) {
		$sql = "DELETE FROM " . $table . " ";
		if (!count($custom)) {
			if (is_array($id)) {
				foreach ($id as $k => $v) {
					$prms .= ":p" . $k . ",";
				}
				$where = "WHERE id IN (" . substr($prms, 0, -1) . ")";
			} else {
				$where = "WHERE id = :id";
			}
		} else {
			$where = "WHERE ";
			foreach ($custom as $field => $value) {
				if (is_array($value)) {
					$prms = null;
					foreach ($value as $k => $v) {
						$prms .= ":p" . $k . ",";
					}
					$where .= "`" . $field . "` IN (" . substr($prms, 0, -1) . ") AND ";
				} else {
					$where .= "`" . $field . "` = :" . $field . " AND ";
				}
			}
			//Strip of last AND
			$where = substr($where, 0, -5);
		}

		$query = $this->connection->prepare($sql . $where);

		if (!count($custom)) {
			if (is_array($id)) {
				foreach ($id as $key => &$val) {
					$query->bindParam(':p' . $key, $val);
				}
			} else {
				$query->bindParam(':id', $id);
			}
		} else {
			foreach ($custom as $field => &$value) {
				if (is_array($value)) {
					foreach ($value as $key => &$val) {
						$query->bindParam(':p' . $key, $val);
					}
				} else {
					$query->bindParam(':' . $field, $value);
				}
			}
		}
		if (!$query->execute()) {
			$this->handleError($query);
		}
		return $id;
	}

	/**
	 * Select prepared statement
	 * 
	 * @param	string	$statement	SQL prepared statement 
	 * @param	array	$params		field => value pairs as selection criteria
	 */
	public function select($statement, array $params = array()) {
		$query = $this->connection->prepare($statement);
		if (count($params)) {
			foreach ($params as $field => &$value) {
				$query->bindParam(':' . $field, $value);
			}
		}
		if (!$query->execute()) {
			$this->handleError($query);
		}
		return $query->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Insert statement builder
	 * 
	 * @param	string	$table	Database table
	 * @param	array	$params	Field => value pairs to insert
	 */
	public function insert($table, array $params = array()) {
		$sql = "INSERT INTO " . $table . " (`" . implode('`, `', array_keys($params)) . "`) VALUES (:" . implode(", :", array_keys($params)) . ")";
		$query = $this->connection->prepare($sql);
		if (count($params)) {
			foreach ($params as $field => &$value) {
				$query->bindParam(':' . $field, $value);
			}
		}
		if (!$query->execute()) {
			$this->handleError($query);
		}
		return $this->connection->lastInsertId();
	}

	/**
	 * Update statement builder
	 * 
	 * @param	string	$table  Database table
	 * @param	array	$params field => value pairs to update
	 * @param	mixed	$id		PK value or array of PK values
	 * @param	array	$custom	field => value pairs as update criteria
	 */
	public function update($table, array $params = array(), $id = null, array $custom = array()) {
		$qry = null;
		foreach ($params as $field => $value) {
			$qry.= ($qry != '' ? ',' : '') . "`" . $field . "` = :" . $field . "";
		}
		$sql = "UPDATE " . $table . " SET " . $qry . " ";

		if (!count($custom)) {
			if (is_array($id)) {
				$prms = null;
				foreach ($id as $k => $v) {
					$prms .= ":p" . $k . ",";
				}
				$where = "WHERE id IN (" . substr($prms, 0, -1) . ")";
			} else {
				$where = "WHERE id = :id";
			}
		} else {
			$where = "WHERE ";
			foreach ($custom as $field => $value) {
				if (is_array($value)) {
					$prms = null;
					foreach ($value as $k => $v) {
						$prms .= ":p" . $k . ",";
					}
					$where .= "`" . $field . "` IN (" . substr($prms, 0, -1) . ") AND ";
				} else {
					$where .= "`" . $field . "` = :" . $field . " AND ";
				}
			}
			//Strip of last AND
			$where = substr($where, 0, -5);
		}

		$query = $this->connection->prepare($sql . $where);
		if (count($params)) {
			foreach ($params as $field => &$value) {
				$query->bindParam(':' . $field, $value);
			}
		}

		if (!count($custom)) {
			if (is_array($id)) {
				foreach ($id as $key => &$val) {
					$query->bindParam(':p' . $key, $val);
				}
			} else {
				$query->bindParam(':id', $id);
			}
		} else {
			foreach ($custom as $field => &$value) {
				if (is_array($value)) {
					foreach ($value as $key => &$val) {
						$query->bindParam(':p' . $key, $val);
					}
				} else {
					$query->bindParam(':' . $field, $value);
				}
			}
		}
		if (!$query->execute()) {
			$this->handleError($query);
		}
		return $id;
	}

}

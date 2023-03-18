<?php

namespace App\appModel;

require_once __DIR__ . '/../emspay/emspay-helper.php';

use App\emspay\Emspay_Helper;

class App_Model {

	private $dbn;
	private $def_store_data = [
		'store_id' => '',
		'payload' => '',
		'emspay_api_key' => ''
	];

	/**
	 * App_Model constructor.
	 */
	public function __construct($db) {
		try {
			$this->dbh = new \PDO("mysql:host=". $db['host'] .";dbname=" . $db['database'], $db['user'], $db['password']);
			// set the PDO error mode to exception
			$this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		catch(\PDOException $exception) {
			die("Connection failed: " . $exception->getMessage());
		}
	}

	/**
	 * Function save_store_data
	 *
	 * @param $store_data
	 * @return string
	 */
	public function save_store_data($store_data) {

		try {
			if(isset($store_data['emspay_api_key'])) {
				$query = "UPDATE emspay_stores SET store_id=:store_id, payload=:payload, emspay_api_key=:emspay_api_key
					WHERE store_id=:store_id";
			} else {
				$query = "INSERT INTO emspay_stores (store_id, payload, emspay_api_key) 
					VALUES (:store_id, :payload, :emspay_api_key)";
			}

			$store_data = array_merge($this->def_store_data, $store_data);
			$stmt = $this->dbh->prepare($query);

			return $stmt->execute($store_data);
		}
		catch(\PDOException $exception) {
			Emspay_Helper::logError($store_data['store_id'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die("Error occurred: " . $exception->getMessage());
		}
	}

	/**
	 * Function get_store_data
	 *
	 * @param $store_id
	 * @return bool
	 */
	public function get_store_data($store_id) {

		try {
			$query = "SELECT * FROM emspay_stores WHERE store_id = :store_id";
			$stmt = $this->dbh->prepare($query);
			$stmt->execute([':store_id' => $store_id]);
			$stmt->setFetchMode(\PDO::FETCH_ASSOC);

			return $stmt->fetchAll()[0];
		}
		catch(\PDOException $exception) {
			Emspay_Helper::logError($store_id, $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

	public function save_error_log($store_id, $log) {
		$log_data = [
			'store_id' => $store_id,
			'log' => $log
		];

		try {
			$query = "INSERT INTO emspay_log (store_id, log) 
					VALUES (:store_id, :log)";

			$stmt = $this->dbh->prepare($query);

			return $stmt->execute($log_data);
		}
		catch(\PDOException $exception) {
			die("Error occurred: " . $exception->getMessage());
		}
	}
}
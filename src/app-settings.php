<?php

namespace App;

require_once __DIR__ . '/emspay/emspay-helper.php';
require_once __DIR__ . '/appModel/app-model.php';
require_once __DIR__ . '/ecwid/ecwid-api.php';

use App\appModel\App_Model;
use App\emspay\Emspay_Helper;
use App\ecwid\Ecwid_Api;

class Embedded_App {

	private $app_model = null;
	private $app_config = [];
	private $ecwid_client = null;
	private $payload_arr = [];
	private $lang_data = [];

	/**
	 * Embedded_App constructor.
	 */
	public function __construct() {

		try {
			// Create Ecwid API Client
			$this->ecwid_client = new Ecwid_Api();
			$this->app_config = Emspay_Helper::getAppConfig();
			$this->app_model = new App_Model($this->app_config['db']);

			// Set payload array
			$payload_str = (isset($_GET['payload'])) ? filter_input(INPUT_GET, 'payload', FILTER_UNSAFE_RAW) : filter_input(INPUT_POST, 'payload', FILTER_UNSAFE_RAW);
			$this->payload_arr = Emspay_Helper::getEcwidPayload($this->app_config['app']['client_secret'], $payload_str);

			if ($_SERVER['REQUEST_METHOD'] === 'POST' and $payload_str) {
				$args = [
					'store_id' => FILTER_SANITIZE_STRING,
					'payload' =>FILTER_SANITIZE_STRING,
					'emspay_api_key' => FILTER_SANITIZE_STRING
				];
				$post_data = filter_input_array(INPUT_POST, $args);
				$this->app_settings_handler($post_data);
			} elseif($_SERVER['REQUEST_METHOD'] === 'GET' and $payload_str) {
				$this->ecwid_payload_handler($payload_str);
			} else {
				throw new \Exception('Undefined request!');
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($this->payload_arr['store_id'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

	/**
	 * Function app_settings_handler
	 *
	 * @param $post_data
	 */
	private function app_settings_handler($post_data) {
		$response = [
			'success' => true,
			'msg' => $this->get_translate('Saved')
		];
		if(! $this->app_model->save_store_data($post_data)) {
			$response = [
				'success' => false,
				'msg' => $this->get_translate('Is not saved')
			];
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Function ecwid_payload_handler
	 *
	 * @param $ecwid_payload
	 */
	private function ecwid_payload_handler($payload_str) {

		// Set public config
		$this->set_public_config();

		// Save store data to DB
		if (empty($store_data = $this->app_model->get_store_data($this->payload_arr['store_id']))) {
			$store_data['store_id'] = $this->payload_arr['store_id'];
		}
		$store_data['payload'] = $payload_str;
		$this->app_model->save_store_data($store_data);

		// Pass data to app settings template
		$store_data['app_config'] = $this->app_config;
		$store_data['app_url'] = Emspay_Helper::getAppUrl();

		$this->view_app_page($store_data);
	}

	/**
	 * Function view_app_page
	 *
	 * @param $store_data
	 */
	private function view_app_page($store_data) {
		extract($store_data);
		require_once __DIR__ . '/../templates/app.html';
	}

	/**
	 * Function set_public_config
	 *
	 * @return mixed
	 */
	private function set_public_config() {

		// Prepare request body for updating the order
		$json = json_encode([
			'appUrl' => Emspay_Helper::getAppUrl(),
			'gateways' => $this->app_config['app']['gateways']
		]);

		try {
			return $this->ecwid_client->addDataToPublicConfig($this->payload_arr['store_id'], $this->payload_arr['access_token'], $json);
		}
		catch(\PDOException $exception) {
			Emspay_Helper::logError($this->payload_arr['store_id'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die("Error occurred: " . $exception->getMessage());
		}


	}

	/**
	 * Function get_translate
	 *
	 * @param $key
	 * @return mixed
	 */
	public function get_translate($key) {
		if(! $this->lang_data) {
			return $key;
		}

		return $this->lang_data[$key];
	}
}
new Embedded_App();
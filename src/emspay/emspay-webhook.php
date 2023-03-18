<?php

namespace App\emspay;

require_once 'emspay-gateway.php';

class Emspay_Webhook extends Emspay_Gateway{

	/**
	 * Emspay_Webhook constructor.
	 */
	public function __construct(){

		try {
			$ecwid_order_id = filter_input(INPUT_GET, 'orderNumber', FILTER_SANITIZE_STRING);
			$store_id = filter_input(INPUT_GET, 'storeId', FILTER_SANITIZE_STRING);

			$data = json_decode(file_get_contents("php://input"), true);
			if (!is_array($data)) {
				throw new \Exception('Invalid JSON!');
			}

			if(! $store_id or ! $ecwid_order_id) {
				throw new \Exception('Access forbidden!');
			}

			if ( !empty($data['event']) and $data['event'] == 'status_changed') {
				parent::__construct($store_id);
				$this->handle_ems_webhook($data, $ecwid_order_id, $store_id);
			} else {
				throw new \Exception('Unauthorised action!');
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($store_id, $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

	/**
	 * Function handle_ems_webhook
	 *
	 * @param $data
	 * @param $ecwid_order_id
	 * @param $store_id
	 */
	public function handle_ems_webhook($data, $ecwid_order_id, $store_id) {

		try {
			$token = Emspay_Helper::getEcwidPayload($this->app_config['app']['client_secret'], $this->store_data['payload'])['access_token'];
			$ems_order = $this->ems_client->getOrder($data['order_id']);

			$order_data = [
				'paymentStatus' => Emspay_Helper::getEcwidOrderStatus($ems_order['status'])
			];

			if ( !empty($ems_order) ) {
				$this->ecwid_client->update_order(filter_input(INPUT_GET, 'orderNumber'), $order_data, filter_input(INPUT_GET, 'storeId'), $token);
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($store_id, $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}
}
new Emspay_Webhook();
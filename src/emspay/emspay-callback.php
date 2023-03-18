<?php

namespace App\emspay;

require_once 'emspay-gateway.php';

class Emspay_Callback extends Emspay_Gateway {

	/**
	 * Emspay_Callback constructor.
	 */
	public function __construct() {

		$ems_order_id = filter_input(INPUT_GET, 'order_id', FILTER_UNSAFE_RAW);
		$ecwid_order_id = filter_input(INPUT_GET, 'orderNumber', FILTER_UNSAFE_RAW);
		$store_id = filter_input(INPUT_GET, 'storeId', FILTER_UNSAFE_RAW);

		try {
			// If we are returning back to storefront. Callback from payment
			if ($ems_order_id and $ecwid_order_id and $store_id) {
				parent::__construct($store_id);

				$this->handle_callback($ems_order_id, $ecwid_order_id, $store_id);
			} else {
				throw new \Exception('Access forbidden!');
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($store_id, $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

	/**
	 * Function handle_callback
	 *
	 * @param $ems_order_id
	 * @param $ecwid_order_id
	 * @param $store_id
	 */
	public function handle_callback($ems_order_id, $ecwid_order_id, $store_id){

		// Set variables
		$order_data = [];
		$client_id = $this->app_config['app']['client_id'];
		$token = Emspay_Helper::getEcwidPayload($this->app_config['app']['client_secret'], $this->store_data['payload'])['access_token'];
		$return_url = "https://app.ecwid.com/custompaymentapps/$store_id?orderId=$ecwid_order_id&clientId=$client_id";

		try {
			$emsOrder = $this->ems_client->getOrder($ems_order_id);
			$ecwid_order = $this->ecwid_client->getOrder($ecwid_order_id, $store_id, $token);

			// Set merchant_customer_id in the EMS order
			//$emsOrder['customer']['merchant_customer_id'] = $array["customerId"];
			$this->ems_client->updateOrder($emsOrder['id'], $emsOrder);

			// Update Ecwid order status
			$order_data['paymentStatus'] = Emspay_Helper::getEcwidOrderStatus($emsOrder['status']);
			$order_data['externalTransactionId'] = $ems_order_id;
			$result = $this->ecwid_client->update_order_status($ecwid_order_id, $order_data, $store_id, $token);

			if( !empty($result->updateCount) ) {
				header('Location:' . $return_url, true, 302);
				exit;
			} else {
				throw new \Exception('Error updating order status in Ecwid');
			}
		} catch (Exception $exception) {
			Emspay_Helper::logError($store_id, $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}
}
new Emspay_Callback();
<?php

namespace App\ecwid;

require_once __DIR__ . '/../emspay/emspay-gateway.php';

use App\emspay\Emspay_Gateway;
use App\emspay\Emspay_Helper;

class Ecwid_Webhook extends Emspay_Gateway {

	/**
	 * Ecwid_Webhook constructor.
	 */
	public function __construct() {

		try {
			// Get contents of webhook request
			$requestBody = json_decode(file_get_contents('php://input'), true);

			if (!is_array($requestBody)) {
				throw new \Exception('Invalid JSON!');
			}

			if ( !empty($requestBody) and $requestBody['eventType'] == 'order.updated') {
				parent::__construct($requestBody['storeId']);
				$this->ship_an_order($requestBody);
			} else {
				throw new \Exception('Unauthorised action!');
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($requestBody['storeId'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

	/**
	 * Support for Klarna Pay Later and Afterpay order shipped state
	 *
	 * @param array $requestBody
	 */
	function ship_an_order($requestBody) {

		try {
			http_response_code(200);

			// Verify the webhook (check that it is sent by Ecwid)
			foreach (getallheaders() as $name => $value) {
				if ($name == "X-Ecwid-Webhook-Signature") {
					$headerSignature = "$value";

					$hmac_result = hash_hmac("sha256", $requestBody['eventCreated'].$requestBody['eventId'], $this->app_config['app']['client_secret'], true);
					$generatedSignature = base64_encode($hmac_result);

					if ($generatedSignature !== $headerSignature) {
						throw new \Exception('Signature verification failed');
					}
				}
			}

			if(empty($this->store_data['payload'])) {
				throw new \Exception('Store data is empty!');
			}

			$payload = Emspay_Helper::getEcwidPayload($this->app_config['app']['client_secret'], $this->store_data['payload']);

			if(empty($ecwid_order = $this->ecwid_client->getOrder($requestBody['entityId'], $requestBody['storeId'], $payload['access_token']))) {
				throw new \Exception('Order ' . $requestBody['entityId'] . ' does not exist in the Ecwid system!');
			};

			if(empty($emsOrder = $this->ems_client->getOrder($ecwid_order->externalTransactionId))){
				throw new \Exception('Order ' . $ecwid_order->externalTransactionId . ' does not exist in the Ginger system!');
			};

			if( $requestBody['data']['newFulfillmentStatus'] == 'SHIPPED' &&
				in_array($requestBody['data']['oldFulfillmentStatus'], ['AWAITING_PROCESSING', 'PROCESSING']) &&
				in_array(current($emsOrder['transactions'])['payment_method'],['klarna-pay-later', 'afterpay']
				)) {
					$transaction_id = !empty(current($emsOrder['transactions'])) ? current($emsOrder['transactions'])['id'] : null;
				$this->ems_client->captureOrderTransaction($emsOrder['id'], $transaction_id);
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($requestBody['storeId'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}
}
new Ecwid_Webhook();
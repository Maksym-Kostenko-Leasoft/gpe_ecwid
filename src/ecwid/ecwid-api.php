<?php

namespace App\ecwid;

class Ecwid_Api {

	/**
	 * Function update_order_status
	 *
	 * @param $orderNumber
	 * @param $order_data
	 * @param $storeId
	 * @param $token
	 * @return mixed
	 */
	public function update_order_status($orderNumber, $order_data, $storeId, $token) {

		// Prepare request body for updating the order
		$json = json_encode($order_data);

		// URL used to update the order via Ecwid REST API
		$url = "https://app.ecwid.com/api/v3/$storeId/orders/transaction_$orderNumber?token=$token";

		// Send request to update order
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			throw new \Exception(curl_error($ch));
		}
		curl_close($ch);

		return json_decode($response);
	}

	/**
	 * Function update_order
	 *
	 * @param $orderNumber
	 * @param $order_data
	 * @param $storeId
	 * @param $token
	 * @return mixed
	 */
	public function update_order($orderNumber, $order_data, $storeId, $token) {

		// Prepare request body for updating the order
		$json = json_encode($order_data);

		// URL used to update the order via Ecwid REST API
		$url = "https://app.ecwid.com/api/v3/$storeId/orders/$orderNumber?token=$token";

		// Send request to update order
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			throw new \Exception(curl_error($ch));
		}
		curl_close($ch);

		return json_decode($response);
	}

	/**
	 * Function getOrder
	 *
	 * @param $orderNumber
	 * @param $storeId
	 * @param $token
	 * @return mixed
	 */
	public function getOrder($orderNumber, $storeId, $token) {

		$url = "https://app.ecwid.com/api/v3/$storeId/orders/$orderNumber?token=$token";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			throw new \Exception(curl_error($ch));
		}
		curl_close($ch);

		return json_decode($response);
	}

	/**
	 * Function addDataToPublicConfig
	 *
	 * @param $storeId
	 * @param $token
	 * @param $json
	 * @return mixed
	 */
	public function addDataToPublicConfig($storeId, $token, $json) {

		$url = "https://app.ecwid.com/api/v3/$storeId/storage/public?token=$token";

		// Send request to update order
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			throw new \Exception(curl_error($ch));
		}
		curl_close($ch);

		return json_decode($response);
	}
}
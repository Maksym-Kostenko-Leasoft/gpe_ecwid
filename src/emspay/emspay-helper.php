<?php
namespace App\emspay;

require_once __DIR__ . '/../appModel/app-model.php';
use App\appModel\App_Model;

class Emspay_Helper {

	/*
	 * App version
	 */
	const EMSPAY_APP_VERSION = '1.0.0';

	/**
	 * GINGER_ENDPOINT used for create Ginger client
	 */
	const GINGER_ENDPOINT = 'https://api.paygate.payments.dimater.cloud/';

	/**
	 * Method formats the floating point amount to amount in cents
	 *
	 * @param float $total
	 * @return int
	 */
	public static function getAmountInCents($total){
		return (int) round($total * 100);
	}

	/**
	 * Method returns currencyCurrency in ISO-4217 format
	 *
	 * @return string
	 */
	public static function getCurrency($order){
		return $order["cart"]["currency"];
	}

	/**
	 * Generate order description
	 *
	 * @param type $orderId
	 * @return string
	 */
	public static function getOrderDescription($order){
		return sprintf('Your order %s at storeId %s', $order['cart']['order']['orderNumber'], $order['storeId']);
	}

	/**
	 * Function get_app_config
	 *
	 * @return mixed
	 */
	public static function getAppConfig() {
		return require __DIR__ . '/../config/app-config.php';
	}

	/**
	 * Function get_app_url
	 *
	 * @return string
	 */
	public static function getAppUrl() {
		return $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'];
	}

	/**
	 * Method returns customer information from the order
	 *
	 * @param $order
	 * @return array
	 */
	public static function getCustomerInfo($order) {

		return array_filter([
			'address_type' => 'customer',
			'email_address' => (string) $order['cart']['order']['email'],
		]);
	}

	/**
	 * Function getCallbackUrl
	 *
	 * @param $order
	 * @return string
	 */
	public static function getCallbackUrl($order) {
		// Encode access token and prepare calltack URL template
		return self::getAppUrl() . "/src/emspay/emspay-callback.php"."?storeId=".$order['storeId']."&orderNumber=".$order['cart']['order']['orderNumber'];
	}

	/**
	 * Function getWebhookUrl
	 *
	 * @param $order
	 * @return string|null
	 */
	public static function getWebhookUrl($order){
		// Encode access token and prepare calltack URL template
		return self::getAppUrl() . "/src/emspay/emspay-webhook.php"."?storeId=".$order['storeId']."&orderNumber=".$order['cart']['order']['orderNumber'];
	}

	/**
	 * Function getEcwidPayload
	 *
	 * @param $app_secret_key
	 * @param $data
	 * @return mixed
	 */
	public static function getEcwidPayload($app_secret_key, $data) {

		// Get the encryption key (16 first bytes of the app's client_secret key)
		$encryption_key = substr($app_secret_key, 0, 16);

		// Decrypt payload
		$json_data = self::aes_128_decrypt($encryption_key, $data);

		// Decode json
		$json_decoded = json_decode($json_data, true);
		return $json_decoded;
	}

	/**
	 * Functions to decrypt the payment request from Ecwid
	 *
	 * @param $key
	 * @param $data
	 * @return string
	 */
	private static function aes_128_decrypt($key, $data) {
		// Ecwid sends data in url-safe base64. Convert the raw data to the original base64 first
		$base64_original = str_replace(array('-', '_'), array('+', '/'), $data);

		// Get binary data
		$decoded = base64_decode($base64_original);

		// Initialization vector is the first 16 bytes of the received data
		$iv = substr($decoded, 0, 16);

		// The payload itself is is the rest of the received data
		$payload = substr($decoded, 16);

		// Decrypt raw binary payload
		$json = openssl_decrypt($payload, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
		//$json = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $payload, MCRYPT_MODE_CBC, $iv); // You can use this instead of openssl_decrupt, if mcrypt is enabled in your system

		return $json;
	}

	/**
	 * Function for mapping EMS and Ecwid statuses
	 *
	 * @param $ems_order_status
	 * @return string
	 */
	public static function getEcwidOrderStatus($ems_order_status) {

		if(in_array($ems_order_status, ['new', 'processing', 'see-transactions', 'expired'])) {
			$ecwid_order_status = 'INCOMPLETE';
		} elseif (in_array($ems_order_status, ['error', 'cancelled'])){
			$ecwid_order_status = 'CANCELLED';
		} else{
			$ecwid_order_status = 'PAID';
		}

		return $ecwid_order_status;
	}

	public static function logError($store_id, $msg, $bt) {

		$app_config = self::getAppConfig();
		$app_model = new App_Model($app_config['db']);

		$log = [
			'file' => $bt['file'],
			'line' => $bt['line'],
			'function' => $bt['function'],
			'msg' => $msg
		];

		$app_model->save_error_log($store_id, serialize($log));
	}
}
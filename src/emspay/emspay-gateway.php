<?php

namespace App\emspay;

require_once __DIR__ . '/../../lib/vendor/autoload.php';
require_once 'emspay-helper.php';
require_once __DIR__ . '/../ecwid/ecwid-api.php';
require_once __DIR__ . '/../ecwid/bank-config.php';
require_once __DIR__ . '/../appModel/app-model.php';

use Ginger\Ginger;
use App\ecwid\Ecwid_Api;
use App\appModel\App_Model;
use App\ecwid\Bankconfig;

use GingerPluginSdk\Client;
use GingerPluginSdk\Properties\ClientOptions;


class Emspay_Gateway {

	public $ems_client;
	public $ecwid_client;
	public $store_data;
	public $app_config;

	/**
	 * Emspay_Gateway constructor.
	 * @param $store_id
	 */
    public function __construct($store_id){

    	// Get App config
		$this->app_config = Emspay_Helper::getAppConfig();

    	// Create Model object
		$app_model = new App_Model($this->app_config['db']);

		// Create Ecwid API Client
		$this->ecwid_client = $this->create_ecwid_client();

		// Create Ginger API Client
		$this->store_data = $app_model->get_store_data($store_id);

		$this->ems_client = $this->create_ems_client($this->store_data);
	}

	/**
	 * Function create_ecwid_client
	 *
	 * @return Ecwid_Api
	 */
	public function create_ecwid_client() {
		return new Ecwid_Api();
	}

	/**
	 * Function create_ems_client
	 *
	 * @param $storeData
	 */
	public function create_ems_client($storeData) {

        /*return new Client(
            new ClientOptions(
                endpoint: Emspay_Helper::GINGER_ENDPOINT,
                useBundle: true,
                apiKey: $storeData['emspay_api_key']
            ));*/

		try {
			if(empty( $storeData['emspay_api_key'])) {
				throw new \Exception('EMS API key is empty!');
			}

			return Ginger::createClient(
                Bankconfig::BANK_ENDPOINT,
				$storeData['emspay_api_key']
			);
		} catch (\Exception $exception) {
			Emspay_Helper::logError($storeData['store_id'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}
}
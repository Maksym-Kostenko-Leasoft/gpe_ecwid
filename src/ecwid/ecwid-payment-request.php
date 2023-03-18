<?php

namespace App\ecwid;

require_once __DIR__ . '/../emspay/emspay-gateway.php';

use App\emspay\Emspay_Gateway;
use App\emspay\Emspay_Helper;

use GingerPluginSdk\Entities\Order;
use GingerPluginSdk\Collections\AdditionalAddresses;
use GingerPluginSdk\Collections\OrderLines;
use GingerPluginSdk\Collections\PhoneNumbers;
use GingerPluginSdk\Collections\Transactions;
use GingerPluginSdk\Entities\Address;
use GingerPluginSdk\Entities\Customer;
use GingerPluginSdk\Entities\Extra;
use GingerPluginSdk\Entities\Line;
use GingerPluginSdk\Entities\PaymentMethodDetails;
use GingerPluginSdk\Properties\Amount;
use GingerPluginSdk\Properties\Birthdate;
use GingerPluginSdk\Properties\Country;
use GingerPluginSdk\Properties\Currency;
use GingerPluginSdk\Properties\EmailAddress;
use GingerPluginSdk\Properties\Locale;
use GingerPluginSdk\Properties\VatPercentage;
use GingerPluginSdk\Entities\Transaction;


class Ecwid_Payment_Request extends Emspay_Gateway {

    /**
	 * Ecwid_Payment_Request constructor.
	 * @throws \Exception
	 */
	public function __construct() {
        
		try {
			if(!empty($ecwid_payload = filter_input(INPUT_POST, 'data'))) {

				$app_config = Emspay_Helper::getAppConfig();

				// The resulting JSON from payment request will be in $order variable
				$payload = Emspay_Helper::getEcwidPayload($app_config['app']['client_secret'], $ecwid_payload);

				if (empty($payload)) {
					throw new \Exception('Invalid payment request!');
				}

				parent::__construct($payload['storeId']);
				$this->process_payment($payload);
			}
		} catch (\Exception $exception) {
			Emspay_Helper::logError($payload['storeId'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}

    public function getPayload()
    {
        $ecwid_payload = filter_input(INPUT_POST, 'data');

        $app_config = Emspay_Helper::getAppConfig();

        return Emspay_Helper::getEcwidPayload($app_config['app']['client_secret'], filter_input(INPUT_POST, 'data'));
    }

    public function getAmount()
    {
        return new Amount(Emspay_Helper::getAmountInCents($this->getPayload()['cart']['order']['total']));
    }

    public function getCurrency()
    {
        return new Currency(Emspay_Helper::getCurrency());
    }

    public function getAddress($addressType)
    {
        return new Address(
            addressType: $addressType,
            postalCode: $this->getPayload()["cart"]["order"]["shippingPerson"]["postalCode"],
            country: new Country($this->getPayload()["cart"]["order"]["shippingPerson"]["countryCode"]),
        );
    }

    public function getAdditionalAddress()
    {
        return new AdditionalAddresses(
            $this->getAddress('customer'),
            $this->getAddress('billing'),
        );
    }

    public function getCustomer()
    {
        return new Customer(
            additionalAddresses: $this->getAdditionalAddress(),
            firstName: $this->getPayload()["cart"]["order"]["shippingPerson"]["firstName"],
            lastName: $this->getPayload()["cart"]["order"]["shippingPerson"]["lastName"],
            emailAddress: new EmailAddress($this->getPayload()['cart']['order']['email']),
        );
    }

    public function getExtra()
    {
        return new Extra([
            'fields' => [
                'plugin' => Emspay_Helper::EMSPAY_APP_VERSION
            ]]
        );
    }

    public function getTransactions()
    {
        return new Transactions(
            new Transaction(
                paymentMethod: $this->getPayload()["cart"]["order"]["paymentMethod"],
            )
        );
    }

    public function getWeebhook()
    {
        return Emspay_Helper::getWebhookUrl($this->getPayload());
    }

    public function getReturnUrl()
    {
        return Emspay_Helper::getCallbackUrl($this->getPayload());
    }

    public function getOrderId()
    {
        return $this->getPayload()['cart']['order']['orderNumber'];
    }

    public function getDescription()
    {
        return Emspay_Helper::getOrderDescription($this->getPayload());
    }

    public function buildOrder()
    {
        return new Order(
            currency: $this->getCurrency(),
            amount: $this->getAmount(),
            transactions: $this->getTransactions(),
            customer: $this->getCustomer(),
            extra: $this->getExtra(),
            webhook_url: $this->getWeebhook(),
            return_url: $this->getReturnUrl(),
            merchantOrderId: $this->getOrderId(),
            description: $this->getDescription()
        );
    }

	/**
	 * Function process_payment
	 *
	 * @param $payload
	 */
	public function process_payment($payload){
        
		try {
			$emsOrder = $this->ems_client->createOrder(array_filter([
				'amount' => Emspay_Helper::getAmountInCents($payload['cart']['order']['total']),
				'currency' => Emspay_Helper::getCurrency($this->getPayload()),
				'merchant_order_id' => (string) $payload['cart']['order']['orderNumber'],
				'description' => Emspay_Helper::getOrderDescription($this->getPayload()),
				'return_url' => Emspay_Helper::getCallbackUrl($this->getPayload()),
				'customer' => Emspay_Helper::getCustomerInfo($payload),
				'extra' => ['plugin' => Emspay_Helper::EMSPAY_APP_VERSION],
				'webhook_url' => Emspay_Helper::getWebhookUrl($this->getPayload()),
			]));

			if(empty($emsOrder)) {
				throw new \Exception('An order was not created!');
			}

			header('Location:' . $emsOrder['order_url'], true, 302);
			exit;
		} catch (\Exception $exception) {
			Emspay_Helper::logError($payload['storeId'], $exception->getMessage(), array_shift(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
			die($exception->getMessage());
		}
	}
}
new Ecwid_Payment_Request();
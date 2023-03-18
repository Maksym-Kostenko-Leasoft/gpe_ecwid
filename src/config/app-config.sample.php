<?php

return [
	'db' => [
		'host' => 'DB_HOST',
		'user' => 'DB_USERNAME',
		'password' => 'DB_PASSWORD',
		'database' => 'DB_DATABASE'
	],
	'app' => [
		'client_secret' => 'CLIENT_SECRET',
		'client_id' => 'CLIENT_ID',
		'gateways' => [
			'bancontact',
			'bank-transfer',
			'credit-card',
			'ideal',
			'klarna-pay-later',
			'klarna-pay-now',
			'pay-now',
			'payconiq',
			'paypal',
			'tikkie-payment-request',
			'wechat',
			'afterpay',
			'amex',
			'apple-pay'
		],
		'langs' => ['en', 'de', 'fr', 'nl']
	],
];
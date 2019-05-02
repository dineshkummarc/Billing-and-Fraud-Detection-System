<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Billing receiver for payment gateway.
 *
 * @author idan
 */

class Billrun_Receiver_NonCDRs_PaymentGateway extends Billrun_Receiver_Ssh {
	
	/**
	 * Name of the payment gateway in Billrun.
	 * @var string
	 */
	protected $gatewayName;
	
	
	/**
	 * Name of the receiver related action.
	 * @var string
	 */
	protected $actionType;
	
	public function __construct($options) {
		if (!isset($options['version'])) {
			throw new Exception("Please pass " . $this->gatewayName . " version for receiving files");
		}
		if (!isset($options['receiver']['connection'])) {
			throw new Exception('Missing connection details');
		}
		$this->loadConfig(Billrun_Factory::config()->getConfigValue($this->gatewayName . '.' . $options['version'] . '.config_path'));
		$options = array_merge($options, $this->getAllReceiverDefinitions($this->actionType));
		parent::__construct($options);
	}
	
	/**
	 * method to receive files.
	 * 
	 * @return array list of the files received
	 */
	public function receive() {
		return parent::receive();
	}
	
	/**
	 * the structure configuration
	 * @param type $path
	 */
	protected function loadConfig($path) {
		$this->structConfig = (new Yaf_Config_Ini($path))->toArray();
		$this->receiverDefinitions = $this->structConfig['receiver'][$this->actionType];
		$this->gateway = Billrun_Factory::paymentGateway($this->gatewayName);
	}
	
	protected function getAllReceiverDefinitions($type) {
		$receiverDefinitions = array();
		foreach ($this->receiverDefinitions  as $key => $value) {
			$receiverIniDefinitions[$key] = $value;
		}
		$dbReceiverDefinitions = $this->gateway->getGatewayReceiver($type);
		$connections = $dbReceiverDefinitions['connections'];
		foreach ($connections as $key => $connection) {
			if (isset($receiverIniDefinitions['port'])) {
				$connections[$key]['port'] = $receiverIniDefinitions['port'];
				unset($receiverIniDefinitions['port']);
				continue;
			}
			Billrun_Factory::log()->log("Missing port definition in " . $this->gatewayName . " configuration", Zend_Log::NOTICE);
		}
		$dbReceiverDefinitions['connections'] = $connections;
		foreach ($dbReceiverDefinitions as $key => $value) {
			$receiverDefinitions[$key] = $value;
		}

		return array('receiver' => array_merge($receiverDefinitions, $receiverIniDefinitions));
	}

	/**
	 * method to get receiver settings in config.
	 * 
	 * @param mixed $options
	 * @return mixed recevier settings
	 * 
	 */
	public static function getReceiverSettings($options) {
		$type = $options['type'];
		if (!isset($options['gateway'])) {
			throw new Exception('Missing gateway');
		}
		$gateway = $options['gateway'];
		$pgReceiver = array();
		$paymentGatewaySettings = array_filter(Billrun_Factory::config()->getConfigValue('payment_gateways'), function($paymentGateway) use ($gateway) {
			return $paymentGateway['name'] === $gateway;
		});
		if ($paymentGatewaySettings) {
			$paymentGatewaySettings = current($paymentGatewaySettings);
		}
		if (!empty($paymentGatewaySettings[$type]['receiver'])) {
			$pgReceiver = $paymentGatewaySettings[$type]['receiver'];
		}
		return $pgReceiver;
	}

}
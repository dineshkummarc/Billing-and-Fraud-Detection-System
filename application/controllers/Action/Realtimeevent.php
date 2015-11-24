<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012-2015 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero General Public License Version 3; see LICENSE.txt
 */
require_once APPLICATION_PATH . '/application/controllers/Action/Api.php';

/**
 * Realtime event action controller class
 *
 * @package     Controllers
 * @subpackage  Action
 * @since       4.0
 */
class RealtimeeventAction extends ApiAction {

	protected $event = null;
	protected $usaget = null;

	/**
	 * method to execute realtime event
	 */
	public function execute() {
		Billrun_Factory::log("Execute realtime event", Zend_Log::INFO);
		$this->event = $this->getRequestData();
		$this->setEventData();
		$data = $this->process();
		return $this->respond($data);
	}

	/**
	 * make simple sanity to the event input
	 */
	protected function preCheck() {
		
	}

	protected function customer() {
		if (!empty($this->event['imsi'])) {
			
		} else if (!empty($this->event['msisdn'])) {
			
		} else {
			// die no customer identifcation
			return FALSE;
		}
		return TRUE;
	}

	protected function rate() {
		$this->event['arate'] = MongoDBRef::create($collection, $id);
	}

	protected function charge() {
		
	}

	protected function saveEvent() {
		
	}
	
	/**
	 * Gets the data sent to the api
	 * @todo get real data from request (now it's only mock-up)
	 */
	protected function getRequestData() {
		$request = $this->getRequest()->getRequest();
		$this->usaget = $request['usaget'];
		$decoder = Billrun_Decoder_Manager::getDecoder(array(
			'usaget' => $this->usaget
		));
		if (!$decoder) {
			Billrun_Factory::log('Cannot get decoder', Zend_Log::ALERT);
			return false;
		}
		
		return Billrun_Util::parseDataToBillrunConvention($decoder->decode($request['request']));
	}
	
	/**
	 * Sets the data of $this->event
	 */
	protected function setEventData() {
		$this->event['source'] = 'realtime';
		$this->event['type'] = 'gy';
		$this->event['rand'] = rand(1,1000000);
		$this->event['stamp'] = Billrun_Util::generateArrayStamp($this->event);
		if ($this->usaget === 'data') {
			$this->event['sgsn_address'] = $this->getSgsn($this->event);
			$this->event['record_type'] = $this->getDataRecordType($this->event['request_type']);
		}
				
		$this->event['billrun_pretend'] = $this->isPretend($this->event);
		// we are on real time -> the time is now
		$this->event['urt'] = new MongoDate();
	}
	
	protected function getSgsn($event) {
		$sgsn = 0;
		if (isset($event['service']['sgsn_address'])) {
			$sgsn = $event['service']['sgsn_address'];
		} else if(isset ($event['sgsn_address'])) {
			$sgsn = $event['sgsn_address'];
		} else if(isset ($event['sgsnaddress'])) {
			$sgsn = $event['sgsnaddress'];
		}
		return long2ip(hexdec($sgsn));
	}
	
	protected function getDataRecordType($requestCode) {
		$requestTypes = Billrun_Factory::config()->getConfigValue('realtimeevent.data.requestType',array());
		foreach ($requestTypes as $requestTypeDesc => $requestTypeCode) {
			if ($requestCode === $requestTypeCode) {
				return strtolower($requestTypeDesc);
			}
		}
		return false;
	}
	
	/**
	 * Runs Billrun process
	 * 
	 * @return type Data generated by process
	 */
	protected function process() {
		$options = array(
			'type' => 'Realtime',
			'parser' => 'none',
		);
		$processor = Billrun_Processor::getInstance($options);
		$processor->addDataRow($this->event);
		$processor->process();
		return $processor->getData()['data'][0];
	}
	
	/**
	 * Send respond
	 * 
	 * @param type $data
	 * @return boolean
	 */
	protected function respond($data) {
		$encoder = Billrun_Encoder_Manager::getEncoder(array(
			'usaget' => $this->usaget
			));
		if (!$encoder) {
			Billrun_Factory::log('Cannot get encoder', Zend_Log::ALERT);
			return false;
		}
		
		$responder = Billrun_ActionManagers_Realtime_Responder_Manager::getResponder($data);
		if (!$responder) {
			Billrun_Factory::log('Cannot get responder', Zend_Log::ALERT);
			return false;
		}

		$response = array($encoder->encode($responder->getResponse(), "response"));
		$this->getController()->setOutput($response);
		return $response;
		// Sends response
		//$responseUrl = Billrun_Factory::config()->getConfigValue('IN.respose.url.realtimeevent');
		//return Billrun_Util::sendRequest($responseUrl, $response);
	}
	
	/**
	 * Checks if the row should really decrease balance from the subscriber's balance, or just prepend
	 * 
	 * @return boolean
	 */
	protected function isPretend($event) {
		return (($this->usaget === 'call' && $event['record_type'] === 'start_call') ||
			($this->usaget === 'data' && $event['request_type'] === "1"));
	}

}

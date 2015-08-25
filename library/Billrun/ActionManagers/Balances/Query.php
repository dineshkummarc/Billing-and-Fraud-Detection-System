<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012-2015 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero General Public License Version 3; see LICENSE.txt
 */

/**
 * This is a parser to be used by the balances action.
 *
 * @author tom
 */
class Billrun_ActionManagers_Balances_Query extends Billrun_ActionManagers_Balances_Action{
	
	/**
	 * Field to hold the data to be written in the DB.
	 * @var type Array
	 */
	protected $balancesQuery = array();
	
	protected $rawinput = array();

	protected $queryInRange = false;
	
	/**
	 */
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * Query the subscribers collection to receive data in a range.
	 */
	protected function queryRangeBalances() {
		try {
			$cursor = $this->collection->query($this->subscriberQuerys)->cursor();
			if(!$this->queryInRange) {
				$cursor->limit(1);
			}
			$returnData = array();
			
			// Going through the lines
			foreach ($cursor as $line) {
				$returnData[] = json_encode($line->getRawData());
			}
		} catch (\Exception $e) {
			Billrun_Factory::log('failed quering DB got error : ' . $e->getCode() . ' : ' . $e->getMessage(), Zend_Log::ALERT);
			return null;
		}	
		
		return $returnData;
	}
	/**
	 * Query the subscribers collection to receive a single record.
	 */
	private function querySingleBalance() {
		try {
			$line = $this->collection->findOne($this->balancesQuery);
			
		} catch (\Exception $e) {
			Billrun_Factory::log('failed quering DB got error : ' . $e->getCode() . ' : ' . $e->getMessage(), Zend_Log::ALERT);
			return null;
		}	
		
		return array(json_encode($line));
	}
	
	/**
	 * Execute the action.
	 * @return data for output.
	 */
	public function execute() {
		$returnData = 
			$this->queryRangeBalances();

		$success=true;
		// Check if the return data is invalid.
		if(!$returnData) {
			$returnData = array();
			$success=false;
		}
		
		$outputResult = 
			array('status'  => ($success) ? (1) : (0),
				  'desc'    => ($success) ? ('success') : ('Failed querying balances'),
				  'details' => $returnData);
		return $outputResult;
	}

	/**
	 * Parse the received request.
	 * @param type $input - Input received.
	 * @return true if valid.
	 */
	public function parse($input) {
		$jsonData = null;
		$query = $input->get('query');
		if(empty($query) || (!($jsonData = json_decode($query, true)))) {
			return false;
		}
		
		// TODO: Do i need to validate that all these fields are set?
		$this->balancesQuery = 
			array('imsi'			 => $jsonData['imsi'],
				  'msisdn'			 => $jsonData['msisdn'],
				  'sid'				 => $jsonData['sid']);
		
		// Check if there is a to field.
		$to = $input->get('to');
		$from = $input->get('from');
		if($to && $from) {
			$this->balancesQuery['to'] =
				array('$lte' => new MongoTimestamp($to));
			$this->balancesQuery['from'] = 
				array('$gte' => new MongoTimestamp($from));
			$this->queryInRange = true;
		}
		
		return true;
	}
}

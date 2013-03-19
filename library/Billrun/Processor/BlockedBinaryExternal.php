<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012 S.D.O.C. LTD. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * This defines an empty processor that pass the processing action to extarnal plugin.
 */
class Billrun_Processor_BlockedBinaryExternal extends Billrun_Processor_Base_BlockedSeperatedBinary
{	
	static protected $type = 'blockedBinaryExternal';

	public function __construct($options = array()) {
		parent::__construct($options);
		if($this->getType() == 'blockedBinaryExternal') {
			throw new Exception('Billrun_Processor_BlockedBinaryExternal::__construct : cannot run without specifing a specific type.');
		}
	}
	
	protected function parse() {
			if (!is_resource($this->fileHandler)) {
				$this->log->log('Resource is not configured well', Zend_Log::ERR);
				return false;
			}
			return $this->chain->trigger('processData',array($this->getType(), $this->fileHandler, &$this));
	}

	protected function processFinished() {
			return $this->chain->trigger('isProcessingFinished',array($this->getType(), $this->fileHandler, &$this));		
	}
	
	/**
	 * @see Billrun_Processor::getSequenceData
	 */
	public function getSequenceData($filename) {
		return $this->chain->trigger('getSequenceData',array($this->getType(), $filename, &$this));		
	}
	
}


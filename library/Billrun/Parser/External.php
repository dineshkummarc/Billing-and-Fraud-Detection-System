<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012 S.D.O.C. LTD. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * This defiens an empty parser the do nothing but pass behavior to the out side plugins
 */
class Billrun_Parser_External  extends Billrun_Parser_Base_Binary  {
	static protected $type = "external";

	public function __construct($options) {
		parent::__construct($options);
		if($this->getType() == "external") {
			throw new Exception('Billrun_Parser_External::__construct : cannot run without specifing a specific type for external parser, current type is :'.$this->getType());
		}
	}


	public function parse() {
		return $this->chain->trigger('parseData',array($this->getType(), $this->getLine(), &$this));
	}

	public function parseField($data, $fileDesc) {
		return $this->chain->trigger('parseSingleField', array($this->getType(), $data, $fileDesc, &$this));
	}

	public function parseHeader($data) {
		return $this->chain->trigger('parseHeader', array($this->getType(), $data, &$this));
	}

	public function parseTrailer($data) {
		return $this->chain->trigger('parseTrailer', array($this->getType(), $data, &$this));
	}
	
	/**
	 * Set the amount of bytes that were parsed on the last parsing run.
	 * @param $parsedBytes	Containing the count of the bytes that were processed/parsed.
	 */
	public function setLastParseLength($parsedBytes) {
		$this->parsedBytes = $parsedBytes;
	}
	
}

?>

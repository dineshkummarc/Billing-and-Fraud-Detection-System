<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012 S.D.O.C. LTD. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Billing index controller class
 *
 * @package  Controller
 * @since    1.0
 */
class IndexController extends Yaf_Controller_Abstract {

	protected $eol = PHP_EOL;
	protected $possibleActions = array('receive', 'process', 'respond', 'calculate', 'aggregate', 'generate','alert');

	public function indexAction() {
		$this->getView()->title = "BillRun | The best open source billing system";
		$this->getView()->content = "Open Source Last Forever!";
	}

	/**
	 * method which run when the app running from command line
	 * 
	 * @return void
	 * @since 1.0
	 */
	public function cliAction() {
		// add log to stdout when we are on cli
		Billrun_Log::getInstance()->addWriter(new Zend_Log_Writer_Stream('php://stdout'));
		$this->outputAdd("Running Billrun from CLI!");
		try {
			$input = array(
				'r|R|receive' => 'Recieve files and process them into database',
				'p|P|process' => 'Process files into database',
				'c|C|calc|calculate' => 'Calculate lines in database',
				'a|A|aggregate' => 'Aggregate lines for billrun',
				'g|G|generate' => 'Generate xml and csv files of specific billrun',
				'e|E|respond' => 'Respond to files that were processed',
				'l|L|alert' => 'Process and detect alerts',
				'h|H|help' => 'Displays usage information.',
				'type-s' => 'Process: Ild type to use',
				'stamp-s' => 'Process: Stamp to use for this run',
				'path-s' => 'Process: Path of the process file',
				'export-path-s' => 'Respond: The path To export files',
				'workspace-s' => 'The path to the workspace directory',
				'parser-s' => 'Process: Parser type (default fixed)',
				'backup' => 'Process: Backup path after the file processed (default ./backup)',
			);

			$opts = new Zend_Console_Getopt($input);
			$opts->parse();
		} catch (Zend_Console_Getopt_Exception $e) {
			$this->outputAdd($e->getMessage() . "\n\n" . $e->getUsageMessage());
			return;
		}

		//Go through all actions and run the first one that was selected
		foreach ($this->possibleActions as $val) {
			if (isset($opts->{$val})) {
				$this->outputAdd(ucfirst($val) . "...");
				$this->{$val}($opts);
				return;
			}
		}

		$this->outputAdd($opts->getUsageMessage());
	}

	protected function outputAdd($content) {
		Billrun_Log::getInstance()->log($content, Zend_Log::INFO);
//		$this->getView()->output .= $content . $this->eol;
	}

	protected function receive($opts) {
		$posibleOptions = array(
			'type' => false,
			'path' => true,
			'workspace' => true,
		);


		$options = $this->getInstanceOptions($opts, $posibleOptions);
//		$options['db'] = 0; //temporary hack for testing
		$this->outputAdd("Loading receiver");
		$receiver = Billrun_Receiver::getInstance($options);
		$this->outputAdd("Receiver loaded");

		if ($receiver) {
			$this->outputAdd("Start to receiving. This action can take awhile...");

			// buffer all action output
			ob_start();
			$files = $receiver->receive();
			$this->outputAdd("Received " . count($files) . " files");
			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Receiver cannot be loaded");
		}
	}

	protected function process($opts) {
		$posibleOptions = array(
			'type' => false,
			'parser' => false,
			'path' => true,
			'backup' => true, // backup path
		);

		$options = $this->getInstanceOptions($opts, $posibleOptions);
		if (!$options) {
			return;
		}

		$this->outputAdd("Parser selected: " . $options['parser']);
		$options['parser'] = Billrun_Parser::getInstance(array('type' => $options['parser']));

		$this->outputAdd("Loading processor");
		$processor = Billrun_Processor::getInstance($options);
		$this->outputAdd("Processor loaded");

		if ($processor) {
			$this->outputAdd("Start to process. This action can take awhile...");

			// buffer all action output
			ob_start();
			if (isset($options['path']) && $options['path']) {
				$lines = $processor->process();
			} else {
				$lines = $processor->process_files();
			}
			// write the buffer into log and output
			$this->outputAdd("processed " . count($lines) . " lines");
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Processor cannot be loaded");
		}
	}

	protected function respond($opts) {
		$options = $this->getInstanceOptions($opts, array(	'type' => false,
															'export-path' => true ));
		if (!$options) {
			return;
		}

		$this->outputAdd("Loading Responder");
		$responder = Billrun_Responder::getInstance($options);
		$this->outputAdd("Responder loaded");

		if ($responder) {
			// buffer all action output
			ob_start();

			$paths = $responder->respond($options);
			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
			$this->outputAdd("Responder responded on ".count($paths)." files.");
		} else {
			$this->outputAdd("Responder cannot be loaded");
		}
	}

	protected function calculate($opts) {
		$options = $this->getInstanceOptions($opts, array('type' => false));
		if (!$options) {
			return;
		}

		$this->outputAdd("Loading Calculator");
		$calculator = Billrun_Calculator::getInstance($options);
		$this->outputAdd("Calculator loaded");

		if ($calculator) {
			// buffer all action output
			ob_start();

			$this->outputAdd("Loading data to calculate...");
			$calculator->load();
			$this->outputAdd("Starting to calculate. This action can take awhile...");
			$calculator->calc();
			$this->outputAdd("Writing calculated data.");
			$calculator->write();

			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Calculator cannot be loaded");
		}
	}

	protected function aggregate($opts) {
		$options = $this->getInstanceOptions($opts, array('type' => false,
			'stamp' => false,));
		if (!$options) {
			return;
		}

		$this->outputAdd("Loading aggregator");
		$aggregator = Billrun_Aggregator::getInstance($options);
		$this->outputAdd("Aggregator loaded");

		if ($aggregator) {
			// buffer all action output
			ob_start();
			$this->outputAdd("Loading data to Aggregate...");
			$aggregator->load();
			$this->outputAdd("Starting to Aggregate. This action can take awhile...");
			$aggregator->aggregate();
			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Aggregator cannot be loaded");
		}
	}

	protected function alert($opts) {
		$availableOptions =array(
			'type' => false,
		);
		$options = $this->getInstanceOptions($opts, $availableOptions);
		if (!$options) {
			return;
		}

		$this->outputAdd("Loading handler");
		$handler = Billrun_Handler::getInstance();
		$this->outputAdd("Handler loaded");

		if ($handler) {
			// buffer all action output
			ob_start();
			$handler->execute();

			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Aggregator cannot be loaded");
		}
	}	
	
	protected function generate($opts) {
		$options = $this->getInstanceOptions($opts, array('type' => false,
			'stamp' => false,));
		if (!$options) {
			return;
		}

		$this->outputAdd("Loading generator");
		$generator = Billrun_Generator::getInstance($options);
		$this->outputAdd("Generator loaded");

		if ($generator) {
			// buffer all action output
			ob_start();
			$this->outputAdd("Loading data to Generate...");
			$generator->load();
			$this->outputAdd("Starting to Generate. This action can take awhile...");
			$generator->generate();
			// write the buffer into log and output
			$this->outputAdd(ob_get_contents());
			ob_end_clean();
		} else {
			$this->outputAdd("Aggregator cannot be loaded");
		}
	}

	protected function getInstanceOptions($opts, $posibleOptions = false) {
		if (!$posibleOptions) {
			$posibleOptions = array(
				'type' => false,
				'stamp' => false,
				'path' => "./",
				'parser' => 'fixed');
		}
		$options = array();
		foreach ($posibleOptions as $key => $defVal) {
			$options[$key] = $opts->getOption($key);
			if (empty($options[$key])) {
				if (!$defVal) {
					$this->outputAdd("Error: No $key selected");
					return null;
				} else if(true !== $defVal) {
					$options[$key] = $defVal ;
				}
			}
		}
		return $options;
	}

}

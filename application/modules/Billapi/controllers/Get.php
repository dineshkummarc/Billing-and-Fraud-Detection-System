<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012-2016 BillRun Technologies Ltd. All rights reserved.
 * @license         GNU Affero General Public License Version 3; see LICENSE.txt
 */
require_once APPLICATION_PATH . '/application/modules/Billapi/controllers/Billapi.php';

/**
 * Billapi controller for getting BillRun entities
 *
 * @package  Billapi
 * @since    5.3
 */
class GetController extends BillapiController {
	
	protected $action;

	protected function verifyTranslated($translated) {
		
	}

	public function init() {
		parent::init();
		$request = $this->getRequest();
		$this->params['sort'] = json_decode($request->get('sort'), TRUE);
		$this->params['page'] = $request->get('page', 0);
		$this->params['size'] = $request->get('size', 10);
		if (!is_null($this->params['sort'])) {
			$this->validateSort($this->params['sort']);
		}
	}

	protected function runOperation() {
		$this->action = Models_Action::getInstance($this->params);
		if (!$this->action) {
			throw new Billrun_Exceptions_Api(999999, array(), 'Action cannot be found');
		}
		$this->output->status = 1;
		try {
			$this->output->details = $this->action->execute();
		} catch (Exception $ex) {
			$this->output->status = 0;
			$this->output->errorCode = $ex->getCode();
			$this->output->desc = $ex->getMessage();
			Billrun_Factory::log($this->output->errorCode . ': ' . $this->output->desc, Zend_Log::ERR);
		}
		
		return $this->output->details;
	}

}

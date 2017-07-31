<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012-2017 BillRun Technologies Ltd. All rights reserved.
 * @license         GNU Affero General Public License Version 3; see LICENSE.txt
 */
require_once APPLICATION_PATH . '/application/controllers/Action/Api.php';

/**
 * Report action class
 *
 * @package  Action
 * @since 5.5
 * 
 */
class ReportAction extends ApiAction {
	
	use Billrun_Traits_Api_UserPermissions;
	
	
	
	protected $model = null;
	protected $request = null;
	protected $status = 1;
	protected $desc = 'success';
	protected $next_page = true;
	protected $response = array();
	protected $type = null;
	protected $headers = array();
	
	public function execute() {
		$this->request = $this->getRequest(); // supports GET / POST requests;
		$action = $this->request->getRequest('action', '');
		$this->model = new ReportModel();
		if (!method_exists($this, $action)) {
			return $this->setError('Report controller - cannot find action: ' . $action);
		}
		$report = $this->request->getRequest('report', null);
		$page = $this->request->getRequest('page', 0);
		$size = $this->request->getRequest('size', -1);
		$this->{$action}($report, $page, $size);
		$this->response();
	}
	
	public function exportCSV($report_name) {
		$report = $this->model->getReportByKey($report_name);
		if (empty($report)) {
			throw new Exception("Report {$report_name} not exist");
		}
		$this->type = 'csv';
		$this->headers = array_reduce(
			$report['columns'], 
			function ($carry, $column) {
				$carry[$column['key']] = $column['label'];
				return $carry;
			},
			array()
		);
		$this->response = $this->model->applyFilter($report, 0, -1);
	}
	
	public function generateReport($report, $page, $size) {
		$parsed_report = json_decode($report, TRUE);
		$this->response = $this->model->applyFilter($parsed_report, $page, $size);
		$nextPageData = ($size !== -1) ? $this->model->applyFilter($parsed_report, $page + 1, $size) : array(); // TODO: improve performance, avoid duplicate aggregate run
		$this->next_page = count($nextPageData) > 0; 
	}
	
	public function taxationReport($report, $page, $size) {	
		$parsed_query = json_decode($report, TRUE);
		$reportData = Billrun_Factory::chain()->trigger('getTaxationReport',array($parsed_query['billrun_key']));
		$this->response =  $reportData['data'];
		$this->getRequest()->setParam('headers', json_encode($reportData['headers']));
		$this->next_page = false; 
	}
	
	protected function response() {
		$this->getController()->setOutput(array(
			array(
				'status' => $this->status,
				'desc' => $this->desc,
				'details' => $this->response,
				'next_page' => $this->next_page,
			)
		));
		return true;
	}

	protected function getPermissionLevel() {
		return Billrun_Traits_Api_IUserPermissions::PERMISSION_WRITE;
	}
	
	protected function render($tpl, array $parameters = null) {
		$request = array_merge($this->getRequest()->getParams(),$this->getRequest()->getRequest());
		$type = !empty($this->type) ? $this->type : $this->request->getRequest('type', '');
		if($type === 'csv') {
			return $this->renderCsv($request, $parameters);
		}
		return parent::render('index', $parameters);
	}

	protected function renderCsv($request, array $parameters = null) {
		$filename = isset($request['file_name']) ? $request['file_name'] : date('Ymd').'_report';
		$headers = isset($request['headers']) ? json_decode($request['headers'], TRUE) : $this->headers;
		$delimiter = isset($request['delimiter']) ? $request['delimiter'] : ',';
		$this->getController()->setOutputVar('headers', $headers);
		$this->getController()->setOutputVar('delimiter', $delimiter);
		$resp = $this -> getResponse();
		$resp->setHeader("Cache-Control", "max-age=0");
		$resp->setHeader("Content-type",  "application/csv; charset=UTF-8");
		$resp->setHeader('Content-disposition', 'inline; filename="' . $filename . '.csv"');
		return $this->getView()->render('api/aggregatecsv.phtml', $parameters);
	}
}

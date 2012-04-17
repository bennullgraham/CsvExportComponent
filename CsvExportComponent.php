<?php

App::uses('Component', 'Controller');

/**
 * CsvExportComponent will turn your database into text 
 * with commas everywhere.
 * 
 * Usage:
 * 
 * Include CsvExport as a Component in your controller.
 * 
 * var $components = array('CsvExport');
 * 
 * Use $this->CsvExport->setConditions(array(...)) if only specific 
 * records should be exported.
 * 
 * Use $this->CsvExport->setFields(array(...)) to limit which fields
 * are exported and to provide friendly names for fields. If an array
 * item is numerically indexed then it is assumed this is a DB field
 * and a human-friendly name is generated from it.
 * 
 * Use $this->CsvExport->export($this->Model); to
 * actually do the export thing.
 * 
 * $this->CsvExport->debug = true will keep output in the browser.
 * 
 * See setRecordProcessorCallback if records require processing before
 * being exported.
 * 
 * @author bgraham
 */
class CsvExportComponent extends Component {

/**
 * If true, headers will not be output and Cake debug will not be disabled
 * so that output may be examined in the browser.
 * 
 * @var boolean
 */
	public $debug = false;

/**
 * Reference to parent controller
 * 
 * @var Controller
 */
	private $controller = null;

/**
 * Model we are exporting from, set during export
 * 
 * @var array
 */
	private $model = null;

/**
 * query conditions for exported records
 * 
 * @var array
 */
	private $conditions = array();

/**
 * Record fields to be exported
 * 
 * @var array
 */
	private $fields = null;

/**
 * Maximum number of records to retrieve in each page
 * 
 * @var array
 */
	private $limit = 1000;

/**
 * Path to temporary disk buffer
 * 
 * @var string
 */
	private $diskBufferPath = null;

/**
 * Resource pointer to temporary disk buffer
 * 
 * @var resource filepointer
 */
	private $diskBuffer = null;

/**
 * Remember if we need to run a callback against each record
 * 
 * @var mixed
 */
	private $callback = null;

/**
 * CakePHP callback. 
 * 
 * @param Controller controller
 * @access public
 */
	public function initialize(&$controller) {
		$this->controller =& $controller;
	}

/**
 * Reads records from database and sends as CSV output to browser. Records
 * are read page-at-a-time and buffered to disk before output. Use 
 * setConditions() and setFields() to customise output.
 * 
 * @param Model $model model to export records from
 * @param string $filename basis of name for file exported to user
 * @return void
 */
	public function export($model, $filename=null) {
		$this->model = $model;
		$order = $this->getOrder();
		$filename = $this->formatFilename($filename);
		
		if (!$this->debug) {
			Configure::write('debug', 0);
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}

		$params = array(
			'conditions' => $this->conditions,
			'fields' => $this->getFields('slugged'),
			'limit' => $this->limit,
			'page' => 1,
			'order' => $order,
		);

		$this->openDiskBuffer();
		$this->writeHeader();
		while($records = $this->getRecords($params)) {
			$this->writeRecords($records);
		}
		$this->flushDiskBuffer();
		exit();
	}

/**
 * Opens write-mode file pointer to temporary disk buffer
 * 
 * @access private
 * @return boolean success
 */
	private function openDiskBuffer() {
		if (!$this->diskBufferPath = tempnam(sys_get_temp_dir(), 'csvexport')) {
			throw new InternalErrorException('Could not find location for temporary buffer file ');
		}
		if (!$this->diskBuffer = fopen($this->diskBufferPath, 'w')) {
			throw new InternalErrorException('Could not open temporary buffer file');
		}
		return true;
	}

/**
 * Close then write contents of CSV export to output buffer. Unlink
 * buffer file on success.
 * 
 * @access private
 * @return boolean success
 */
	private function flushDiskBuffer() {
		if ($this->diskBufferPath === null || $this->diskBuffer === null) {
			return false;
		}
		if (!fclose($this->diskBuffer) || !readfile($this->diskBufferPath)) {
			throw new InternalErrorException('Could not write disk buffer to output buffer');
		}
		if (!unlink($this->diskBufferPath)) {
			throw new InternalErrorException('Could not clean up disk buffer');
		}
		return true;
	}

/**
 * Generate an order value for database find call
 * 
 * @access private
 * @return string order value
 */
	private function getOrder(){
		if ($this->model === null) {
			return false;
		}

		return "{$this->model->alias}.{$this->model->primaryKey}";
	}

/**
 * Return a new page of database records. Increments page counter automatically.
 *
 * @param array $params parameters for Model::find call. 
 * @return array database records
 * 
 */
	private function getRecords(&$params) {
		$records = $this->model->find('all', $params);
		$params['page']++;
		if ($this->callback!==null) {
			$records = array_map($this->callback, $records);
		}
		return $records;
	}

/**
 * Write out human-readable column headers to buffer
 * 
 * @access private
 * @return void
 */
	private function writeHeader() {
		$headers = $this->getFields();
		fputcsv($this->diskBuffer, $headers);
	}

/**
 * Write out a set of database records to buffer
 * 
 * @access private
 * @param array $records database records
 * @return void
 */
	private function writeRecords($records) {
		$alias = $this->model->alias;
		foreach ($records as $record) {
			fputcsv($this->diskBuffer, $record[$alias]);
		}
	}

/**
 * Add current date to export filename
 * 
 * @param string $baseFilename name that date is prepended to
 * @return string filename
 */
	private function formatFilename($baseFilename) {
		if ($baseFilename===null){
			$baseFilename = Inflector::pluralize($this->model->alias);
		}
		return date('Y-m-d') . "-{$baseFilename}.csv";
	}

/**
 * 
 * 
 */
	public function setConditions($conditions) { 
		$this->conditions = $conditions;
	}

/**
 * 
 * 
 */
	public function setFields($fields) { 
		$this->fields = $fields; 
	}

/**
 * Apply the argued function to every record before exporting it. The callback
 * function will be passed an array of field=>value pairs arranged under the
 * model alias, one for each record:
 * 
 *    'Entry' => 
 *      array
 *        'id' => '272583' 
 *        'modified' => '2012-01-12 16:44:06'
 * 
 * An example callback looks like:
 *     	$this->CsvExport->setRecordProcessorCallback(array($this, 'capitalise'));
 * 
 * @access public
 * @param mixed $callback anything that array_map will recognise.
 * @return void
 */
	public function setRecordProcessorCallback($callback) {
		$this->callback = $callback;
	}

/**
 * Return normalised fields in associative machine=>human format,
 * or just an indexed array of only machine or only human values.
 * 
 * @param string $format One of 'both', 'slugged', 'human'
 * @return mixed Fields, or false if param is dumb
 */
	public function getFields($format='both') {
		$fields = ($this->fields===null) ? array_keys($this->model->schema()) : $this->fields;
		$normalisedFields = array();

		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$normalisedFields[$value] = Inflector::humanize($value);
			}
			else {
				$normalisedFields[$key] = $value;
			}
		}
		if ($format==='both') {
			return $normalisedFields;
		} elseif ($format==='slugged') {
			return array_keys($normalisedFields);
		} elseif ($format==='human') {
			return array_values($normalisedFields);
		} else {
			return false;
		}
	}
}

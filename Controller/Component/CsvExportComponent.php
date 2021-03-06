<?php

App::uses('Field', 'CsvExport.Lib');
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
 * Reference to CakePHP model object
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
 * Reads records from database and sends as CSV output to browser. Records
 * are read page-at-a-time and buffered to disk before output. Use 
 * setConditions() and setFields() to customise output.
 * 
 * @param Model $model model to export records from
 * @param array $params array of parameters. Keys include 'filename' and 'download'.
 *                      With download disabled, export only returns the path to the
 *                      on-disk CSV file. Enabled, the file contents are sent to the
 *                      browser.
 * @param string $filename basis of name for file exported to user
 * @return path to exported CSV, or void
 */
    public function export($model, $params=array()) {

        $filename = isset($params['filename']) ? $params['filename'] : null;
        $download = isset($params['download']) ? $params['download'] : true;

        $this->model = $model;
        $order = $this->getOrder();
        $filename = $this->formatFilename($filename);
        
        $fields = $this->parseFields('slugged');
        if (!$this->debug) {
            Configure::write('debug', 0);
            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
        }

        $findParams = array(
            'conditions' => $this->conditions,
            'fields' => $fields,
            'limit' => $this->limit,
            'page' => 1,
            'order' => $order,
            'contain' => $this->getContainableModels(),
        );

        $this->openDiskBuffer();
        $this->writeHeader();
        while($records = $this->getRecords($findParams)) {
            $this->writeRecords($records, $fields);
        }
        if ($download) {
            $this->flushDiskBuffer();
            return true;
        }
        else {
            $this->closeDiskBuffer();
            return $this->diskBufferPath;
        }
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
 * Close the disk buffer.
 * 
 * @access private
 * @return boolean success
 */
    private function closeDiskBuffer() {
        if ($this->diskBufferPath === null || $this->diskBuffer === null) {
            throw new InternalErrorException('No disk buffer to close');;
        }
        if (!fclose($this->diskBuffer)) {
            throw new InternalErrorException('Could not close disk buffer');
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
        $this->closeDiskBuffer();
        if ($this->diskBufferPath === null) {
            throw new InternalErrorException('No disk buffer to flush');
        }
        if (readfile($this->diskBufferPath) === false) {
            throw new InternalErrorException('Could not write disk buffer to PHP output buffer');
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
        $headers = $this->parseFields();
        fputcsv($this->diskBuffer, $headers);
    }

/**
 * Write out a set of database records to buffer
 *
 * @access private
 * @param array $records database records
 * @param array $fields which field names to write, and in which order
 * @return void
 */
    private function writeRecords($records, $fields) {
        $paths = array();
        foreach ($fields as $field) {
            list($model, $column) = explode('.', $field);
            $paths[] = array('model'=>$model, 'column'=>$column);
        }
        foreach ($records as $record) {
            $vals = array();
            foreach($paths as $path) {
                $vals[] = $record[ $path['model'] ][ $path['column'] ];
            }
            fputcsv($this->diskBuffer, $vals);
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
        if (is_array($conditions)) {
            $this->conditions = $conditions;
        } else {
            throw new Exception("setConditions requires an array");
        }
    }
    public function getConditions(){ 
        return $this->conditions; 
    }

/**
 *
 *
 */
    public function setFields($fields) {
        if (is_array($fields)) {
            $this->fields = $fields;
        } else {
            throw new Exception("setFields requires an array");
        }
    }
    public function getFields(){ 
        return $this->fields; 
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
 *      $this->CsvExport->setRecordProcessorCallback(array($this, 'capitalise'));
 *
 * @access public
 * @param mixed $callback anything that array_map will recognise.
 * @return void
 */
    public function setRecordProcessorCallback($callback) {
        if (is_callable($callback)) {
            $this->callback = $callback;
        } else {
            throw new Exception("Callback is not callable");
        }
    }
    public function getRecordProcessorCallback() {
        return $this->callback;
    }

/**
 * Return normalised fields in associative machine=>human format,
 * or just an indexed array of only machine or only human values.
 *
 * @param string $output One of 'both', 'slugged', 'human', or 'model'
 * @return mixed Fields, or false if param is dumb
 */
    private function parseFields($output='both') {
        $fields = ($this->fields===null) ? array_keys($this->model->schema()) : $this->fields;
        $normalisedFields = array();
        $models = array();

        foreach ($fields as $key => $value) {
            $hasHuman = !is_numeric($key);
            $field = $hasHuman ? $key : $value;
            $title = $hasHuman ? $value : null;
            $Field = new Field($field, $title);
            $Field->setDefaultModel($this->model->alias);
            $normalisedFields[$Field->getField()] = $Field->getTitle();
            $models[] = $Field->getModel();
        }
        if ($output==='both') {
            return $normalisedFields;
        } elseif ($output==='slugged') {
            return array_keys($normalisedFields);
        } elseif ($output==='human') {
            return array_values($normalisedFields);
        } elseif ($output==='model') {
            return $models;
        } else {
            return false;
        }
    }

/**
 * Return an array of models which should be 'contained' during find,
 * based on the fields requested for the export.
 *
 */
    private function getContainableModels() {
        $models = $this->parseFields('model');
        $models = array_unique($models);
        $key = array_search($this->model->alias, $models);
        if ($key !== false) {
            unset($models[$key]);
        }
        return $models;
    }

}
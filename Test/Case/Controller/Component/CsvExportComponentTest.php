<?php

App::uses('Controller', 'Controller');
App::uses('CsvExportComponent', 'CsvExport.Controller/Component');

class CsvExportComponentTest extends ControllerTestCase {

    public function setUp() {
        parent::setUp();
        $this->_loadComponent();
    }

    protected function _loadComponent() {
        $this->Controller = new Controller();
        $this->Components = new ComponentCollection($this->Controller);
        $this->CsvExport = $this->Components->load('CsvExport.CsvExport');
    }

    public function testObject() {
        $this->assertInstanceOf('CsvExportComponent', $this->CsvExport);
    }

    public function testDebugDefaultsFalse() {
        $this->assertFalse($this->CsvExport->debug);
    }

    public function testSetConditions() {
        $conditions = array('Hat' => 'Sock');
        $this->CsvExport->setConditions($conditions);
        $this->assertEqual($this->CsvExport->getConditions(), $conditions);
    }

    public function testSetFields() {
        $fields = array('field_1' => 'Field', 'field_2');
        $this->CsvExport->setFields($fields);
        $this->assertEqual($this->CsvExport->getFields(), $fields);
    }

    public function testSetCallback() {
        $callbackMethod = array($this, 'testSetCallback');
        $this->CsvExport->setRecordProcessorCallback($callbackMethod);
        $this->assertEqual($this->CsvExport->getRecordProcessorCallback(), $callbackMethod);

        $callbackUserFunc = create_function('$a, $b', 'return $a + $b;');
        $this->CsvExport->setRecordProcessorCallback($callbackUserFunc);
        $this->assertEqual($this->CsvExport->getRecordProcessorCallback(), $callbackUserFunc);
    }

/**
 * @expectedException Exception
 * @expectedExceptionMessage requires an array
 */
    public function testSetInvalidConditions() {
        $conditions = (float)3.1415;
        $this->CsvExport->setConditions($conditions);
    }

/**
 * @expectedException Exception
 * @expectedExceptionMessage requires an array
 */
    public function testSetInvalidFields() {
        $fields = (float)3.1415;
        $this->CsvExport->setFields($fields);
    }

/**
 * @expectedException Exception
 * @expectedExceptionMessage not callable
 */
    public function testSetInvalidCallback() {
        $callback = "I am a callback";
        $this->CsvExport->setRecordProcessorCallback($callback);
    }

/**
 * TODO - this is all fairly awful
 */
    public function testExportFile() {
        require_once('MockUserModel.php');
        $model = new MockUserModel();
        $results = array(
            array('User' => array('name' => 'Steve')),
            array('User' => array('name' => 'Napkin')),
            array('User' => array('name' => 'Turnip')),
        );
        $expected = array(
            array('Name'),
            array('Steve'),
            array('Napkin'),
            array('Turnip'),
        );
        $model->setFindResults($results);
        $this->CsvExport->setFields(array('name'));
        $path = $this->CsvExport->export($model, array('download'=>false));

        $f = fopen($path, 'r');
        $csv = array();
        while ($csv[] = fgetcsv($f)) {}
        array_pop($csv); // remove final 'false' value from fgetcsv
        $this->assertEqual($expected, $csv);
    }

/**
 * TODO - this is similarly awful
 */
    public function testExportDownload() {
        require_once('MockUserModel.php');
        $model = new MockUserModel();
        $results = array(
            array('User' => array('name' => 'Steve')),
            array('User' => array('name' => 'Napkin')),
            array('User' => array('name' => 'Turnip')),
        );
        $expected = <<<EOT
Name
Steve
Napkin
Turnip

EOT;

        $model->setFindResults($results);
        $this->CsvExport->setFields(array('name'));
        ob_start();
        $this->CsvExport->export($model, array('download'=>true));
        $csv = ob_get_contents();
        ob_end_clean();
        header('Content-type: bullshit/socks');
        $this->assertEqual($expected, $csv);
    }


}
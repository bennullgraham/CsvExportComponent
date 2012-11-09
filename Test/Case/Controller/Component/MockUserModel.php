<?php
class MockUserModel {

    // CakePHP model attributes
    public $alias = 'User';
    public $primaryKey = 'id';
    
    // record whether find() has been called already
    private $foundOnce = false;

    // the results find() will return
    private $results = null;

    // mock CakePHP's find() method.
    public function find($type, $params) {
        if ($this->foundOnce) {
            return false;
        } else {
            $this->foundOnce = true;
            return $this->results;
        }
    }

    public function setFindResults($results) {
        $this->results = $results;
    }

    public function getFindResults() {
        return $this->results;
    }
}
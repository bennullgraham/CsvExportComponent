<?php

App::uses('Field', 'CsvExport.Lib');


class FieldTest extends CakeTestCase {

/**
 * @expectedException Exception
 * @expectedExceptionMessage no default provided
 */
    public function testNoDefaultModelException() {
        $field = new Field('name');
        $field->getField();
    }

    public function testDefaultedModel() {
        $model = 'User';
        $column = 'name';
        $field = new Field($column);
        $field->setDefaultModel($model);
        $this->assertEqual($field->getModel(), $model);
        $this->assertEqual($field->getField(), "{$model}.{$column}");
    }

    public function testExplicitModel() {
        $model = 'User';
        $column = 'name';
        $field = new Field("{$model}.{$column}");
        $this->assertEqual($field->getModel(), $model);
        $this->assertEqual($field->getColumn(), $column);
        $this->assertEqual($field->getField(), "{$model}.{$column}");
    }

    public function testOverwriteDefaultModel() {
        $modelDefault = 'User';
        $modelExplicit = 'Vegemite';
        $column = 'name';
        $field = new Field("{$modelExplicit}.{$column}");
        $field->setDefaultModel($modelDefault);
        $this->assertEqual($field->getModel(), $modelExplicit);
        $this->assertEqual($field->getColumn(), $column);
        $this->assertEqual($field->getField(), "{$modelExplicit}.{$column}");
    }

    public function testExplicitTitle() {
        $field = 'User.slugged_column';
        $title = 'Slugged Column Title';
        $field = new Field($field, $title);
        $this->assertEqual($field->getTitle(), $title);
    }

    public function testHumanisedTitle() {
        $field = 'User.slugged_column';
        $field = new Field($field, null);
        $expectedTitle = 'Slugged Column';
        $this->assertEqual($field->getTitle(), $expectedTitle);
    }

}
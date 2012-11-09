<?php
/**
 * Handles normalisation of field specifications and humanisation
 * of field names where necessary.
 *
 * @author bgraham
 */
class Field extends Object {

/**
 * Field specification as provided to constructor
 *
 * @var String
 */
    private $field = null;

/**
 * Human-readable title of field, potentially derived from
 * the machine-readable form of the field.
 *
 * @var String
 */
    private $title = null;

/**
 * The model to use in the field specification if none is
 * present.
 *
 * @var mixed
 */
    private $defaultModel = null;

/**
 * Constructor.
 *
 * @param String $field CakePHP field specification
 * @param String $title optional human-readable title for this field.
 * @return instance of self
 */
    public function Field($field, $title=null) {
        $this->field = $field;
        $this->title = $title;
        return $this;
    }

/**
 * Sets the default model for this Field
 *
 * @param String $model Model name, e.g. "Product"
 */
    public function setDefaultModel($model) {
        $this->defaultModel = $model;
        return $this;
    }

/**
 * Return a field formatted like "Model.column"
 *
 * @return String
 */
    public function getField() {
        return $this->normalise($this->field);
    }

/**
 * Return a human-readable field title like "Column"
 *
 * @return String
 */
    public function getTitle() {
        if ($this->title !== null) {
            return $this->title;
        }
        return Inflector::humanize($this->getColumn());
    }

/**
 * Return the model of this field's specification, e.g.
 * "Model" from "Model.column"
 *
 * @return String
 */
    public function getModel() {
        list($model, $column) = explode('.', $this->getField());
        return $model;
    }

/**
 * Return the model of this field's specification, e.g.
 * "column" from "Model.column"
 *
 * @return String
 */
    public function getColumn() {
        list($model, $column) = explode('.', $this->getField());
        return $column;
    }

/**
 * Take field specifications in either 'Model.column' or plain
 * 'column' format, and normalise them to 'Model.column' format.
 *
 * @param String $field un-normalised field specification
 * @return void
 */
    private function normalise($field) {
        $hasModel = (strpos($field, '.') !== false);

        if ($hasModel === false && $this->defaultModel === null) {
            throw new Exception("Field has no model and no default provided");
        }
        elseif ($hasModel === false) {
            $model = $this->defaultModel;
        }
        elseif ($hasModel === true) {
            list($model, $field) = explode('.', $field);
        }
        return "{$model}.{$field}";
    }

}
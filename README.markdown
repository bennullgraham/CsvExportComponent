CakePHP CSV Export 
====================

What The Devil?
--------------------

CsvExportComponent will turn your database into text 
with commas everywhere. Export data is read page-at-a-time
to keep memory usage under control.

Usage
----------

Include CsvExport as a Component in your controller.

```php
var $components = array('CsvExport');
```

To filter which records are exported, you can provide conditions. These 
look just like the standard conditions array used with `Model->find()`.

```php
$this->CsvExport->setConditions(array(
	'Model.group_id' => 12
));
```

To limit which fields are exported and to provide friendly names for 
fields. If an array item is numerically indexed then it is assumed 
this is a DB field and a human-friendly name is generated from it.

```php
$this->CsvExport->setFields(array(
	'Model.name_first' => 'First Name', // column will be titled 'First Name'
	'Model.birthdate',                  // column will be titled 'Birthdate'
));
```

To cause the CSV output to appear as a file download in-browser, run

```php
$this->CsvExport->export($this->Model);
```

The PHP script is `exit()`ed here.

Debugging
----------

```php
$this->CsvExport->debug = true;
```

This will prevent "download file" headers being sent, keeping output
in the browser. This is useful to get a quick idea of what your CSV
output looks like, without having to have the file open in a CSV
program. If not set, CakePHP's debugging is disabled, as it
does not, surprisingly, generate output in CSV format. As a result, 
errors during export may be masked.

Hooks
----------

```php
$this->CsvExport->setRecordProcessorCallback(array($this, 'capitalise'));
```

In this example, the function `MyController::capitalise()` will be passed every
record before it is exported. The callback function receives one
argument which looks like the results from a `Model->find('first')` call:

```php
array(
	'Model' => array(
		'id' => 1,
		'name' => 'Beans',
	),
	'AssociatedModel' => array(
		'id' => 12,
		'title' => 'Hats',
	),
);
```

`setRecordProcessorCallback()` accepts any argument that `array_map()` will recognise.
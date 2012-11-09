CakePHP CSV Export 
====================

What The Devil?
--------------------

CavExport is a CakePHP plugin containing, basically, just a Component. It will
turn your database into text with commas everywhere. Export data is read page-
at-a-time to keep memory usage under control.

Usage
----------

Include CsvExport as a Component in your controller.

```php
var $components = array('CsvExport.CsvExport');
```

To filter which records are exported, you can provide conditions. These look
just like the standard conditions array used with `Model->find()`.

```php
$this->CsvExport->setConditions(array(
	'Model.group_id' => 12
));
```

To limit which fields are exported and to provide friendly names for fields,
use `setFields()`. The single array argument to this function should contain
keys and values specifying `Model.column` and `Human-readable Title`,
respectively. The Model name may be omitted. If the Human-readable title is
omitted, it will be generated from the column name.

```php
$this->CsvExport->setFields(array(
	'Model.cabbage_id',                    // column will be titled 'Cabbage Id'
	'Model.carrot_id' => 'Carrot Number',  // column will be titled 'Carrot Number'
));
```

To cause the CSV output to appear as a file download in-browser, run

```php
$this->CsvExport->export($this->Model);
```

CsvExportComponent can return the path to a CSV file instead of causing a file
download:

```php
$path = $this->CsvExport->export($this->Model, array('download' => false));
$f = fopen($path)
// ...
```

You can explicitly set a filename stub to use. This is prefixed by the current
date, and suffixed by `.csv`. For example,

```php
$this->CsvExport->export($this->Model, array('filename' => 'user-accounts'));
// user will download '2000-01-01-user-accounts.csv'
```

Debugging
----------

```php
$this->CsvExport->debug = true;
```

This will prevent "download file" headers being sent, keeping output in the
browser. This is useful to get a quick idea of what your CSV output looks
like, without having to have the file open in a CSV program. 

Note: By default, CakePHP's debug level is set to zero to prevent non-CSV-
formatted timing information being output. As a result, errors during export
will be masked, even if you are working in a development environment. Setting
`debug` to true will prevent errors from being masked.

Hooks
----------

```php
$this->CsvExport->setRecordProcessorCallback(array($this, 'capitalise'));
```

In this example, the function `MyController::capitalise()` will be passed
every record before it is exported. The callback function receives one
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

`setRecordProcessorCallback()` accepts any argument that `array_map()` will
recognise.

Tests
-----

With PHPUnit installed,

```bash
cake test CsvExport AllCsvExport
```
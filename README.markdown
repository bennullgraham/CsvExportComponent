CsvExportComponent will turn your database into text 
with commas everywhere.

Usage
----------

Include CsvExport as a Component in your controller.

`var $components = array('CsvExport');`

Use `$this->CsvExport->setConditions(array(...))` if only specific 
records should be exported.

Use `$this->CsvExport->setFields(array(...))` to limit which fields
are exported and to provide friendly names for fields. If an array
item is numerically indexed then it is assumed this is a DB field
and a human-friendly name is generated from it.

Use `$this->CsvExport->export($this->Model);` to
actually do the export thing. This results in CSV output being
written to the output buffer ("the browser") and execution
terminating.

Debugging
----------

`$this->CsvExport->debug = true` will prevent "download file" headers
being sent, keeping output in the browser. If not set, CakePHP's
debugging is disabled, as it does not, surprisingly, generate output
in CSV format.

Hooks
----------

See setRecordProcessorCallback if records require processing before
being exported.
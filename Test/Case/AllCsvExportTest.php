<?php
/**
 * An amalgamation of DebugKit's AllTestsTest and DebugkitGroupTestCase. 100%
 * Copy-paste.
 * 
 */
App::uses('CakeTestSuite', 'TestSuite');
class AllCsvExportTestCase extends CakeTestSuite {

    public static function suite() {
        $suite = new self();
        $files = $suite->getTestFiles();
        $suite->addTestFiles($files);

        return $suite;
    }

    public static function getTestFiles($directory = null, $excludes = null) {
        if (is_array($directory)) {
            $files = array();
            foreach ($directory as $d) {
                $files = array_merge($files, self::getTestFiles($d, $excludes));
            }
            return array_unique($files);
        }

        if ($excludes !== null) {
            $excludes = self::getTestFiles((array)$excludes);
        }
        if ($directory === null || $directory !== realpath($directory)) {
            $basePath = App::pluginPath('CsvExport') . 'Test' . DS . 'Case' . DS;
            $directory = str_replace(DS . DS, DS, $basePath . $directory);
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $files = array();
        while ($it->valid()) {

            if (!$it->isDot()) {
                $file = $it->key();

                if (
                    preg_match('|Test\.php$|', $file) &&
                    $file !== __FILE__ &&
                    !preg_match('|^All.+?\.php$|', basename($file)) &&
                    ($excludes === null || !in_array($file, $excludes))
                ) {

                    $files[] = $file;
                }
            }

            $it->next();
        }

        return $files;
    }
}
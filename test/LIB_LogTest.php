<?php
require_once '../LIB_Log.php';

class LIB_LogTest extends PHPUnit_Framework_TestCase {
    private $_logSrv;
    const DEFAULT_TIME_ZONE = 'Asia/Hong_Kong';

    public static function setUpBeforeClass() {
        define('APPPATH', '.');
        define('ENVIRONMENT', '.');
    }

    public function setUp() {
        date_default_timezone_set(self::DEFAULT_TIME_ZONE);
        $this->_logSrv = new LIB_Log();
    }

    public function tearDown() {
        $this->_logSrv = NULL;
    }

    private function _get_log_json_array($filePath) {
        $result = [];

        $handle = fopen($filePath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                array_push($result, json_decode($line));
            }

            fclose($handle);
        } else {
            // error opening the file.
        }       

        return $result;
    }

    /*** Functional tests ***/
    public function testLogNOTICEMessage() {
        $this->_logSrv->addlog('key', 'Test Value');
        $this->_logSrv->write();        

        // Open the file, and verify the format is correct.
        $expectedFilePath = 'output/sys/sys.'.date('Y-m-d').'.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Test Value', $contents[$lastRow]->key);

        // ... other tests for other fields.
    }

    public function testLogINFOMessage() {

    }

    public function testLogFATALMessage() {
        // .. test stack trace as well.
    }

    public function testLogWARNMessage() {

    }

    /*** Performance tests ***/
    public function testPerformanceOfLogMethod() {
        // Test the number of logs per second (LPS) that the framework can handle. What is the fastest it can do?

        // 1. Single process
        // 2. Multiple processes
        // 3. Multiple threads (if applicable), plus thread safety.
    }

}

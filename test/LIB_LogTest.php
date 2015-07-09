<?php
require_once '../LIB_Log.php';
require_once '../log_config.php';

class LIB_LogTest extends PHPUnit_Framework_TestCase {
	private $_logSrv;
	private $_logid;
	const DEFAULT_TIME_ZONE = 'Asia/Hong_Kong';

	public static function setUpBeforeClass() {
		define('APPPATH', '.');
		define('ENVIRONMENT', '.');
	}

	public function setUp() {
		date_default_timezone_set(self::DEFAULT_TIME_ZONE);
		$this->_logSrv = new LIB_Log();
		$this->_logid  = LIB_Log::genLogID();
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
		$expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.log';
		$this->assertTrue(file_exists($expectedFilePath));

		$contents = $this->_get_log_json_array($expectedFilePath);

		$this->assertNotNull($contents);

		// Test the row just inserted.
		$lastRow = sizeof($contents) - 1;
		$this->assertEquals('Test Value', $contents[$lastRow]->key);
		$this->assertEquals('NOTICE', $contents[$lastRow]->level);
		$this->assertEquals('uc', $contents[$lastRow]->product);
		$this->assertEquals($this->_logid, $contents[$lastRow]->logid);
		$this->assertTrue(isset($contents[$lastRow]->timestamp));
		$this->assertTrue(isset($contents[$lastRow]->date));

		// ... other tests for other fields.
	}

	public function testLogRPCMessage() {
		$this->_logSrv->rpcstart();
		$this->_logSrv->writerpc(array('rpcinfo' => 'Yun ji Test'));
		$expectedFilePath = 'output/rpc/rpc.' . date('Y-m-d') . '.log';
		$this->assertTrue(file_exists($expectedFilePath));

		$contents = $this->_get_log_json_array($expectedFilePath);

		$this->assertNotNull($contents);

		// Test the row just inserted.
		$lastRow = sizeof($contents) - 1;
		$this->assertEquals('Yun ji Test', $contents[$lastRow]->rpcinfo);
		$this->assertEquals('RPC', $contents[$lastRow]->level);
		$this->assertEquals('uc', $contents[$lastRow]->product);
		$this->assertEquals($this->_logid, $contents[$lastRow]->logid);
		$this->assertTrue(isset($contents[$lastRow]->timestamp));
		$this->assertTrue(isset($contents[$lastRow]->date));
		$this->assertTrue(isset($contents[$lastRow]->time));
	}

	public function testLogFATALMessage() {
		// .. test stack trace as well.
		$this->_logSrv->write_log('error', 'Test Value');
		$expectedFilePath = 'output/php/php.' . date('Y-m-d') . '.log';
		$this->assertTrue(file_exists($expectedFilePath));

		$contents = $this->_get_log_json_array($expectedFilePath);

		$this->assertNotNull($contents);

		// Test the row just inserted.
		$lastRow = sizeof($contents) - 1;
		$this->assertEquals('Test Value', $contents[$lastRow]->error);
		$this->assertEquals('FATAL', $contents[$lastRow]->level);
		$this->assertTrue(isset($contents[$lastRow]->trace));
		$this->assertEquals('uc', $contents[$lastRow]->product);
		$this->assertEquals($this->_logid, $contents[$lastRow]->logid);
		$this->assertTrue(isset($contents[$lastRow]->timestamp));
		$this->assertTrue(isset($contents[$lastRow]->date));
	}

	public function testLogWARNMessage() {
		$this->_logSrv->writewarning(array('WARNING' => 'Yun ji Test'));
		$expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.wf';
		$this->assertTrue(file_exists($expectedFilePath));

		$contents = $this->_get_log_json_array($expectedFilePath);

		$this->assertNotNull($contents);

		// Test the row just inserted.
		$lastRow = sizeof($contents) - 1;
		$this->assertEquals('Yun ji Test', $contents[$lastRow]->WARNING);
		$this->assertEquals('WARNING', $contents[$lastRow]->level);
		$this->assertEquals('uc', $contents[$lastRow]->product);
		$this->assertEquals($this->_logid, $contents[$lastRow]->logid);
		$this->assertTrue(isset($contents[$lastRow]->timestamp));
		$this->assertTrue(isset($contents[$lastRow]->date));

	}

	public function testLogSYSMessage() {
		$this->_logSrv->write_log('info', 'Yun ji Test');
		$expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.log';
		$this->assertTrue(file_exists($expectedFilePath));

		$contents = $this->_get_log_json_array($expectedFilePath);

		$this->assertNotNull($contents);

		// Test the row just inserted.
		$lastRow = sizeof($contents) - 1;
		$this->assertEquals('Yun ji Test', $contents[$lastRow]->sysmsg);
		$this->assertEquals('SYS', $contents[$lastRow]->level);
		$this->assertEquals('INFO', $contents[$lastRow]->syslevel);
		$this->assertEquals('uc', $contents[$lastRow]->product);
		$this->assertEquals($this->_logid, $contents[$lastRow]->logid);
		$this->assertTrue(isset($contents[$lastRow]->timestamp));
		$this->assertTrue(isset($contents[$lastRow]->date));

	}

	/*** Performance tests ***/
	public function testPerformanceOfLogMethod() {
		// Test the number of logs per second (LPS) that the framework can handle. What is the fastest it can do?

		// 1. Single process
		// 2. Multiple processes
		// 3. Multiple threads (if applicable), plus thread safety.
	}

}

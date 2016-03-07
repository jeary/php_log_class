<?php
require_once '../LIB_Log.php';

class LIB_LogTest extends PHPUnit_Framework_TestCase
{
    private $_logSrv;
    private $_logid;
    private $_path          = array('./log_config.php');
    const DEFAULT_TIME_ZONE = 'Asia/Hong_Kong';

    public static function setUpBeforeClass()
    {
        define('APPPATH', '.');
        define('ENVIRONMENT', '.');
    }

    public function setUp()
    {
        date_default_timezone_set(self::DEFAULT_TIME_ZONE);
        $this->_logSrv = new LIB_Log();
        $this->_logid  = LIB_Log::genLogID();
        $this->_logSrv->set_config($this->_path);
    }

    public function tearDown()
    {
        $this->_logSrv = null;
    }

    private function _get_log_json_array($filePath)
    {
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
    public function testLogINFOMessage()
    {
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
        $this->assertEquals('INFO', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));
        // ... other tests for other fields.
    }

    public function testLogRPCMessage()
    {
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

    public function testLogFATALMessage()
    {
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

    public function testLogWARNMessage()
    {
        $this->_logSrv->write_warning(array('WARNING' => 'Yun ji Test'));
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

    public function testLogSYSMessage()
    {
        $this->_logSrv->write_log('debug', 'Yun ji debug');
        $expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Yun ji debug', $contents[$lastRow]->msg);
        $this->assertEquals('DEBUG', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));

    }

    public function testLogSYSTESTMessage()
    {
        $this->_logSrv->write_log('test', 'Yun ji test');
        $expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Yun ji test', $contents[$lastRow]->sysmsg);
        $this->assertEquals('TEST', $contents[$lastRow]->syslevel);
        $this->assertEquals('SYS', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));

    }

    public function testLogCommonINFOMessage()
    {
        define('APP', 'login');
        $this->_logSrv->write_log('info', 'Yun ji info');
        $expectedFilePath = 'output/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Yun ji info', $contents[$lastRow]->msg);
        $this->assertEquals('INFO', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));
        //unset(APP);
    }

    public function testLogINFORESETMessage()
    {
        $this->_logSrv->write_log('info', 'Yun ji info');
        $expectedFilePath = 'output/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $logid     = $contents[$lastRow]->logid;
        $timestamp = $contents[$lastRow]->timestamp;

        sleep(1);
        $this->_logSrv->write_log('info', 'Yun ji info');
        $expectedFilePath = 'output/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals($logid, $contents[$lastRow]->logid);
        $this->assertEquals($timestamp, $contents[$lastRow]->timestamp);

        sleep(1);
        $this->_logSrv->init_notice(true);
        $this->_logSrv->write_log('info', 'Yun ji info');
        $expectedFilePath = 'output/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals($logid, $contents[$lastRow]->logid);
        $this->assertFalse($timestamp === $contents[$lastRow]->timestamp);

        sleep(1);
        LIB_Log::genLogID(true);
        $this->_logSrv->init_notice(true);
        $this->_logSrv->write_log('info', 'Yun ji info');
        $expectedFilePath = 'output/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertFalse($logid === $contents[$lastRow]->logid);
        $this->assertFalse($timestamp === $contents[$lastRow]->timestamp);

    }

    public function testLogsetconfigMessage()
    {
        $this->_logSrv->set_config(array('../log_config.php'));
        $this->_logSrv->write_log('info', 'Yun ji info path');
        $expectedFilePath = '/data/logs/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Yun ji info path', $contents[$lastRow]->msg);
        $this->assertEquals('INFO', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));

        $config = array(
            'log_path' => 'output/',
            'product'  => 'uc',
            'level'    => 7,
            'path'     => array(
                'FATAL' => 'php/php',
                'RPC'   => 'rpc/rpc',
                'SYS'   => 'sys/sys',
                'INFO'  => 'ha/ha/login/login',
            ),
            'subffix'  => array(
                'WARNING' => '.wf',
            ),
        );
        $this->_logSrv->set_config(array('../log_config.php'), $config);
        $this->_logSrv->write_log('info', 'Yun ji info path');
        $expectedFilePath = 'output/ha/ha/login/login.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        $this->assertEquals('Yun ji info path', $contents[$lastRow]->msg);
        $this->assertEquals('INFO', $contents[$lastRow]->level);
        $this->assertEquals('uc', $contents[$lastRow]->product);
        $this->assertEquals($this->_logid, $contents[$lastRow]->logid);
        $this->assertEquals(15, strlen($contents[$lastRow]->logid));
        $this->assertTrue(isset($contents[$lastRow]->timestamp));
        $this->assertTrue(isset($contents[$lastRow]->date));
        //unset(APP);
    }
    /*** Performance tests ***/
    public function testPerformanceOfLogMethod()
    {
        // Test the number of logs per second (LPS) that the framework can handle. What is the fastest it can do?

        // 1. Single process
        $this->_logSrv->addlog('key', 'Test5678901234567890123456789012345678900000');
        $start_time = microtime(true);
        $num        = 100000;
        for ($i = 0; $i < $num; $i++) {
            $this->_logSrv->write();
        }
        $end_time = microtime(true);
        // Open the file, and verify the format is correct.
        $expectedFilePath = 'output/sys/sys.' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFilePath));

        $contents = $this->_get_log_json_array($expectedFilePath);

        $this->assertNotNull($contents);

        // Test the row just inserted.
        $lastRow = sizeof($contents) - 1;
        echo "\n time:" . strval($end_time - $start_time) . "\n";
        echo "\n 日志大小：" . strlen(json_encode($contents[$lastRow])) . "\n";
        echo "\n 日志性能：" . $num / intval($end_time - $start_time) . "\n";
        // 2. Multiple processes
        // 3. Multiple threads (if applicable), plus thread safety.
    }

}

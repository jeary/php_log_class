<?php
/**
 * 日志 处理类
 *
 * @author wangyunji
 */
class LIB_Log {
	/**
	 * 测试开关
	 * @var [type]
	 */

	public $debug;
	/**
	 * log init params
	 * @var [type]
	 */

	protected $_noticelog;
	/**
	 * path of log
	 * @var [type]
	 */

	protected $_log_path;
	/**
	 * 是否写日志文件
	 * @var [type]
	 */

	protected $_enabled = TRUE;
	/**
	 * 配置数据
	 * @var [type]
	 */

	protected $_config;
	/**
	 * 日志等级
	 * @var array
	 */
	protected $_levels = array('FATAL' => 1, 'NOTICE' => 2, 'RPC' => 3, 'WARNING' => 4, 'DEBUG' => 5, 'INFO' => 6, 'SYS' => 7);
	/**
	 * Format of timestamp for log files
	 *
	 * @var string
	 */
	protected $_date_fmt = 'Y-m-d H:i:s';
	/**
	 * 日志的基本公用字段信息
	 * @var array
	 */

	private $_log_base = array('logid', 'timestamp', 'date', 'product', 'module');
	/**
	 * 标记耗时变量
	 * @var [type]
	 */

	private $_marker;
	/**
	 * log 构造类
	 * @author wangyunji
	 * @date   2015-05-13
	 */
	public function __construct($init = TRUE) {
		if (defined('APPPATH') && defined('ENVIRONMENT') && file_exists($path = APPPATH . 'config/' . ENVIRONMENT . '/log.php')) {
			include $path;
		} elseif (defined('APPPATH') && file_exists($path = APPPATH . 'config/log.php')) {
			include $path;
		} elseif (defined('FCPATH') && defined('ENVIRONMENT') && file_exists($path = FCPATH . '../shared/config/' . ENVIRONMENT . '/log.php')) {
			include $path;
		} elseif (defined('FCPATH') && file_exists($path = FCPATH . '../shared/config/log.php')) {
			include $path;
		} elseif (file_exists($path = 'log_config.php')) {
			include $path;
		} else {
			$config = array();
		}
		$this->_config = $config;
		!$init or $this->initnotice();
		set_error_handler(array($this, '_error_handler'));
		defined('BASEPATH') or register_shutdown_function(array($this, 'writefatal'));

		$this->_log_path = ($config['log_path'] !== '') ? $config['log_path'] : APPPATH . 'logs/';
		if (!is_dir($this->_log_path)) {
			$path           = realpath($this->_log_path);
			$this->_enabled = $this->_newpath('/', $path);
		}
		if (!$this->_is_really_writable($this->_log_path)) {
			$this->_enabled = FALSE;
		}
		$this->_mark('srvStart');
	}
	/**
	 * php普通错误捕获
	 * @param  [type] $severity [错误类型]
	 * @param  [type] $message [错误信息]
	 * @param  [type] $filepath [报错的文件]
	 * @param  [type] $line [报错的行]
	 * @author wangyunji
	 * @date   2015-07-03
	 */

	public function _error_handler($severity, $message, $filepath, $line) {
		$warning = array(
			'errno'  => $severity,
			'errmsg' => $message,
			'file'   => $filepath,
			'line'   => $line,
		);
		$this->writewarning($warning);
	}
	/**
	 * 写日志处理类
	 * @param  [type] $app [模块名称],用于区分相同系统不同模块之间的日志
	 * @author wangyunji
	 * @date   2015-06-02
	 */

	public function write($app = '') {
		$app = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
		$this->writenotice($app);
	}

	/**
	 * Write Fatal Error
	 * @author wangyunji
	 * @date   2015-07-02
	 */
	public function writefatal($msg = '') {
		$app     = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
		$message = $this->_elements($this->_log_base, $this->initnotice());
		if (error_get_last() && $this->_config['level'] >= $this->_levels['FATAL']) {
			$message['error'] = error_get_last();
			$message['trace'] = $this->_gettrace($app);
			$res              = $this->_write_file('FATAL', $message, $app, 'FATAL');
		} elseif (!empty($msg)) {
			$message['error'] = $msg;
			$message['trace'] = $this->_gettrace($app);
			$res              = $this->_write_file('FATAL', $message, $app, 'FATAL');
		}
	}
	/**
	 * 写notice日志
	 * @param  [type] $app [模块名称]
	 * @author wangyunji
	 * @date   2015-07-02
	 */

	public function writenotice($app) {
		$this->_noticelog['module'] = $app;
		$this->_noticelog['time']   = $this->_elapsed_time('srvStart', 'srvEnd') * 1000;

		$res = $this->_write_file('NOTICE', $this->_noticelog, $app);
	}
	/**
	 * 添加日志项
	 * @param  [type] $key [日志项名称]
	 * @param  [type] $value [日志项值]
	 * @author wangyunji
	 * @date   2015-06-02
	 */

	public function addlog($key, $value) {
		if (isset($this->_noticelog[$key]) && is_array($this->_noticelog[$key]) && is_array($value)) {
			$this->_noticelog[$key] = array_merge($this->_noticelog[$key], $value);
		} else {
			$this->_noticelog[$key] = $value;
		}
	}
	/**
	 * 写rpc日志处理类
	 * @param  [type] $app [模块名称]
	 * @author wangyunji
	 * @date   2015-06-02
	 */

	public function writerpc($rpcdata) {
		$app     = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
		$message = $this->_elements($this->_log_base, $this->initnotice());
		$message = array_merge($message, $rpcdata);

		$message['module'] = $app;
		$message['time']   = $this->_elapsed_time('rpcStart', 'rpcEnd') * 1000;

		$res = $this->_write_file('RPC', $message, $app);
	}
	/**
	 * rpc开始计时
	 * @return [type] [计时类对象]
	 * @author wangyunji
	 * @date   2015-07-03
	 */

	public function rpcstart() {
		$this->_mark('rpcStart');
	}
	/**
	 * 写warning日志处理类
	 * @param  [type] $app [模块名称]
	 * @author wangyunji
	 * @date   2015-06-02
	 */

	public function writewarning($data) {
		$app     = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
		$message = $this->_elements($this->_log_base, $this->initnotice());
		$message = array_merge($message, $data);

		$message['module'] = $app;
		$message['trace']  = $this->_gettrace();

		$res = $this->_write_file('WARNING', $message, $app);
	}
	/**
	 * 功能异常时候记录trace
	 * @return [type] [description]
	 * @author wangyunji
	 * @date   2015-07-02
	 */

	private function _gettrace($app = '') {
		$app    = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
		$result = $this->_elements($this->_log_base, $this->initnotice());
		$trace  = debug_backtrace();
		$need   = array(
			'object_name',
			'type',
			'class',
			'function',
			'file',
			'line',
		);
		$return_trace = array();
		foreach ($trace as $key => $value) {
			$value['object_name'] = isset($value['object']) ? get_class($value['object']) : '';
			$message              = $this->_elements($need, $value);
			$return_trace[]       = $message;
		}
		return $return_trace;
	}
	/**
	 * 生成logid
	 * @return [int] [logid]
	 * @author wangyunji
	 * @date   2015-05-16
	 */
	public static function genLogID() {
		static $logid;
		if (!empty($logid)) {
			return $logid;
		}

		if (!empty($_SERVER['HTTP_X_YMT_LOGID']) && intval(trim($_SERVER['HTTP_X_YMT_LOGID'])) !== 0) {
			$logid = trim($_SERVER['HTTP_X_YMT_LOGID']);
		} elseif (isset($_REQUEST['logid']) && intval($_REQUEST['logid']) !== 0) {
			$logid = trim($_REQUEST['logid']);
		} else {
			$arr = gettimeofday();
			mt_srand(ip2long(self::_gethostip()));
			$logid = sprintf('%04d', mt_rand(0, 999));
			$logid .= sprintf('%03d', rand(0, 999));
			$logid .= sprintf('%04d', $arr['usec'] % 10000);
			$logid .= sprintf('%04d', $arr['sec'] % 3600);
			//$logid = ((($arr['sec'] * 100000 + $arr['usec'] % 1000) & 0x7FFFFFFF) | 0x80000000);
		}
		return $logid;
	}

	/**
	 * CI 的系统log重写，如果使用CI框架，通过这个换算可以控制此的日志
	 * @param  [type] $level [日志等级]
	 * @param  [type] $msg [日志的内容]
	 * @return [type] [description]
	 * @author wangyunji
	 * @date   2015-07-02
	 */

	public function write_log($level, $msg) {
		$level = strtoupper($level);
		if ('ERROR' === $level) {
			$this->writefatal($msg);
			return intval(TRUE);
		}
		$result = $this->_elements($this->_log_base, $this->initnotice());
		if (!array_key_exists($level, $this->_levels)) {
			$message = array(
				'syslevel' => $level,
				'sysmsg'   => $msg,
			);
			$message = array_merge($result, $message);
			$res     = $this->_write_file('SYS', $message);
			return intval($res);
		} else {
			$result['msg'] = $msg;
			$res           = $this->_write_file($level, $result);
			return intval($res);
		}
	}
	/**
	 * 初始化日志通用信息
	 * @param  [type] $config [配置信息]
	 * @author wangyunji
	 * @date   2015-06-23
	 */

	public function initnotice() {
		static $notice_init;
		if (!empty($notice_init)) {
			$this->_noticelog = $notice_init;
			return $notice_init;
		}
		$this->_noticelog['level']     = 'NOTICE';
		$this->_noticelog['logid']     = self::genLogID();
		$this->_noticelog['timestamp'] = time();
		$this->_noticelog['date']      = date($this->_date_fmt, $this->_noticelog['timestamp']);
		$this->_noticelog['product']   = isset($this->_config['product']) ? $this->_config['product'] : 'unknow';
		$this->_noticelog['module']    = '';
		$this->_noticelog['errno']     = '';
		$this->_noticelog['cookie']    = isset($_COOKIE) ? $_COOKIE : '';
		$this->_noticelog['method']    = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
		$this->_noticelog['uri']       = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$this->_noticelog['caller_ip'] = self::_getclientip();
		$this->_noticelog['host_ip']   = self::_gethostip();

		$notice_init = $this->_noticelog;
		return $notice_init;
	}
	/**
	 * 获取数组的指定参数
	 * @param  [type] $items [key数组]
	 * @param  [type] $array [原数组]
	 * @param  [type] $default [如果变量在数组中不存在时，返回的默认值]
	 * @return [type] [查找后他数组]
	 * @author wangyunji
	 * @date   2015-07-02
	 */

	private function _elements($items, $array, $default = '') {
		$return = array();

		is_array($items) OR $items = array($items);
		foreach ($items as $item) {
			$return[$item] = array_key_exists($item, $array) ? $array[$item] : $default;
		}
		return $return;
	}
	/**
	 * 将日志写入对应的文件中，根据日志的等级不同，可能将日志写入不同的文件
	 * @param  [string] $level [日志的等级]
	 * @param  [array] $msg [日志内容]
	 * @param  string $app [日志的子路径]
	 * @param  string $subffix [日志文件后缀]
	 * @return [type] [FALSE:写日志失败；TRUE:写日志成功]
	 * @author wangyunji
	 * @date   2015-07-03
	 */

	private function _write_file($level, $msg, $app = 'sys', $type = '') {
		$level = strtoupper($level);
		if (!$this->_enabled ||
			!isset($this->_levels[$level]) ||
			$this->_config['level'] < $this->_levels[$level]) {
			return FALSE;
		}
		$msg      = array_merge(array('level' => $level), $msg);
		$level    = empty($type) ? $level : $type;
		$subffix  = isset($this->_config['subffix'][$level]) ? $this->_config['subffix'][$level] : '.log';
		$app_path = $this->_log_path . $app . '/' . $app . '.' . date('Y-m-d') . $subffix;
		$filepath = !isset($this->_config['path'][$level]) ? $app_path : $this->_log_path . $this->_config['path'][$level] . '.' . date('Y-m-d') . $subffix;
		if (!file_exists($filepath)) {
			$path = !isset($this->_config['path'][$level]) ? $app . '/' . $app : $this->_config['path'][$level];
			if (preg_match('/^([\w\_\/\.]+)\/([^\/]+)$/', $path, $res)) {
				$this->_newpath($this->_log_path, $res[0]);
			}
		}
		if (TRUE === $this->debug) {
			echo '======path========' . "\n";
			echo $filepath . "\n";
			echo '=====content======' . "\n";
			echo json_encode($msg) . "\n";
		} else {
			file_put_contents($filepath, json_encode($msg) . "\n", FILE_APPEND);
		}
		return TRUE;
	}
	/**
	 * 子路径不存在，创建路径
	 * @param  [type] $father [父目录，根目录]
	 * @param  [type] $child [子目录]
	 * @return [type] [TRUE:成功]
	 * @author wangyunji
	 * @date   2015-08-04
	 */

	private function _newpath($father, $child) {
		if (FALSE === $this->_enabled) {
			return FALSE;
		}
		$path_arr = explode('/', $child);
		foreach ($path_arr as $key => $value) {
			$_newpath = $father . '/' . $value;
			if (!file_exists($_newpath)) {
				if (!$this->_is_really_writable($father)) {
					return FALSE;
				}
				mkdir($_newpath, 0755, true);
			}
		}
		return TRUE;
	}
	/**
	 * Set a benchmark marker
	 *
	 * Multiple calls to this function can be made so that several
	 * execution points can be timed.
	 *
	 * @param	string	$name	Marker name
	 * @return	void
	 */
	private function _mark($name) {
		$this->_marker[$name] = microtime(TRUE);
	}
	/**
	 * Elapsed time
	 *
	 * Calculates the time difference between two marked points.
	 *
	 * If the first parameter is empty this function instead returns the
	 * {elapsed_time} pseudo-variable. This permits the full system
	 * execution time to be shown in a template. The output class will
	 * swap the real value for this variable.
	 *
	 * @param	string	$point1		A particular marked point
	 * @param	string	$point2		A particular marked point
	 * @param	int	$decimals	Number of decimal places
	 *
	 * @return	string	Calculated elapsed time on success,
	 *			an '{elapsed_string}' if $point1 is empty
	 *			or an empty string if $point1 is not found.
	 */
	private function _elapsed_time($point1 = '', $point2 = '', $decimals = 4) {
		if (!isset($this->_marker[$point1])) {
			return 0;
		}
		$this->_marker[$point2] = microtime(TRUE);
		return number_format($this->_marker[$point2] - $this->_marker[$point1], $decimals);
	}/**
	 * Tests for file writability
	 *
	 * is_writable() returns TRUE on Windows servers when you really can't write to
	 * the file, based on the read-only attribute. is_writable() is also unreliable
	 * on Unix servers if safe_mode is on.
	 *
	 * @link    https://bugs.php.net/bug.php?id=54709
	 * @param    string
	 * @return    bool
	 */
	private function _is_really_writable($file) {
		// If we're on a Unix server with safe_mode off we call is_writable
		if (DIRECTORY_SEPARATOR === '/' && (version_compare(PHP_VERSION, '5.4', '>=') or !ini_get('safe_mode'))) {
			return is_writable($file);
		}

		/* For Windows servers and safe_mode "on" installations we'll actually
		 * write a file then read it. Bah...
		 */
		if (is_dir($file)) {
			$file = rtrim($file, '/') . '/' . md5(mt_rand());
			if (($fp = @fopen($file, 'ab')) === false) {
				return false;
			}

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			return true;
		} elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
			return false;
		}

		fclose($fp);
		return true;
	}

	private static function _getclientip() {
		$ip = array_key_exists('HTTP_X_REAL_IP', $_SERVER) ? $_SERVER['HTTP_X_REAL_IP'] : (
			array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (
				array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] :
				'0.0.0.0'));
		return $ip;
	}

	private static function _gethostip() {
		return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
	}

}

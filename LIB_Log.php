<?php
/**
 * 日志 处理类
 *
 * @author wangyunji
 */
class LIB_Log
{
    /**
     * 测试开关
     * @var [type]
     */

    public $debug;
    /**
     * log init params
     * @var [type]
     */

    protected $_infolog;
    /**
     * path of log
     * @var [type]
     */

    protected $_log_path;
    /**
     * 是否写日志文件
     * @var [type]
     */

    protected $_enabled = true;
    /**
     * 配置数据
     * @var [type]
     */

    protected $_config = array();
    /**
     * 日志等级
     * @var array
     */
    protected $_levels = array('FATAL' => 1, 'INFO' => 2, 'RPC' => 3, 'WARNING' => 4, 'DEBUG' => 5, 'SYS' => 6);
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

    private $_log_base = array('level', 'logid', 'timestamp', 'date', 'product', 'module');
    /**
     * 标记耗时变量
     * @var [type]
     */

    private $_marker;
    /**
     * Log的构造类
     * @param  bool $reset [是否要重置日志公共信息，时间等]
     * @author wangyunji
     * @date   2016-03-07
     */

    public function __construct()
    {
        // 配置信息
        $this->set_config();
        // 初始化公共信息的判断
        $this->init_notice();
        // 捕获错误信息设置
        set_error_handler(array($this, '_error_handler'));
        defined('BASEPATH') or register_shutdown_function(array($this, 'write_fatal'));
        // 记录服务的起始时间点
        $this->srvStart(false);
    }
    /**
     * 获取CI框架中的配置信息
     * @return [bool]
     * @author wangyunji
     * @date   2016-03-07
     */

    public function get_CI_config()
    {
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
        if (is_array($config) && !empty($config)) {
            $this->_config = $config;
            return true;
        }
        return false;
    }
    /**
     * 设置配置(通过文件路径，或者配置来设置配置,优先读取配置信息)
     * 最后判断是否是CI框架，读取CI框架的配置
     * @param  [array] $path_arr [配置路径信息]读取配置文件中的$config配置项
     * @param  [array] $config [配置信息]
     * @return [bool] [true,成功；false,失败]
     * @author wangyunji
     * @date   2015-09-01
     */

    public function set_config($path_arr = array(), $config = array())
    {
        if (is_array($config) && !empty($config)) {
            $this->_config = $config;
            $this->_set_enable($this->_config);
            return true;
        }
        $config = array();
        if (!empty($path_arr) && is_array($path_arr)) {
            foreach ($path_arr as $key => $file) {
                if (file_exists($file)) {
                    include $file;
                    //include 读取新的$config
                    if (is_array($config) && !empty($config)) {
                        $this->_config = $config;
                        $this->_set_enable($this->_config);
                        return true;
                    }
                }
            }
        }
        // ci中是否有配置
        if (true === $this->get_CI_config()) {
            $this->_set_enable($this->_config);
        }
        return false;
    }

    /**
     * 初始化起始时间
     * @param  [bool] $force [是否覆盖之前的起始时间设置]
     * @return [type] [description]
     * @author wangyunji
     * @date   2015-09-23
     */

    public function srvStart($force = true)
    {
        if (true === $force || empty($this->_marker['srvStart'])) {
            $this->_mark('srvStart');
        }
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

    public function _error_handler($severity, $message, $filepath, $line)
    {
        $warning = array(
            'errno'  => $severity,
            'errmsg' => $message,
            'file'   => $filepath,
            'line'   => $line,
        );
        $this->write_warning($warning);
    }
    /**
     * 写日志处理类
     * @param  [type] $app [模块名称],用于区分相同系统不同模块之间的日志
     * @author wangyunji
     * @date   2015-06-02
     */

    public function write($app = '')
    {
        $app = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
        $this->write_info($app);
    }

    /**
     * Write Fatal Error
     * @author wangyunji
     * @date   2015-07-02
     */
    public function write_fatal($msg = '')
    {
        $app     = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
        $message = $this->_elements($this->_log_base, $this->init_notice());
        if (error_get_last() && $this->_config['level'] >= $this->_levels['FATAL']) {
            $message['error'] = error_get_last();
            $message['trace'] = $this->_get_trace($app);
            $res              = $this->_write_file('FATAL', $message, $app);
        } elseif (!empty($msg)) {
            $message['error'] = $msg;
            $message['trace'] = $this->_get_trace($app);
            $res              = $this->_write_file('FATAL', $message, $app);
        }
    }
    /**
     * 写info(旧的notice)日志（兼容旧版的notice日志等级）
     * @param  [type] $app [模块名称]
     * @author wangyunji
     * @date   2015-07-02
     */

    public function write_info($app)
    {
        $this->_infolog['module'] = $app;
        $this->_infolog['time']   = $this->_elapsed_time('srvStart', 'srvEnd') * 1000;

        $res = $this->_write_file('INFO', $this->_infolog, $app);
    }
    /**
     * 添加日志项
     * @param  [type] $key [日志项名称]
     * @param  [type] $value [日志项值]
     * @author wangyunji
     * @date   2015-06-02
     */

    public function addlog($key, $value)
    {
        if (isset($this->_infolog[$key]) && is_array($this->_infolog[$key]) && is_array($value)) {
            $this->_infolog[$key] = array_merge($this->_infolog[$key], $value);
        } else {
            $this->_infolog[$key] = $value;
        }
    }
    /**
     * 写rpc日志处理类
     * @param  [array] $rpcdata [日志信息]
     * @author wangyunji
     * @date   2015-06-02
     */

    public function writerpc($rpcdata)
    {
        $app     = defined('APP') ? APP : 'sys';
        $message = $this->_elements($this->_log_base, $this->init_notice());
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

    public function rpcstart()
    {
        $this->_mark('rpcStart');
    }
    /**
     * 写warning日志处理类
     * @param  [array] $data [日志信息]
     * @author wangyunji
     * @date   2015-06-02
     */

    public function write_warning($data = array())
    {
        $app     = defined('APP') ? APP : 'sys';
        $message = $this->_elements($this->_log_base, $this->init_notice());
        $message = array_merge($message, $data);

        $message['module'] = $app;
        $message['trace']  = $this->_get_trace();

        $res = $this->_write_file('WARNING', $message, $app);
    }
    /**
     * 功能异常时候记录trace
     * @return [type] [description]
     * @author wangyunji
     * @date   2015-07-02
     */

    private function _get_trace($app = '')
    {
        $app    = !empty($app) ? $app : (defined('APP') ? APP : 'sys');
        $result = $this->_elements($this->_log_base, $this->init_notice());
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
     * @param  [bool] $reset [是否需要覆盖掉之前的logid信息]
     * @return [int] [logid]
     * @author wangyunji
     * @date   2015-05-16
     */
    public static function genLogID($reset = false)
    {
        static $logid;
        if (!empty($logid) && false === $reset) {
            return $logid;
        }
        if (!empty($_SERVER['HTTP_X_YMT_LOGID']) && intval(trim($_SERVER['HTTP_X_YMT_LOGID'])) !== 0) {
            $logid = trim($_SERVER['HTTP_X_YMT_LOGID']);
        } elseif (isset($_REQUEST['logid']) && intval($_REQUEST['logid']) !== 0) {
            $logid = trim($_REQUEST['logid']);
        } else {
            $timestamp = explode(' ', microtime());
            $pack_0    = sprintf('%04d', $timestamp[1] % 3600);
            $pack_1    = sprintf('%04d', intval(($timestamp[0] * 1000000) % 1000));
            $pack_2    = sprintf('%03d', mt_rand(0, 987654321) % 1000);
            $pack_3    = sprintf('%04d', crc32(self::_gethostip() * (mt_rand(0, 987654321) % 1000)) % 10000);
            $logid     = ($pack_0 . $pack_1 . $pack_2 . $pack_3);
            //$logid = ((($arr['sec'] * 100000 + $arr['usec'] % 1000) & 0x7FFFFFFF) | 0x80000000);
        }
        return $logid;
    }

    /**
     * CI 的系统log重写，如果使用CI框架，通过这个换算可以控制此的日志
     * @param  [string] $level [日志等级]
     * @param  [string/array] $msg [日志的内容]
     * @return [type] [description]
     * @author wangyunji
     * @date   2015-07-02
     */

    public function write_log($level, $msg)
    {
        $level = strtoupper($level);
        if ('ERROR' === $level) {
            $this->write_fatal($msg);
            return intval(true);
        }
        $result = $this->_elements($this->_log_base, $this->init_notice());
        if (!array_key_exists($level, $this->_levels)) {
            $message = array(
                'syslevel' => $level,
                'sysmsg'   => $msg,
            );
            $message = array_merge($result, $message);
            $res     = $this->_write_file('SYS', $message);
            return intval($res);
        } else {
            is_array($msg) ? $result = array_merge($result, $msg) : $result['msg'] = $msg;
            $res                     = $this->_write_file($level, $result);
            return intval($res);
        }
    }
    /**
     * 初始化日志通用信息
     * @param  [type] $config [配置信息]
     * @author wangyunji
     * @date   2015-06-23
     */

    public function init_notice($reset = false)
    {
        static $notice_init;
        if (!empty($notice_init) && is_array($notice_init) && false === $reset) {
            $this->_infolog = $notice_init;
            return $notice_init;
        }
        $this->_infolog['level']      = 'NOTICE';
        $this->_infolog['logid']      = self::genLogID();
        $this->_infolog['timestamp']  = time();
        $this->_infolog['date']       = date($this->_date_fmt, $this->_infolog['timestamp']);
        $this->_infolog['product']    = isset($this->_config['product']) ? $this->_config['product'] : 'unknow';
        $this->_infolog['module']     = '';
        $this->_infolog['errno']      = '';
        $this->_infolog['cookie']     = isset($_COOKIE) ? $_COOKIE : '';
        $this->_infolog['method']     = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $this->_infolog['uri']        = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $this->_infolog['caller_ip']  = self::_getclientip();
        $this->_infolog['host_ip']    = self::_gethostip();
        $this->_infolog['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $notice_init = $this->_infolog;
        return $notice_init;
    }

    /**
     * 获取客户端ip
     * @return [type] [访问客户ip地址]
     * @author wangyunji
     * @date   2016-03-07
     */

    public function getClientIp()
    {
        return self::_getclientip();
    }
    /**
     * 获取访问的客户ip地址
     *
     * @return [type] [description]
     * @author wangyunji
     * @date   2016-03-07
     */

    private static function _getclientip()
    {
        $ip = array_key_exists('HTTP_X_REAL_IP', $_SERVER) ? $_SERVER['HTTP_X_REAL_IP'] : (
            array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (
                array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] :
                '0.0.0.0'));
        //识别代理
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match('/^10\./', $ip) && preg_match('/([\d\.]+)(\, 10\.([\d\.]+)){1,}$/', $_SERVER['HTTP_X_FORWARDED_FOR'], $res)) {
            $ip = $res[1];
        }
        return $ip;
    }
    /**
     * 获取本机地址
     * @return [type] [description]
     * @author wangyunji
     * @date   2016-03-07
     */

    private static function _gethostip()
    {
        return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
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

    private function _elements($items, $array, $default = '')
    {
        $return = array();

        is_array($items) or $items = array($items);
        foreach ($items as $item) {
            $return[$item] = array_key_exists($item, $array) ? $array[$item] : $default;
        }
        return $return;
    }
    /**
     * // 判断是否能将日志写入对应文件
     * @param  [type] $config [description]
     * @author wangyunji
     * @date   2016-03-07
     */

    private function _set_enable($config)
    {
        $this->_log_path = ($config['log_path'] !== '') ? $config['log_path'] : APPPATH . 'logs/';
        if (!is_dir($this->_log_path) or !$this->_is_really_writable($this->_log_path)) {
            $this->_enabled = false;
        }
    }
    /**
     * 将日志写入对应的文件中，根据日志的等级不同，可能将日志写入不同的文件
     * @param  [string] $level [日志的等级]
     * @param  [array] $msg [日志内容]
     * @param  string $app [日志的子路径]
     * @param  string $subffix [日志文件后缀]
     * @return [bool] [FALSE:写日志失败；TRUE:写日志成功]
     * @author wangyunji
     * @date   2015-07-03
     */

    private function _write_file($level, $msg, $app = 'sys')
    {
        $app   = defined('APP') ? APP : $app;
        $level = strtoupper($level);
        if (!$this->_enabled ||
            !isset($this->_levels[$level]) ||
            $this->_config['level'] < $this->_levels[$level]) {
            return false;
        }
        $msg['level'] = $level = empty($level) ? $msg['level'] : $level; //array_merge(array('level' => $level), $msg);
        // 后缀
        $subffix = isset($this->_config['subffix'][$level]) ? $this->_config['subffix'][$level] : '.log';
        // 默认逻辑
        $app_path = $this->_log_path . $app . '/' . $app . '.' . date('Y-m-d') . $subffix;
        // 结合配置生成最终路径
        $filepath = !isset($this->_config['path'][$level]) ? $app_path : $this->_log_path . $this->_config['path'][$level] . '.' . date('Y-m-d') . $subffix;
        // 路径不存在，生成对应路径,日志文件设置为777权限
        if (!file_exists($filepath) && preg_match('/^([\w\_\/\.]+)\/([^\/]+)$/', $filepath, $res)) {
            file_exists($res[1]) or mkdir($res[1], 0777, true);
            touch($filepath);
            chmod($filepath, 0777);
        }
        file_put_contents($filepath, json_encode($msg) . "\n", FILE_APPEND);
        return true;
    }
    /**
     * Set a benchmark marker
     *
     * Multiple calls to this function can be made so that several
     * execution points can be timed.
     *
     * @param    string    $name    Marker name
     * @return    void
     */
    private function _mark($name)
    {
        $this->_marker[$name] = microtime(true);
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
     * @param    string    $point1        A particular marked point
     * @param    string    $point2        A particular marked point
     * @param    int    $decimals    Number of decimal places
     *
     * @return    string    Calculated elapsed time on success,
     *            an '{elapsed_string}' if $point1 is empty
     *            or an empty string if $point1 is not found.
     */
    private function _elapsed_time($point1 = '', $point2 = '', $decimals = 4)
    {
        if (!isset($this->_marker[$point1])) {
            return 0;
        }
        $this->_marker[$point2] = microtime(true);
        return number_format($this->_marker[$point2] - $this->_marker[$point1], $decimals);
    }
    /**
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
    private function _is_really_writable($file)
    {
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

}

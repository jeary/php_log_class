# php_log_class
php的日志处理类

# 文件说明
LIB_Log.php   文件是日志处理类的代码文件

log_config.php  文件是它的配置文件

log.php   是测试文件

# 功能说明
本日志处理类支持FATAl日志、NOTICE日志、TRACE日志、RPC日志、SYS等级日志。

FATAL日志：php异常中断日志。

NOTICE日志：请求的功能日志，每次请求只记录一条日志

TRACE日志：调用关系日志

RPC日志：对依赖接口的调用日志，调用异常后端依赖就应该记录一条RPC日志

SYS日志：对CI框架起作用，如果使用的是CI框架，用这个日志类覆盖CI的系统日志，SYS等级就是用户记录CI系统的日志。

# 调用demo
记录NOTICE日志：

$app = 'login';//模块名称,控制日志的子目录 同时这个参数还支持`defind('APP','login');`方式来支持

$logSrv = new LIB_Log();//日志类初始化

$logSrv->addlog('key', 'value');//添加NOTICE日志

$logSrv->write($app);

记录RPC日志：
$logSrv = new LIB_Log();//日志类初始化

$logSrv->rpcstart();//rpc调用的起始时间记录

$logSrv->writerpc(array('rpc_params' => 'rpc_value'));




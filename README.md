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
```
$app = 'login';//模块名称,控制日志的子目录 同时这个参数还支持`defind('APP','login');`方式来支持
$logSrv = new LIB_Log();//日志类初始化
$logSrv->addlog('key', 'value');//添加NOTICE日志
$logSrv->write($app);
```
记录RPC日志：
```
$logSrv = new LIB_Log();//日志类初始化

$logSrv->rpcstart();//rpc调用的起始时间记录

$logSrv->writerpc(array('rpc_params' => 'rpc_value'));
```

# 配置文件说明
配置文件说明
```
$config = array(
	'log_path' => '/data/logs/',
	'product'  => 'uc',
	'level'    => 4,
	'path'     => array(
		'FATAL' => 'php/php.log.',
		'RPC'   => 'rpc/rpc.log.',
		'SYS'   => 'cisys/sys.log.',
	),
);
```
说明：

`log_path`:日志的根目录配置，日志将会全部记录在这个目录下，日志可能会根据模块名称不同或者日志等级不同而被记录到不同的子目录下。

`product`:系统名称uc代表`user center`

`level`:日志的等级。
根据代码中的：
```
protected $_levels = array('FATAL' => 1, 'NOTICE' => 3, 'RPC' => 3, 'WARNING' => 4, 'TRACE' => 5, 'SYS' => 6, 'ALL' => 7);
```
来配置日志的等级，如果配置的是`3`，那么系统只会记录等级小于等于`4`的日志，这里就是`FATAL`,`NOTICE`,`RPC`

`path`:根据这个配置，可以强制将不同等级的日志记录到不同的目录中。

# 测试结果
当level配置为`4`时：
```
-------------------test write notice start-----------------------
======path========
/data/logs/sys/sys.log.2015-07-03
=====content======
{"level":"NOTICE","logid":2156165134,"timestamp":1435893553,"date":"2015-07-03 11:19:13","product":"uc","module":"sys","cookie":[],"method":"","uri":"","caller_ip":"","host_ip":"","key":"value","time":0.3}
-------------------test write notice   end-----------------------





-------------------test write notice write app start-----------------------
======path========
/data/logs/app/app.log.2015-07-03
=====content======
{"level":"NOTICE","logid":2156165134,"timestamp":1435893553,"date":"2015-07-03 11:19:13","product":"uc","module":"app","cookie":[],"method":"","uri":"","caller_ip":"","host_ip":"","key":"value","time":1.3}
-------------------test write notice  write app  end-----------------------





-------------------test write rpc start-----------------------
======path========
/data/logs/rpc/rpc.log.2015-07-03
=====content======
{"level":"RPC","logid":2156165134,"timestamp":1435893553,"date":"2015-07-03 11:19:13","product":"uc","module":"sys","rpc_params":"rpc_value","time":0}
-------------------test write rpc  end-----------------------

```

当level配置为`7`时：
```

-------------------test write notice start-----------------------
======path========
/data/logs/sys/sys.log.2015-07-03
=====content======
{"level":"TRACE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"sys","file":"\/Users\/wangyunji\/phpui\/phplib\/rdtest\/LIB_Log.php","line":94,"function":"writetrace","class":"LIB_Log"}
======path========
/data/logs/sys/sys.log.2015-07-03
=====content======
{"level":"TRACE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"sys","file":"\/Users\/wangyunji\/phpui\/phplib\/rdtest\/log.php","line":10,"function":"write","class":"LIB_Log"}
======path========
/data/logs/sys/sys.log.2015-07-03
=====content======
{"level":"NOTICE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"sys","cookie":[],"method":"","uri":"","caller_ip":"","host_ip":"","key":"value","time":0.4}
-------------------test write notice   end-----------------------





-------------------test write notice write app start-----------------------
======path========
/data/logs/app/app.log.2015-07-03
=====content======
{"level":"TRACE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"app","file":"\/Users\/wangyunji\/phpui\/phplib\/rdtest\/LIB_Log.php","line":94,"function":"writetrace","class":"LIB_Log"}
======path========
/data/logs/app/app.log.2015-07-03
=====content======
{"level":"TRACE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"app","file":"\/Users\/wangyunji\/phpui\/phplib\/rdtest\/log.php","line":16,"function":"write","class":"LIB_Log"}
======path========
/data/logs/app/app.log.2015-07-03
=====content======
{"level":"NOTICE","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"app","cookie":[],"method":"","uri":"","caller_ip":"","host_ip":"","key":"value","time":11.7}
-------------------test write notice  write app  end-----------------------





-------------------test write rpc start-----------------------
======path========
/data/logs/rpc/rpc.log.2015-07-03
=====content======
{"level":"RPC","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"sys","rpc_params":"rpc_value","time":0}
-------------------test write rpc  end-----------------------





-------------------test write warning start-----------------------
======path========
/data/logs/sys/sys.wf.log.2015-07-03
=====content======
{"level":"WARNING","logid":2314863631,"timestamp":1435895140,"date":"2015-07-03 11:45:40","product":"uc","module":"sys","warning_params":"warning_value"}
-------------------test write warning  end-----------------------


```

fatal 日志demo：

```
======path========
/data/logs/php/php.log.2015-07-03
=====content======
{"level":"FATAL","logid":3999749131,"timestamp":1435890514,"date":"2015-07-03 10:28:34","product":"uc","module":"","error":{"type":2,"message":"Missing argument 2 for LIB_Log::writerpc(), called in \/Users\/wangyunji\/phpui\/phplib\/rdtest\/log.php on line 22 and defined","file":"\/Users\/wangyunji\/phpui\/phplib\/rdtest\/LIB_Log.php","line":145}}
```





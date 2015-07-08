<?php
//日志配置
$config = array(
	'log_path' => 'output/',
	'product'  => 'uc',
	'level'    => 3,
	'path'     => array(
		'FATAL' => 'php/php',
		'RPC'   => 'rpc/rpc',
		'SYS'   => 'sys/sys',
	),
	'suffix'  => array(
		'WARNING' => '.wf',
	),
);

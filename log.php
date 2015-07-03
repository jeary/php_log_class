<?php
require_once 'LIB_Log.php';

//生成一个session
$logSrv        = new LIB_Log();
$logSrv->debug = TRUE;

echo "\n\n\n-------------------test write notice start-----------------------\n";
$logSrv->addlog('key', 'value');
$logSrv->write();

echo "-------------------test write notice   end-----------------------\n\n\n";

echo "\n\n\n-------------------test write notice write app start-----------------------\n";
$logSrv->addlog('key', 'value');
$logSrv->write('app');

echo "-------------------test write notice  write app  end-----------------------\n\n\n";

echo "\n\n\n-------------------test write rpc start-----------------------\n";
$logSrv->rpcstart();
$logSrv->writerpc(array('rpc_params' => 'rpc_value'));

echo "-------------------test write rpc  end-----------------------\n\n\n";

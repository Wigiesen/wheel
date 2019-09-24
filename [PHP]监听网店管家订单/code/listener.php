<?php
require_once 'sqlsrv.php';

$DB = [
    'hostname' => '',  // 数据库地址
    'username' => '',  // 用户名
    'password' => '',  // 密码
    'dbname'   => '',  // 数据库名称
    'port'     => 1433,  // 端口
];
$DB = SQLSrv::getdatabase($DB);

$lastID = $DB->find("select BillID from G_API_TradeList order by BillID desc")['BillID'];
while (1) {
    $newID = $DB->find("select BillID from G_API_TradeList order by BillID desc")['BillID'];
    if ($newID > $lastID) {
        $newOrderListCount = $newID - $lastID;
        echo "当前最新订单的ID：{$lastID} , 在监听时间内已更新了 {$newOrderListCount} 条新订单 - ". date('Y-m-d H:i:s') ."\r\n";
        $lastID = $newID;
    }else{
        echo "当前最新订单的ID：{$lastID} , 在监听时间内没有新的订单 - ". date('Y-m-d H:i:s') ."\r\n";
    }
    sleep(30);
}
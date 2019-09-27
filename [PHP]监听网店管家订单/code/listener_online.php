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

function sendTemplateData($lastID, $newID){
	global $DB;
	for ($i = $newID; $i > $lastID; $i--) {
		// 订单详情
		$orderInfo = $DB->find("select BillID,TradeNO,TotalMoney,ShopID,CustomerName,Phone,Province,City,Town,Adr from G_API_TradeList where BillID = {$i}");
		
		// 店铺名称
		$shopName = $DB->find("select ShopName from G_Cfg_ShopList where ShopID = {$orderInfo['ShopID']}")['ShopName'];
		
		// 商品详情
		$goodsInfo = $DB->findAll("select TradeGoodsName,GoodsCount from G_API_TradeGoods where BillID = {$i}");
		
		// 拼合数据
		$orderInfo['TotalMoney'] = sprintf("%.2f", $orderInfo['TotalMoney']);
		$orderInfo['shopName'] = $shopName;
		$orderInfo['addr'] = "{$orderInfo['Province']}{$orderInfo['City']}{$orderInfo['Town']}{$orderInfo['Adr']}";
		unset($orderInfo['Province'], $orderInfo['City'], $orderInfo['Town'], $orderInfo['Adr']);
		$orderInfo['goodsInfo'] = $goodsInfo;
		
		// SQLServer GBK转UTF8
		foreach($orderInfo as $key => $item){
			if($key == 'goodsInfo'){
				foreach($item as $shop => $shopitem){
					$orderInfo['goodsInfo'][$shop]['TradeGoodsName'] = iconv('GB2312','UTF-8',$shopitem['TradeGoodsName']);
					$orderInfo['goodsInfo'][$shop]['GoodsCount'] = intval($shopitem['GoodsCount']);
				}
			}else{
				$orderInfo[$key] = iconv('GB2312','UTF-8', $item);
			}
		}
		
		// 组合商品数据字符串
		$goodsInfoString = "";
		foreach($orderInfo['goodsInfo'] as $key => $item){
			$goodsInfoString .= "{$item['TradeGoodsName']} × {$item['GoodsCount']}; \n";
		}
		$goodsInfoString .= "实付金额: {$orderInfo['TotalMoney']}";
		
		// 微信模板数据生成
		$wxTemaplteData = array(
			'first' => array(
				'value' => "一条来自{$orderInfo['shopName']}的订单，请您及时处理。"
			),
			'keyword1' => ['value' => $orderInfo['TradeNO']],
			'keyword2' => ['value' => $goodsInfoString],
			'keyword3' => ['value' => $orderInfo['CustomerName']],
			'keyword4' => ['value' => $orderInfo['Phone']],
			'keyword5' => ['value' => $orderInfo['addr']],
			'remark' => array(
				'value' => "您还可以点击本消息通知查看最近7天内的所有订单"
			),
		);
		$wxTemaplteData = base64_encode(json_encode($wxTemaplteData, true));
		// 发送模板消息
		$send = file_get_contents('http://API域名/鉴权?info='.$wxTemaplteData);
		$send = json_decode($send, true);
		if($send['errmsg'] == 'ok'){
			echo "模板消息已经推送成功\r\n";
		}
	}
}

$lastID = $DB->find("select BillID from G_API_TradeList order by BillID desc")['BillID'];

while (1) {
    $newID = $DB->find("select BillID from G_API_TradeList order by BillID desc")['BillID'];
    if ($newID > $lastID) {
        $newOrderListCount = $newID - $lastID;
        echo "当前最新订单的ID：{$lastID} , 在监听时间内已更新了 {$newOrderListCount} 条新订单 - ". date('Y-m-d H:i:s') ."\r\n";
		// 发送模板函数
		sendTemplateData($lastID, $newID);
        $lastID = $newID;
    }else{
        echo "当前最新订单的ID：{$lastID} , 在监听时间内没有新的订单 - ". date('Y-m-d H:i:s') ."\r\n";
    }
    sleep(30);
}
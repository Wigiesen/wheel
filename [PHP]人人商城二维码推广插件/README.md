1.覆盖 ewei_shopv2 目录到 addons 目录下

2.导入 qrcodetg.sql 到数据库

3. /addons/ewei_shopv2/core/mobile/order/create.php 大约3454行 $orderid = pdo_insertid(); 下面写入
//----------------推广二维码逻辑----------------
if (isset($_SESSION['invite_code'])) {
	$tg_data['invite_code'] = $_SESSION['invite_code'];
	$tg_data['orderid'] = $orderid;
	$tg_data['price'] = $order['goodsprice'];
	$tg_data['order_status'] = 0;
	//查询分成比例
	$tg_data['proportion'] = pdo_fetchcolumn("SELECT proportion FROM ".tablename('ewei_shop_qrcodetg_qrcode')." WHERE invite_code = '".$_SESSION['invite_code']."'");
	//如果没有设置比例分成,那么就选择平台统一设置的比例
	if (empty($tg_data['proportion']) || $tg_data['proportion'] == 0.00) {
		$tg_data['proportion'] = pdo_fetchcolumn("SELECT proportion FROM ".tablename('ewei_shop_qrcodetg_param'));
	}
	pdo_insert('ims_ewei_shop_qrcodetg_log', $tg_data);
}
//---------------推广二维码逻辑结束-------------

4. /addons/ewei_shopv2/site.php 添加函数
/**
 * [qrcode_invte 二维码推广设置]
 * @return [type] [description]
 */
public function qrcode_invte(){
	global $_W;
	global $_GPC;
	if (isset($_GPC['invite_code'])) {
		if (!isset($_SESSION['invite_code'])) {
			$_SESSION['invite_code'] = $_GPC['invite_code'];
		}
		pdo_query("UPDATE ".tablename('ewei_shop_qrcodetg_qrcode')." SET amount = amount + 1 WHERE invite_code = '".$_SESSION['invite_code']."'");
	}
}

5. /addons/ewei_shopv2/site.php doMobileMobile函数里添加代码
$this->qrcode_invte();

6. /addons/ewei_shopv2/core/web/order/op.php finish函数中 extract($opdata); 下面添加代码
//----------------推广二维码逻辑----------------
pdo_update('ewei_shop_qrcodetg_log', array('order_status' => 2), array('orderid' => $item['id']));
//---------------推广二维码逻辑结束-------------

7. /addons/ewei_shopv2/core/web/order/op.php fetch函数中	if ($item['isverify'] == 1) 逻辑下面添加代码
//----------------推广二维码逻辑----------------
pdo_update('ewei_shop_qrcodetg_log', array('order_status' => 2), array('orderid' => $item['id']));
//---------------推广二维码逻辑结束-------------

8. /addons/ewei_shopv2/core/model/order.php payResult函数中 $order = pdo_fetch(xxxx) 后添加代码
//----------------推广二维码逻辑----------------
pdo_update('ewei_shop_qrcodetg_log', array('order_status' => 1), array('orderid' => $order['id']));
//---------------推广二维码逻辑结束-------------
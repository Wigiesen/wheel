<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage{
	/**
	 * [main 首页 - 二维码推广管理页]
	 * @return [type] [description]
	 */
    public function main(){
        global $_W;
        global $_GPC;
        $list = pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_qrcodetg_qrcode')." ORDER BY id desc");
        //全局参数
        $param = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_qrcodetg_param'));
        include $this->template('/qrcodetg/qrcodetg');
    }

    public function cash_list(){
    	$cashLogList = pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_qrcodetg_cash_log')." ORDER BY id desc");
    	include $this->template('/qrcodetg/cash_list');
    }

    /**
     * [del 删除二维码]
     * @return [type] [description]
     */
    public function del(){
        global $_W;
        global $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('ewei_shop_qrcodetg_qrcode', array('id' => $id));
        if ($res) {
            echo json_encode(array('message' => 'success'), true);
        }else{
            echo json_encode(array('message' => 'error'), true);
        }
        die();        
    }

    /**
     * [info 推广详情页]
     * @return [type] [description]
     */
    public function info(){
        global $_W;
        global $_GPC;
        //获取推广人详情
        $promoterInfo = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_qrcodetg_qrcode')." WHERE id =".$_GPC['id']);
        //获取推广人获客订单前20个
        $orderidTemp = pdo_fetchall("SELECT orderid FROM ".tablename('ewei_shop_qrcodetg_log')." WHERE invite_code = '".$promoterInfo['invite_code']."' ORDER BY id desc limit 20");
        //订单id数据结构整理
        $orderidArr = [];
        foreach ($orderidTemp as $key => $value) {
            $orderidArr[] = $value['orderid'];
        }
        if (count($orderidArr) >= 1) {
            //输出可where in 查询格式的订单id. eg (1,2,3,4)
            $orderidStr = implode($orderidArr, ',');
            $order_list = pdo_fetchall("SELECT id, ordersn, goodsprice, status, createtime FROM ".tablename('ewei_shop_order')." WHERE id in (".$orderidStr.") ORDER BY id desc");
        }else{
            $order_list = [];
        }

        //--------款项计算----------
        $logTemp = pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_qrcodetg_log')." WHERE invite_code = '".$promoterInfo['invite_code']."'");
        $advance_cash = 0;  //预计到账佣金
        $total_cash = 0;    //累计到账佣金
        $ordersCount = pdo_fetchcolumn("SELECT count(*) FROM ".tablename('ewei_shop_qrcodetg_log')." WHERE invite_code = '".$promoterInfo['invite_code']."'");
        foreach ($logTemp as $key => $value) {
            if ($value['order_status'] == 1) {
                $advance_cash += $value['price'] * ($value['proportion'] / 100);
            }
            if ($value['order_status'] == 2) {
                $total_cash += $value['price'] * ($value['proportion'] / 100);
            }
        }
        $advance_cash = number_format($advance_cash, 2);
        $total_cash = number_format($total_cash, 2);
        //查询已经使用的提现额度
        $use_amount = pdo_fetchcolumn("SELECT use_amount FROM ".tablename('ewei_shop_qrcodetg_amount')." WHERE invite_code = '".$promoterInfo['invite_code']."'");
        $available_cash = $total_cash - $use_amount;  //可提现佣金


        include $this->template('/qrcodetg/info');     
    }

    /**
     * [createqrcode 添加二维码页面]
     */
    public function createqrcode(){
        global $_W;
        global $_GPC;
        if (isset($_GPC['is_edit'])) {
        	$res = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_qrcodetg_qrcode')." WHERE id =".$_GPC['is_edit']);
        }
        include $this->template('/qrcodetg/createqrcode');
    }

    /**
     * [do_createqrcode 添加&编辑二维码执行操作]
     * @return [type] [description]
     */
    public function do_createqrcode(){
        global $_W;
        global $_GPC;
        $data = [
        	  'shop_id' => $_GPC['shop_id'],         //商品id
	          'proportion' => $_GPC['proportion'],   //分成比例
	          'invite_code' => $this->randString(5), //随机生成5位邀请码
	          'leader' => $_GPC['leader'],           //发码人
	          'promoter' => $_GPC['promoter'],       //推广人
	          'phone' => $_GPC['phone'],             //手机号码
	          'wechat' => $_GPC['wechat'],           //微信号码
	          'status' => $_GPC['status'],           //二维码状态
	          'add_time' => time(),                  //生成时间
	          'end_time' => strtotime(str_replace('T', ' ', $_GPC['end_time']))   //截止时间
        ];
        //判断是添加还是修改
        if (isset($_GPC['edit_id']) && !empty($_GPC['edit_id'])) {
        	unset($data['add_time']);
        	unset($data['invite_code']);
            //检查shop_id是否有变动，如果没有则不需要重新生成二维码
            $shop_id = pdo_fetchcolumn("SELECT shop_id FROM ".tablename('ewei_shop_qrcodetg_qrcode')." WHERE id = ".$_GPC['edit_id']);
            if ($shop_id != $data['shop_id']) {
	        	//拿到原先的邀请码
	        	$data['invite_code'] = pdo_fetchcolumn("SELECT invite_code FROM ".tablename('ewei_shop_qrcodetg_qrcode')." WHERE id = ".$_GPC['edit_id']);
	        	//生成二维码推广链接和二维码图片
	        	$qrcode = $this->qrcode_Setting($data, $_GPC['edit_id']);
	        	if($qrcode['message'] == 'error'){
	        		echo json_encode(array('message' => 'false', 'data' => $qrcode['info']), true);die();
	        	}
            }
        	$res = pdo_update('ewei_shop_qrcodetg_qrcode', $data, array('id' => $_GPC['edit_id']));
        }else{
        	$res = pdo_insert('ewei_shop_qrcodetg_qrcode', $data);
        	$uid = pdo_insertid();
        	//生成二维码推广链接和二维码图片
        	$qrcode = $this->qrcode_Setting($data, $uid);
        	if($qrcode['message'] == 'error'){
        		echo json_encode(array('message' => 'false', 'data' => $qrcode['info']), true);die();
        	}
            //为新二维码创建新的提现表
            $amount_data = [
                'invite_code' => $data['invite_code'],
                'use_amount' => '0.00'
            ];
            pdo_insert('ewei_shop_qrcodetg_amount', $amount_data);
        }  

        //添加or修改完成重新生成二维码
        if ($res) {
        	echo json_encode(array('message' => 'success'), true);die();
        }else{
        	echo json_encode(array('message' => 'false', 'data' => '二维码添加/修改失败，请联系管理员'), true);die();
        }
    }

    /**
     * [qrcode_Setting 二维码链接与图片生成]
     * @param  [type] $data [description]
     * @param  [type] $uid  [description]
     * @return [type]       [description]
     */
    public function qrcode_Setting($data, $uid){
        global $_W;
        global $_GPC;
        //----------------生成二维码+链接-------------------
		$path = IA_ROOT . '/attachment/qrcodetg/' . $_W['uniacid'];
		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}
        //如果shop_id为空,则默认为首页,并追加邀请码
        if (is_numeric($data['shop_id'])) {
        	$data_step_2['qrcode_link'] = 'http://'.$_SERVER['HTTP_HOST'].'/app/index.php?i='.$_W['uniacid'].'&c=entry&m=ewei_shopv2&do=mobile&r=goods.detail&id='.$data['shop_id'].'&invite_code='.$data['invite_code'];
        	$file_name = 'goods_qrcode_' . $data['invite_code'] . '.png';
        }else{
        	//如果非数字说明是链接推广
        	$data_step_2['qrcode_link'] = $data['shop_id'].'&invite_code='.$data['invite_code'];
        	$file_name = 'link_qrcode_' . $data['invite_code'] . '.png';
        }
        $qrcode_file = $path . '/' . $file_name;
        if (is_file($qrcode_file)) {
        	unlink($qrcode_file);
        }
		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			$create = QRcode::png($data_step_2['qrcode_link'], $qrcode_file, QR_ECLEVEL_L, 8);
			$data_step_2['qrcode_img'] = 'http://'.$_SERVER['HTTP_HOST'].'/attachment/qrcodetg/'.$_W['uniacid'].'/'.$file_name;
		}
        //----------------生成二维码+链接结束-------------------

        $res = pdo_update('ewei_shop_qrcodetg_qrcode', $data_step_2, array('id' => $uid));
        if (!$res) {
        	return ['message' => 'error', 'data' => '添加到数据库失败'];
        }
    }

    /**
     * [save_setting 全局参数保存]
     * @return [type] [description]
     */
    public function save_setting(){
        global $_W;
        global $_GPC;
       	$data = [
       		'proportion' => $_GPC['proportion']
       	];
       	$res = pdo_update('ewei_shop_qrcodetg_param', $data);
        if ($res) {
        	echo json_encode(array('message' => 'success'), true);die();
        }else{
        	echo json_encode(array('message' => 'false', 'data' => '保存失败'), true);die();
        }        
    }

    /**
     * [withdraw 提现操作]
     * @return [type] [description]
     */
    public function withdraw_do(){
        global $_W;
        global $_GPC;
       	$data = [
			'amount' => $_GPC['amount'],
			'invite_code' => $_GPC['invite_code'],
			'promoter' => $_GPC['promoter'],
			'phone' => $_GPC['phone'],
			'wechat' => $_GPC['wechat'],
			'create_time' => time()
       	];
       	pdo_query("UPDATE ".tablename('ewei_shop_qrcodetg_amount')." SET use_amount = use_amount + ".$data['amount']." WHERE invite_code = '".$data['invite_code']."'");
       	$res = pdo_insert('ewei_shop_qrcodetg_cash_log', $data);
        if ($res) {
        	echo json_encode(array('message' => 'success'), true);die();
        }else{
        	echo json_encode(array('message' => 'false', 'data' => '提交失败'), true);die();
        }  
    }

    /**
     * [randString 辅助函数 - 随机生成字符串]
     * @param  [type] $num [description]
     * @return [type]      [description]
     */
    public function randString($num){
	    $result = '';
	    $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
	    for ($i=0;$i<$num;$i++){
	        $result .= $str[rand(0,48)];
	    }
	    return $result;
    }
}
?>
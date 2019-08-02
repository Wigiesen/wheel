<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

return array(
	'version' => '1.0',
	'id'      => 'qrcodetg',
	'name'    => '二维码推广',
	'v3'      => true,
	'menu'    => array(
		'plugincom' => 1,
		'icon'      => 'page',
		'items'     => array(
			array('title' => '管理入口', 'route' => 'main'),
			array('title' => '提现记录', 'route' => 'cash_list'),
			)
		)
	);

?>

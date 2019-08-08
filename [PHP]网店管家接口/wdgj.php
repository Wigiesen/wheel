<?php
/**
 * [WDGJ 网店管家接口]
 * @author [心语难诉] <[<admin@xinyu19.com>]>
 */
class WDGJ
{
    // 接入码
    public $uCode = '这里填写网店管家接入码';
    // DB
    private $DB;
    /**
     * [__construct 初始化]
     */
    public function __construct()
    {
        // 记录日志
        $this->writeLog();
        // 检验参数
        $this->checkParams();

        $dbConfig = [
            'hostname' 			 => '',  	//数据库地址
            'dbname'   			 => '',			//数据库名称
            'username' 			 => '',			//数据库账号
            'password' 			 => '',			//数据库密码
            'prefix'   			 => '',				//数据库表前缀
            'charset'  			 => 'utf8'			//数据库编码
        ];
        $this->DB = Db::getInstance($dbConfig);
    }

    /**
     * [writeLog 记录日志]
     */
    private function writeLog()
    {
        $res = $_REQUEST;
        $log = fopen('./wdgj_log.txt', 'a+');
        fwrite($log, date('Y-m-d H:i:s')." 接收到的数据：\r\n".json_encode($res, true). "\r\n");
        fclose($log);
    }

    /**
     * [checkParams 检验参数]
     */
    private function checkParams()
    {
        if ($_REQUEST['uCode'] != $this->uCode) {
            $xml = "<?xml version='1.0' encoding='gb2312'?><Rsp><Result>0</Result><Cause>接入码错误</Cause></Rsp>";
        }
        if (empty($_REQUEST['mType'])) {
            $xml = "<?xml version='1.0' encoding='gb2312'?><Rsp><Result>0</Result><Cause>接口类型错误</Cause></Rsp>";
        }
        if (!empty($xml)) {
            echo $xml;
            exit;
        }
    }
    
    /**
     * [pregArea 根据地址识别省市县]
     */
    private function pregArea($address)
    {
        preg_match('/(.*?(省|自治区|北京市|天津市))/', $address, $matches);
        if (count($matches) > 1) {
            $province = $matches[count($matches) - 2];
            $address = str_replace($province, '', $address);
        }
        preg_match('/(.*?(市|自治州|地区|区划|县))/', $address, $matches);
        if (count($matches) > 1) {
            $city = $matches[count($matches) - 2];
            $address = str_replace($city, '', $address);
        }
        preg_match('/(.*?(区|县|镇|乡|街道))/', $address, $matches);
        if (count($matches) > 1) {
            $area = $matches[count($matches) - 2];
            $address = str_replace($area, '', $address);
        }
         
        return [
            'province' => isset($province) ? $province : '',
            'city' => isset($city) ? $city : '',
            'area' => isset($area) ? $area : '',
        ];
    }

    
    // -------------------接口业务----------------------

    /**
     * [mTest 接口URL合法性测试]
     */
    public function mTest()
    {
        echo "<?xml version='1.0' encoding='gb2312'?><rsp><result>1</result></rsp>";
    }

    public function test()
    {
        echo 'test fnc';
    }

    /**
     * [mOrderSearch 订单查询]
     */
    public function mOrderSearch()
    {
        $orderData  = $this->DB->fetchAll('ims_yihe_order', ['orderId'], ['payStatus' => 1]);
        $orderCount = count($orderData);
        $xmlData  = "<?xml version='1.0' encoding='gb2312'?>";
        $xmlData .= "<OrderList>";
        $xmlData .= "<Ver>3.0</Ver>";
        foreach ($orderData as $key => $value) {
            $xmlData .= "<OrderNO>{$value['orderId']}[1]</OrderNO>";
        }
        $xmlData .= "<OrderCount>{$orderCount}</OrderCount>";
        $xmlData .= "</OrderList>";
        echo $xmlData;
    }

    /**
     * [mGetOrder 订单详细]
     */
    public function mGetOrder()
    {
        // 接收到的原始订单号
        $OrderNO = $_REQUEST['OrderNO'];
        // 订单信息
        $orderInfo = $this->DB->fetch('ims_yihe_order', [], ['orderId' => $OrderNO]);
        // 通过订单地址分析省市县
        $area = $this->pregArea($orderInfo['address']);
        // 商品信息
        $goodsInfo = json_decode($orderInfo['goodsInfo'], true);
        foreach ($goodsInfo as $key => $value) {
            $goodsArr = $this->DB->fetch('ims_goods', ['goodsName','marketprice'], ['id' => $value['id']]);
            $goodsInfo[$key]['goodsName'] = $goodsArr['goodsName'];
            $goodsInfo[$key]['price'] = $goodsArr['marketprice'];
        }
        // 组合XML数据
        $xmlData = "<?xml version='1.0' encoding='utf-8'?>";
        $xmlData .= "<Order>";
        $xmlData .= "<OrderNO>{$OrderNO}</OrderNO>";
        $xmlData .= "<DateTime>".date('Y-m-d H:i:s', $orderInfo['payTime'])."</DateTime>";
        $xmlData .= "<BuyerID><![CDATA[FX-{$orderInfo['uid']}]]></BuyerID>";
        $xmlData .= "<BuyerName><![CDATA[{$orderInfo['username']}]]></BuyerName>";
        $xmlData .= "<Country><![CDATA[中国]]></Country>";
        $xmlData .= "<Province><![CDATA[{$area['province']}]]></Province>";
        $xmlData .= "<City><![CDATA[{$area['city']}]]></City>";
        $xmlData .= "<Town><![CDATA[{$area['area']}]]></Town>";
        $xmlData .= "<Adr><![CDATA[{$orderInfo['address']}]]></Adr>";
        $xmlData .= "<Zip><![CDATA[000000]]></Zip>";
        $xmlData .= "<Email><![CDATA[]]></Email>";
        $xmlData .= "<Phone><![CDATA[{$orderInfo['mobile']}]]></Phone>";
        $xmlData .= "<Total>{$orderInfo['payPrice']}</Total>";
        $xmlData .= "<logisticsName><![CDATA[]]></logisticsName>";
        $xmlData .= "<chargetype><![CDATA[及时到账]]></chargetype>";
        $xmlData .= "<PayAccount><![CDATA[在线支付]]></PayAccount>";
        $xmlData .= "<PayID><![CDATA[0]]></PayID>";
        $xmlData .= "<Postage>0.00</Postage>";
        $xmlData .= "<CustomerRemark><![CDATA[本订单来自分销系统]]></CustomerRemark>";
        $xmlData .= "<Remark><![CDATA[]]></Remark>";
        $xmlData .= "<InvoiceTitle><![CDATA[]]></InvoiceTitle>";
        // 商品信息XML
        foreach ($goodsInfo as $key => $value) {
            $xmlData .= "<Item>";
            $xmlData .= "<GoodsID><![CDATA[{$value['id']}]]></GoodsID>";
            $xmlData .= "<GoodsName><![CDATA[{$value['goodsName']}]]></GoodsName>";
            $xmlData .= "<Price>{$value['price']}</Price>";
            $xmlData .= "<GoodsSpec>件</GoodsSpec>";
            $xmlData .= "<Count>{$value['num']}</Count>";
            $xmlData .= "</Item>";
        }
        $xmlData .= "</Order>";

        echo $xmlData;
    }

    /**
     * [mUpdateStock 库存同步]
     */
    public function mUpdateStock()
    {
        // 商品ID
        $GoodsNO = $_REQUEST['GoodsNO'];
        // 库存量
        $Stock = $_REQUEST['Stock'];
        // 修改库存
        $upd = $this->DB->update('ims_goods', ['total' => $Stock], ['id' => $GoodsNO]);
        echo "<?xml version='1.0' encoding='gb2312'?><rsp><result>1</result></rsp>";
    }

    /**
     * [mSndGoods 发货通知]
     */
    public function mSndGoods()
    {
        // 接受订单、物流相关数据
        $OrderID    = $_REQUEST['OrderID'];     // 网店管家平台自动生成的订单编码
        $OrderNO    = $_REQUEST['OrderNO'];     // 原始订单编码
        $CustomerID = $_REQUEST['CustomerID'];  // 客户ID，格式：FX-UID
        $SndStyle   = $_REQUEST['SndStyle'];    // 物流名称
        $BillID     = $_REQUEST['BillID'];      // 物流单号
        $SndDate    = $_REQUEST['SndDate'];     // 发货时间
        $upd = $this->DB->update('ims_yihe_order', [
            "express"     =>iconv('GB2312', 'UTF-8', $SndStyle),
            "expressNo"   =>$BillID,
            "orderStatus" =>4,
            "deliveryTime"=>strtotime($SndDate),
            "Ouid"        =>1
		], ['orderId' => $OrderNO]);
		echo "<?xml version='1.0' encoding='gb2312'?><rsp><result>1</result></rsp>";
    }
}

/**
 * [Db DB类]
 */
class Db
{
    private static $instance;
    private $db;

    private function __construct($config)
    {
        if (!empty($config['hostname'])) {
            try {
                $this->dsn = 'mysql:host='.$config['hostname'].';dbname='.$config['dbname'];
                $this->db = new PDO($this->dsn, $config['username'], $config['password']);
                $this->db->exec('SET character_set_connection='.$config['charset'].', character_set_results='.$config['charset'].', character_set_client=binary');
                $this->db->exec("SET NAMES ".$config['charset']);
            } catch (PDOException $e) {
                $this->printError($e->getMessage());
            }
        }
    }

    /**
     * [getInstance 单例唯一实例化]
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public static function getInstance($config)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * [create 新增数据]
     * @param  [type] $table [description]
     * @param  array  $data  [description]
     * @return [type]        [description]
     */
    public function create($table, $data = [])
    {
        $sql = "INSERT INTO `$table` (`".implode('`,`', array_keys($data))."`) VALUES ('".implode("','", $data)."')";
        $result = $this->db->exec($sql);
        $this->getError();
        return $result;
    }

    /**
     * [update 删除数据]
     * @param  [type] $table     [description]
     * @param  array  $data      [description]
     * @param  array  $condition [description]
     * @return [type]            [description]
     */
    public function update($table, $data = [], $condition = [])
    {
        $sql = '';
        foreach ($data as $key => $value) {
            $sql .= ", `$key`='$value'";
        }
        $sql = substr($sql, 1);
        if (!empty($condition)) {
            $where  = $this->condition($condition);
            $sql = "UPDATE `$table` SET {$sql} {$where}";
        } else {
            $sql = "UPDATE `$table` SET {$sql}";
        }
        $result = $this->db->exec($sql);
        $this->getError();
        return $result;
    }

    /**
     * [delete 删除数据]
     * @param  [type] $table     [description]
     * @param  array  $condition [description]
     * @return [type]            [description]
     */
    public function delete($table, $condition = [])
    {
        if (!empty($condition)) {
            $where  = $this->condition($condition);
            $sql = "DELETE FROM `$table` {$where}";
            $result = $this->db->exec($sql);
            $this->getError();
            return $result;
        } else {
            $this->printError('condition is Null');
        }
    }

    /**
     * [getColumn 获取但个字段数据]
     * @param  [type] $table     [description]
     * @param  [type] $column    [description]
     * @param  array  $condition [description]
     * @return [type]            [description]
     */
    public function getColumn($table, $column, $condition = [])
    {
        if (!empty($column)) {
            $where  = $this->condition($condition);
            $result = $this->db->query("SELECT {$column} FROM `{$table}` {$where} limit 1", PDO::FETCH_ASSOC);
            $result = $result->fetch();
            return $result[$column];
        } else {
            $this->printError('column is Null');
        }
    }

    /**
     * [fetch 读取一行数据]
     * @param  [type] $table     [description]
     * @param  array  $fields    [description]
     * @param  array  $condition [description]
     * @return [type]            [description]
     */
    public function fetch($table, $fields = [], $condition = [])
    {
        $fields = !empty($fields) ? implode(",", $fields) : '*';
        $where  = $this->condition($condition);
        $result = $this->db->query("SELECT {$fields} FROM `{$table}` {$where} limit 1", PDO::FETCH_ASSOC);
        if (!is_object($result)) {
            $result = [];
        } else {
            $result = $result->fetch();
        }
        $this->getError();
        return $result;
    }

    /**
     * [fetchAll 获取全部数据]
     * @param  [type] $table     [description]
     * @param  array  $fields    [description]
     * @param  array  $condition [description]
     * @return [type]            [description]
     */
    public function fetchAll($table, $fields = [], $condition = [])
    {
        $fields = !empty($fields) ? implode(",", $fields) : '*';
        $where  = $this->condition($condition);
        $result = $this->db->query("SELECT {$fields} FROM `{$table}` {$where}", PDO::FETCH_ASSOC);
        if (!is_object($result)) {
            $result = [];
        } else {
            $result = $result->fetchAll();
        }
        $this->getError();
        return $result;
    }

    public function query($sql, $mode = 'all')
    {
        $result = $this->db->query($sql, PDO::FETCH_ASSOC);
        if (!is_object($result)) {
            return [];
        }
        if ($mode == 'all') {
            $result = $result->fetchAll();
        } elseif ($mode == 'row') {
            $result = $result->fetch();
        } else {
            $this->printError('mode is false');
        }
        $this->getError();
        return $result;
    }

    /**
     * [condition WHERE条件处理]
     * @param  [type] $condition [description]
     * @return [type]            [description]
     */
    private function condition($condition)
    {
        $where = '';
        if (!empty($condition)) {
            foreach ($condition as $key => $value) {
                $where .= "`".(string)$key."` = '".(string)$value."' AND ";
            }
            $where = "WHERE " . rtrim($where, ' AND ');
        }
        return $where;
    }

    /**
     * getPDOError 捕获PDO错误信息
     */
    private function getError()
    {
        if ($this->db->errorCode() != '00000') {
            $error = $this->db->errorInfo();
            $this->printError($error[2]);
        }
    }

    /**
     * [printError 输出异常信息]
     * @param  [type] $ErrMsg [description]
     * @return [type]         [description]
     */
    private function printError($ErrMsg)
    {
        throw new Exception('MySQL Error: '.$ErrMsg);
    }

    private function __clone()
    {
    }
}


//---------业务逻辑----------
$WDGJ = new WDGJ();
switch ($_REQUEST['mType']) {
    case 'mTest':
        $WDGJ->mTest();
        break;
    case 'mOrderSearch':
        $WDGJ->mOrderSearch();
        break;
    case 'mGetOrder':
        $WDGJ->mGetOrder();
        break;
    case 'mUpdateStock':
        $WDGJ->mUpdateStock();
		break;
	case 'mSndGoods':
        $WDGJ->mSndGoods();
        break;
    case 'test':
        $WDGJ->test();
        break;
    default:
        $xml = "<?xml version='1.0' encoding='gb2312'?><rsp><result>0</result><cause>接口类型错误</cause></rsp>";
        echo $xml;
        break;
}

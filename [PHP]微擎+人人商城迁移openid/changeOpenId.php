<?php
require_once './iniFile.php';
require_once './Db.php';

/**
 * 微信API类
 * @author [心语难诉] <[<admin@xinyu19.com>]>
 */
class WechatApi
{
    // 单一实例化变量
    private static $instance;
    // 公众号AppId
    private $appId;
    // 公众号AppSecret
    private $appSecret;
    // 公众号AccessToken
    private $AccessToken;
    // 用户登陆授权AccessToken
    private $AuthAccessToken;
    // 微信APIURL
    private $wxApi = "https://api.weixin.qq.com/cgi-bin";
    // 微信access_token储存文件
    private $iniFile = './changeOpenId.ini';

    public function __construct($config)
    {
        $this->appId = $config['appid'];
        $this->appSecret = $config['appsecret'];
        $this->iniFile = new iniFile($this->iniFile);
        //读取微信access_token数据
        $accessInfo = $this->iniFile->getCategory('access');
        //检测accesstoken
        // if (empty($accessInfo['access_token']) || (time() + 600) > $accessInfo['expires_in']) {
        if (!$this->checkAccessToken($accessInfo['access_token'])) {
            $res = $this->getAccessToken();
            if (!isset($res['errcode'])) {
                $this->iniFile->updItem('access', [
                    'access_token' => $res['access_token'],
                    'expires_in' => time() + $res['expires_in'],
                ]);
                $this->AccessToken = $res['access_token'];
            } else {
                echo json_encode($res, true);
                exit;
            }
        } else {
            $this->AccessToken = $accessInfo['access_token'];
        }
    }

    /**
     * [getAccessToken 获取公众号accesstoken]
     */
    private function getAccessToken()
    {
        $apiUrl = "{$this->wxApi}/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $res = json_decode($this->Request($apiUrl), true);
        return $res;
    }
    
    /**
     * [checkAccessToken 检测接口是否失效]
     * 2020-4-26 14:29:31 訫語难诉 add
     */
    private function checkAccessToken($AccessToken){
        $apiUrl = "{$this->wxApi}/getcallbackip?access_token={$AccessToken}";
        $res = json_decode($this->Request($apiUrl), true);
        if (isset($res['errcode'])) {
            return false;
        }else{
            return true;
        }
    }
    /**
     * [Request 请求方法]
     * @param   [str]  $url      [$url API接口地址]
     * @param   [arr]  $data     [$data description]
     * @param   [str]  $type     [$type 请求类型 get | post, 默认get]
     * @param   [arr]  $headers  [$headers header头参数, 默认空]
     * @return  [str | json]
     */
    public function Request($url, $data = [], $type = 'get', $headers = [])
    {
        if ($type == 'get') {
            // 如果url中有参数,那么就使用'&'链接，否则就使用'?'链接
            $Connector = strpos($url, '?') ? '&' : '?';
            $url = "{$url}{$Connector}" . http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if ($type == 'post') {
            // 如果是post请求，就启用CURL POST表单和 headers
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }elseif ($type == 'postjson'){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,true));
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    /**
     * ------------------------------------------
     * ----------------API业务开始----------------
     * ------------------------------------------
     */

    /**
     * [getUserInfo 根据粉丝openid获取粉丝信息]
     * @param   [String]  $openid  [$openid 粉丝openid]
     */
    public function getUserInfo($openid)
    {
        $apiUrl = "{$this->wxApi}/user/info?access_token={$this->AccessToken}&openid={$openid}&lang=zh_CN";
        $res = json_decode($this->Request($apiUrl), true);
        return $res;
    }

    /**
     * [getUserOpenId 获取用户openid]
     * @param   [String]  $code  [$code 登陆授权code参数]
     */
    public function getUserOpenId($code)
    {
        $apiUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $apiUrl .= "?appid={$this->appId}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code";
        $res = json_decode($this->Request($apiUrl), true);
        if (!isset($res['errcode'])) {
            return $res['openid'];
        } else {
            echo json_encode($res, true);
            exit;
        }
    }

    /**
     * [changeOpenId 迁移openid，旧openid换新openid]
     */
    public function changeOpenId($from_appid, $openid_list){
        $apiUrl = "{$this->wxApi}/changeopenid?access_token={$this->AccessToken}";
        $data = [
            'from_appid' => $from_appid,
            'openid_list' => array_values($openid_list)
        ];
        $res = $this->Request($apiUrl, $data, 'postjson');
        return $res;
    }

}

class changeOpenIdClass
{
    private $dbConfig = [
        'hostname' 	=> '127.0.0.1',
        'dbname'   	=> 'test',
        'username' 	=> 'test',
        'password' 	=> 'test',
        'prefix'   	=> '',
        'charset'  	=> 'utf8'
    ];
    private $logHandle; // log实例

    private $logPath = './changeLog/';   // 日志路径

    private $fromAppid = 'wx2658614fxxxxxxxx';  // 原始微信公众号appid
    
    private $appid = 'wx528c5affxxxxxxxx';  // 目标公众号appid

    private $appsecret = '3e533b748df8f60971b3xxxxxxxxxxxx';    // 目标公众号appsecret

    private $DB;    // DB类库实例

    private $wechatApi; // 微信SDK实例

    public function __construct(){
        $this->DB = Db::getInstance($this->dbConfig);
        $this->wechatApi = new WechatApi([
            'appid' => $this->appid,
            'appsecret' => $this->appsecret
        ]);
        $this->logHandle = fopen($this->logPath."转换日志 - ".date('Y-m-d').".csv", 'a+');
        $this->fputcsv2($this->logHandle,array('所属数据表','原openid','新openid','是否转化成功','错误信息'));
    }

    /**
     * [getTableList 获取需要迁移openid的数据表]
     * @return  [array]  [数据库表名列表]
     */
    public function getTableList(){
        $tableData = $this->DB->query("SELECT a.table_name FROM information_schema.COLUMNS AS a LEFT JOIN information_schema.TABLES AS b ON a.table_name = b.TABLE_NAME WHERE a.column_name = 'openid' AND a.table_schema = '{$this->dbConfig['dbname']}' AND a.table_name LIKE 'ims_ewei_shop%' AND b.table_rows > 0");
        $tableList = [];
        foreach ($tableData as $value) {
            $tableList[] = $value['table_name'];
        }
        $tableList[] = 'ims_mc_mapping_fans';
        return $tableList;
    }

    /**
     * [getNewOpenId description]
     * @param   [type]  $openidList  [旧openid数组，每次最多100个]
     * @return  [json]               [回执结果，包含了新openid]
     */
    public function getNewOpenId($openidList){
        return $this->wechatApi->changeOpenId($this->fromAppid, $openidList);
    }

    /**
     * [getTableOpenidList 获取指定数据表的openid列表]
     * @param   [str]  $tableName  [$tableName description]
     * @return  [array]              [return openidList]
     */
    public function getTableOpenidList($tableName){
        $openidRes = $this->DB->fetchAll($tableName, [
            'fields' => ['openid'],
			'where'  => [
                ['openid', 'not like' ,'wap_%'],
                ['openid', 'not like' ,'sns_%'],
                ['openid', '<>' ,''],
                ['openid', '<>' ,'0'],
			],
        ]);
        $openidList = [];
        if (!empty($openidRes)) {
            foreach ($openidRes as $key => $value) {
                $openidList[] = $value['openid'];
            }
        }
        return $openidList;
    }

    /**
     * [transformOpenId 转换openid]
     * @param   [str]  $tableName   [$tableName 数据表名称]
     * @param   [array]  $openidList  [$openidList 当前数据表的openid列表]
     * @return  [type]               [return description]
     */
    public function transformOpenId($tableName, $openidList){
        // 数组去重
        $openidList = array_unique($openidList);
        $openidCount = count($openidList);
        
        if ($openidCount <= 100) {  // 如果少于100个openid，一次性转换
            $newOpenIdList = $this->getNewOpenId($openidList);
            $this->changeOpenId($tableName, json_decode($newOpenIdList,true));
        }elseif ($openidCount >= 100) { //如果大于100个openid，分割数组，批次转换
            $openidListChunk = array_chunk($openidList, 100);
            foreach ($openidListChunk as $key => $openidListArr) {
                $newOpenIdList = $this->getNewOpenId($openidListChunk[$key]);
                $this->changeOpenId($tableName, json_decode($newOpenIdList,true));
            }
        }
    }

    /**
     * [changeOpenId 更改openid]
     * @param   [str]  $tableName   [$tableName 数据库表名]
     * @param   [array]  $openidList  [$openidList openid列表]
     * @return  [str]               [return description]
     */
    public function changeOpenId($tableName, $openidList){
        $successCount = 0;  //正确转换计数器
        $errorCount = 0;    //错误转换计数器
        if ($openidList['errcode'] == 0) {
            foreach ($openidList['result_list'] as $list => $item) {
                if ($item['err_msg'] == 'ori_openid error') {
                    $this->fputcsv2($this->logHandle,[$tableName, $item['ori_openid'], '空', '未成功','此openid不属于原公众号号，或用户已经取关']);
                    $errorCount++;
                }else {
                    $update = $this->DB->update($tableName,['openid' => $item['new_openid']],['where' => [['openid', '=', $item['ori_openid']]]]);
                    $this->fputcsv2($this->logHandle,[$tableName, $item['ori_openid'], $item['new_openid'], '转化成功']);
                    $successCount++;
                }
            }
        }else {
            $this->fputcsv2($this->logHandle,[$tableName, '空', '空', '未成功','数据表可转换openid为空']);
            $errorCount++;
        }
        $allCount = $successCount + $errorCount;
        echo "{$tableName} 数据表openid共转换 {$allCount} 条,其中正确转换 {$successCount} 条,异常转换 {$errorCount} 条,详情请转换完成后查询日志.\r\n";
    }

    /**
     * [fputcsv2 写入CSV日志函数]
     */
    function fputcsv2($handle, array $fields, $delimiter = ",", $enclosure = '"', $escape_char = "\\") {
        foreach ($fields as $k => $v) {
            $fields[$k] = iconv("UTF-8", "GB2312//IGNORE", $v);  // 这里将UTF-8转为GB2312编码
        }
        fputcsv($handle, $fields, $delimiter, $enclosure, $escape_char);
    }
    /**
     * [__destruct 析构函数]
     */
    public function __destruct(){
        fclose($this->logHandle);
    }

}

$changeOpenIdClass = new changeOpenIdClass();
$tableList = $changeOpenIdClass->getTableList();
foreach ($tableList as $tableName) {
    $openidList = $changeOpenIdClass->getTableOpenidList($tableName);
    if (!empty($openidList)) {
        $changeOpenIdClass->transformOpenId($tableName, $openidList);
        sleep(2);
    }
}
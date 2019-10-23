<?php
require_once './iniFile.php';
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
    private $iniFile = './C7WFamritZylxsYO.ini';

    private function __construct($config)
    {
        $this->appId = $config['appid'];
        $this->appSecret = $config['appsecret'];
        $this->iniFile = new iniFile($this->iniFile);
        //读取微信access_token数据
        $accessInfo = $this->iniFile->getCategory('access');
        //如果access_token为空，或expires_in离过期不足，就重新获取access_token
        if (empty($accessInfo['access_token']) || (time() + 600) > $accessInfo['expires_in']) {
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
     * [create 单例唯一实例化]
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public static function create($config)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }
        return self::$instance;
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
     * [getUserOpenidList 获取公众号粉丝openid列表]
     */
    public function getUserOpenidList()
    {
        $apiUrl = "{$this->wxApi}/user/get?access_token={$this->AccessToken}";
        $res = json_decode($this->Request($apiUrl), true);
        return $res;
    }

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
     * [Auth 登陆授权 - 跳转获取code参数]
     */
    public function Auth()
    {
        $redirectUri = urldecode("http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}?getcode=1");
        $apiUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $apiUrl .="?appid={$this->appId}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        header("Location: {$apiUrl}");
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
     * [sendTplNotice 推送模板消息]
     * @param   [str]  $openid  [$openid 粉丝的openid]
     * @param   [str]  $tpl_id  [$tpl_id 消息模板id]
     * @param   [arr]  $data    [$data 模板消息数据]
     * @param   [str]  $url     [$url 模板消息是否可以跳转, 默认为空]
     */
    public function sendTplNotice($openid, $tpl_id, $data, $url = '')
    {
        $apiUrl = "{$this->wxApi}/message/template/send?access_token={$this->AccessToken}";
        $data = [
            'touser' => $openid,
            'template_id' => $tpl_id,
            'url' => $url,
            'data' => $data
        ];
        $data = json_encode($data, true);
        $res = $this->Request($apiUrl, $data, 'post');
        return $res;
    }

    /**
     * [sendTextNotice 推送客服消息 - 文本]
     * @param   [str]  $openid   [$openid 粉丝的openid]
     * @param   [str]  $content  [$content 文本消息]
     */
    public function sendTextNotice($openid, $content)
    {
        $apiUrl = "{$this->wxApi}/message/custom/send?access_token={$this->AccessToken}";
        $data = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ];
        $data = json_encode($data, true);
        $res = $this->Request($apiUrl, $data, 'post');
        return $res;
    }
}

$WechatApi = WechatApi::create([
    'appid' => '这里填写公众号APPID',
    'appsecret' => '这里填写公众号APPSECRET',
]);

//-------------exaplme----------------------

if (isset($_GET['r']) && $_GET['r'] == 'getUserOpenidList') {
    $res = $WechatApi->getUserOpenidList();
    print_r($res);
}

if (isset($_GET['getcode']) && $_GET['getcode'] == 1) {
    $code = $_GET['code'];
    $openid = $WechatApi->getUserOpenId($code);
    $userInfo = $WechatApi->getUserInfo($openid);
    print_r($userInfo);
    return;
}

if (isset($_GET['r']) && $_GET['r'] == 'auth') {
    $WechatApi->Auth();
}

if (isset($_GET['r']) && $_GET['r'] == 'sendTplNotice') {
    $date = date('Y-m-d H:i:s');
    $data = array(
        'first' => array(
            'value' => '平台有订单成功支付'
        ),
        'keyword1' => ['value' => '心语难诉'],
        'keyword2' => ['value' => '张三'],
        'keyword3' => ['value' => '已对帐订单支付'],
        'keyword4' => ['value' => '9999'.'元'],
        'keyword5' => ['value' => $date],
        'remark' => array(
            'value' => "截止：{$date}，已有456个已支付订单待发货！！！！"
        ),
    );
    $res = $WechatApi->sendTplNotice(
        'o60Vh1S0tKxg1QRzDxxxxxxx',
        'ZddIaLzVG9yIPfa8wf60I0R8w8UjBCKuhdEixxxxxxx',
        $data,
        'http://www.baidu.com'
    );
    print_r($res);
}

if (isset($_GET['r']) && $_GET['r'] == 'sendTextNotice') {
    $res = $WechatApi->sendTextNotice('o60Vh1S0tKxg1QRzDxxxxxxx', 'hello world!');
    print_r($res);
}

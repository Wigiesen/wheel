<?php
require_once './Db.php';
class SpiderForCasting
{
    private $db;
    public function __construct()
    {
        $dbConfig = [
            'hostname' 			 => '127.0.0.1',  	//数据库地址
            'dbname'   			 => 'Casting',	    //数据库名称
            'username' 			 => 'root',			//数据库账号
            'password' 			 => 'root',			//数据库密码
            'prefix'   			 => '',				//数据库表前缀
            'charset'  			 => 'utf8mb4'			//数据库编码
        ];
        $this->db = Db::getInstance($dbConfig);
    }

    public function run()
    {
        $pageUrl = "https://www.yinchengcasting.com/part/page_%u.html";
        $infoUrl = "https://www.yinchengcasting.com/part/c_show-id_%u.html";
        // 开始获取数据 [列表链接、详情链接、从第1页开始、一共500页]
        $this->getData($pageUrl, $infoUrl, 1, 500);
    }

    /**
     * [getData 获取数据]
     */
    private function getData($pageUrl, $infoUrl, $firstPage, $totalPage)
    {
        $insert_num = 0;
        for ($i = $firstPage; $i <= $totalPage; $i++) {
            $url   = sprintf($pageUrl, $i);
            $html  = file_get_contents($url);
            $regex = "/<div class=\"ret_item clearfix\".*?>.*?<\/div>/ism";
            preg_match_all($regex, $html, $matches);
            foreach ($matches as $value) {
                foreach ($value as $list) {
                    preg_match_all('/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/i', $list, $temp);
                    preg_match_all('/<p(.*?)>(.*)<\/p>/', $list, $temp_p);
                    // 获取详情内的联系电话
                    $info = $this->getInfo($temp[2][0]);
                    // 组合数据
                    $temp = [
                        'url'    => $temp[2][0],
                        'title'  => $temp[4][0],
                        'cash'   => $temp_p[2][0],
                        'click'  => $temp_p[2][1],
                    ];
                    $all = array_merge($info, $temp);
                    $this->db->create('list', $all);
                    $insert_num++;
                    echo "第{$insert_num}条数据已经储存完成\r\n";
                }
            }
        }
    }
    /**
     * [getInfo 详情页数据获取]
     */
    private function getInfo($infoUrl)
    {
        $html  = file_get_contents($infoUrl);
        $regex_mobile = "/<span class=\"Company_Basic_information_tel\".*?>(.*?)<\/span>/ism";
        preg_match_all($regex_mobile, $html, $mobile);
        $regex_city = "/<span class=\"partjob_showjobinfo_s partjob_showjobinfo_city\".*?>(.*?)<\/span>/ism";
        preg_match_all($regex_city, $html, $address);
        $regex_director = "/<div class=\"partjob_infolist_p\".*?>联系人：(.*?)<\/div>/ism";
        preg_match_all($regex_director, $html, $director);
        $regex_updtime = "/<span class=\"partjob_showjobinfo_s partjob_showjobinfo_time\".*?>(.*?)<\/span>/ism";
        preg_match_all($regex_updtime, $html, $updtime);
        $regex_studio = "/<div class=\"partjob_showcomname\".*?>(.*?)<\/div>/ism";
        preg_match_all($regex_studio, $html, $studio);
        $regex_des = "/<div class=\"partjob_content_p\".*?>(.*?)<\/div>/ism";
        preg_match_all($regex_des, $html, $des);   
        $data = [
            'mobile'   => $mobile[1][0],
            'address'  => trim($address[1][0]),
            'director' => $director[1][0],
            'updtime'  => $updtime[1][0],
            'studio'   => str_replace("'", '', $studio[1][0]),
            'des'      => $des[1][0]
        ];
        return $data;
    }
}
$Spider = new SpiderForCasting();
$Spider -> run();

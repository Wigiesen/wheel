<?php
/**
 * [Request 请求方法]
 * @param   [str]  $url      [$url API接口地址]
 * @param   [arr]  $data     [$data description]
 * @param   [str]  $type     [$type 请求类型 get | post, 默认get]
 * @param   [arr]  $headers  [$headers header头参数, 默认空]
 * @return  [str | json]
 */
function Request($url, $data = [], $type = 'get', $headers = [])
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
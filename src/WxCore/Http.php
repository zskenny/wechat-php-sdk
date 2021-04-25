<?php

namespace zskenny\wechat\WxCore;

use zskenny\wechat\WxCore\Exception\InvalidArgumentException;

class Http
{
  /**
   * 以get访问模拟访问
   * @param string $url 访问URL
   * @param array $httpCode http状态码
   * @return boolean|string
   */
  public static function get($url, &$httpCode = 0)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // 不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $content;
  }

  /**
   * 以post访问模拟访问
   * @param string $url 访问URL
   * @param array $params POST数据
   * @return boolean|string
   */
  public static function post($url, $params = [], $options = [])
  {
    $data = is_array($params) ? Helper::arr2json($params) : $params;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 不做证书校验,部署在linux环境下请改为true

    // 证书文件设置
    if (!empty($options['ssl_cer'])) if (file_exists($options['ssl_cer'])) {
      curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
      curl_setopt($ch, CURLOPT_SSLCERT, $options['ssl_cer']);
    } else throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_cer]");
    // 证书文件设置
    if (!empty($options['ssl_key'])) if (file_exists($options['ssl_key'])) {
      curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
      curl_setopt($ch, CURLOPT_SSLKEY, $options['ssl_key']);
    } else throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_key]");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
  }
}

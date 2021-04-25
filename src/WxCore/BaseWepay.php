<?php

namespace zskenny\wechat\WxCore;

use zskenny\wechat\WxCore\Exception\InvalidArgumentException;
use zskenny\wechat\WxCore\Exception\InvalidResponseException;

class BaseWepay
{
  // 商户配置
  protected $config;

  // 当前请求数据
  protected $params;

  // 静态缓存
  protected static $cache;

  public function __construct(array $options)
  {
    if (empty($options['appid'])) {
      throw new InvalidArgumentException("Missing Config -- [appid]");
    }
    if (empty($options['mch_id'])) {
      throw new InvalidArgumentException("Missing Config -- [mch_id]");
    }
    if (empty($options['mch_key'])) {
      throw new InvalidArgumentException("Missing Config -- [mch_key]");
    }
    if (!empty($options['cache_path'])) {
      Helper::$cache_path = $options['cache_path'];
    }
    $this->config = new Config($options);

    // 商户基础参数
    $this->params = new Config([
      'appid'     => $this->config->get('appid'),
      'mch_id'    => $this->config->get('mch_id'),
      'nonce_str' => Helper::createNoncestr(),
    ]);

    // 商户参数支持
    if ($this->config->get('sub_appid')) {
      $this->params->set('sub_appid', $this->config->get('sub_appid'));
    }
    if ($this->config->get('sub_mch_id')) {
      $this->params->set('sub_mch_id', $this->config->get('sub_mch_id'));
    }
  }

  // 静态创建对象
  public static function instance(array $config)
  {
    $key = md5(get_called_class() . serialize($config));
    if (isset(self::$cache[$key])) return self::$cache[$key];
    return self::$cache[$key] = new static($config);
  }

  // 获取微信支付通知
  public function getNotify()
  {
    // 测试 
    $data = file_get_contents('php://input');
    file_put_contents(public_path().'uploads/'.date('Ymd').'.log', $data, FILE_APPEND);

    $data = Helper::xml2arr(file_get_contents('php://input'));
    if (isset($data['sign']) && $this->getPaySign($data) === $data['sign']) {
      return $data;
    }
    throw new InvalidResponseException('Invalid Notify.', '0');
  }

  // 获取微信支付通知回复内容
  public function getNotifySuccessReply()
  {
    return Helper::arr2xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
  }

  /**
   * 生成支付签名
   * @param array $data 参与签名的数据
   * @param string $signType 参与签名的类型
   * @param string $buff 参与签名字符串前缀
   * @return string
   */
  public function getPaySign(array $data, $signType = 'MD5', $buff = '')
  {
    ksort($data);
    if (isset($data['sign'])) unset($data['sign']);
    foreach ($data as $k => $v) $buff .= "{$k}={$v}&";
    $buff .= ("key=" . $this->config->get('mch_key'));
    if (strtoupper($signType) === 'MD5') {
      return strtoupper(md5($buff));
    }
    return strtoupper(hash_hmac('SHA256', $buff, $this->config->get('mch_key')));
  }

  /**
   * 以Post请求接口
   * @param string $url 请求
   * @param array $data 接口参数
   * @param bool $isCert 是否需要使用双向证书
   * @param string $signType 数据签名类型 MD5|SHA256
   * @param bool $needSignType 是否需要传签名类型参数
   * @return array
   * @throws InvalidResponseException
   * @throws \WeChat\Exceptions\LocalCacheException
   */
  protected function callPostApi($url, array $data, $isCert = false, $signType = 'HMAC-SHA256', $needSignType = true)
  {
    $option = [];
    if ($isCert) {
      $option['ssl_p12'] = $this->config->get('ssl_p12');
      $option['ssl_cer'] = $this->config->get('ssl_cer');
      $option['ssl_key'] = $this->config->get('ssl_key');
      if (is_string($option['ssl_p12']) && file_exists($option['ssl_p12'])) {
        $content = file_get_contents($option['ssl_p12']);
        if (openssl_pkcs12_read($content, $certs, $this->config->get('mch_id'))) {
          // $option['ssl_key'] = Helper::pushFile(md5($certs['pkey']) . '.pem', $certs['pkey']);
          // $option['ssl_cer'] = Helper::pushFile(md5($certs['cert']) . '.pem', $certs['cert']);
        } else throw new InvalidArgumentException("P12 certificate does not match MCH_ID --- ssl_p12");
      }
      if (empty($option['ssl_cer']) || !file_exists($option['ssl_cer'])) {
        throw new InvalidArgumentException("Missing Config -- ssl_cer", '0');
      }
      if (empty($option['ssl_key']) || !file_exists($option['ssl_key'])) {
        throw new InvalidArgumentException("Missing Config -- ssl_key", '0');
      }
    }
    $params = $this->params->merge($data);
    $needSignType && ($params['sign_type'] = strtoupper($signType));
    $params['sign'] = $this->getPaySign($params, $signType);
    $result = Helper::xml2arr(Http::post($url, Helper::arr2xml($params), $option));
    if ($result['return_code'] !== 'SUCCESS') {
      throw new InvalidResponseException($result['return_msg'], '0');
    }
    return $result;
  }
}

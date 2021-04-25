<?php

namespace zskenny\wechat\WxCore;

use think\facade\Cache;
use zskenny\wechat\WxCore\Config;
use zskenny\wechat\WxCore\Exception\InvalidArgumentException;
use zskenny\wechat\WxCore\Exception\InvalidResponseException;

class BaseWechat
{
  public $config = [];

  protected static $instance;

  public $access_token = '';
  public $cache = '';
  protected $requested = false;

  public function __construct(array $options)
  {
    if (empty($options['appid'])) {
      throw new InvalidArgumentException("Missing Config -- [appid]");
    }
    if (empty($options['appsecret'])) {
      throw new InvalidArgumentException("Missing Config -- [appsecret]");
    }

    $this->config = new Config($options);
    $this->cache = $this->config->get('appid') . '_access_token';
  }

  public static function instance(array $config)
  {
    $key = md5(get_called_class() . serialize($config));
    if (isset(self::$instance[$key])) return self::$instance[$key];
    return self::$instance[$key] = new static($config);
  }

  public function getAccessToken()
  {
    if (!empty($this->access_token)) return $this->access_token;

    $this->access_token = $this->get_cache();
    if (!empty($this->access_token)) {
      return $this->access_token;
    }

    list($appid, $secret) = [$this->config->get('appid'), $this->config->get('appsecret')];
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
    $result = Helper::json2arr(Http::get($url));
    if (!empty($result['access_token'])) {
      $this->set_cache($result['access_token']);
    }
    return $this->access_token = $result['access_token'];
  }

  public function del_cache()
  {
    $this->access_token = '';
    return Cache::delete($this->cache);
  }

  public function get_cache()
  {
    return Cache::get($this->cache);
  }

  public function set_cache($data)
  {
    return Cache::set($this->cache, $data, 7200);
  }

  protected function parse_url(&$url, $method, $arguments = [])
  {
    $this->currentMethod = ['method' => $method, 'arguments' => $arguments];
    if (empty($this->access_token)) $this->access_token = $this->getAccessToken();
    $url = str_replace('ACCESS_TOKEN', urlencode($this->access_token), $url);
    return $url;
  }

  protected function http_post($url, array $data, $buildToJson = true)
  {
    try {
      return Helper::json2arr(Http::post($url, $buildToJson ? Helper::arr2json($data) : $data));
    } catch (InvalidResponseException $exception) {
      if (!$this->requested && in_array($exception->getCode(), ['40014', '40001', '41001', '42001'])) {
        [$this->del_cache(), $this->requested = true];
        return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
      }
      throw new InvalidResponseException($exception->getMessage(), $exception->getCode());
    }
  }

  protected function http_get($url)
  {
    try {
      return Helper::json2arr(Http::get($url));
    } catch (InvalidResponseException $exception) {
      if (isset($this->currentMethod['method']) && empty($this->requested)) {
        if (in_array($exception->getCode(), ['40014', '40001', '41001', '42001'])) {
          $this->del_cache();
          $this->requested = true;
          return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
        }
      }
      throw new InvalidResponseException($exception->getMessage(), $exception->getCode());
    }
  }
}

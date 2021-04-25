<?php

namespace zskenny\wechat\WxCore;

use zskenny\wechat\WxCore\Exception\InvalidResponseException;
use zskenny\wechat\WxCore\Exception\LocalCacheException;

class Helper
{
  // 缓存路径
  public static $cache_path = null;

  // 缓存写入操作
  public static $cache_callable = [
    'set' => null, // 写入缓存
    'get' => null, // 获取缓存
    'del' => null, // 删除缓存
    'put' => null, // 写入文件
  ];

  /**
   * 产生随机字符串
   * @param int $length 指定字符长度
   * @param string $str 字符串前缀
   * @return string
   */
  public static function createNoncestr($length = 32, $str = "")
  {
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  /**
   * 数组转XML内容
   * @param array $data
   * @return string
   */
  public static function arr2xml($data)
  {
    return "<xml>" . self::_arr2xml($data) . "</xml>";
  }

  /**
   * XML内容生成
   * @param array $data 数据
   * @param string $content
   * @return string
   */
  private static function _arr2xml($data, $content = '')
  {
    foreach ($data as $key => $val) {
      is_numeric($key) && $key = 'item';
      $content .= "<{$key}>";
      if (is_array($val) || is_object($val)) {
        $content .= self::_arr2xml($val);
      } elseif (is_string($val)) {
        $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
      } else {
        $content .= $val;
      }
      $content .= "</{$key}>";
    }
    return $content;
  }

  /**
   * 解析XML内容到数组
   * @param string $xml
   * @return array
   */
  public static function xml2arr($xml)
  {
    $entity = libxml_disable_entity_loader(true);
    $data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_disable_entity_loader($entity);
    return json_decode(json_encode($data), true);
  }

  /**
   * 解析XML文本内容
   * @param string $xml
   * @return boolean|mixed
   */
  public static function parserXml($xml)
  {
    $state = xml_parse($parser = xml_parser_create(), $xml, true);
    return xml_parser_free($parser) && $state ? self::xml2arr($xml) : false;
  }

  /**
   * 数组转JSON内容
   * @param array $data
   * @return null|string
   */
  public static function arr2json($data)
  {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    return $json === '[]' ? '{}' : $json;
  }

  /**
   * 解析JSON内容到数组
   * @param string $json
   * @return array
   * @throws InvalidResponseException
   */
  public static function json2arr($json)
  {
    $result = json_decode($json, true);
    if (empty($result)) {
      throw new InvalidResponseException('invalid response.', '0');
    }
    if (!empty($result['errcode'])) {
      throw new InvalidResponseException($result['errmsg'], $result['errcode'], $result);
    }
    return $result;
  }

  /**
   * 数组对象Emoji编译处理
   * @param array $data
   * @return array
   */
  public static function buildEnEmojiData(array $data)
  {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = self::buildEnEmojiData($value);
      } elseif (is_string($value)) {
        $data[$key] = self::emojiEncode($value);
      } else {
        $data[$key] = $value;
      }
    }
    return $data;
  }

  /**
   * 数组对象Emoji反解析处理
   * @param array $data
   * @return array
   */
  public static function buildDeEmojiData(array $data)
  {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = self::buildDeEmojiData($value);
      } elseif (is_string($value)) {
        $data[$key] = self::emojiDecode($value);
      } else {
        $data[$key] = $value;
      }
    }
    return $data;
  }

  /**
   * Emoji原形转换为String
   * @param string $content
   * @return string
   */
  public static function emojiEncode($content)
  {
    return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($string) {
      return addslashes($string[0]);
    }, json_encode($content)));
  }

  /**
   * Emoji字符串转换为原形
   * @param string $content
   * @return string
   */
  public static function emojiDecode($content)
  {
    return json_decode(preg_replace_callback('/\\\\\\\\/i', function () {
      return '\\';
    }, json_encode($content)));
  }

  /**
   * 写入文件
   * @param string $name 文件名称
   * @param string $content 文件内容
   * @return string
   * @throws LocalCacheException
   */
  public static function pushFile($name, $content)
  {
    if (is_callable(self::$cache_callable['put'])) {
      return call_user_func_array(self::$cache_callable['put'], func_get_args());
    }
    $file = self::_getCacheName($name);
    if (!file_put_contents($file, $content)) {
      throw new LocalCacheException('local file write error.', '0');
    }
    return $file;
  }

  /**
   * 应用缓存目录
   * @param string $name
   * @return string
   */
  private static function _getCacheName($name)
  {
    if (empty(self::$cache_path)) {
      self::$cache_path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    }
    self::$cache_path = rtrim(self::$cache_path, '/\\') . DIRECTORY_SEPARATOR;
    file_exists(self::$cache_path) || mkdir(self::$cache_path, 0755, true);
    return self::$cache_path . $name;
  }
}

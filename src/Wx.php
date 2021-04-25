<?php

namespace zskenny\wechat;

class WX
{

  // 当前版本号
  const VERSION = '1.0.0';

  public static function make($name, array $config)
  {
    if (substr($name, 0, 6) === 'WxChat') {
      $class = '\\zskenny\\wechat\\WxChat\\' . substr($name, 6);
    } elseif (substr($name, 0, 5) === 'WxApp') {
      $class = '\\zskenny\\wechat\\WxApp\\' . substr($name, 5);
    } elseif (substr($name, 0, 6) === 'AliPay') {
      $class = '\\zskenny\\wechat\\AliPay\\' . substr($name, 6);
    } elseif (substr($name, 0, 5) === 'WxPay') {
      $class = '\\zskenny\\wechat\\WxPay\\' . substr($name, 5);
    }

    return new $class($config);
  }

  public static function __callStatic($name, $arguments)
  {
    return self::make($name, ...$arguments);
  }
}

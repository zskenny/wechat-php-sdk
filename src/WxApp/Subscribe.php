<?php

namespace zskenny\wechat\WxApp;

use zskenny\wechat\WxCore\BaseWechat;

class Subscribe extends BaseWechat
{
  /**
   * 发送订阅消息
   * @param array $data 发送的消息对象数组
   * @return array
   * @throws \WeChat\Exceptions\InvalidResponseException
   * @throws \WeChat\Exceptions\LocalCacheException
   */
  public function send(array $data)
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=ACCESS_TOKEN';
    $this->parse_url($url, __FUNCTION__, func_get_args());
    return $this->http_post($url, $data, true);
  }
}

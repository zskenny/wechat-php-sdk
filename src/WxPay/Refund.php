<?php

namespace zskenny\wechat\WxPay;;

use zskenny\wechat\WxCore\BaseWepay;
use zskenny\wechat\WxCore\Helper;

class Refund extends BaseWepay
{
  // 创建退款订单
  public function create(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    return $this->callPostApi($url, $options, true);
  }
}

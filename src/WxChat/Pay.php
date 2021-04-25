<?php

namespace zskenny\wechat\WxChat;

use zskenny\wechat\WxCore\BaseWepay;
use zskenny\wechat\WxPay\Order;
use zskenny\wechat\WxPay\Refund;

class Pay extends BaseWepay
{
  // 统一下单
  public function createOrder(array $options)
  {
    return Order::instance($this->config->get())->create($options);
  }

  // 创建JsApi及H5支付参数
  // $prepay_id 统一下单预支付码
  public function createParamsForJsApi($prepay_id)
  {
    return Order::instance($this->config->get())->jsapiParams($prepay_id);
  }

  // 申请退款
  public function createRefund(array $options)
  {
    return Refund::instance($this->config->get())->create($options);
  }
}

<?php

namespace zskenny\wechat\WxPay;;

use zskenny\wechat\WxCore\BaseWepay;
use zskenny\wechat\WxCore\Helper;

// 微信商户订单
class Order extends BaseWepay
{
  // 统一下单
  public function create(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    return $this->callPostApi($url, $options, false, 'MD5');
  }

  // 刷卡支付
  public function micropay(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/pay/micropay';
    return $this->callPostApi($url, $options, false, 'MD5');
  }

  // 查询订单
  public function query(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
    return $this->callPostApi($url, $options);
  }

  // 关闭订单
  public function close($outTradeNo)
  {
    $url = 'https://api.mch.weixin.qq.com/pay/closeorder';
    return $this->callPostApi($url, ['out_trade_no' => $outTradeNo]);
  }

  // 创建JsApi及H5支付参数
  // $prepayId 统一下单预支付码
  public function jsapiParams($prepayId)
  {
    $option = [];
    $option["appId"] = $this->config->get('appid');
    $option["timeStamp"] = (string)time();
    $option["nonceStr"] = Helper::createNoncestr();
    $option["package"] = "prepay_id={$prepayId}";
    $option["signType"] = "MD5";
    $option["paySign"] = $this->getPaySign($option, 'MD5');
    $option['timestamp'] = $option['timeStamp'];
    return $option;
  }

  // 获取支付规则二维码
  // $productId 商户定义的商品id或者订单号
  public function qrcParams($productId)
  {
    $data = [
      'appid'      => $this->config->get('appid'),
      'mch_id'     => $this->config->get('mch_id'),
      'time_stamp' => (string)time(),
      'nonce_str'  => Helper::createNoncestr(),
      'product_id' => (string)$productId,
    ];
    $data['sign'] = $this->getPaySign($data, 'MD5');
    return "weixin://wxpay/bizpayurl?" . http_build_query($data);
  }

  // 获取微信App支付秘需参数
  // $prepayId 统一下单预支付码
  public function appParams($prepayId)
  {
    $data = [
      'appid'     => $this->config->get('appid'),
      'partnerid' => $this->config->get('mch_id'),
      'prepayid'  => (string)$prepayId,
      'package'   => 'Sign=WXPay',
      'timestamp' => (string)time(),
      'noncestr'  => Helper::createNoncestr(),
    ];
    $data['sign'] = $this->getPaySign($data, 'MD5');
    return $data;
  }

  // 刷卡支付 撤销订单
  public function reverse(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/reverse';
    return $this->callPostApi($url, $options, true);
  }

  // 刷卡支付 授权码查询openid
  // $authCode 扫码支付授权码，设备读取用户微信中的条码或者二维码信息
  public function queryAuthCode($authCode)
  {
    $url = 'https://api.mch.weixin.qq.com/Helper/authcodetoopenid';
    return $this->callPostApi($url, ['auth_code' => $authCode]);
  }

  // 刷卡支付 交易保障
  public function report(array $options)
  {
    $url = 'https://api.mch.weixin.qq.com/payitil/report';
    return $this->callPostApi($url, $options);
  }
}

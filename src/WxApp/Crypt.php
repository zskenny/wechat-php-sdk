<?php

namespace zskenny\wechat\WxApp;

use zskenny\wechat\WxApp\Crypt\WXBizDataCrypt;
use zskenny\wechat\WxCore\BaseWechat;
use zskenny\wechat\WxCore\Http;

class Crypt extends BaseWechat
{

  /**
   * 登录凭证校验
   * @param string $code 登录时获取的 code
   * @return array
   */
  public function session($code)
  {
    $appid = $this->config->get('appid');
    $secret = $this->config->get('appsecret');
    $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
    return json_decode(Http::get($url), true);
  }

  /**
   * 数据签名校验
   * @param string $iv
   * @param string $sessionKey
   * @param string $encryptedData
   * @return bool
   */
  public function decode($iv, $sessionKey, $encryptedData)
  {
    $pc = new WXBizDataCrypt($this->config->get('appid'), $sessionKey);
    $errCode = $pc->decryptData($encryptedData, $iv, $data);
    if ($errCode == 0) {
      return json_decode($data, true);
    }
    return false;
  }
}

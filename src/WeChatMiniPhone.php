<?php


namespace BusyPHP\wechat\oauth;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\wechat\WithWeChatConfig;
use RuntimeException;

/**
 * 微信小程序手机号解密
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/13 上午10:29 WeChatMiniPhone.php $
 * @see https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/getPhoneNumber.html
 */
class WeChatMiniPhone
{
    use WithWeChatConfig;
    
    /**
     * @var string
     */
    private $code;
    
    /**
     * @var string
     */
    private $encryptedData;
    
    /**
     * @var string
     */
    private $iv;
    
    /**
     * @var string
     */
    private $appId;
    
    /**
     * @var string
     */
    private $appSecret;
    
    
    /**
     * WeChatMiniPhone constructor.
     * @param string $code
     * @param string $encryptedData 加密的手机号数据
     * @param string $iv 与数据一同返回的初始向量
     * @param string $accountId 三方账户ID，用于区分同一种登录方式，不同账户
     */
    public function __construct(string $code, string $encryptedData, string $iv, string $accountId = '')
    {
        if (!$accountId) {
            $this->appId     = $this->getWeChatConfig('mini.app_id', '');
            $this->appSecret = $this->getWeChatConfig('mini.app_secret', '');
        } else {
            $this->appId     = $this->getWeChatConfig("mini.multi.{$accountId}.app_id", '');
            $this->appSecret = $this->getWeChatConfig("mini.multi.{$accountId}.app_secret", '');
        }
        
        $this->code          = $code;
        $this->encryptedData = $encryptedData;
        $this->iv            = $iv;
    
        if (!$this->appId) {
            throw new RuntimeException('请到config/extend/wechat.php配置mini.app_id');
        }
    
        if (!$this->appSecret) {
            throw new RuntimeException('请到config/extend/wechat.php配置mini.app_secret');
        }
        if (!$this->code) {
            throw new ParamInvalidException('$this->data');
        }
        if (!$this->encryptedData) {
            throw new ParamInvalidException('$this->encryptedData');
        }
        if (!$this->iv) {
            throw new ParamInvalidException('$this->iv');
        }
    }
    
    
    /**
     * 解密手机号
     * @return WeChatMiniPhoneResult
     */
    public function decrypt() : WeChatMiniPhoneResult
    {
        $result = WeChatMiniOAuth::getSessionByCode($this->code, $this->appId, $this->appSecret);
        $data   = WeChatMiniOAuth::decryptData($this->appId, $this->encryptedData, $this->iv, $result['session_key']);
        
        return WeChatMiniPhoneResult::init($data);
    }
}
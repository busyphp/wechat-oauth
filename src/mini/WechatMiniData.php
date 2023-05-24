<?php
declare(strict_types = 1);
namespace BusyPHP\oauth\driver\mini;

use BusyPHP\model\ArrayOption;
use BusyPHP\oauth\interfaces\OAuthDataInterface;

/**
 * 微信小程序登录数据
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 11:06 WechatMiniData.php $
 * @property string $iv
 * @property string $signature
 * @property string $rawData
 * @property string $encryptedData
 * @property string $code
 */
class WechatMiniData extends ArrayOption implements OAuthDataInterface
{
    /**
     * 设置调用 wx.login() 获取 临时登录凭证code，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/login.html}
     * @param string $code
     * @return static
     */
    public function setCode(string $code) : static
    {
        $this->code = $code;
        
        return $this;
    }
    
    
    /**
     * 设置加密算法的初始向量，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/wx.getUserInfo.html} {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E5%8A%A0%E5%AF%86%E6%95%B0%E6%8D%AE%E8%A7%A3%E5%AF%86%E7%AE%97%E6%B3%95}
     * @param string $iv
     * @return WechatMiniData
     */
    public function setIv(string $iv) : static
    {
        $this->iv = $iv;
        
        return $this;
    }
    
    
    /**
     * 设置签名，用于计算签名
     * 详见：{@link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/wx.getUserInfo.html} {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E6%95%B0%E6%8D%AE%E7%AD%BE%E5%90%8D%E6%A0%A1%E9%AA%8C}
     * @param string $signature
     * @return WechatMiniData
     */
    public function setSignature(string $signature) : static
    {
        $this->signature = $signature;
        
        return $this;
    }
    
    
    /**
     * 设置不包括敏感信息的原始数据字符串，用于计算签名
     * 详见：{@link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/wx.getUserInfo.html} {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E6%95%B0%E6%8D%AE%E7%AD%BE%E5%90%8D%E6%A0%A1%E9%AA%8C}
     * @param string $rawData
     * @return WechatMiniData
     */
    public function setRawData(string $rawData) : static
    {
        $this->rawData = $rawData;
        
        return $this;
    }
    
    
    /**
     * 设置包括敏感数据在内的完整用户信息的加密数据，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/wx.getUserInfo.html} {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E5%8A%A0%E5%AF%86%E6%95%B0%E6%8D%AE%E8%A7%A3%E5%AF%86%E7%AE%97%E6%B3%95}
     * @param string $encryptedData
     * @return WechatMiniData
     */
    public function setEncryptedData(string $encryptedData) : static
    {
        $this->encryptedData = $encryptedData;
        
        return $this;
    }
}
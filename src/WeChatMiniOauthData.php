<?php
declare(strict_types = 1);

namespace BusyPHP\wechat\oauth;

use BusyPHP\helper\ArrayHelper;
use BusyPHP\oauth\interfaces\OAuthAppData;

/**
 * 微信小程序登录参数结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/11 上午9:03 WeChatMiniOauthData.php $
 * @property string $nickName 昵称
 * @property string $openId openid
 * @property int    $gender 性别 1男 2女 0 未知
 * @property string $city 所在城市
 * @property string $province 所在省份
 * @property string $country 所在国家
 * @property string $avatarUrl 用户头像
 * @property string $unionId UnionId
 * @property array  $watermark appId和timestamp信息
 * @property string $appId appId
 * @property int    $timestamp TIMESTAMP
 * @link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/wx.getUserInfo.html
 */
class WeChatMiniOauthData extends OAuthAppData
{
    /**
     * 加密算法的初始向量，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E5%8A%A0%E5%AF%86%E6%95%B0%E6%8D%AE%E8%A7%A3%E5%AF%86%E7%AE%97%E6%B3%95}
     * @var string
     */
    public $iv;
    
    /**
     * 包括敏感数据在内的完整用户信息的加密数据，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E5%8A%A0%E5%AF%86%E6%95%B0%E6%8D%AE%E8%A7%A3%E5%AF%86%E7%AE%97%E6%B3%95}
     * @var string
     */
    public $encryptedData;
    
    /**
     * 调用 wx.login() 获取 临时登录凭证code，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/login.html}
     * @var string
     */
    public $code;
    
    /**
     * 数据
     * @var array
     */
    public $info = [];
    
    /**
     * 签名
     * @var string
     */
    public $signature;
    
    
    /**
     * WeChatMiniOauthData constructor.
     * @param string $code 临时登录凭证code
     * @param string $iv 向量
     * @param string $encryptedData 用户数据
     * @param string $signature 签名
     */
    public function __construct(string $code, string $iv, string $encryptedData, string $signature)
    {
        $this->code          = trim($code);
        $this->iv            = trim($iv);
        $this->encryptedData = trim($encryptedData);
        $this->signature     = $signature;
    }
    
    
    /**
     * 获取数据
     * @return mixed
     */
    public function getData()
    {
        return $this->info;
    }
    
    
    /**
     * 设置解密后的信息数据
     * @param $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }
    
    
    public function __get($name)
    {
        switch ($name) {
            case 'appId':
            case 'timestamp':
                return $this->info['watermark'][$name] ?? null;
            case 'gender':
                return intval($this->info[$name] ?? 0);
            default:
                return ArrayHelper::get($this->info, $name);
        }
    }
}
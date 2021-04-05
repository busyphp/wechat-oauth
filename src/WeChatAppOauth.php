<?php

namespace BusyPHP\wechat\oauth;

use BusyPHP\exception\AppException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\helper\net\Http;
use BusyPHP\oauth\interfaces\OAuthApp;
use BusyPHP\oauth\interfaces\OAuthInfo;
use BusyPHP\oauth\OAuthType;
use Throwable;

/**
 * 微信APP端登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/9 下午7:34 下午 WeChatAppOauth.php $
 * @see https://developers.weixin.qq.com/doc/oplatform/Mobile_App/WeChat_Login/Authorized_API_call_UnionID.html
 * @property WeChatAppOauthData $data
 */
class WeChatAppOauth extends OAuthApp
{
    protected $openId;
    
    protected $avatar;
    
    protected $isVerify = false;
    
    
    /**
     * WeChatAppOauth constructor.
     * @param $data
     * @throws ParamInvalidException
     */
    public function __construct($data)
    {
        parent::__construct($data);
        
        $this->openId = trim($this->data->openid);
        if (!$this->openId) {
            throw new ParamInvalidException('data.openid');
        }
    }
    
    
    /**
     * 获取登录类型
     * @return string
     */
    public function getType()
    {
        return OAuthType::TYPE_WECHAT_APP;
    }
    
    
    /**
     * 获取厂商类型
     * @return string
     */
    public function getUnionType()
    {
        return OAuthType::COMPANY_WECHAT;
    }
    
    
    /**
     * 获取用户信息，该方法可能会多次触发，请自行处理重复处理锁
     * @return OAuthInfo
     * @throws AppException
     */
    public function onGetInfo()
    {
        if (!$this->isVerify) {
            try {
                $result = Http::get("https://api.weixin.qq.com/sns/auth?access_token={$this->data->accessToken}&openid={$this->openId}");
            } catch (Throwable $e) {
                throw new AppException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result = json_decode($result, true);
            if (($result['errcode'] ?? 0) != 0) {
                throw new AppException("验证AccessToken失败: {$result['errmsg']} [{$result['errcode']}]");
            }
            
            $this->isVerify = true;
        }
        
        $info = new OAuthInfo($this);
        $info->setOpenId($this->openId);
        $info->setUnionId($this->data->unionid);
        $info->setNickname($this->data->nickname);
        $info->setAvatar($this->data->headimgurl);
        $info->setSex($this->data->sex);
        $info->setUserInfo($this->data->getData());
        $this->avatar = $info->getAvatar();
        
        return $info;
    }
    
    
    /**
     * 验证是否可以更新头像
     * @param string $avatar 用户已设置的头像地址
     * @return bool
     */
    public function canUpdateAvatar($avatar) : bool
    {
        // 无头像需要更新
        if (!$avatar) {
            return true;
        }
        
        // 无需更新
        if ($avatar == $this->avatar) {
            return false;
        }
        
        // 如果用户已设置的头像包含微信域名，则可以更新头像
        if (stripos($avatar, 'qlogo.cn')) {
            return true;
        }
        
        return false;
    }
}
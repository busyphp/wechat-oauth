<?php

namespace BusyPHP\wechat\oauth;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\helper\net\Http;
use BusyPHP\helper\util\Str;
use BusyPHP\oauth\interfaces\OAuth;
use BusyPHP\oauth\interfaces\OAuthInfo;
use BusyPHP\oauth\OAuthType;
use BusyPHP\wechat\WeChat;
use think\response\Redirect;
use Throwable;

/**
 * 微信OAuth2.0登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午3:29 下午 WeChatOAuth.php $
 */
class WeChatPublicOAuth extends WeChat implements OAuth
{
    /**
     * 公众号appId
     * @var string
     */
    protected $appId;
    
    /**
     * 公众号密钥
     * @var string
     */
    protected $appSecret;
    
    /**
     * 头像地址
     * @var string
     */
    protected $avatar;
    
    /**
     * @var OAuthInfo
     */
    protected $oauthInfo;
    
    /**
     * openid
     * @var string
     */
    protected $openId;
    
    /**
     * AccessToken
     * @var string
     */
    protected $accessToken;
    
    /**
     * 是否静默授权
     * @var bool
     */
    protected $isHidden;
    
    
    /**
     * WeChatOAuth constructor.
     * @param bool $isHidden 是否静默授权，默认否
     */
    public function __construct($isHidden = false)
    {
        parent::__construct();
        
        $this->appId     = $this->getConfig('public.app_id');
        $this->appSecret = $this->getConfig('public.app_secret');
        $this->isHidden  = $isHidden;
        
        // 干掉这2个属性，否则无法序列化
        $this->app     = null;
        $this->request = null;
    }
    
    
    /**
     * 获取登录类型
     * @return string
     */
    public function getType()
    {
        return OAuthType::TYPE_WECHAT_PUBLIC;
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
     * 执行申请授权
     * @param string $redirectUri 回调地址
     * @return Redirect
     */
    public function onApplyAuth($redirectUri)
    {
        $redirectUri = urlencode($redirectUri);
        $type        = $this->isHidden ? 'snsapi_base' : 'snsapi_userinfo';
        $state       = Str::random(32);
        
        return redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appId}&redirect_uri={$redirectUri}&response_type=code&scope={$type}&state={$state}#wechat_redirect");
    }
    
    
    /**
     * 换取票据
     * @return string
     * @throws WeChatOAuthException
     */
    public function onGetAccessToken()
    {
        if (!$this->accessToken || !$this->openId) {
            $code = trim($_GET['code']);
            if (!$code) {
                return false;
            }
            
            try {
                $result = Http::init()
                    ->get("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appId}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            $result            = $this->parseResult($result);
            $this->openId      = $result['openid'];
            $this->accessToken = $result['access_token'];
        }
        
        return $this->accessToken;
    }
    
    
    /**
     * 获取 OpenId
     * @return string|false
     * @throws WeChatOAuthException
     */
    public function getOpenId()
    {
        if (false === $this->onGetAccessToken()) {
            return false;
        }
        
        return $this->openId;
    }
    
    
    /**
     * 获取用户信息，该方法可能会多次触发，请自行处理重复处理锁
     * @return OAuthInfo
     * @throws WeChatOAuthException
     * @throws ParamInvalidException
     */
    public function onGetInfo()
    {
        if (!$this->oauthInfo) {
            $this->onGetAccessToken();
            
            try {
                $result = Http::init()
                    ->get("https://api.weixin.qq.com/sns/userinfo?access_token={$this->accessToken}&openid={$this->openId}&lang=zh_CN");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result = $this->parseResult($result);
            
            
            $info = new OAuthInfo($this);
            $info->setUserInfo($result);
            $info->setOpenId($result['openid']);
            $info->setNickname($result['nickname']);
            $info->setAvatar($result['headimgurl']);
            $info->setSex(OAuthInfo::parseSex($result['sex']));
            if ($result['unionid']) {
                $info->setUnionId($result['unionid']);
            }
            
            $this->avatar    = $info->getAvatar();
            $this->oauthInfo = $info;
        }
        
        
        return $this->oauthInfo;
    }
    
    
    /**
     * 验证是否可以更新头像
     * @param $avatar
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
    
    
    /**
     * 解析返回数据
     * @param string $result
     * @return array
     * @throws WeChatOAuthException
     */
    protected function parseResult($result)
    {
        $result = json_decode($result, true);
        if (!$result) {
            throw new WeChatOAuthException('系统异常，请稍候再试');
        }
        
        if ($result['errcode'] != 0) {
            throw new WeChatOAuthException($result['errmsg'], $result['errcode']);
        }
        
        return $result;
    }
}
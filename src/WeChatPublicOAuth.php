<?php
declare(strict_types = 1);

namespace BusyPHP\wechat\oauth;

use BusyPHP\App;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\helper\HttpHelper;
use BusyPHP\helper\StringHelper;
use BusyPHP\oauth\defines\OAuthType;
use BusyPHP\oauth\interfaces\OAuth;
use BusyPHP\oauth\interfaces\OAuthInfo;
use BusyPHP\wechat\WeChatConfig;
use RuntimeException;
use think\response\Redirect;
use Throwable;

/**
 * 微信OAuth2.0登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/11 上午8:30 WeChatPublicOAuth.php $
 */
class WeChatPublicOAuth implements OAuth
{
    use WeChatConfig;
    
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
     * @param bool   $isHidden 是否静默授权，默认否
     * @param string $accountId 三方账户ID，用于区分同一种登录方式，不同账户
     */
    public function __construct(bool $isHidden = false, $accountId = '')
    {
        if (!$accountId) {
            $this->appId     = $this->getWeChatConfig('public.app_id');
            $this->appSecret = $this->getWeChatConfig('public.app_secret');
        } else {
            $this->appId     = $this->getWeChatConfig("public.multi.{$accountId}.app_id");
            $this->appSecret = $this->getWeChatConfig("public.multi.{$accountId}.app_secret");
        }
        
        $this->isHidden = $isHidden;
        
        if (!$this->appId) {
            throw new RuntimeException('请到config/extend/wechat.php配置public.app_id');
        }
        
        if (!$this->appSecret) {
            throw new RuntimeException('请到config/extend/wechat.php配置public.app_secret');
        }
    }
    
    
    /**
     * 获取登录类型
     * @return int
     */
    public function getType() : int
    {
        return OAuthType::TYPE_WECHAT_PUBLIC;
    }
    
    
    /**
     * 获取厂商类型
     * @return int
     */
    public function getUnionType() : int
    {
        return OAuthType::COMPANY_WECHAT;
    }
    
    
    /**
     * 获取三方APPID
     * @return string
     */
    public function getAppId() : string
    {
        return $this->appId;
    }
    
    
    /**
     * 执行申请授权
     * @param string $redirectUri 回调地址
     * @return Redirect
     */
    public function onApplyAuth(string $redirectUri)
    {
        $redirectUri = urlencode($redirectUri);
        $type        = $this->isHidden ? 'snsapi_base' : 'snsapi_userinfo';
        $state       = StringHelper::random(32);
        
        return redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appId}&redirect_uri={$redirectUri}&response_type=code&scope={$type}&state={$state}#wechat_redirect");
    }
    
    
    /**
     * 换取票据
     * @return string
     */
    public function onGetAccessToken() : string
    {
        if (!$this->accessToken || !$this->openId) {
            $code = trim(App::getInstance()->request->get('code/s', '', 'trim'));
            if (!$code) {
                throw new ParamInvalidException('code');
            }
            
            try {
                $result = HttpHelper::init()
                    ->get("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appId}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result            = self::parseResult($result);
            $this->openId      = $result['openid'];
            $this->accessToken = $result['access_token'];
        }
        
        return $this->accessToken;
    }
    
    
    /**
     * 获取 OpenId
     * @return string
     */
    public function getOpenId() : string
    {
        $this->onGetAccessToken();
        
        return $this->openId;
    }
    
    
    /**
     * 获取用户信息，该方法可能会多次触发，请自行处理重复处理锁
     * @return OAuthInfo
     * @throws WeChatOAuthException
     * @throws ParamInvalidException
     */
    public function onGetInfo() : OAuthInfo
    {
        if (!$this->oauthInfo) {
            $this->onGetAccessToken();
            
            try {
                $result = HttpHelper::init()
                    ->get("https://api.weixin.qq.com/sns/userinfo?access_token={$this->accessToken}&openid={$this->openId}&lang=zh_CN");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result = self::parseResult($result);
            
            
            $info = new OAuthInfo($this);
            $info->setUserInfo($result);
            $info->setOpenId($result['openid'] ?? '');
            $info->setNickname($result['nickname'] ?? '');
            $info->setAvatar($result['headimgurl'] ?? '');
            $info->setSex(OAuthInfo::parseSex($result['sex'] ?? ''));
            
            if (!empty($result['unionid'])) {
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
    public static function parseResult($result) : array
    {
        $result = json_decode((string) $result, true) ?: [];
        if (!$result) {
            throw new WeChatOAuthException('系统异常，请稍候再试');
        }
        
        if (isset($result['errcode'])) {
            throw new WeChatOAuthException($result['errmsg'] ?? '', $result['errcode'] ?? 0);
        }
        
        return $result;
    }
}
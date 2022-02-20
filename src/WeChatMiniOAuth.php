<?php

namespace BusyPHP\wechat\oauth;

use BusyPHP\App;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\helper\HttpHelper;
use BusyPHP\oauth\defines\OAuthType;
use BusyPHP\oauth\interfaces\OAuthApp;
use BusyPHP\oauth\interfaces\OAuthInfo;
use BusyPHP\wechat\WithWeChatConfig;
use RuntimeException;
use Throwable;

/**
 * 微信小程序登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/11/5 下午7:38 下午 WeChatMiniOAuth.php $
 * @property WeChatMiniOauthData $data
 */
class WeChatMiniOAuth extends OAuthApp
{
    use WithWeChatConfig;
    
    /**
     * 用户头像
     * @var string
     */
    protected $avatar;
    
    /**
     * 是否已执行验证
     * @var bool
     */
    protected $isVerify = false;
    
    /**
     * @var App
     */
    protected $app;
    
    /**
     * 小程序APPID
     * @var string
     */
    protected $appId;
    
    /**
     * 小程序密钥
     * @var string
     */
    protected $appSecret;
    
    /**
     * 通过code换取的临时密钥
     * @var array
     */
    protected static $session = [];
    
    
    /**
     * WeChatMiniOAuth constructor.
     * @param WeChatMiniOauthData $data 三方登录数据
     * @param string              $accountId 三方账户ID，用于区分同一种登录方式，不同账户
     */
    public function __construct(WeChatMiniOauthData $data = null, string $accountId = '')
    {
        parent::__construct($data, $accountId);
        
        $this->app = App::getInstance();
        
        if (!$accountId) {
            $this->appId     = $this->getWeChatConfig('mini.app_id', '');
            $this->appSecret = $this->getWeChatConfig('mini.app_secret', '');
        } else {
            $this->appId     = $this->getWeChatConfig("mini.multi.{$accountId}.app_id", '');
            $this->appSecret = $this->getWeChatConfig("mini.multi.{$accountId}.app_secret", '');
        }
        
        if (!$this->appId) {
            throw new RuntimeException('请到config/extend/wechat.php配置mini.app_id');
        }
        
        if (!$this->appSecret) {
            throw new RuntimeException('请到config/extend/wechat.php配置mini.app_secret');
        }
        
        if (!$this->data->code) {
            throw new ParamInvalidException('$this->data->code');
        }
        
        if (!$this->data->signature) {
            throw new ParamInvalidException('$this->data->signature');
        }
        
        if (!$this->data->iv) {
            throw new ParamInvalidException('$this->data->iv');
        }
        
        if (!$this->data->encryptedData) {
            throw new ParamInvalidException('$this->data->encryptedData');
        }
    }
    
    
    /**
     * 获取登录类型
     * @return int
     */
    public function getType() : int
    {
        return OAuthType::TYPE_WECHAT_MIME;
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
     * 获取用户信息，该方法可能会多次触发，请自行处理重复处理锁
     * @return OAuthInfo
     */
    public function onGetInfo() : OAuthInfo
    {
        // 解密数据
        if (!$this->isVerify) {
            $result = static::getSessionByCode($this->data->code, $this->appId, $this->appSecret);
            $this->data->setInfo($this->decryptData($this->appId, $this->data->encryptedData, $this->data->iv, $result['session_key']));
            $this->data->openId  = $result['openid'];
            $this->data->unionId = $result['union_id'];
            $this->isVerify      = true;
        }
        
        $info = new OAuthInfo($this);
        $info->setOpenId($this->data->openId);
        $info->setUnionId($this->data->unionId);
        $info->setNickname($this->data->nickName);
        $info->setAvatar($this->data->avatarUrl);
        $info->setSex($this->data->gender);
        $info->setUserInfo($this->data->getData());
        
        $this->avatar = $this->data->avatarUrl;
        
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
    
    
    /**
     * 通过小程序code换取会话密钥和openid及unionId
     * @param string $code 登录code
     * @param string $appId 小程序APPID
     * @param string $appSecret 小程序秘钥
     * @return array 返回数组包含键：openid、union_id、session_key
     */
    public static function getSessionByCode(string $code, string $appId, string $appSecret) : array
    {
        $code = trim($code);
        if (isset(self::$session[$code])) {
            return self::$session[$code];
        }
        
        try {
            $result = HttpHelper::get("https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code");
        } catch (Throwable $e) {
            throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
        }
        
        $result = WeChatPublicOAuth::parseResult($result);
        if (empty($result['session_key']) || empty($result['openid'])) {
            throw new WeChatOAuthException('换取的票据数据异常');
        }
        
        self::$session[$code] = [
            'openid'      => $result['openid'],
            'union_id'    => $result['unionid'] ?? '',
            'session_key' => $result['session_key'],
        ];
        
        return self::$session[$code];
    }
    
    
    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param string $appId 小程序APPID
     * @param string $encryptedData 加密的用户数据
     * @param string $iv 与用户数据一同返回的初始向量
     * @param string $sessionKey 会话密钥
     * @return array
     */
    public static function decryptData(string $appId, string $encryptedData, string $iv, string $sessionKey) : array
    {
        if (strlen($sessionKey) != 24) {
            throw new WeChatOAuthException('encodingAesKey 非法');
        }
        
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            throw new WeChatOAuthException('iv非法' . strlen($iv));
        }
        
        $aesIV     = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $result    = json_decode((string) $result, true) ?: [];
        if (!$result) {
            throw new WeChatOAuthException('数据解密失败');
        }
        
        if (($result['watermark']['appid'] ?? '') != $appId) {
            throw new WeChatOAuthException('数据非法');
        }
        
        return $result;
    }
}
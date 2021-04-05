<?php

namespace BusyPHP\wechat\oauth;

use BusyPHP\App;
use BusyPHP\exception\AppException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\helper\net\Http;
use BusyPHP\oauth\interfaces\OAuthApp;
use BusyPHP\oauth\interfaces\OAuthInfo;
use BusyPHP\oauth\OAuthType;
use BusyPHP\wechat\WeChatConfig;

/**
 * 微信小程序登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/11/5 下午7:38 下午 WeChatMiniOAuth.php $
 * @property WeChatMiniOauthData $data
 */
class WeChatMiniOAuth extends OAuthApp
{
    use WeChatConfig;
    
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
     * @param WeChatMiniOauthData $data
     * @throws ParamInvalidException
     */
    public function __construct(WeChatMiniOauthData $data)
    {
        parent::__construct($data);
        
        $this->app       = app();
        $this->appId     = $this->getConfig('mini.app_id');
        $this->appSecret = $this->getConfig('mini.app_secret');
        
        if (!is_null($data)) {
            if (!$data instanceof WeChatMiniOauthData) {
                throw new ParamInvalidException('data');
            }
            
            if (!$this->data->code) {
                throw new ParamInvalidException('data.code');
            }
            
            if (!$this->data->signature) {
                throw new ParamInvalidException('data.signature');
            }
            
            if (!$this->data->iv) {
                throw new ParamInvalidException('data.iv');
            }
            
            if (!$this->data->encryptedData) {
                throw new ParamInvalidException('data.encryptedData');
            }
        }
    }
    
    
    /**
     * 获取登录类型
     * @return int
     */
    public function getType()
    {
        return OAuthType::TYPE_WECHAT_MIME;
    }
    
    
    /**
     * 获取厂商类型
     * @return int
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
        // 解密数据
        if (!$this->isVerify) {
            $result = static::getSessionByCode($this->data->code);
            $this->data->setInfo($this->decryptData($this->data->encryptedData, $this->data->iv, $result['session_key']));
            
            $this->isVerify = true;
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
     * @param string $code
     * @return array 返回数组包含键：openid、union_id、session_key
     * @throws WeChatOAuthException
     */
    public static function getSessionByCode($code)
    {
        $code = trim($code);
        if (isset(self::$session[$code])) {
            return self::$session[$code];
        }
        
        try {
            $self   = new self(null);
            $result = Http::get("https://api.weixin.qq.com/sns/jscode2session?appid={$self->appId}&secret={$self->appSecret}&js_code={$code}&grant_type=authorization_code");
        } catch (AppException $e) {
            throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
        }
        
        $result = json_decode($result, true);
        if (($result['errcode'] ?? 0) != 0) {
            throw new WeChatOAuthException($result['errmsg'] ?? '', $result['errcode'] ?? 0);
        }
        
        if (!$result['session_key'] || !$result['openid']) {
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
     * @param string $encryptedData 加密的用户数据
     * @param string $iv 与用户数据一同返回的初始向量
     * @param string $sessionKey 会话密钥
     * @return array
     * @throws WeChatOAuthException
     */
    public static function decryptData($encryptedData, $iv, $sessionKey)
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
        $result    = json_decode($result, true);
        if (!$result) {
            throw new WeChatOAuthException('数据解密失败');
        }
        
        $self = new self(null);
        if ($result['watermark']['appid'] != $self->appId) {
            throw new WeChatOAuthException('数据非法');
        }
        
        return $result;
    }
    
    
    /**
     * 获取手机号，
     * 详见: {@link https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/getPhoneNumber.html}
     * @param string $code
     * @param string $encryptedData 加密的手机号数据
     * @param string $iv 与数据一同返回的初始向量
     * @param bool   $returnRaw 是否返回原样数据，默认支返回不带区号的手机号，否则返回array
     * @return string|array 手机号码
     * @throws WeChatOAuthException
     */
    public static function getPhone($code, $encryptedData, $iv, $returnRaw = false)
    {
        $result = self::getSessionByCode($code);
        
        $data = static::decryptData($encryptedData, $iv, $result['session_key']);
        
        return $returnRaw ? $data : $data['purePhoneNumber'];
    }
}
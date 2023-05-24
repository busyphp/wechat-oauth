<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver;

use BusyPHP\helper\StringHelper;
use BusyPHP\oauth\Driver;
use BusyPHP\oauth\driver\publics\WechatPublicData;
use BusyPHP\oauth\interfaces\OAuthInfo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\Request;

/**
 * 微信公众号登录驱动
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 11:45 WechatPublic.php $
 * @property WechatPublicData $data
 */
class WechatPublic extends Driver
{
    protected string $code   = '';
    
    protected string $openid = '';
    
    
    public function __construct(array $config)
    {
        parent::__construct($config);
        
        if (!$this->name) {
            $this->name = '微信公众号登录';
        }
        if (!$this->union) {
            $this->union = 'wechat';
        }
    }
    
    
    /**
     * @inheritDoc
     */
    public function onGetApplyAuthUrl(string $redirectUri) : string
    {
        $type  = $this->data?->hidden ? 'snsapi_base' : 'snsapi_userinfo';
        $state = StringHelper::random(32);
        
        return sprintf("https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect", $this->appId, $redirectUri, $type, $state);
    }
    
    
    protected function isApplyAuthRedirected(Request $request) : bool
    {
        if ($code = $request->get('code/s', '', 'trim')) {
            $this->code = $code;
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * @inheritDoc
     */
    protected function onGetAccessToken() : string
    {
        $result       = (new Client())->get(sprintf("https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code", $this->appId, $this->appSecret, $this->code));
        $result       = WechatHelper::parseResult($result->getBody()->getContents());
        $this->openid = $result['openid'];
        
        return $result['access_token'];
    }
    
    
    /**
     * 获取openid
     * @return string
     */
    public function getOpenid() : string
    {
        $this->getAccessToken();
        
        return $this->openid;
    }
    
    
    /**
     * @inheritDoc
     * @throws GuzzleException
     */
    protected function onGetInfo() : OAuthInfo
    {
        $result = (new Client())->get(sprintf("https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN", $this->getAccessToken(), $this->openid));
        $result = WechatHelper::parseResult($result->getBody()->getContents());
        $info   = new OAuthInfo($this);
        $info->setUserInfo($result);
        $info->setOpenId($result['openid'] ?? '');
        $info->setNickname($result['nickname'] ?? '');
        $info->setAvatar($result['headimgurl'] ?? '');
        $info->setSex(OAuthInfo::parseSex($result['sex'] ?? ''));
        $info->setUnionId($result['unionid'] ?? '');
        $this->openid = $info->getOpenId();
        
        return $info;
    }
    
    
    /**
     * @inheritDoc
     */
    public function canUpdateAvatar(string $avatar) : bool
    {
        // 无头像需要更新
        if (!$avatar) {
            return true;
        }
        
        // 无需更新
        if ($avatar == $this->getInfo()->getAvatar()) {
            return false;
        }
        
        // 如果用户已设置的头像包含微信域名，则可以更新头像
        if (stripos($avatar, 'qlogo.cn')) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * @inheritDoc
     */
    public function getSettingForm() : array
    {
        return [
            [
                'label'       => 'AppID(公众号AppID)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_id',
                'required'    => true,
                'placeholder' => '请输入公众号AppId',
                'help'        => '设置公众号AppId',
                'attributes'  => [
                    'data-msg-required' => '请输入公众号AppId'
                ]
            ],
            [
                'label'       => 'AppSecret(公众号密钥)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_secret',
                'required'    => true,
                'placeholder' => '请输入公众号密钥',
                'help'        => '设置公众号秘钥',
                'attributes'  => [
                    'data-msg-required' => '请输入公众号密钥'
                ]
            ]
        ];
    }
}
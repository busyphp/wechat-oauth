<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\oauth\Driver;
use BusyPHP\oauth\driver\app\WechatAppData;
use BusyPHP\oauth\interfaces\OAuthInfo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 微信APP登录驱动
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 12:10 WechatApp.php $
 * @link https://developers.weixin.qq.com/doc/oplatform/Mobile_App/WeChat_Login/Authorized_API_call_UnionID.html
 * @property WechatAppData $data
 */
class WechatApp extends Driver
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        
        if (!$this->name) {
            $this->name = '微信APP登录';
        }
        if (!$this->union) {
            $this->union = 'wechat';
        }
    }
    
    
    /**
     * @inheritDoc
     */
    protected function onGetAccessToken() : string
    {
        return $this->data->accessToken;
    }
    
    
    /**
     * @inheritDoc
     * @throws GuzzleException
     */
    protected function onGetInfo() : OAuthInfo
    {
        if (!$this->data) {
            throw new ParamInvalidException('$this->data');
        }
        
        WechatHelper::parseResult(
            (new Client())
                ->get(sprintf("https://api.weixin.qq.com/sns/auth?access_token=%s&openid=%s", $this->data->accessToken, $this->data->auth->openid))
                ->getBody()
                ->getContents()
        );
        
        $info = new OAuthInfo($this);
        $info->setOpenId($this->data->auth->openid);
        $info->setUnionId($this->data->auth->unionid);
        $info->setNickname($this->data->auth->nickname);
        $info->setAvatar($this->data->auth->headimgurl);
        $info->setSex(OAuthInfo::parseSex($this->data->auth->sex));
        $info->setUserInfo($this->data->auth->toArray());
        
        return $info;
    }
    
    
    /**
     * @inheritDoc
     */
    public function canUpdateAvatar(string $avatar) : bool
    {
        return WechatHelper::canUpdateAvatar($avatar, $this->getInfo()->getAvatar());
    }
    
    
    /**
     * @inheritDoc
     */
    public function getSettingForm() : array
    {
        return [
            [
                'label'       => 'AppID(开放平台AppID)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_id',
                'required'    => true,
                'placeholder' => '请输入开放平台AppId',
                'help'        => '设置开放平台AppId',
                'attributes'  => [
                    'data-msg-required' => '请输入开放平台AppId'
                ]
            ],
            [
                'label'       => 'AppSecret(开放平台密钥)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_secret',
                'required'    => true,
                'placeholder' => '请输入开放平台密钥',
                'help'        => '设置开放平台秘钥',
                'attributes'  => [
                    'data-msg-required' => '请输入开放平台密钥'
                ]
            ]
        ];
    }
}
<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\oauth\Driver;
use BusyPHP\oauth\driver\exception\WeChatOAuthException;
use BusyPHP\oauth\driver\mini\WechatMiniPhone;
use BusyPHP\oauth\driver\mini\WechatMiniData;
use BusyPHP\oauth\interfaces\OAuthInfo;
use GuzzleHttp\Client;
use Throwable;

/**
 * 微信小程序登录
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/23 17:56 WechatMini.php $
 * @property WechatMiniData $data
 */
class WechatMini extends Driver
{
    protected string $openid  = '';
    
    protected string $unionId = '';
    
    
    public function __construct(array $config)
    {
        parent::__construct($config);
        
        if (!$this->name) {
            $this->name = '微信小程序登录';
        }
        if (!$this->union) {
            $this->union = 'wechat';
        }
    }
    
    
    /**
     * 通过小程序code换取会话密钥和openid及unionId
     * @inheritDoc
     */
    protected function onGetAccessToken() : string
    {
        try {
            $result = (new Client)
                ->get(sprintf("https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code", $this->appId, $this->appSecret, $this->data->code))
                ->getBody()
                ->getContents();
        } catch (Throwable $e) {
            throw new WeChatOAuthException($e->getMessage(), $e->getCode(), $e);
        }
        
        $result = WechatHelper::parseResult($result);
        if (empty($result['session_key']) || empty($result['openid'])) {
            throw new WeChatOAuthException('换取的票据数据异常');
        }
        
        $this->openid  = $result['openid'];
        $this->unionId = $result['unionid'] ?? '';
        
        return $result['session_key'];
    }
    
    
    /**
     * @inheritDoc
     */
    protected function onGetInfo() : OAuthInfo
    {
        if (!isset($this->data)) {
            throw new ParamInvalidException('$this->data');
        }
        if (!$this->data->iv) {
            throw new ParamInvalidException('iv');
        }
        if (!$this->data->encryptedData) {
            throw new ParamInvalidException('encryptedData');
        }
        if (!$this->data->signature) {
            throw new ParamInvalidException('signature');
        }
        if (!$this->data->code) {
            throw new ParamInvalidException('code');
        }
        if (!$this->data->rawData) {
            throw new ParamInvalidException('rawData');
        }
        
        $result = $this->decrypt($this->data->iv, $this->data->encryptedData);
        if (sha1($this->data->rawData . $this->getAccessToken()) !== $this->data->signature) {
            throw new WeChatOAuthException('数据签名验证失败');
        }
        $info = new OAuthInfo($this);
        $info->setOpenId($this->openid);
        $info->setUnionId($this->unionId);
        $info->setNickname($result['nickName'] ?? '');
        $info->setSex(intval($result['gender'] ?? 0));
        $info->setAvatar($result['avatarUrl'] ?? '');
        $info->setUserInfo($result);
        
        return $info;
    }
    
    
    /**
     * 获取手机号
     * @param string $iv
     * @param string $encryptedData
     * @return WechatMiniPhone
     */
    public function getPhone(string $iv, string $encryptedData) : WechatMiniPhone
    {
        return WechatMiniPhone::init($this->decrypt($iv, $encryptedData));
    }
    
    
    /**
     * @inheritDoc
     */
    public function canUpdateAvatar(string $avatar) : bool
    {
        return WechatHelper::canUpdateAvatar($avatar, $this->getInfo()->getAvatar());
    }
    
    
    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param string $iv
     * @param string $encryptedData
     * @param string $source
     * @return array
     */
    protected function decrypt(string $iv, string $encryptedData, string &$source = '') : array
    {
        $aesKey = base64_decode($this->getAccessToken());
        if (strlen($iv) != 24) {
            throw new WeChatOAuthException('iv非法' . strlen($iv));
        }
        
        $aesIV     = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $source    = $result;
        $result    = json_decode((string) $result, true) ?: [];
        if (!$result) {
            throw new WeChatOAuthException('数据解密失败');
        }
        
        if (($result['watermark']['appid'] ?? '') != $this->appId) {
            throw new WeChatOAuthException('数据非法');
        }
        
        return $result;
    }
    
    
    /**
     * @inheritDoc
     */
    public function getSettingForm() : array
    {
        return [
            [
                'label'       => 'AppID(小程序ID)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_id',
                'required'    => true,
                'placeholder' => '请输入小程序AppId',
                'help'        => '设置小程序AppId',
                'attributes'  => [
                    'data-msg-required' => '请输入小程序AppId'
                ]
            ],
            [
                'label'       => 'AppSecret(小程序密钥)',
                'tag'         => 'input',
                'type'        => 'text',
                'name'        => 'app_secret',
                'required'    => true,
                'placeholder' => '请输入小程序密钥',
                'help'        => '设置小程序秘钥',
                'attributes'  => [
                    'data-msg-required' => '请输入小程序密钥'
                ]
            ]
        ];
    }
}
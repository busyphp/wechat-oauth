<?php

namespace BusyPHP\wechat\oauth {
    
    use BusyPHP\exception\AppException;
    use BusyPHP\exception\ParamInvalidException;
    use BusyPHP\helper\net\Http;
    use BusyPHP\oauth\interfaces\OAuth_Info;
    use BusyPHP\oauth\interfaces\OAuthApp;
    use BusyPHP\oauth\interfaces\OAuthApp_Data;
    use BusyPHP\oauth\OAuthType;
    use Throwable;
    
    /**
     * 微信APP端登录
     * @author busy^life <busy.life@qq.com>
     * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
     * @version $Id: 2020/7/9 下午7:34 下午 WeChatAppOauth.php $
     * @see https://developers.weixin.qq.com/doc/oplatform/Mobile_App/WeChat_Login/Authorized_API_call_UnionID.html
     * @property WeChatAppOauth_Data $data
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
         * @return OAuth_Info
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
                if ($result['errcode'] != 0) {
                    throw new AppException("验证AccessToken失败: {$result['errmsg']} [{$result['errcode']}]");
                }
                
                $this->isVerify = true;
            }
            
            $info = new OAuth_Info($this);
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
    
    
    class WeChatAppOauth_Data extends OAuthApp_Data
    {
        /**
         * 省份
         * @var string
         */
        public $province = '';
        
        /**
         * 城市
         * @var string
         */
        public $city = '';
        
        /**
         * 国家
         * @var string
         */
        public $country = '';
        
        /**
         * 头像地址
         * @var string
         */
        public $headimgurl = '';
        
        /**
         * 昵称
         * @var string
         */
        public $nickname = '';
        
        /**
         * OPENID
         * @var string
         */
        public $openid = '';
        
        /**
         * unionId
         * @var string
         */
        public $unionid = '';
        
        /**
         * 性别 1=男, 2=女
         * @var int
         */
        public $sex = 0;
        
        /**
         * @var string
         */
        public $accessToken = '';
        
        
        /**
         * WeChatAppOauth_Data constructor.
         * @param string       $accessToken 三方给的密钥
         * @param string|array $data 三方给的数据 JSON格式, 示例: {
         *      "city"       : "城市",
         *      "country"    : "国家",
         *      "headimgurl" : "头像地址",
         *      "nickname"   : "昵称",
         *      "openid"     : "openid",
         *      "province"   : "省份",
         *      "sex"        : 性别 1=男, 2=女,
         *      "unionid"    : "unionid 如果有"
         * }
         */
        public function __construct($accessToken, $data = '')
        {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            
            $this->accessToken = $accessToken;
            $this->province    = $data['province'] ?? '';
            $this->city        = $data['city'] ?? '';
            $this->country     = $data['country'] ?? '';
            $this->headimgurl  = $data['headimgurl'] ?? '';
            $this->nickname    = $data['nickname'] ?? '';
            $this->openid      = $data['openid'] ?? '';
            $this->unionid     = $data['unionid'] ?? '';
            
            $this->sex = $data['sex'] ?? 0;
            $this->sex = intval($this->sex);
            $this->sex = $this->sex > 2 ? 2 : $this->sex;
            $this->sex = $this->sex < 0 ? 0 : $this->sex;
        }
        
        
        /**
         * 获取数据
         * @return array
         */
        function getData()
        {
            $data = get_object_vars($this);
            unset($data['accessToken']);
            
            return $data;
        }
    }
}
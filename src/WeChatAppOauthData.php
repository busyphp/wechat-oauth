<?php
declare(strict_types = 1);

namespace BusyPHP\wechat\oauth;

use BusyPHP\oauth\interfaces\OAuthAppData;

/**
 * 微信APP登录数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/11 上午9:18 WeChatAppOauthData.php $
 */
class WeChatAppOauthData extends OAuthAppData
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
            $data = json_decode($data, true) ?: [];
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
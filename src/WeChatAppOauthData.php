<?php
declare(strict_types = 1);

namespace BusyPHP\wechat\oauth;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\model\ArrayOption;

/**
 * 微信APP登录数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/11 上午9:18 WeChatAppOauthData.php $
 * @property string $province 省份
 * @property string $city 城市
 * @property string $country 国家
 * @property string $headimgurl 头像地址
 * @property string $nickname 昵称
 * @property string $openid OPENID
 * @property string $unionid unionId
 */
class WeChatAppOauthData extends ArrayOption
{
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
     * @var array
     */
    private $data;
    
    
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
    public function __construct(string $accessToken, $data = '')
    {
        if (!$accessToken) {
            throw new ParamInvalidException('$accessToken');
        }
        
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        
        parent::__construct($data);
        
        
        $this->data        = $data;
        $this->accessToken = $accessToken;
        $this->sex         = $data['sex'] ?? 0;
        $this->sex         = intval($this->sex);
        $this->sex         = $this->sex > 2 ? 2 : $this->sex;
        $this->sex         = $this->sex < 0 ? 0 : $this->sex;
    }
    
    
    /**
     * 获取数据
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
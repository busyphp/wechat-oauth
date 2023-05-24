<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver\app;

use BusyPHP\oauth\interfaces\OAuthDataInterface;

/**
 * 微信APP登录数据
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 12:13 WechatAppData.php $
 */
class WechatAppData implements OAuthDataInterface
{
    public string            $accessToken;
    
    public WechatAppAuthData $auth;
    
    
    /**
     * @param string       $accessToken
     * @param array|string $data 三方给的数据 JSON格式, 示例:
     * <pre>
     * {
     *      "city"       : "城市",
     *      "country"    : "国家",
     *      "headimgurl" : "头像地址",
     *      "nickname"   : "昵称",
     *      "openid"     : "openid",
     *      "province"   : "省份",
     *      "sex"        : 性别 1=男, 2=女,
     *      "unionid"    : "unionid 如果有"
     * }
     * </pre>
     */
    public function __construct(string $accessToken, array|string $data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $this->accessToken = $accessToken;
        $this->auth        = WechatAppAuthData::init($data);
    }
}
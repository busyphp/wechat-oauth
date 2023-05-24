<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver\app;

use BusyPHP\model\ArrayOption;

/**
 * 微信APP登录授权数据
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 12:13 WechatAppAuthData.php $
 * @property string $city 城市
 * @property string $country 国家
 * @property string $headimgurl 头像地址
 * @property string $nickname 昵称
 * @property string $openid openid
 * @property string $province 省份
 * @property string $sex 性别 1=男, 2=女
 * @property string $unionid unionid 如果有
 */
class WechatAppAuthData extends ArrayOption
{
}
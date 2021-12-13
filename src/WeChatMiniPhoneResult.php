<?php

namespace BusyPHP\wechat\oauth;

use BusyPHP\model\ArrayOption;

/**
 * @see WeChatMiniPhone::decrypt()
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/13 上午10:35 WeChatMiniPhoneResult.php $
 * @property string $phoneNumber 用户绑定的手机号（国外手机号会有区号）
 * @property string $purePhoneNumber 没有区号的手机号
 * @property string $countryCode 区号
 * @property array $watermark
 */
class WeChatMiniPhoneResult extends ArrayOption
{
}
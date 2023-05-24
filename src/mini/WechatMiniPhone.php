<?php
declare(strict_types=1);

namespace BusyPHP\oauth\driver\mini;

use BusyPHP\model\ArrayOption;

/**
 * WechatMiniPhone
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/23 17:37 WechatMiniPhone.php $
 * @property string $phoneNumber 用户绑定的手机号（国外手机号会有区号）
 * @property string $purePhoneNumber 没有区号的手机号
 * @property string $countryCode 区号
 * @property array $watermark
 */
class WechatMiniPhone extends ArrayOption
{
}
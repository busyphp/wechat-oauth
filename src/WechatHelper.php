<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver;

use BusyPHP\oauth\driver\exception\WeChatOAuthException;

/**
 * WechatHelper
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 11:48 WechatHelper.php $
 */
class WechatHelper
{
    /**
     * 解析返回数据
     * @param string $result
     * @return array
     * @throws WeChatOAuthException
     */
    public static function parseResult(string $result) : array
    {
        $result = json_decode($result, true) ?: [];
        if (!$result) {
            throw new WeChatOAuthException('系统异常，请稍候再试');
        }
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            throw new WeChatOAuthException($result['errmsg'] ?? '', $result['errcode']);
        }
        
        return $result;
    }
    
    
    /**
     * 是否可以更新头像
     * @param string $oldAvatar
     * @param string $newAvatar
     * @return bool
     */
    public static function canUpdateAvatar(string $oldAvatar, string $newAvatar) : bool
    {
        // 无头像需要更新
        if (!$oldAvatar) {
            return true;
        }
        
        // 无需更新
        if ($oldAvatar == $newAvatar) {
            return false;
        }
        
        // 如果用户已设置的头像包含微信域名，则可以更新头像
        if (stripos($oldAvatar, 'qlogo.cn')) {
            return true;
        }
        
        return false;
    }
}
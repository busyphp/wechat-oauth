<?php
declare(strict_types = 1);

namespace BusyPHP\oauth\driver\publics;

use BusyPHP\oauth\interfaces\OAuthDataInterface;

/**
 * 微信公众号登录数据
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/24 11:53 WechatPublicData.php $
 */
class WechatPublicData implements OAuthDataInterface
{
    public bool $hidden;
    
    
    /**
     * 构造函数
     * @param bool $hidden 是否静默登录
     */
    public function __construct(bool $hidden = false)
    {
        $this->hidden = $hidden;
    }
    
    
    /**
     * 设置是否静默登录
     * @param bool $hidden
     * @return static
     */
    public function setHidden(bool $hidden) : static
    {
        $this->hidden = $hidden;
        
        return $this;
    }
}
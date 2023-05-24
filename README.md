BusyPHP OAuth2.0 微信登录模块
===============

## 说明

用于BusyPHP微信登录，支持公众号、小程序、App端

## 安装

```shell
composer require busyphp/wechat-oauth
```

## 配置

> 安装成功后请将以下配置复制到 `config/oauth.php` 的 `drivers` 中

```php
'wechat_public' => [
    'type' => 'wechat_public'
],  
'wechat_mini' => [
    'type' => 'wechat_mini'
],  
'wechat_app' => [
    'type' => 'wechat_app'
],      
```

## 微信登录

```php
<?php
use BusyPHP\Controller;
use BusyPHP\model\Field;
use BusyPHP\oauth\defines\OAuthType;
use BusyPHP\oauth\interfaces\OAuthCallback;
use BusyPHP\oauth\model\MemberOauth;
use BusyPHP\wechat\oauth\WeChatMiniOauthData;

class Login extends Controller {
    // 小程序登录
    public function index() {
        // 微信公众号登录
        $driver = OAuth::driver('wechat_public');
        $driver->setData(new WechatPublicData(false));
        $driver->webLogin(url()->domain(true)->build());
        
        
        // 微信小程序登录
        $driver = OAuth::driver('wechat_mini');
        $driver->setData(new WechatMiniData([
            'iv'            => 'iv',
            'signature'     => 'signature',
            'rawData'       => 'rawData',
            'encryptedData' => 'encryptedData',
            'code'          => 'code',
        ]));
        $driver->login();
        
        // 微信APP登录
        $driver = OAuth::driver('wechat_app');
        $driver->setData(new WechatAppData('accessToken', [
            "city"       => "城市",
            "country"    => "国家",
            "headimgurl" => "头像地址",
            "nickname"   => "昵称",
            "openid"     => "openid",
            "province"   => "省份",
            "sex"        => '性别 1=男, 2=女',
            "unionid"    => "unionid 如果有"
        ]));
        $driver->login();
    }
}
```
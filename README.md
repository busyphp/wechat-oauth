微信登录模块
===============

## 说明

用于BusyPHP微信登录，支持公众号、小程序、App端。支持公众号JSSDK

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
        // 获取前端传来的小程序登录参数
        $iv            = $this->post('iv/s', 'trim');
        $encryptedData = $this->post('encryptedData/s', 'trim');
        $code          = $this->post('code/s', 'trim');
        $signature     = $this->post('signature/s', 'trim');
        
        // 构建小程序登录参数
        $data = new WeChatMiniOauthData($code, $iv, $encryptedData, $signature);
    
        // 实例化模型
        $oauthModel = MemberOauth::init();
        $app = $oauthModel->getOAuthApp(OAuthType::TYPE_WECHAT_MIME, $data);

        // 执行登录
        $oauthModel->login($app, new class implements OAuthCallback {
            /**
             * 执行注册校验
             * @param \BusyPHP\oauth\interfaces\OAuth $oauth
             * @return int 返回用户ID代表已注册，则执行绑定，返回0代表用户未注册，则执行注册
             */
            public function onCheckRegister(\BusyPHP\oauth\interfaces\OAuth $oauth) : int {
                return 0;
            }

            /**
             * 获取注册的用户数据
             * @param \BusyPHP\oauth\interfaces\OAuth $oauth
             * @return Field
             */
            public function onGetRegisterData(\BusyPHP\oauth\interfaces\OAuth $oauth) : Field {
                $oauthInfo             = $oauth->onGetInfo();
                $memberField           = MemberField::init();
                $memberField->nickname = $oauthInfo->getNickname();
                $memberField->sex      = $oauthInfo->getSex();
                $memberField->avatar   = $oauthInfo->getAvatar();
                
                return $memberField;
            }
        });  
    }
}
```

## JSSDK

```php
<?php
use BusyPHP\Controller;
use BusyPHP\wechat\oauth\WeChatPublicJsSDK;
use think\Response;

class Jssdk extends Controller {
    /**
     * JsSDK配置
     */
    public function config() {
        $js = new WeChatPublicJsSDK();
        $config = $js->getSignPackage();
        $config['debug'] = true; // 开始调试模式
        $config['jsApiList'] = [
            // 需要使用的JS接口列表
            // 参考：https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/JS-SDK.html
        ]; 
        $config = json_encode($config, JSON_UNESCAPED_UNICODE);    
        $script = <<<JS
wx.config({$config});
JS; 
        return Response::create($script, 'html', 200)->contentType('application/javascript');
    }
    
    /**
     * 关闭微信浏览器
     */
    public function close() {
       return WeChatPublicJsSDK::closeBrowser('消息提示');
    }
}
```
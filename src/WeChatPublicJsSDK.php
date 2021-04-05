<?php

namespace BusyPHP\wechat\oauth;


use BusyPHP\Cache;
use BusyPHP\helper\net\Http;
use BusyPHP\helper\util\Str;
use BusyPHP\wechat\WeChat;
use think\Response;
use Throwable;

/**
 * 微信公众号JS SDK
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午11:01 上午 WeChatJsSDK.php $
 */
class WeChatPublicJsSDK extends WeChat
{
    /**
     * @var string
     */
    protected $appId;
    
    /**
     * @var string
     */
    protected $appSecret;
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->appId     = $this->getConfig('public.app_id');
        $this->appSecret = $this->getConfig('public.app_secret');
    }
    
    
    /**
     * 获取Ticket
     * @return string
     * @throws WeChatOAuthException
     */
    private function getJsApiTicket()
    {
        $ticket = Cache::get($this, 'ticket');
        if (!$ticket) {
            $accessToken = $this->getJsAccessToken();
            try {
                $result = Http::get("https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$accessToken}");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result = json_decode($result, true);
            if ($result['errcode'] != 0) {
                throw new WeChatOAuthException($result['errmsg'], $result['errcode']);
            }
            
            if (!$result['ticket']) {
                throw new WeChatOAuthException('无法获取Ticket');
            }
            
            $ticket = $result['ticket'];
            Cache::set($this, 'ticket', $ticket, 7000);
        }
        
        return $ticket;
    }
    
    
    /**
     * 获取AccessToken
     * @return string
     * @throws WeChatOAuthException
     */
    private function getJsAccessToken()
    {
        $accessToken = Cache::get($this, 'access_token');
        if (!$accessToken) {
            try {
                $result = Http::get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}");
            } catch (Throwable $e) {
                throw new WeChatOAuthException("HTTP请求失败: {$e->getMessage()} [{$e->getCode()}]");
            }
            
            $result = json_decode($result, true);
            if (($result['errcode'] ?? 0) > 0) {
                throw new WeChatOAuthException($result['errmsg'] ?? '', $result['errcode'] ?? 0);
            }
            if (!$result['access_token']) {
                throw new WeChatOAuthException('无法获取accessToken');
            }
            $accessToken = $result['access_token'];
            Cache::set($this, 'access_token', $accessToken, 7000);
        }
        
        return $accessToken;
    }
    
    
    /**
     * 生成JS SDK参数
     * @return array
     * @throws WeChatOAuthException
     */
    public function getSignPackage()
    {
        $ticket    = $this->getJsApiTicket();
        $url       = $_SERVER['HTTP_REFERER'] ?: request()->url();
        $timestamp = trim(time());
        $nonceStr  = Str::random(16);
        
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string    = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($string);
        
        return [
            'appId'     => $this->appId,
            'nonceStr'  => $nonceStr,
            'timestamp' => $timestamp,
            'url'       => $url,
            'signature' => $signature
        ];
    }
    
    
    /**
     * 提示消息并关闭微信浏览器
     * @param string $message 提示的消息内容
     * @return Response
     */
    public static function closeBrowser($message = '')
    {
        $message = trim($message);
        $script  = '';
        if ($message) {
            $message = str_replace('"', '\"', $message);
            $script  = 'alert("' . $message . '");';
        }
        
        $data = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>消息</title>
</head>
<body>
    <script src='http://res.wx.qq.com/open/js/jweixin-1.0.0.js'></script>
    <script>
        {$script}
        setInterval(function() {
            wx.closeWindow();
        }, 50);
    </script>
</body>
</html>
HTML;
        
        return Response::create($data);
    }
}
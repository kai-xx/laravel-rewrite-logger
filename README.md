[![Laravel Version](https://img.shields.io/badge/Laravel-%3E=5.5.0-brightgreen.svg?maxAge=2592000)](https://learnku.com/docs/laravel/5.5)
[![Laravel Version](https://img.shields.io/badge/Laravel-%3E=6.2.0-brightgreen.svg?maxAge=2592000)](https://learnku.com/docs/laravel/6.x)

更改目的：
- 重写了日志格式
- 加入```trace```，一次请求的唯一标识
- 加入```error```级别信息推送，事例中使用企业微信群助手

1. 将文件 ```AppTool.php``` ```Logger.php``` ```LogServiceProvider.php``` 复制到 ```app/Providers```文件夹下
2. 在```config/app.php→providers```中加入

   ```php
   'providers' => [
     ……
     // 注册日志
      App\Providers\LogServiceProvider::class
     ……
     ];
   ```

3. 在项目中使用如下方式调用

   ```php  
   // php-fpm方式调用 日志路径 /opt/logs/xxx.log /opt/logs/xxx.error
   app('Log')->info("info");
   app('Log')->debug("debug");
   app('Log')->error("error");
   // 在cli方式调用 日志路径 /opt/clogs/xxx.log /opt/clogs/xxx.error
   app('cLog')->info("info");
   app('cLog')->debug("debug");
   app('cLog')->error("error");
   ```

4. 在日志级别为```error```时，会执行推送，本事例中采用企业微信群推送

   ```php 
       /**
        * 推送错误信息
        * @param $message
        */
       public function pushErrorMessage($message)
       {
           $content = "app：". static::getAppName() ."  
   src： ". static::getRequestSource() ."
   trace：". self::getTrace() ."
   url：". static::$uri_info ." 
   error: ". $message ."
   time：". date("Y-m-d H:i:s");
           // 测试群
           $url = "xxxxxxxxxxxx";
           $result = app('\GuzzleHttp\Client')->request('POST', $url, [
               \GuzzleHttp\RequestOptions::JSON=>[
                   "msgtype"=> "text",
                   "text"=> [
                       "content" => $content
                   ]
               ]
           ]);
           $body = \GuzzleHttp\json_decode($result->getBody()->getContents(), true);
       }
   ```


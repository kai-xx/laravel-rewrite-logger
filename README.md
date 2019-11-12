[![Laravel Version](https://img.shields.io/badge/Laravel-%3E=5.5.0-brightgreen.svg?maxAge=2592000)](https://learnku.com/docs/laravel/5.5)
[![Laravel Version](https://img.shields.io/badge/Laravel-%3E=6.x-brightgreen.svg?maxAge=2592000)](https://learnku.com/docs/laravel/6.x)

更改目的：
- 重写了日志格式
- 加入```trace```，一次请求的唯一标识
- 加入```error```级别信息推送，事例中使用企业微信群助手
- 让我们可以更优雅、更方便追踪日志信息

1. 将文件 ```AppTool.php``` ```Logger.php``` ```LogServiceProvider.php``` 复制到 ```app/Providers```文件夹下，
将文件```BaseCommand.php```复制到```App\Console```下
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
   \Log::info("info");
   \Log::debug("debug");
   \Log::error("error");
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
5. 日志内容
```
// cli模式下的
[2019-11-11 17:50:01] local.INFO: [app:partner-counselor src:127.0.0.1 time:406 trace:20ba14b9 url:App\Console\Commands\TrailPushMessage@handle href:N] 推送未锁定商机信息-未锁定: 结束执行

// php-fpm模式下
[2019-11-08 18:58:57] local.INFO: [app:partner-counselor src:127.0.0.1 time:36 trace:3633ccee url:GEThttp://127.0.0.1:50111/api/customers/348 href:N] 请求的数据为:{"token":"bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6NTAxMTFcL2FwaVwvYXV0aFwvbG9naW4iLCJpYXQiOjE1NzMyMDQwMzAsImV4cCI6MTU3MzgwODgzMCwibmJmIjoxNTczMjA0MDMwLCJqdGkiOiJZV2RsMU9PdFBOMTV3ZmNZIiwic3ViIjo2LCJwcnYiOiIyNTE5NzdjOTQ4NzExYTE4NDQyNGQ1ZDFmNjQ4Y2U0Mjg1NzQ5YmQwIn0.KTA7a8v5jw80O2WrXMHeVsJSeiv194hsTYHQEn_2KCo"}
```

注意事项：
1. 修改如下代码不同版本bind部分会有所不同，具体根据```\Illuminate\Foundation\Application::registerCoreContainerAliases```中```log```信息修改。
如laravel6.x中为```'log'                  => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],```，修改方式就如下方代码
```php
        ……
        // 注入全局容器
        $app->instance('Log', $logger);
        $app->bind('Psr\Log\LoggerInterface', function (Application $app) {
            return $app['log']->getMonolog();
        });
        $app->bind('\Illuminate\Log\LogManager', function (Application $app) {
            return $app['log'];
        });
        ……
```
2. 有关console中使用时，建议重写```\Illuminate\Console\Command::info``` ```\Illuminate\Console\Command::line``` ```\Illuminate\Console\Command::error```，然后所有```console```继承```BaseCommand```
demo代码块：
```php
use App\Console\BaseCommand;

class Demo extends BaseCommand
{
    protected $signature = 'command:demo';
    protected $description = 'demo';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        $this->info('this is info!');
        $this->line('this is line!');
        $this->error('this is error!!!');
    }
}
```

demo 命令行输出：
![命令行输出信息][image1]





[image1]:data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIwAAAAzCAYAAABFXOCeAAAEGWlDQ1BrQ0dDb2xvclNwYWNlR2VuZXJpY1JHQgAAOI2NVV1oHFUUPrtzZyMkzlNsNIV0qD8NJQ2TVjShtLp/3d02bpZJNtoi6GT27s6Yyc44M7v9oU9FUHwx6psUxL+3gCAo9Q/bPrQvlQol2tQgKD60+INQ6Ium65k7M5lpurHeZe58853vnnvuuWfvBei5qliWkRQBFpquLRcy4nOHj4g9K5CEh6AXBqFXUR0rXalMAjZPC3e1W99Dwntf2dXd/p+tt0YdFSBxH2Kz5qgLiI8B8KdVy3YBevqRHz/qWh72Yui3MUDEL3q44WPXw3M+fo1pZuQs4tOIBVVTaoiXEI/MxfhGDPsxsNZfoE1q66ro5aJim3XdoLFw72H+n23BaIXzbcOnz5mfPoTvYVz7KzUl5+FRxEuqkp9G/Ajia219thzg25abkRE/BpDc3pqvphHvRFys2weqvp+krbWKIX7nhDbzLOItiM8358pTwdirqpPFnMF2xLc1WvLyOwTAibpbmvHHcvttU57y5+XqNZrLe3lE/Pq8eUj2fXKfOe3pfOjzhJYtB/yll5SDFcSDiH+hRkH25+L+sdxKEAMZahrlSX8ukqMOWy/jXW2m6M9LDBc31B9LFuv6gVKg/0Szi3KAr1kGq1GMjU/aLbnq6/lRxc4XfJ98hTargX++DbMJBSiYMIe9Ck1YAxFkKEAG3xbYaKmDDgYyFK0UGYpfoWYXG+fAPPI6tJnNwb7ClP7IyF+D+bjOtCpkhz6CFrIa/I6sFtNl8auFXGMTP34sNwI/JhkgEtmDz14ySfaRcTIBInmKPE32kxyyE2Tv+thKbEVePDfW/byMM1Kmm0XdObS7oGD/MypMXFPXrCwOtoYjyyn7BV29/MZfsVzpLDdRtuIZnbpXzvlf+ev8MvYr/Gqk4H/kV/G3csdazLuyTMPsbFhzd1UabQbjFvDRmcWJxR3zcfHkVw9GfpbJmeev9F08WW8uDkaslwX6avlWGU6NRKz0g/SHtCy9J30o/ca9zX3Kfc19zn3BXQKRO8ud477hLnAfc1/G9mrzGlrfexZ5GLdn6ZZrrEohI2wVHhZywjbhUWEy8icMCGNCUdiBlq3r+xafL549HQ5jH+an+1y+LlYBifuxAvRN/lVVVOlwlCkdVm9NOL5BE4wkQ2SMlDZU97hX86EilU/lUmkQUztTE6mx1EEPh7OmdqBtAvv8HdWpbrJS6tJj3n0CWdM6busNzRV3S9KTYhqvNiqWmuroiKgYhshMjmhTh9ptWhsF7970j/SbMrsPE1suR5z7DMC+P/Hs+y7ijrQAlhyAgccjbhjPygfeBTjzhNqy28EdkUh8C+DU9+z2v/oyeH791OncxHOs5y2AtTc7nb/f73TWPkD/qwBnjX8BoJ98VQNcC+8AAAxkSURBVHgB7VwJkFTFGf76vd1lD5ZjWZYNhyyHIogoQgS0EMQAhkMREiolKYUSLI0ISHlwVICQxKMIJBVSGq0yJBYGxYKkagMkEpUAZTgi4fAIN0EMINcu7DF7zOt8/d68ua8dZgb2OX/t7PS87v67///9/fff3d97ACBjfURryNxhLJcfuax2A2TBZCGhRy4Tq53g/OuNp2gPmTdKyMKpQuZ/l7LGoTuzTBJ1Eneb8fatieU0diCACiYJ6N0DLkHrALRZqENrFXjd/1feQIHC6RpoXEmj64mn9i2g5Lc6CqdpyO4jkD9RxCVndl+gdJOO3OFxFb/uC2UF97BwqgajykDtMTWA4qfqDRI1W9yQF+OvE6vk9cQz/z4B6QK+ftwNVMfquS9fGp40qzmBvAYjSgG9iCJlA1ll/PSxxHN/5RMzqxNQ8ISGnL4CdbslKlYa0NoAbed5HFUjcGGurSGrnn4jUPgQ6/SnwpnfeFTi0pLAMr4WrJTWMb08tS5s7zkNl98w0PJ7lnyunQYqf2MNmqxuQIs7BNwXJLK6Wn1sPMnvKuqpF9CKnjW7O/PPSFS9Y8C1zSeRrLPSBo3NCaRTiCVKkJYPCrR+SofIBXJ6CeQN1ZA/QkP9pxKynoYyRkPedzS4TwOurQxnJjJ9ggZwHDAqJLQ8KvUuDVWrAz1T0VINejFQ+WsDDQfIqwZo+Fy1GJ3SyVPjYCl8hPKO9slXMEmjbJLeFij+hQ69C6fqYurlbksvrp2Uk9oreZUBCmVScuvtBVpO0VC3n/XOWvKJltTdgxpqNvLa19Flbg65Xg9T9TZHx9tulJbrHFmckv7mu/H6TZYorm30Ki/SO9C9FkyC6V3kFRrQPwBB5eXdHyqyli8gWrBsPsv9i/m1Pr6hpa0r14KnajlYPr1IwKDXODvJjbaLOVXXSFQu8/W/QMUxOcDFVwwY9Di11E/pezoKxmqo2Gd5UdvDqEHnBPLMJfGJcvlNy1hUaUPN48o/xaBLr7jhviTRZpGODqt1cHURV71obFPBU7XXVPl0BsKgIRhfenrLaaf+c05bnLq91GClnDIlhRiMijOUNwhL/qGHb6CFLWpfbDzIuGamgXM/cqN+n0SrZ9Qqw85N7DsVPM2eNFE+4wJr0cOIEo8cHEDZPRjLnPPJpaarypfpgZK4GPBxT38qxGAaGJTmDhNQ83rOHVZQF3e3lPPoRoPraNUQ7RgQzhJmYGhcosveYt0RwcA6bkoDz7j7ElSwZoc1alrP0JDVm3HQo5x+ufVgy6mKqxgm53bqhVsTTiBvDGMLc+UtA0VLdZS8xeFCfVSuMFBPI1LkXSKqH2qZ6DcipWGVaf+6jgYGyuZqiflZNwgUL6ddciTKWipzM/ntUwxiUzp5qt5El48FgpbGxgnLe7Sm18y9x9JX9ZrAVVJ2VxXbMYD+ykD1EUtHqq3mShy/5s5sYP9pRhpXNpJxigpAr5oY9Iq25KVcuGdOvy55JtopJV8h5eP0A/XxJ9qR8rjGeV7kgGnuFN5gmrtUmf6nTAMhMUzKWsowdoQGMgbjiNuYPiEyBpM+XTuipYzBOOI2pk+IjMGkT9eOaCkug1EYFwKoQABVRCLYCQRQXfW2v38DyeSp8CxKBvVpMci/FV86me35uDLFpbVTKMRgnAqgyumuTqR1tH6Gnznh72AqAFtOA1Apw1fbj94PT6tl3uhA+CFPq2Xp+7rkcYG3nH8dM90CUhRFyfdrI6RupLwU8MyfIGTJGj28HCloj7giU3e5Q5Osm0g6S/F179GA0wFUNNKIFA2wFRVcpXZuucPbimdIuYM1SO5iV681ULNJ2aNFNrzBKafVXoMpIASxgKAo8K9gAkFCoyyBL/2c8ASCiBQVvaSjbpdE9Xoi0wjlzNst4CLyrnqdYSLS1JlJMLWZTZwvDxsrlrmhFahDOLW5HJ3UcUSyeUZrMVp7gsCw7JsF2q0IlL1ut9tE1hUt4MEjT6ivEPqRczONh+dKdYepsyNWizYORsE7nUBeg3E6gCrazYoF2FJ1w4GrtA4SOQMEajcYqPtCouG/PGgcy8C6n/AeNNoexjacaP1oDnmhLiFKr5sKMFKsUgF2SgXPKGKbWeFkVyfRipRnVTBOdcpvVPAgkifzXnIYgMrrYWwBUwWg0nvSXT+smS674ZSb+F67xaZ/2wCqZPKM2Qs/KIcZvrJC4xmr1oUXKM/+8BwyAKrwerGuqm2Ybtc/gErFJWY/uXcUQkEyhOT7XXCfIkSGT1W0mU2QeA/y5KBo+QOBnG/7CmUAVLYumimASoG+BDcgFdBLjf6zE3yoqEiALSVyWHAVp5sL890oIkC8/WvW3k7DQYn6Q75VUgZAZRtMU76dDqCiLrgHZS6rEQw4ox1lAFRNMZZMWUdpoEmrJEdJnhEmIQ1kDCYhtX1zK3mX1ZvhTX5ztdGMJR8JPlCWBsp4mDQo2UlNZAzGSXczDbLEZzAdCiHHDeUOFV/tEIn68hVUMybzIKUpjzVGYua5ngqeMZpMe3YuQwGNu4XhKNG8cLySdC3EYOTUh4DbuF3rR7KsI8TsBUAHPo0WgeSgQRCTpwEdk/cKqlTwjND9a3JZDr0VKC+HHD88pP1E80IYJflCSKQrHp7BR1pXQuw73qSmxLpyYPt24Jh6xC85lAqeyelZkrh4Hi8O3Eb28E40L0ldi8TGZzDdiyHbt4MQfKC8axnkoF5mHXGChyUekmVlEFOnQ/a6BeLTPcAvV/IFbm0hZz1rlW3kNvvceXwc1rfdjoE3AuMegOx9Gw9e3BCnTgLPL7ZZhv/u3TH5PFVLJXwyftqjkLfzsKeB+/ob/wTxzkazD/LJH/IwiNuy5/nqhRGj+RahDsC7f4B4dxOi5t15E8SUxyC7lLEuTyPL10GUb43JUxUQdXVmOdSFgmUSzbMYpu6/12Dk2PEQYyZaLY18AOLeMWZarljKF6dcNtPi2UXAoc+ArX8Hxk6C3PsJxMe7gM0bIPoPBAYPp9I5y9kGw7lZPjWXD+LzbGDVa8QB5BKvyOnNaiXy/0vcX082TxUPLPwx0LU7sH41RE8OiMeeBo4eBnYfhujMk8g7GafV10H+9c/AMCLI2vB9bKSIeT1KIH6yHPIcDUXxVPVnzecz6VUQH+6JXM+W3OUxGFeowSDRPJt3ir69BiNWrgLU5/2NkG9ySuLIUqRuru1tsH83lf5TCBdH58jxlkLPXIFY/4F54i+UwQRTvoLZsZmcHIgtOwiQUbjGGJQKnj1KgT79gF3bIPb8G/L4MYgh90Le2g+CBmOTXL7UvNnoxCC+guAWPwrOk8PugcjKglj+IqCm8L9sBta8BzFqHECDsSm4nn0dtoexjcObwUSief48UpD2GkxcvH/3BqFnNBaSrOWrHZQhRCM1D//qJeDxWRBPc6qaUQu59vcQ62iMNRafaNXD5iXIU5Z1tTyb8gK39LfS9ARoQayDTQfoMe0bPY+GY19X3+HyFsyiIijjwVNWyQoX5AkaX2knX91w9Wy+rnozJcIZTKJ5Nu8UfYfe8UbeyIIIDyCpGMUmgziBOEj88wtg50xgAAEj358C8ciTkKdOQXzkG4FxsAkokghPcZrTBkm+vBDiA1/bAUZRb93AgMbsH2Hy5IVzZsyHsmLgP6epN3rRzmXWtB2lnp2FyhrIV5dBnGHcFEyJ5gXzSfJvBhyBJE8eA+4eAag9kNFDIIfw1UpNINm/J+t2sWp0oyKXPg95dx+iwCshP+GUpCjLwo5YP2L/TwrPQ19Cnj9LbzfHlEnexec/ZjIAvn9I7A5EKCF2euSZwUFw3wBg5gzuQ+VBbv8oQo2gy+1bQQwcDNnNoy//7ETz/HmkIB3qYf64CuK5JVwBvQ5Bd2vGM0foZhW5/bwKVzxw+51fePLE0hWQRw9CPDGHASS9VdtiiPk/I/Seo8/F+OXjDyG27bX4xfqfTJ5V9RCLXoCcvxiqj2oqkccp12cHrF74yxLcr0h5e49bHmL6bIh+XHkpnuVrITZti82TJWQ3TpNqijzJ+GcHPbEfJZrnxyIlSTOmVZwDDh/Vbm0nrhAuE5J2jrHK1VI++ZVyQ+9MZeKxS3AfroZnZ8pWR2NOhmyqX6ov7bhkv8IBwTgmbsqmp+VKC/+7SF17Vkx25SbmpevwMbzB2J3OfDcbDaTLYEJimGajoUxHr4kGMgZzTdTefBv1TknNV4RMz9OpgYyHSae2HdBWxmAccBPTKcL/AQhbw3T+JZ28AAAAAElFTkSuQmCC

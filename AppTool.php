<?php
namespace App\Providers;

class AppTool
{
    private static $requestId;

    /**
     * 获取8位 http请求唯一标识
     * @return mixed
     */
    public static function getRequestId()
    {
        if (empty(static::$requestId)){
            static::$requestId = sprintf("%08x", abs(crc32(
                rand(0, 999999) . static::getRequestIp() . static::getRequestTime() . static::getRequestPort()
            )));
        }
        return static::$requestId;
    }

    /**
     * 获取请求端口
     * @return int|mixed
     */
    public static function getRequestPort()
    {
        return $_SERVER['REMOTE_PORT'] ?? 80;
    }

    /**
     * 获取请求IP
     * @return string|null
     */
    public static function getRequestIp()
    {
        return request()->getClientIp();
    }

    /**
     * 获取请求开始时间
     * @return mixed
     */
    public static function getRequestTime()
    {
        return $_SERVER['REQUEST_TIME'] ?? static::getAppStartTime();
    }


    /**
     * 获取laravel启动时间， 秒
     * @return mixed
     */
    public static function getAppStartTime()
    {
        return LARAVEL_START ?? microtime(true);
    }

    /**
     * 获取请求方式 host和路径
     * @return string
     */
    public static function getRequestRoute()
    {
        return request()->getMethod() . request()->getUri();
    }

    /**
     * 获取当前时间与laravel启动时间的间隔， 秒
     * @return mixed
     */
    public static function getCurrentTimesSinceAppStart()
    {
        return microtime(true) - static::getAppStartTime();
    }
}

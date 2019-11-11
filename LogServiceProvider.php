<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services
     *
     * @return  void
     */
    public function boot(){

    }

    public static $folderPath = "/opt/logs/";
    public static $cfolderPath = "/opt/clogs/";

    /**
     * 获取日志路径
     * @param $folderPath
     * @return string
     */
    public static function getLogPath($folderPath)
    {
        return $folderPath . self::getFileName() . ".log";
    }

    /**
     * 获取错误日志路径
     * @param $folderPath
     * @return string
     */
    public static function getErrorLogPath($folderPath)
    {
        return $folderPath . self::getFileName() . ".error";
    }

    /**
     * 获取文件名称
     * @return string
     */
    public static function getFileName(){
        return config('app.name') . "-" . date('Ymd');
    }
    public function register()
    {
        $app = $this->app;
        // 设置http请求日志
        $log = new Logger($app->environment());
        // 设置日志文件路径
        $path = static::getLogPath(static::$folderPath);
        $log->useFiles($path);
        $errorPath = static::getErrorLogPath(static::$folderPath);
        // 设置错误日志文件路径
        $log->useFiles($errorPath, 'error');
        $logger = new \Illuminate\Log\Logger($log, $app['events']);
        // 注入全局容器
        $app->instance('Log', $logger);

        // 设置console请求日志
        $clog = new Logger($app->environment());
        // 设置日志文件路径
        $path = static::getLogPath(static::$cfolderPath);
        $clog->useFiles($path);
        // 设置错误日志文件路径
        $errorPath = static::getErrorLogPath(static::$cfolderPath);
        $clog->useFiles($errorPath, 'error');
        $clogger = new \Illuminate\Log\Logger($clog, $app['events']);
        // 注入全局容器
        $app->instance('cLog', $clogger);
    }
}

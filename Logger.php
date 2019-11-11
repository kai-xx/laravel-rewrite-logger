<?php


namespace App\Providers;



use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
class Logger extends \Monolog\Logger
{
    protected static $uri_info = null;
    protected static $startTime = null;
    protected static $appName = null;
    protected static $parentRequestId = null;
    // 设置日志日期格式
    private static $dateFormat = "Y-m-d H:i:s";
    private function levels(){
        return [
            'debug' => self::DEBUG,
            'info' => self::INFO,
            'notice' => self::NOTICE,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'critical' => self::CRITICAL,
            'alert' => self::ALERT,
            'emergency' => self::EMERGENCY
        ];
    }
    public static function getExecutionTime(){
        if (is_null(static::$startTime)) static::$startTime = microtime(true);
        $diff = microtime(true) - static::$startTime;
        $sec = intval($diff);
        $micro = $diff - $sec;
        return round($micro * 1000, 4);
    }

    /**
     * 获取上一级请求trace
     * @return string|null
     */
    public static function getParentRequestId()
    {
        return static::$parentRequestId ? static::$parentRequestId : '';
    }

    public static function setParentRequestId($Id)
    {
        static::$parentRequestId = $Id;
    }

    /**
     * 获取每次请求唯一trace
     * @return mixed|string
     */
    public static function getTrace()
    {
        return empty(static::getParentRequestId()) ?
            AppTool::getRequestId() : static::getParentRequestId() . "-" . AppTool::getRequestId();
    }

    /**
     * 获取请求IP
     * @return string|null
     */
    public function getRequestSource()
    {
        return AppTool::getRequestIp();
    }

    /**
     * 获取APP_NAME
     * @return |null
     */
    public static function getAppName(){
        if (empty(static::$appName)){
            static::$appName = config('app.name');
        }
        return static::$appName;
    }

    /**
     * 重写日志格式
     * @param int $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addRecord(int $level, string $message, array $context = []): bool
    {
        $appName = static::getAppName();
        $prepend = sprintf("[app:%s src:%s time:%s trace:%s url:%s href:%s]",
        $appName,
        static::getRequestSource(),
        round(AppTool::getCurrentTimesSinceAppStart() * 1000),
        self::getTrace(),
        static::$uri_info = $this->getUri($level),
        "N");
        return parent::addRecord($level, $prepend . " " .$message, $context);
    }

    /**
     * 重写error等级日志记录操作
     * 增加消息推送
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->addRecord(static::ERROR, (string) $message, $context);
        // 生产环境推送错误信息
        if (config('app.env') == 'product') {
            $this->pushErrorMessage($message);
        }

    }

    /**
     * 获取请求URI
     * @param $level
     * @return string
     */
    public function getUri($level)
    {
        if (app()->runningInConsole()) {
            // 运行在命令行下
            $backtrace = debug_backtrace();
            array_shift($backtrace);
            if (isset($backtrace[4])){
                $class = $backtrace[4]['class'] . "@" . $backtrace[4]['function'];
            }
            return $class ?? '';
        }
        return $level >= static::INFO ? AppTool::getRequestRoute() : "N";
    }

    /**
     * 设置日志时间格式存储路径
     * @param $path
     * @param string $level
     * @throws \Exception
     */
    public function useFiles($path, $level = 'debug')
    {
        parent::pushHandler($handler = new StreamHandler($path, $this->levels()[$level] ?? static::DEBUG ));
        $handler->setFormatter(new LineFormatter(null, static::$dateFormat, true, true));
    }

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
        $url = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=xxxxxxxxxxxxxxxx";
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
}

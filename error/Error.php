<?php

namespace error;

class Error
{   
    private static $handle;
    
    /**
     * 注册异常处理
     * @access public
     * @return void
     */
    public static function register()
    {
        //error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    /**
     * 异常处理
     * @access public
     * @param  \Exception|\Throwable $e 异常
     * @return void
     */
    public static function appException($e)
    {   
        if (!$e instanceof \Exception) {
            $e = new ThrowableError($e);
        }
        $handler = self::getExceptionHandler();
        $handler->report($e);
    }

    /**
     * 错误处理
     * @access public
     * @param  integer $errno      错误编号
     * @param  integer $errstr     详细错误信息
     * @param  string  $errfile    出错的文件
     * @param  integer $errline    出错行号
     * @return void
     * @throws ErrorException
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {   

        // 符合异常处理的则将错误信息托管至 ErrorException
        if (error_reporting() & $errno) {
            $exception = new \Exception($errno, $errstr, $errfile, $errline);

            throw $exception;

            self::getExceptionHandler()->report($exception);

        }

    }

    /**
     * 异常中止处理
     * @access public
     * @return void
     */
    public static function appShutdown()
    {   
        // 将错误信息托管至 ErrorException
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            self::appException(new \Exception(
                $error['type'], $error['message'], $error['file'], $error['line']
            ));
        }
    }

    /**
     * 确定错误类型是否致命
     * @access protected
     * @param  int $type 错误类型
     * @return bool
     */
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }
    
    /**
     * 设置异常处理的实例
     * @access public
     * @return Handle
     */
    public static function setExceptionHandler($class)
    {   
        if ($class && is_string($class) && class_exists($class) &&
            is_subclass_of($class, "error\Handle")
        ) {
            self::$handle = new $class;
        }else{
            // 异常处理 handle
            self::$handle = new Handle;
        }
    }

    /**
     * 获取异常处理的实例
     * @access public
     * @return Handle
     */
    public static function getExceptionHandler()
    {
        
        if (!self::$handle) {
            // 异常处理 handle
            self::$handle = new Handle;
        }

        return self::$handle;
    }
}

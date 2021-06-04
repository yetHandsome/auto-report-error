#自动捕获异常、错误使用
=================

在需要的地方引入  

```
include  __DIR__ . '/autoload.php';  

error\Error::register();   
```


如果要自定义错误输出 就 写一个类 继承 Handle 并实现 send() 方法即可，

如果对报错数据需要子定义也可以在继承的类重写 report() 方法。  

默认错误为输出到终端，如果需要直接发邮件请添加如下代码  

error\Error::setExceptionHandler('error\myHtmlEmailHandle');  

并且将 myHtmlEmailHandle.php 中下面变量改成自己的邮件配置 请参考 https://www.php.cn/jishu/php/414138.html 获取自己的 send_auth;

```
$smtp_host = 'smtp.qq.com';  

$send_to = "XXX@XX.com";   

$send_auth = 'XXXX';    

$who_send = "XXX@XX.com";  

```


如果希望统计，某个错误出现错误次数，可以利用redis 来计算  

例如: 

```

$thisErrorCount = $redis->hincrby('phpError'.date('Y-m-d'),md5($data['file'].$data['line'].$data['message']));  

$redis->expipe('phpError'.date('Y-m-d'),24*3600);  


echo $data['file'].$data['line'].$data['message']. '今天出现' . $thisErrorCount .'次';  

```



原理介绍  


```
error\Error::register();  里面调用了3个方法，理解了这3个函数就能理解错误是如何处理的了  

set_error_handler();

set_exception_handler();

register_shutdown_function();
```



1.set_error_handler 这个是设置自己的异常处理函数，这个不是重点，重点是后面2个函数

但是这个函数不能捕获 E_ERROR、E_PARSE、E_CORE_ERROR、E_CORE_WARNING、E_COMPILE_ERROR、E_COMPILE_WARNING，

以及在调用 set_error_handler() 的文件中引发的大部分 E_STRICT。

引发 这个注册函数错误例子

```
<?php  
function myErrorHandler($errno, $errstr, $errfile, $errline) {  
     echo "<b>Custom error:</b> [$errno] $errstr<br>";  
     echo " Error on line $errline in $errfile<br>";  
 }  
  
// 设置用户定义的错误处理函数  
set_error_handler("myErrorHandler");  

trigger_error("A custom error has been triggered");  
```



//set_error_handler 捕获自己通过 trigger_error 抛出的错误好像略显无聊  

2.set_exception_handler 设置用户自定义的异常处理函数

如果用户程序抛出的异常没有捕获，会被这个函数设置的函数捕获

在这个异常处理程序被调用后，脚本会停止执行。

例子：

```
function myException($exception)
{

echo "<b>Exception:</b> " , $exception->getMessage();

}

set_exception_handler('myException');

throw new Exception('Uncaught Exception occurred');

```



上面代码的输出如下所示：

Exception: Uncaught Exception occurred



3.register_shutdown_function 注册一个会在php中止时执行的函数

这个函数是在 php 脚本退出时执行，一般配合 error_get_last() 判断程序退出时是否有报错。

还可以再判断错误是否是自己，设定的错误级别，不是自己设定的错误级别 例如 notice 之类的，可以忽略不处理，避免一直报一些可以忽略的错

可以多次调用 register_shutdown_function() ，这些被注册的回调会按照他们注册时的顺序被依次调用。 

如果你在注册的方法内部调用 exit()， 那么所有处理会被中止，并且其他注册的中止回调也不会再被调用。


你仔细阅读我代码 register_shutdown_function 注册函数里面写的内容，
会发现它又将错误通过异常抛出，这个时候 set_exception_handler 注册的异常处理函数来接管错误处理

```
register_shutdown_function([__CLASS__, 'appShutdown']);

 public static function appShutdown()
    {   
        // 将错误信息托管至 ErrorException
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            self::appException(new \Exception(
                $error['type'], $error['message'], $error['file'], $error['line']
            ));
        }
    }
```




4.error_reporting() - 设置应该报告何种 PHP 错误

这个之前是写在 error\Error::register(); 里面，后来一想，这个应该让用户自己设定


5.正确使用流程

5.1 在代码开头先设定需要报错的级别 

例如 error_reporting(E_ERROR | E_PARSE );


5.2 紧接着引入需要的错误处理函数

```
include  __DIR__ . '/cli/class/autoload.php';

error\Error::register(); //这个只在终端输出错误

error\Error::setExceptionHandler('error\myHtmlEmailHandle'); //这个会把错误发邮件

//需要把 error\myHtmlEmailHandle.php 里面的相关变量改成自己的配置

$smtp_host = 'smtp.qq.com';

$send_to = "XXX@XX.com";

$send_auth = 'XXXX';

$who_send = "XXX@XX.com";

//其实可以改一下代码，写一个初始化相关变量，掉用的时候直接使用，这样就能灵活发各种邮箱的邮件，有想法的自己改吧，改动也不大

//不过觉得，有这个功夫还不如直接写一个新的类，需要发哪个邮箱就用哪个类
```





5.3 再写自己的业务代码

建议最好是业务代码通过引入的方式执行，

因为这样 register_shutdown_function 才能捕获你写的代码包含 E_PARSE 错误类使用

你的入口文件如下

假设名称为 runSomeJob.php

```
<?php

error_reporting(E_ERROR | E_PARSE );

include  __DIR__ . '/cli/class/autoload.php';

error\Error::register(); 

//error\Error::setExceptionHandler('error\myHtmlEmailHandle');


include  __DIR__ . '/cli/class/someService.php';

$someService = new someService([]);

$someService->test();

====== runSomeJob.php 结束

someService.php 内容如下:

```



```
<?php

/**
 * 定时脚本
 * Class someService
 */
class someService
{
	public function test()
    {
        //throw new Exception("单纯测试 throw new Exception 能否发邮件");
        $a++-;
    }
}

```


====== someService.php 结束


主要调用的 $someService->test(); 里面 $a++-; 属于 E_PARSE 错误，这个错误由于跟 register_shutdown_function 不再同一个文件 是可以被捕获的。

如果把 runSomeJob.php 改成如下就不能被捕获了 E_PARSE 错误

```
<?php
error_reporting(E_ERROR | E_PARSE );

include  __DIR__ . '/cli/class/autoload.php';

error\Error::register(); 

//error\Error::setExceptionHandler('error\myHtmlEmailHandle');

$a++-;
```


更多资料请阅读

https://www.cnblogs.com/init-007/p/11242813.html 貌似来源 https://www.cnblogs.com/zyf-zhaoyafei/p/6928149.html

https://www.w3school.com.cn/php/php_ref_error.asp

https://www.laruence.com/2010/08/03/1697.html


文中如有错误的地方欢迎指正







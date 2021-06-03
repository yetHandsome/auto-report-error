错误类使用

在需要的地方引入

include  __DIR__ . '/cli/class/autoload.php';

error\Error::register(); 

如果要自定义错误输出 就 写一个类 继承 Handle 并实现 send() 方法即可，

如果对报错数据需要子定义也可以在继承的类重写 report() 方法。

默认错误为输出到终端，如果需要直接发邮件请添加如下代码

error\Error::setExceptionHandler('error\myHtmlEmailHandle');

并且将 myHtmlEmailHandle.php 中下面变量改成自己的邮件配置 请参考 https://www.php.cn/jishu/php/414138.html 获取自己的 send_auth;

$smtp_host = 'smtp.qq.com';

$send_to = "XXX@XX.com";

$send_auth = 'XXXX';

$who_send = "XXX@XX.com";

如果希望统计，某个错误出现错误次数，可以利用redis 来计算

例如: 

$thisErrorCount = $redis->hincrby('phpError'.date('Y-m-d'),md5($data['file'].$data['line'].$data['message']));

$redis->expipe('phpError'.date('Y-m-d'),24*3600);


echo $data['file'].$data['line'].$data['message']. '今天出现' . $thisErrorCount .'次';

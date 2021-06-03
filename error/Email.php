<?php

namespace error;

class Email
{   
    private static $debug = false;
    
    //$contentType 常用有 纯文本 "text/plain" 、 html "text/html"
    public static function sendmail($send_to,$send_auth,$subject,$content,$contentType="text/plain",$smtp_host= 'smtp.qq.com',$who_send=''){

        $who_send = empty($who_send) ? $send_to : $who_send;
        
        $streamContext = stream_context_create();
        $stream = stream_socket_client("tcp://$smtp_host:25", $errno, $errstr, $timeout = 10, STREAM_CLIENT_CONNECT, $streamContext);
        stream_set_blocking($stream, 1);

        
        if(self::$debug){
            // 220 163.com Anti-spam GT for Coremail System (163com[20141201])
            // 220为响应数字，其后的为欢迎信息
            echo "connet: ".fgets($stream);
        }
        
        fwrite($stream, sprintf("HELO %s\r\n", $smtp_host));
        
        if(self::$debug){
            // 250 OK
            echo "send HELO: ".fgets($stream);
        }
        
        fwrite($stream, "AUTH LOGIN\r\n");
        
        if(self::$debug){
            echo "send AUTH LOGIN: ".fgets($stream);
        }
        
        // 发送经过BASE64编码了的用户名
        fwrite($stream, sprintf("%s\r\n", base64_encode($send_to)));
        
        if(self::$debug){
            echo "send user: ".fgets($stream);
        }
        
        // 发送的经过BASE64编码了的密码
        fwrite($stream, sprintf("%s\r\n", base64_encode($send_auth))); //bHl6cmVxdGtqemxhYmJiYQ==
        
        if(self::$debug){
            echo "send auth pwd: ".fgets($stream);
        }
        // 发送者邮箱
        fwrite($stream, sprintf("MAIL FROM: <%s>\r\n", $who_send));
        
        if(self::$debug){
            echo "send MAIL FROM: ".fgets($stream);
        }
        
        // 接收者邮箱 --- 由于QQ等邮箱会把这个作为垃圾邮件，这里仍然选择163邮箱作为收件人
        fwrite($stream, sprintf("RCPT TO: <%s>\r\n",$send_to));
        
        if(self::$debug){
            echo "send RCPT TO: ".fgets($stream);
        }

        // 将之后的数据作为数据发送
        fwrite($stream, sprintf("%s\r\n", 'DATA'));
        if(self::$debug){
            echo 'send DATA:'.fgets($stream);
        }
        
        // 邮件内容, php <<< 这种写发要兼容低版本PHP的话，必须结束EOF结束要写开头

$header = <<<EOF
Subject: $subject
From:""<$who_send>
To:""<$send_to>
Content-Type: $contentType;
EOF;

        $header = $header."\r\n\r\n";

        $end = "\r\n.\r\n";
        
        $content = str_replace("\n.", "\n..", $content);
        $send_content = $header.$content.$end;
        
        if(self::$debug){
            var_dump($send_content);
            echo PHP_EOL;
        }
        
        fwrite($stream, $send_content);
        
        if(self::$debug){
            echo 'send content:'.fgets($stream);
        }
        
        fwrite($stream, sprintf("%s\r\n", 'QUIT'));
        
        if(self::$debug){
            echo 'send QUIT:'.fgets($stream);
        }

    }
}


//$smtp_host = 'smtp.qq.com';
//$send_to = "young.yang@71360.com";
//$send_auth = 'xCqCVibaeihiHMT7';
//$who_send = "young.yang@71360.com";
//$subject = 'php send email';
//$a = file_get_contents(__DIR__ . '/cli/class/error/Email.php');
//$content = $a;
//$contentType="text/html"
//$contentType="text/plain";
//
//error\Email::sendmail($send_to,$send_auth,$subject,$content,$contentType,$smtp_host ,$who_send);
//邮件发送原理详情请查看 https://blog.csdn.net/kerry0071/article/details/28604267
//例子 https://zhuanlan.zhihu.com/p/36364566
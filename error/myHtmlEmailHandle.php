<?php

namespace error;

use error\Email;

class youngHtmlEmailHandle extends Handle
{
    public function send($data)
    {
        
        //保留一层
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        $data['echo'] = ob_get_clean();

        ob_start();
        extract($data);
        include __DIR__.'/exception.tpl';
        // 获取并清空缓存
        $content  = ob_get_clean();
        
        
        $subject = 'PHP auto report php error';
        
        //smtp.exmail.qq.com(使用SSL，端口号465)
        //qq 的为 smtp.qq.com ，其它类型请自己查询
        $smtp_host = 'smtp.qq.com';
        $send_to = "XXX@XX.com";
        $send_auth = 'XXXX';
        $who_send = "XXX@XX.com";
        
        $contentType="text/html";
        
        Email::sendmail($send_to,$send_auth,$subject,$content,$contentType,$smtp_host ,$who_send);
    }

}

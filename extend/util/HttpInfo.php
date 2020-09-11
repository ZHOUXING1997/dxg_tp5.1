<?php

namespace util;

class HttpInfo {

    public static function isMobile() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }

        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT']) {
            return true;
        }

        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA']))
        //找不到为flase,否则为true
        {
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        }

        //判断手机发送的客户端标志,兼容性有待提高
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile',
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    // 获取referer
    public static function getReferer() {
        $strReferer = strval($_SERVER['HTTP_REFERER']);
        return $strReferer;
    }

    // 获取入口服务器的请求类型是http还是https
    public static function getServerScheme() {
        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
                || (isset($_SERVER['HTTP_X_FORWARDED_Scheme']) && $_SERVER['HTTP_X_FORWARDED_Scheme'] == 'https')
            ) ? 'https' : 'http';
        return $httpType;
    }

    public static function getServerHost() {
        // echo "SERVER_NAME:";
        // echo $_SERVER['SERVER_NAME'];
        // echo "REQUEST_URI: ";
        // echo $_SERVER['REQUEST_URI'];//获取当前域名的后缀
        // echo "HTTP_HOST:";
        // echo $_SERVER['HTTP_HOST'];//获取当前域名, 带端口号
        return $_SERVER['HTTP_HOST'];
    }

    public static function getIP() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ipStr = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipStr = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipStr = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipStr = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipStr = getenv('HTTP_FORWARDED');
        } else {
            $ipStr = $_SERVER['REMOTE_ADDR'];
        }
        // 有时部署环境，有负载均衡等转发后，返回的是多个ip
        $arr = explode(',', $ipStr);
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = trim($arr[$i]);
        }
        // 只取第一个
        $ip = $arr[0];
        return $ip;
    }
}

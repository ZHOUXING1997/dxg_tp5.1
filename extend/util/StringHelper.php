<?php

namespace util;

class StringHelper {
  
    /**
     * 查看手机号是否合法
     */
    public static function isValidMobile($mobile) {
        return preg_match('/^1[345789][0-9]{9}$/', $mobile) > 0;
    }

    /* 验证手机号 或者 座机号*/
    public static function isValidTel($mobile) {
        return (preg_match('/(^(\d{3,4}-)?\d{7,8})$|(^1[34578][0-9]{9}$)/', $mobile) > 0);
    }

    /* 校验邮政编码 */
    public static function isValidPostcode($code) {
        //去掉多余的分隔符
        $code = preg_replace("/[\. -]/", "", $code);
        //包含一个6位的邮政编码
        return preg_match("/^\d{6}$/", $code);
    }

    /**
     * 查看邮箱是否合法
     */
    public static function isValidEmail($email) {
        return preg_match('/^([\w\_])+([\w\_\-]+)@([\w\-\_]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i', $email);
    }

    private static function isLocalIp($ip) {
        $toks = explode('.', $ip);
        if ($toks[0] == '10' || $toks[0] == '192') {
            return true;
        }
        return false;
    }

    // 校验快递单号
    public static function isValidExpress($deliveryId) {
        return preg_match('/^[A-Za-z0-9]{8,30}+$/', $deliveryId);
    }

    // 借鉴自CodeIgniter-3.0.6
    public static function randomString($len = 8, $type = 'alnum') {
        switch ($type) {
        case 'basic':
            return mt_rand();
        case 'alnum':
        case 'numeric':
        case 'nozero':
        case 'alpha':
            switch ($type) {
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            }
            return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
        case 'md5':
            return md5(uniqid(mt_rand()));
        case 'sha1':
            return sha1(uniqid(mt_rand(), TRUE));
        }
    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * [url=home.php?mod=space&uid=67594]@Return[/url] string
     */
    public static function toUrlQueryString( $params )
    {
        $string = '';
        if( !empty($params) ){
            $array = array();
            foreach( $params as $key => $value ){
                $array[] = $key.'='.$value;
            }
            $string = implode("&",$array);
        }
        return $string;
    }

    /**
     * 输出xml字符（数组转换成xml）
     * @param $params 参数名称
     * return string 返回组装的xml
     **/
    public static function array_to_xml( $params )
    {
        if(!is_array($params)|| count($params) <= 0)
        {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * return array
     */
    public static function xml_to_array($xml)
    {
        if(!$xml){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }
}

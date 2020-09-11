<?php
namespace util;
use think\facade\Log;

/**
 *
 * 实用工具类
 *
 */
class Pay {
    const DEBUG_PAY_PRICE = 1; //测试支付金额
    const DEBUG_PAY_REALM_NAME = array(
        'localhost',
        '127.0.0.1',
    );
    const DEBUG_PAY_WHITE_LIST = array(
        '1281696192',
    );


    /*
     *从数据库取钱转换
     */
    public static function drawMoneyChange($money) {
        if ($money != 0) {
            $money = number_format(1.0 * $money / 100, 2, ".", '');
        }
        return $money;
    }
    /*
     *从数据库存钱转换
     */
    public static function saveMoneyChange($money) {
        if ($money != 0) {
            $money = floatval($money * 100);
        }
        return $money;
    }

    // 价格单位为分 ,是否使用1分钱支付的开关和白名单
    public static function getPayMoney($status, $price, $uid) {
        if (empty($price)) {
            return $price;
        }

        if (empty($status)) {
            Log::record('release getPayMoney white user list: ' . json_encode(self::DEBUG_PAY_WHITE_LIST), Log::init()::NOTICE);
            if (in_array($uid, self::DEBUG_PAY_WHITE_LIST)) {
                return self::DEBUG_PAY_PRICE;
            } else {
                return $price;
            }

        }

        $host = $_SERVER['HTTP_HOST'];
        $prefixes = self::DEBUG_PAY_REALM_NAME;
        foreach ($prefixes as $prefix) {
            if (strpos($host, $prefix) !== false) {
                Log::record('release getPayMoney white: uid[$uid] price[$price] DEBUG_PAY_REALM_NAME ' . json_encode(self::DEBUG_PAY_REALM_NAME), Log::init()::NOTICE);
                return self::DEBUG_PAY_PRICE;
            }

        }

        return $price;
    }

    public static function getCallbackSign($params) {
        ksort($params);
        $toSignStr = self::paramsImplode($params);
        return md5($toSignStr . '&key=' . C('SP_KEY'));
    }

    private static function paramsImplode(array $params) {
        $str = '';
        foreach ($params as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            $str .= '&' . $k . '=' . $v;
        }

        return substr($str, 1);
    }

    static function generateOrderid($uid) {
        $time = date("Ymd");
        $sign = self::sign64($uid . "#" . "#" . microtime());
        $subs = substr($sign, 0, 7);
        $r = rand(1, 1000);
        $ret = sprintf("%s%07d%03d", $time, $subs, $r);
        return $ret;
    }

    static function sign64($value) {
        $str = md5($value, true);
        $high1 = unpack("@0/L", $str);
        $high2 = unpack("@4/L", $str);
        $high3 = unpack("@8/L", $str);
        $high4 = unpack("@12/L", $str);
        if (!isset($high1[1]) || !isset($high2[1]) || !isset($high3[1]) || !isset($high4[1])) {
            return false;
        }
        $sign1 = $high1[1] + $high3[1];
        $sign2 = $high2[1] + $high4[1];
        $sign = ($sign1 & 0xFFFFFFFF) | ($sign2 << 32);
        return sprintf("%u", $sign);
    }

    /**
     * 生成签名, $KEY就是支付key
     * [url=home.php?mod=space&uid=67594]@Return[/url] 签名
     */
    static function MakeSign($params, $KEY) {
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = self::ToUrlParams($params); //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    static function ToUrlParams($params) {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }

        return $string;
    }

    /**
     * 调用接口， $data是数组参数
     * @return 签名
     */
    static function http_request($url, $data = null, $headers = array()) {
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //获取xml里面数据，转换成array
    static function xml2array($xml) {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $data = "";
        foreach ($index as $key => $value) {
            if ($key == 'xml' || $key == 'XML') {
                continue;
            }

            $tag = $vals[$value[0]]['tag'];
            $value = $vals[$value[0]]['value'];
            $data[$tag] = $value;
        }
        return $data;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * return array
     */
    static function xml_to_array($xml) {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }
}

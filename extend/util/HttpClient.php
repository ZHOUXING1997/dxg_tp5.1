<?php
namespace util;
class HttpClient {

    public static function get($url, $headers = array(), $timeout = 30) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        if (stripos($url, "https://") !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }

        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($curl);
        $status = curl_getinfo($curl);
        curl_close($curl);

        return $content;

    }

    public static function post($url, $data, $headers = array(), $timeout = 30) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        if (stripos($url, "https://") !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }

        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $dataString = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
        $content = curl_exec($curl);
        $status = curl_getinfo($curl);
        curl_close($curl);

        return $content;

    }

    public static function http_post_data($url, $data_string, $timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $return_content;

    }

    public static function combineUrl($url, $array) {
        $regex = "/\?/";
        $num_matches = preg_match($regex, $url);
        $string = self::splitArray($array);
        if ($num_matches > 0) {
            $url = $url . '&' . $string;
        } else {
            $url = $url . '?' . $string;
        }

        return $url;
    }

    private static function splitArray($array) {
        $return = array();
        foreach ($array as $key => $value) {
            $return[] = $key . '=' . $value;
        }

        $string = implode('&', $return);
        return $string;
    }

    public static function checkmobile() {
        global $_G;
        $mobile = array();
        static $touchbrowser_list = array(
            'iphone',
            'ipad',
            'android',
            'phone',
            'mobile',
            'wap',
            'netfront',
            'java',
            'opera mobi',
            'opera mini',
            'ucweb',
            'windows ce',
            'symbian',
            'series',
            'webos',
            'sony',
            'blackberry',
            'dopod',
            'nokia',
            'samsung',
            'palmsource',
            'xda',
            'pieplus',
            'meizu',
            'midp',
            'cldc',
            'motorola',
            'foma',
            'docomo',
            'up.browser',
            'up.link',
            'blazer',
            'helio',
            'hosin',
            'huawei',
            'novarra',
            'coolpad',
            'webos',
            'techfaith',
            'palmsource',
            'alcatel',
            'amoi',
            'ktouch',
            'nexian',
            'ericsson',
            'philips',
            'sagem',
            'wellcom',
            'bunjalloo',
            'maui',
            'smartphone',
            'iemobile',
            'spice',
            'bird',
            'zte-',
            'longcos',
            'pantech',
            'gionee',
            'portalmmm',
            'jig browser',
            'hiptop',
            'benq',
            'haier',
            '^lct',
            '320x320',
            '240x320',
            '176x220',
        );

        static $mobilebrowser_list = array(
            'windows phone',
        );

        static $wmlbrowser_list = array(
            'cect',
            'compal',
            'ctl',
            'lg',
            'nec',
            'tcl',
            'alcatel',
            'ericsson',
            'bird',
            'daxian',
            'dbtel',
            'eastcom',
            'pantech',
            'dopod',
            'philips',
            'haier',
            'konka',
            'kejian',
            'lenovo',
            'benq',
            'mot',
            'soutec',
            'nokia',
            'sagem',
            'sgh',
            'sed',
            'capitel',
            'panasonic',
            'sonyericsson',
            'sharp',
            'amoi',
            'panda',
            'zte',
        );
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (($v = self::dstrpos($useragent, $mobilebrowser_list, true))) {
            $_G['mobile'] = $v;
            return '1';
        }

        if (($v = self::dstrpos($useragent, $touchbrowser_list, true))) {
            $_G['mobile'] = $v;
            return '2';
        }

        if (($v = self::dstrpos($useragent, $wmlbrowser_list))) {
            $_G['mobile'] = $v;
            return '3';
        }

        $_G['mobile'] = 'unknown';
        if (isset($_GET['mobile']) && isset($_G['mobiletpl'][$_GET['mobile']])) {
            return true;
        } else {
            return false;
        }

    }

    static private function dstrpos($string, $arr, $returnvalue = false) {
        if (empty($string)) {
            return false;
        }

        foreach ((array) $arr as $v) {
            if (strpos($string, $v) !== false) {
                $return = $returnvalue ? $v : true;
                return $return;
            }
        }

        return false;
    }

}

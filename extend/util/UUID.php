<?php
namespace util;
use think\facade\Env;

class UUID {
    const NAME_SPACE = 'dongxinge';
        
    // 根据时间生成uuid版本
    public static function generate($with_seperator = true) {
        $namespace = Env::get('uuid.namespace', 'namespace');
        $worker_name = Env::get('uuid.worker_name', 'instance1');

        $microTime = microtime();
        list($a_dec, $a_sec) = explode(" ", $microTime);
        $dec_hex = dechex(ltrim($a_dec, "0."));
        $sec_hex = dechex($a_sec);
        self::ensure_length($dec_hex, 8);
        self::ensure_length($sec_hex, 8);

        $node = sha1($namespace . "::" . $worker_name);
        $uuidArray = array();
        $uuidArray[] = $sec_hex;
        $uuidArray[] = substr($dec_hex, 0, 4);
        $uuidArray[] = substr($dec_hex, 4, 4);
        $uuidArray[] = sprintf("%04x", mt_rand(0, 0xffff));
        $uuidArray[] = substr($node, 0, 8) . sprintf("%04x", mt_rand(0, 0xffff));
        if ($with_seperator) {
            $guid = implode('-', $uuidArray);
        } else {
            $guid = implode('', $uuidArray);
        }
        return $guid;
    }

    private static function ensure_length(&$string, $length) {
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, "0", STR_PAD_LEFT);
        } else if ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
    }
}

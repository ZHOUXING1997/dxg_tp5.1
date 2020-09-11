<?php
/**
 * User: 周星
 * Date: 2019/4/4
 * Time: 15:19
 * Email: zhouxing@benbang.com
 */

namespace util;


class CipherSalt {

    // 生成盐
    static public function getRandChar($length = 16) {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    // 密码加密
    static public function encryptPassword($salt, $password) {
        if (empty($salt)) {
            throw new  \Exception('盐不能为空');
        }
        $max = strlen($salt) - 1;
        $password = md5(substr($salt, 0, $max / 2 - 1) . $password . substr($salt, $max / 2, $max));
        return $password;
    }
}
<?php

/**
 * 生成token的工具类
 */

namespace util;

class AuthToken {
    //token规则版本号
    const TOKEN_VERSION1 = 1; //版本号:1

    //信息摘要算法
    const ALG_SHA1 = 1; //sha1
    const ALG_MD5 = 2; //MD5
    const ALG_RSA = 3; //RSA

    //生成签名区域,所需参数
    private $tokenType;
    private $version;
    private $alg;
    private $subject;
    private $ak;
    private $sk;
    private $customPayloads;
    private $expireTime;

    public function __construct($subject = null, $ak = null, $sk = null, $alg = 1) {
        $this->version = self::TOKEN_VERSION1; //初始化版本
        $this->alg = empty($alg) ? self::ALG_SHA1 : $alg; //算法
        $this->subject = empty($subject) ? $this->subject : $subject; //项目id
        $this->ak = empty($ak) ? $this->ak : $ak; //公钥
        $this->sk = empty($sk) ? $this->sk : $sk; //密钥
    }

    // 计算签名
    public function generateSign($array) {
        // 按字典序进行排序
        ksort($array);
        // 组装签名字符串
        $joinedString = '';
        foreach ($array as $key => $value) {
            $joinedString = $joinedString . $key . '=' . $value . '&';
        }
        $joinedString = rtrim($joinedString, '&');
        // 加密
        $sign = "";
        if ($this->alg == self::ALG_SHA1) {
            $sign = sha1($joinedString);
        } else if ($this->alg == self::ALG_MD5) {
            $sign = md5($joinedString);
        } else if ($this->alg == self::ALG_RSA) {
            openssl_sign($joinedString, $sign, $this->privateKey);
            $sign = base64_encode($sign);
        } else {
            // 默认
            $sign = sha1($joinedString);
        }

        return $sign;
    }

    // 底层方法 生成token
    public function generateToken($tokenType, $subject, $ak, $sk, $customPayloads = [], $expiresIn = 7200) {
        $this->subject = $subject ? $subject : $this->subject;
        $this->ak = $ak ? $ak : $this->ak;
        $this->sk = $sk ? $sk : $this->sk;
        // 生成签名失败
        if (!$this->subject || !$this->ak || !$this->sk) {
            throw new \Exception("生成签名的参数错误", ErrorCode::TOKEN_GENERATE_FAILED);
        }

        $str1 = $this->generateTokenSection1($tokenType);
        $str2 = $this->generateTokenSection2($subject, $customPayloads);
        $str3 = $this->generateTokenSection3($expiresIn);
        $str4 = $this->generateTokenSection4($str1, $str2, $str3);
        return $str1 . '.' . $str2 . '.' . $str3 . '.' . $str4;
    }

    // 版本与算法区域
    private function generateTokenSection1($tokenType) {
        // token的业务类型
        $this->tokenType = $tokenType;
        return $this->tokenType . '-' . $this->version . '-' . $this->alg;
    }

    //数据区域
    private function generateTokenSection2($subject, $customPayloads = []) {
        $subject = $subject ? $subject : $this->subject;
        $ak = $this->ak; //公钥

        $this->subject = $subject; //数据
        $this->customPayloads = $customPayloads;

        array_unshift($customPayloads, $subject);
        array_unshift($customPayloads, $ak);

        return implode('-', $customPayloads); //公钥-数据块
    }

    //时间区域
    private function generateTokenSection3($expiresIn = 7200) {
        $createTime = time();
        $expireTime = $createTime + $expiresIn;
        $this->expireTime = $expireTime;
        // return $expireTime . '-' . $createTime;
        return $expireTime;
    }

    // 签名区域
    // 使用token的section1.section2.section3.subject_secret拼成的字符串进行签名
    private function generateTokenSection4($section1, $section2, $section3) {
        $joinedString = $section1 . "." . $section2 . "." . $section3 . "." . $this->sk;

        // 加密
        $sign = "";
        if ($this->alg == self::ALG_SHA1) {
            $sign = sha1($joinedString);
        } else if ($this->alg == self::ALG_MD5) {
            $sign = md5($joinedString);
        } else if ($this->alg == self::ALG_RSA) {
            openssl_sign($joinedString, $sign, $this->privateKey);
            $sign = base64_encode($sign);
        } else {
            // 默认
            $sign = sha1($joinedString);
        }

        return $sign;
    }

    public static function createRandomStr() {
        // $chars = md5(uniqid(mt_rand(), true));
        // return substr($chars, 0, 32);
        return UUID::generate(false);
    }

    // 解析BBToken
    public function decodeToken($token) {
        list($section1, $section2, $section3, $section4) = explode('.', $token);
        $area1 = explode('-', $section1);
        $area2 = explode('-', $section2);
        $area3 = explode('-', $section3);

        if (count($area2) > 2) {
            $customPayloads = array_splice($area2, 2);
        } else {
            $customPayloads = array();
        }

        $array = array(
            "token_type" => $area1[0], //业务代码
            "version" => intval($area1[1]), //版本号
            "alg" => intval($area1[2]), //信息摘要算法代号
            "subject" => $area2[1], //业务id
            "subject_key" => $area2[0], //公钥
            "custom_payloads" => $customPayloads, // 用户自定义的其他信息
            "expire_time" => intval($area3[0]), //过期时间
            // "create_time" => intval($area3[1]), //创建时间
            "section1" => $section1,
            "section2" => $section2,
            "section3" => $section3,
            "sign" => $section4, // 签名
        );

        return $array;
    }

    // 验证token有效性
    public function validateToken($tokenInfo, $sk, $notExpiration = true) {
        //验证有效期
        if ($notExpiration) {
            if (time() > intval($tokenInfo['expire_time'])) {
                // 过期
                throw new \Exception("token 过期", ErrorCode::TOKEN_EXPIRED);
            }
        }

        if ($tokenInfo['alg']) {
            $this->alg = $tokenInfo['alg'];
        }

        //生成sign, 准备对比
        $array = $tokenInfo;
        $array['secret_key'] = $sk;
        $this->sk = $sk;
        $sign = $this->generateTokenSection4($tokenInfo["section1"], $tokenInfo["section2"], $tokenInfo["section3"]);

        //对比sign
        if ($sign !== $tokenInfo['sign']) {
            throw new \Exception("token 签名验证失败", ErrorCode::TOKEN_VERIFY_FAILED);
        }

        return true;
    }

}

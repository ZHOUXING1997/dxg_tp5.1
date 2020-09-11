<?php
namespace util;
use think\facade\Env;

/**
 * java/php对应的AES/CBC/NoPadding模式 加密解密
 */
class Crypt {

    /**
     * [$cipher 加密模式]
     * @var [type]
     */
    private $cipher = MCRYPT_RIJNDAEL_128;
    private $mode = MCRYPT_MODE_CBC;

    /**
     * [$key 密匙]
     * @var string
     */
    private $secret_key;
    /**
     * [$iv 偏移量]
     * @var string
     */
    private $iv;
    const IV_SIZE = 16;

    public function __construct($options = []) {
        $this->secret_key = Env::get('aes.secret_key', 'bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3');
        $this->iv = Env::get('aes.iv', '0102030405060708');
    }

    function setCipher($cipher='') {
        $cipher && $this->cipher = $cipher;
    }

    function setMode($mode='') {
        $mode && $this->mode = $mode;
    }

    function setSecretKey($secret_key='') {
        $secret_key && $this->secret_key = $secret_key;
    }

    function setIv($iv='') {
        $iv && $this->iv = $iv;
    }

    //加密
    function encrypt($str) {
        return base64_encode(openssl_encrypt($str, 'aes-128-cbc', $this->secret_key, true, $this->iv));
    }

    //解密
    function decrypt($str) {
        return openssl_decrypt(base64_decode($str), 'aes-128-cbc', $this->secret_key, true, $this->iv);
    }

    //bin2hex还原
    private function hex2bin($hexData) {
        $binData = "";
        for($i = 0; $i < strlen ( $hexData ); $i += 2)
        {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }
        return $binData;
    }

    //PKCS5Padding
    private function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }

    private function pkcs5Unpad($text) {
        $pad = ord ( $text {strlen ( $text ) - 1} );
        if ($pad > strlen ( $text ))
            return false;
        if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
            return false;
        return substr ( $text, 0, - 1 * $pad );
    }

}
?>
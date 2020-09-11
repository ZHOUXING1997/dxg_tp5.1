<?php
namespace helper;

use util\Snowflake;
use util\UUID;

class ClientTokenHelper {
    private $appId;

    public function __construct() {

    }

    public function init() {

    }

    public function generateToken($appId, $policy = "A") {
        // 生成一个新的snowflakeid
        $idGenerator = new Snowflake();
        $clientKey = $idGenerator->nextId();
        // 使用uuid生成一个secret
        $uuid = UUID::generate();
        $clientSecret = str_replace("-", "", $uuid);
        // dump($clientSecret);
        // $clientKey = "2361339113472258048";
        // $clientSecret = "390f65fe949fa46814b4a9810f7d4b3a";
        $str = $policy . "_" . $appId . "_" . $clientKey;// . "_" . $clientSecret;
        // dump($str);
        $salt = abs(intval($appId)) % 7;
        // dump($str);
        $afterMd5 = md5($str);
        $index1 = intval(substr($clientKey, -6 - $salt, 1));
        $index2 = intval(substr($clientKey, -5 - $salt, 1));
        $index3 = intval(substr($clientKey, -4 - $salt, 1));
        $index4 = intval(substr($clientKey, -3 - $salt, 1));;
        $index5 = intval(substr($clientKey, -2 - $salt, 1));;
        $index6 = intval(substr($clientKey, -1 - $salt, 1));;
        // dump($afterMd5);
        $char1 = substr($afterMd5, $index1, 1);
        $char2 = substr($afterMd5, $index2, 1);
        $char3 = substr($afterMd5, $index3, 1);
        $char4 = substr($afterMd5, $index4, 1);
        $char5 = substr($afterMd5, $index5, 1);
        $char6 = substr($afterMd5, $index6, 1);
        $clientToken = $policy . "_" . $appId . "_" . $clientKey . "_" . $char1 . $char2 . $char3 . $char4 . $char5 . $char6;
        
        $clientInfo = [
            "client_key" => $clientKey,
            "client_secret" => $clientSecret,
            "client_token" => $clientToken,
        ];
        return $clientInfo;
    }



    
    // 验证client Token
    public function verifyToken($token) {
        list($policy, $appId, $clientKey, $sign) = explode('_', $token);

        $str = $policy . "_" . $appId . "_" . $clientKey;
        $salt = abs(intval($appId)) % 7;
        // dump($str);
        $afterMd5 = md5($str);
        $index1 = intval(substr($clientKey, -6 - $salt, 1));
        $index2 = intval(substr($clientKey, -5 - $salt, 1));
        $index3 = intval(substr($clientKey, -4 - $salt, 1));
        $index4 = intval(substr($clientKey, -3 - $salt, 1));;
        $index5 = intval(substr($clientKey, -2 - $salt, 1));;
        $index6 = intval(substr($clientKey, -1 - $salt, 1));;
        // dump($afterMd5);
        $char1 = substr($afterMd5, $index1, 1);
        $char2 = substr($afterMd5, $index2, 1);
        $char3 = substr($afterMd5, $index3, 1);
        $char4 = substr($afterMd5, $index4, 1);
        $char5 = substr($afterMd5, $index5, 1);
        $char6 = substr($afterMd5, $index6, 1);
        $mySign = $char1 . $char2 . $char3 . $char4 . $char5 . $char6;
        if ($sign == $mySign) {
            $decoded = [
                "policy" => $policy,
                "app_id" => $appId,
                "client_key" => $clientKey,
            ];
            return $decoded;
        } else {
            return FALSE;
        }
    }

}
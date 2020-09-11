<?php

namespace util;

use think\facade\Env;

class VerifyIp {
    public static function isValidIp() {
        // 从env中，读取是否需要进行IP验证，默认为开启
        $enableVerifyIp = Env::get('verifyip.check', 'enable');
        // 黑名单默认开启
        $enableBlackList = Env::get('verifyip.blacklist', 'enable');
        // 白名单默认关闭
        $enableWhiteList = Env::get('verifyip.whitelist', 'disable');
        
        if ($enableVerifyIp !== "enable") {
            echo "enableVerifyIp disable";
            return true;
        }

        if ($enableBlackList === "enable") {
            if (self::isInBlackList()) {
                // echo "in black list";
                return false;
            }
        }

        if ($enableWhiteList === "enable") {
            if (!self::isInWhiteList()) {
                echo "not in white list";
                return false;
            }
        }
        
        return true;
    }

    public static function getWhiteList() {
        return config("ip_white_list");
    }

    public static function getBLackList() {
        return config("ip_black_list");
    }
    
    public static function isInWhiteList() {
        $clientIp = HttpInfo::getIP();
        // var_dump("clientIp $clientIp");
        $clientIpPartArr = explode('.', $clientIp);
        $ipRangeList = self::getWhiteList();
        // echo "ipRangeList:";
        // var_dump($ipRangeList);
        // echo "endipRangeList";

        foreach ($ipRangeList as $ipRange) {
            $startIp = $ipRange["start"];
            $endIp = $ipRange["end"];

            $startIpPartArr = explode('.', $startIp);
            $endIpPartArr = explode('.', $endIp);

            // 默认情况假定不在此IP范围
            $inRangeFlag = true;
            $index = 0;
            for ($index = 0; $index < 4; $index++) {
                $clientIpPart = $clientIpPartArr[$index];
                $startIpPart = $startIpPartArr[$index];
                $endIpPart = $endIpPartArr[$index];
                if (intval($clientIpPart) < intval($startIpPart)
                        || intval($clientIpPart) > intval($endIpPart)) {
                    $inRangeFlag = false;
                    // echo "not in ip range $startIp ~ $endIp\n<br />";
                    break;
                } else {
                    continue;
                }
            }
            if ($inRangeFlag && $index == 4) {
                // echo "in ip range $startIp ~ $endIp\n<br />";
                return true;
            }
        }
        return false;
    }

    public static function isInBlackList() {
        $clientIp = HttpInfo::getIP();
        // var_dump("clientIp $clientIp");
        $clientIpPartArr = explode('.', $clientIp);
        $ipRangeList = self::getBLackList();
        // echo "ipRangeList:";
        // var_dump($ipRangeList);
        // echo "endipRangeList";

        foreach ($ipRangeList as $ipRange) {
            $startIp = $ipRange["start"];
            $endIp = $ipRange["end"];

            $startIpPartArr = explode('.', $startIp);
            $endIpPartArr = explode('.', $endIp);

            // 默认情况假定不在此IP范围
            $inRangeFlag = true;
            $index = 0;
            for ($index = 0; $index < 4; $index++) {
                $clientIpPart = $clientIpPartArr[$index];
                $startIpPart = $startIpPartArr[$index];
                $endIpPart = $endIpPartArr[$index];
                if (intval($clientIpPart) < intval($startIpPart)
                        || intval($clientIpPart) > intval($endIpPart)) {
                    $inRangeFlag = false;
                    // echo "not in ip range $startIp ~ $endIp\n<br />";
                    break;
                } else {
                    continue;
                }
            }
            if ($inRangeFlag && $index == 4) {
                // echo "in ip range $startIp ~ $endIp\n<br />";
                return true;
            }
        }
        return false;
    }
}
<?php
namespace util;

use think\facade\Log;
use think\facade\Cache;
use think\facade\Env;
use app\common\exceptions\LogQpsException;

// todo 这里只管接收字符串，另一个类获取字符串
// todo 添加limit exception
// todo 限制配置也是外部传过来
class LimitManager {

    const TYPE_MINUTE = 'minute';
    const TYPE_HOUR = 'hour';
    const TYPE_DAY = 'day';

    const DEFAULT_CNT = 0;
    const STEP_CNT = 1;

    // todo
    // public function __construct() {
    //     $config
    // }

    // 总请求限制
    public static function requestLimit($limitStr, $limitConf = []) {
        // 检查是否为白名单
        if (self::isWhiteList($limitStr)) {
            return true;
        }
        $time = time();
        $limit = empty($limitConf) ? config('apply_limit') : $limitConf;
        if (empty($limit)) {
            return true;
        }
        $limitArr = explode('.', $limitStr); // todo
        $limitStr = implode($limitArr); // todo
        foreach ($limit['cache_info'] as $type => $val) {
            $curTime = $time;
            $total = self::DEFAULT_CNT;
            for ($i = 0; $i < $val['time']; $i++) {
                if ($type == self::TYPE_MINUTE) {
                    $keyTime = date('YmdHi', strtotime('-' . $i . 'minute', $curTime));
                } else if ($type == self::TYPE_HOUR) {
                    $keyTime = date('YmdH', strtotime('-' . $i . 'hour', $curTime));
                } else {
                    $keyTime = date('Ymd', strtotime('-' . $i . 'day', $curTime));
                }
                $cache = cache($type . '_' . $limitStr);
                if (!isset($cache[$keyTime])) {
                    $sum = self::DEFAULT_CNT;
                } else {
                    $sum = $cache[$keyTime];
                }
                $total += $sum;
            }
            if ($total >= $val['cnt']) {
                Log::record('[checkLimit-error] limitStr:' . $limitStr . ',cacheInfo:' . json_encode($cache));
                throw new LogQpsException("操作过于频繁，请稍后再操作", ErrorCode::ACTION_TOO_FREQUENTLY);
            }
        }
        self::setCache($limitStr, $time, $limit);
        return true;
    }

    private static function setCache($limitStr, $time, $limit) {
        $cacheType = Env::get('cache.type', '');
        foreach ($limit['cache_info'] as $type => $val) {
            if ($type == self::TYPE_MINUTE) {
                $keyTime = date('YmdHi', $time);
            } else if ($type == self::TYPE_HOUR) {
                $keyTime = date('YmdH', $time);
            } else {
                $keyTime = date('Ymd', $time);
            }
            $cache = cache($type . '_' . $limitStr);
            if (empty($cache)) {
                $cache[$keyTime] = self::STEP_CNT;
                // 设置缓存时间
                Cache::store($cacheType)->set($type . '_' . $limitStr, $cache, $limit['expired_time']);
            } else {
                if (!isset($cache[$keyTime])) {
                    $cache[$keyTime] = self::STEP_CNT;
                } else {
                    $cache[$keyTime] += self::STEP_CNT;
                }
                Cache::store($cacheType)->set($type . '_' . $limitStr, $cache);
            }
        }
    }

    // 判断是否为白名单
    private static function isWhiteList($limitStr) {
        $whiteList = config('request_white_ip');
        if (empty($whiteList)) {
            return false;
        }
        if (in_array($limitStr, $whiteList)) {
            return true;
        }
        return false;
    }
}

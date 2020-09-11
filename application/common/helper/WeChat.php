<?php
/**
 * User: 周星
 * Date: 2019/9/16
 * Time: 12:00
 * Email: zhouxing@benbang.com
 */

namespace app\common\helper;

use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\Factory;
use think\Db;

// 微信扩展
class WeChat {

    protected static $app;
    protected static $options;

    public static function weChatInit ($options = []) {
        if (self::$app) {
            return self::$app;
        }
        if (empty($options)) {
            $options = config('wx_xcx_config');
        }
        if (empty($options)) {
            throw new Exception('缺少微信相关配置');
        }
        self::$app = Factory::officialAccount($options);
        return self::$app;
    }

    public static function miniAppInit ($options = []) {
        if (self::$app) {
            return self::$app;
        }
        if (empty($options)) {
            $options = config('wx_xcx_config');
        }
        if (empty($options)) {
            throw new Exception('缺少微信小程序相关配置');
        }
        self::$app = Factory::miniProgram($options);
        return self::$app;
    }

    public static function payInit ($options = []) {
        if (self::$app) {
            return self::$app;
        }
        if (empty($options)) {
            $options = config('wx_xcx_config');
        }
        if (empty($options)) {
            throw new Exception('缺少微信支付相关配置');
        }
        self::$app = Factory::payment($options);
        return self::$app;
    }
}
<?php
namespace util;

use think\facade\Log;
use think\facade\Config;

/**
 *
 * 请求、响应相关工具类
 *
 */
class ReqResp {
    const CODE_SUCCESS = 0;
    const MSG_SUCCESS = 'success';
    const KEY_CODE = 'code';
    const KEY_ERRORS = 'errors';
    const KEY_MSG = 'msg';
    const KEY_DATA = 'data';
    const KEY_TRACE_ID = 'trace_id';
    const DEFAULT_PAGESIZE = 20;
    const DEFAULT_CURRENT_PAGE = 1;

    public static function outputSuccess($data = null, $msg = null) {
        $resp[self::KEY_CODE] = self::CODE_SUCCESS;
        $resp[self::KEY_ERRORS] = NULL;
        if ($msg) {
            $resp[self::KEY_MSG] = $msg;
        } else {
            $resp[self::KEY_MSG] = self::MSG_SUCCESS;
        }
        if (!empty($data)) {
            $resp[self::KEY_DATA] = $data;
        } else {
            $resp[self::KEY_DATA] = NULL;
        }
        $resp[self::KEY_TRACE_ID] = self::getTraceId();
        self::output($resp);
    }

    public static function outputFail($e) {
        Log::record('outputFail trace:' . json_encode($e->getTrace(), JSON_UNESCAPED_UNICODE));
        Log::record('outputFail code:' . $e->getCode());
        $resp[self::KEY_CODE] = $e->getCode();
        $resp[self::KEY_MSG] = $e->getMessage();
        $resp[self::KEY_DATA] = NULL;
        $enableDebug = Config::get("app_debug", false);
        if ($enableDebug) {
            $resp[self::KEY_ERRORS] = ReqResp::getSafeErrors($e->getTrace());
        } else {
            $resp[self::KEY_ERRORS] = NULL;
        }
        $resp[self::KEY_TRACE_ID] = self::getTraceId();
        self::output($resp);
    }

    // 底层的返回API错误信息的方法
    // 可指定设置code, msg, data, errors等
    public static function outputRaw($msg, $code = 10001, $data = NULL, $errors = NULL) {
        $resp[self::KEY_CODE] = $code;
        $resp[self::KEY_MSG] = $msg;
        $resp[self::KEY_DATA] = $data;
        $enableDebug = Config::get("app_debug", false);
        if ($enableDebug) {
            $resp[self::KEY_ERRORS] = ReqResp::getSafeErrors($errors);
        } else {
            $resp[self::KEY_ERRORS] = NULL;
        }
        $resp[self::KEY_TRACE_ID] = self::getTraceId();
        self::output($resp);
    }

    private static function getSafeErrors($errors) {
        $errorArray = json_decode(json_encode($errors, JSON_UNESCAPED_UNICODE), TRUE);
        $filtered = array();
        for ($i = 0; $i < count($errorArray); $i++) {
            $errorRow = $errorArray[$i];
            if (isset($errorRow["file"])) {
                // 过滤的error log中，文件名中有thinkphp框架的文件
                if (strpos($errorRow["file"], 'thinkphp') === false) {
                    $filtered[] = $errorRow;
                }
            }
        }
        return $filtered;
    }

    private static function getTraceId() {
        if (empty($_ENV['trace_id'])) {
            $snowflake = new Snowflake();
            $snowflakeStr = $snowflake->nextId();
            return $snowflakeStr;
        } else {
            return $_ENV['trace_id'];
        }
    }

    public static function output($result) {
        $str = json_encode($result, JSON_UNESCAPED_UNICODE);
        if (isset($_GET['callback'])) {
            $callback = $_GET['callback'];
            $str = $callback . '(' . $str . ')';
            header('Content-Type: text/javascript');
        } else {
            header('Content-Type:application/json; charset=utf-8');
        }
        exit($str);
    }

    public static function getRespData($return) {
        $array = json_decode($return, TRUE);
        if ($array['code'] == 0) {
            return $array['data'];
        } else {
            Log::record('getRespData code:' . $array['code'], Log::init()::NOTICE);
            return null;
        }
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

}
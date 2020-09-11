<?php
namespace util;

use think\facade\Env;

class Snowflake {
    // protected $autoCheckFields = false; // 设置为虚拟模型
    
    //开始时间,固定一个小于当前时间的毫秒数即可
    // const twepoch =  1474992000000;//2016/9/28 0:0:0
    const twepoch = 946656000000;

    //机器标识占的位数
    const workerIdBits = 5;

    //数据中心标识占的位数
    const datacenterIdBits = 5;

    //毫秒内自增数点的位数
    const sequenceBits = 12;

    static $workId = 0;
    static $datacenterId = 0;

    static $lastTimestamp = -1;
    static $sequence = 0;

    function __construct($workId = null, $datacenterId = null) {
        if (!$workId) {
            $workId = Env::get('snowflake.worker_id', 1);
        }

        if (!$datacenterId) {
            $datacenterId = Env::get('snowflake.data_center_id', 1);
        }

        //机器ID范围判断
        $maxWorkerId = -1 ^ (-1 << self::workerIdBits);
        if ($workId > $maxWorkerId || $workId < 0) {
            throw new Exception("worker Id can't be greater than " . $this->maxWorkerId . " or less than 0");
        }

        //数据中心ID范围判断
        $maxDatacenterId = -1 ^ (-1 << self::datacenterIdBits);
        if ($datacenterId > $maxDatacenterId || $datacenterId < 0) {
            throw new Exception("datacenter Id can't be greater than " . $maxDatacenterId . " or less than 0");
        }

        //赋值
        self::$workId = $workId;
        self::$datacenterId = $datacenterId;
    }

    //生成一个ID
    public function nextId() {
        $timestamp = $this->timeGen();
        $lastTimestamp = self::$lastTimestamp;
        //判断时钟是否正常
        if ($timestamp < $lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds", ($lastTimestamp - $timestamp));
        }

        //生成唯一序列
        if ($lastTimestamp == $timestamp) {
            $sequenceMask = -1 ^ (-1 << self::sequenceBits);
            self::$sequence = (self::$sequence + 1) & $sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
            }
        } else {
            self::$sequence = 0;
        }

        self::$lastTimestamp = $timestamp;
        $timeSpan = $timestamp - self::twepoch;

        //时间毫秒/数据中心ID/机器ID,要左移的位数
        $timestampLeftShift = self::sequenceBits + self::workerIdBits + self::datacenterIdBits;
        $datacenterIdShift = self::sequenceBits + self::workerIdBits;
        $workerIdShift = self::sequenceBits;
        if (strripos(php_uname("sm"), "windows") !== false) {
            $nextId = $this->gmpGenerator($timeSpan, $timestampLeftShift, $datacenterIdShift, $workerIdShift);
        } else {
            $nextId = $this->bitwiseGenerator($timeSpan, $timestampLeftShift, $datacenterIdShift, $workerIdShift);
        }

        //组合4段数据返回: 时间戳.数据标识.工作机器.序列
        // $nextId = (($timestamp - self::twepoch) << $timestampLeftShift) | ($this->datacenterId << $datacenterIdShift) | ($this->workId << $workerIdShift) | self::$sequence;
        return $nextId;
    }

    //取当前时间毫秒
    protected function timeGen() {
        $timestramp = (float) sprintf("%.0f", microtime(true) * 1000);
        return $timestramp;
    }

    //取下一毫秒
    protected function tilNextMillis($lastTimestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    // linux系统
    private function bitwiseGenerator($timeSpan, $timestampLeftShift, $datacenterIdShift, $workerIdShift) {
        $nextId = ($timeSpan << $timestampLeftShift) | (self::$datacenterId << $datacenterIdShift) | (self::$workId << $workerIdShift) | self::$sequence;
        return $nextId;
    }

    // windows系统
    private function gmpGenerator($timeSpan, $timestampLeftShift, $datacenterIdShift, $workerIdShift) {
        $gmp_mul = "gmp_mul";
        $gmp_pow = "gmp_pow";
        $gmp_or = "gmp_or";
        $gmp_strval = "gmp_strval";
        if (!function_exists($gmp_mul) || !function_exists($gmp_pow) || !function_exists($gmp_or) || !function_exists($gmp_strval) || empty($timeSpan) || empty($timestampLeftShift) || empty($datacenterIdShift) || empty($workerIdShift)) {
            return 0;
        }

        $timePart = $gmp_mul('' . $timeSpan, $gmp_pow('2', '' . $timestampLeftShift));
        $datacenterPart = $gmp_mul('' . self::$datacenterId, $gmp_pow('2', '' . $datacenterIdShift));
        $workerPart = $gmp_mul('' . self::$workId, $gmp_pow('2', '' . $workerIdShift));
        $nextId = $gmp_strval($gmp_or($gmp_or($gmp_or($timePart, $datacenterPart), $workerPart), '' . self::$sequence));
        return $nextId;
    }

    // 调用方法
    public function getRequestId($workId = null, $datacenterId = null) {
        if (!$workId) {
            $workId = config("app_deploy.snowflake_worker_id");
        }

        if (!$datacenterId) {
            $datacenterId = config("app_deploy.snowflake_data_center_id");
        }

        $requestId = I('request_id');
        if (!empty($requestId)) {
            return $requestId;
        } else {
            $work = new SnowFlakeService($workId, $datacenterId);
            $requestId = $work->nextId();
            return $requestId;
        }
    }
}
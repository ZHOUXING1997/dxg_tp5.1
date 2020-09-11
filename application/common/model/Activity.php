<?php

namespace app\common\model;

use think\Model;
use util\Pay;

class Activity extends Model {

    protected $table = 'activity';
    protected $pk = 'activity_id';
    
    // 获取器
    public function getActivityStartTimeAttr ($value) {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        }

        return $value;
    }

    public function getActivityEndTimeAttr ($value) {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        }

        return $value;
    }

    public function getActivityCoverImgUrlAttr ($value, $data) {
        return get_resources_by_id($data['activity_cover_img']);
    }

    public function getPriceAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getActivityTypeAttr ($value) {
        return (string) $value;
    }

    public function getProductIdAttr ($value) {
        return explode(',', $value);
    }

    public function getCateIdAttr ($value) {
        return (string) $value;
    }

    public function getActivityCorrelationAttr ($value, $data) {
        if ($data['product_title'] && $data['activity_type'] == config('activity_type_product')) {
            return config('activity_type_title')[$data['activity_type']] . ':' . $data['product_title'];
        }

        if ($data['cate_title'] && $data['activity_type'] == config('activity_type_cate')) {
            return config('activity_type_title')[$data['activity_type']] . ':' . $data['cate_title'];
        }

        if ($data['activity_type'] == config('activity_type_extension')) {
            return '宣传活动';
        }

        return '无关联';
    }

    public function getActivityNotePreviewAttr ($value, $data) {
        if (mb_strlen($data['activity_note']) > 12) {
            return mb_substr($data['activity_note'], 0, 11) . '...';
        }
        return $data['activity_note'];
    }

    public function getActivityRulePreviewAttr ($value, $data) {
        if (mb_strlen($data['activity_rule']) > 12) {
            return mb_substr($data['activity_rule'], 0, 11) . '...';
        }
        return $data['activity_rule'];
    }

    public function getActivityStatusTitleAttr ($value, $data) {
        return config('activity_status_title')[$data['activity_status']];
    }

    public function getActivitySelfStartTitleAttr ($value, $data) {
        if ($data['activity_status'] > config('activity_status_close')) {
            return '活动已启动';
        }

        return config('activity_self_start_status_title')[$data['activity_is_self_start']];
    }

    // 修改器
    public function setPriceAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function setActivityStartTimeAttr ($value, $data) {
        return strtotime($value);
        /*if (empty($data['start_time'])) {
            return time();
        }
        return strtotime($data['activity_start_time'] . $data['start_time']);*/
    }

    public function setActivityEndTimeAttr ($value, $data) {
        return strtotime($value);
        /*if (empty($data['end_time'])) {
            return time();
        }
        return strtotime($data['activity_end_time'] . $data['end_time']);*/
    }
}

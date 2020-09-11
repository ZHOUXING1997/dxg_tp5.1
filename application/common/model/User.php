<?php
/**
 * User: 周星
 * Date: 2019/4/12
 * Time: 15:01
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\db\Query;
use think\Model;
use util\ErrorCode;
use util\Pay;

class User extends Model {

    protected $table = 'user';
    protected $pk = 'user_id';

    protected $field = [];

    protected $addField = [];

    public function userList ($table, array $where, array $whereOr, array $field, array $join, array $order, $group, $nextOffset, $pageSize, $callBack = 'defaultCallBack') {
        if (!empty(array_filter($join))) {
            $fieldMerge = [];
            // foreach ($this->field as $k => $v) {
            //     if (!is_numeric($k)) {
            //         $fieldMerge[$table . '.' .$k] = $v;
            //     } else {
            //         $fieldMerge[$k] = $table . '.' . $v;
            //     }
            // }
            if (!empty($field)) {
                $fieldMerge = array_merge($fieldMerge, $field);
            }
            $this->field = $fieldMerge;
        }

        $data = $this->alias($table)
            ->field($this->field)
            ->join($join)
            ->where($where)
            ->whereOr(function (Query $query) use ($whereOr) {
                $query->where($whereOr);
            })
            ->order($order)
            ->group($group)
            ->paginate($pageSize, false, ['page' => $nextOffset])
            ->each(function ($item) use ($callBack) {
                $callBack($item);
                return $item;
            });

        return $data;
    }

    public function saveData (array $data, array $where) {
        if (empty(array_filter($where))) {
            throw new \Exception('没有更新条件', ErrorCode::UPDATE_FAILED);
        }
        $default = isset($data[$this->pk]) ? [$this->pk => $data[$this->pk]] : [];
        $where = array_merge($data, $default);
        return $this->readonly([$this->pk])->allowField(true)->save($data, $where);
    }

    public function getUserConsumeTotalFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getUserCommissionFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getUserSexTitleAttr ($value, $data) {
        return config('sex_title')[$data['user_sex']];
    }

    public function getUserStatusTitleAttr ($value, $data) {
        return config('user_status_title')[$data['user_status']];
    }
}
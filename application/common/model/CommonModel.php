<?php
/**
 * User: 周星
 * Date: 2019/4/15
 * Time: 9:26
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use app\common\exceptions\CommonModelException;
use think\db\Query;
use think\Model;
use util\ReqResp;

class CommonModel extends Model {

    protected $table;
    protected $pk;
    protected $field;
    protected $as;

    private function setModelOption ($option = []) {
        try {
            if (isset($option['table']) && $option['table'] && class_exists($option['table'])) {
                $this->table = app()->model($option['table']);
            } else {
                throw new CommonModelException('请传入可可实例化mode类', - 1);
            }
            if (isset($option['pk']) && $option['pk']) {
                $this->pk = $option['pk'];
            } else {
                $this->pk = 'id';
            }
            if (isset($option['as']) && $option['as']) {
                $this->as = $option['as'];
            } else {
                $this->as = strtolower(basename($this->table));
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    public function getList (array $option, array $field, $nextPage = 1, $page = null, $isPaging = false, $callBack = 'defaultCallBack', array $where = [], array $whereOr = [], array $join = [], array $order = [], $group = '', $appends = []) {
        $this->setModelOption($option);
        if (!$this->table) {
            $this->table = app()->model($option['table']);
        }
        // 判断是否需要处理字段
        if (!empty(array_filter($join))) {
            $this->handleField($field);
            $order = $this->handleOrder($order);
        }
        $query = $this->table->alias($this->as)->field($this->field ? $this->field : $field);

        if (!empty($where)) {
            $query->where($where)->whereOr(function (Query $query) use ($whereOr) {
                $query->where($whereOr);
            });
        }

        if (!empty($join)) {
            $query->join($join);
        }

        if (!empty($order)) {
            $query->order($order);
        }

        if (!empty($group)) {
            $query->group($group);
        }

        if (!empty($appends)) {
            $query->append($appends);
        }

        if ($isPaging) {
            $page = $page < 1 ? config('default_paginate_rows') : $page;
            $query = $query->paginate($page, false, ['page' => $nextPage]);
        } else {
            $query = $query->select();
        }

        $data = $query->each(function ($item) use ($callBack) {
            $callBack($item);

            return $item;
        });

        return $data;
    }

    // 处理字段
    private function handleField (array $field) {
        $this->field = handleField($field, $this->as);
    }

    // 处理order
    private function handleOrder ($field) {
        $orderMerge = [];
        foreach ($field as $k => $v) {
            if (false === strpos($k, '.') && false === strpos($v, '.')) {
                if (!is_numeric($k)) {
                    $orderMerge[$this->as . '.' . $k] = $v;
                } else {
                    $orderMerge[$k] = $this->as . '.' . $v;
                }
            } else {
                $orderMerge[$k] = $v;
            }
        }

        return $orderMerge;
    }
}
<?php

namespace app\common\helper;

use think\Model;

class UserThirdParty extends Model {

    // 表名
    protected $table = 'user_third_party';
    // 是否更新
    protected $isUpdate = false;

    // 主键
    protected $pk = 'id';
    // 外键
    protected $outK = 'user_id';
    // 第三方
    protected $thirdK = 'openid';

    // 默认数据
    protected function defaultData () {
        if ($this->isUpdate) {
            return [];
        }

        return [];
    }

    public function saveData (array $data, array $upWhere = []) {
        $exists = $this->checkUserExists($upWhere);
        if (null == $exists) {
            // TODO 增加自定义更新条件字段限制
            // 如果自定义更新条件，必须可以查询出来
            return false;
        }
        $data[$this->pk] = $exists[$this->pk];

        // 存在主键视为已存在
        if ((isset($data[$this->pk]) && $data[$this->pk]) || !empty($upWhere)) {
            $this->isUpdate = true;
            if (empty($upWhere)) {
                $upWhere = [
                    $this->pk => $data[$this->pk],
                ];
            } else {
                $data[$this->pk] = $exists[$this->pk];
            }
        } else {
            // 添加时必须存在user_id 与 第三方 openid
            if (!isset($data[$this->outK]) || !isset($data[$this->thirdK])) {
                return false;
            }
            $this->isUpdate = false;
        }
        
        $data = array_merge($this->defaultData(), $data);

        $res = $this->readonly($this->readonly)->allowField(true)->isUpdate($this->isUpdate)->save($data, empty($upWhere) ? [] : $upWhere);

        if (false === $res) {
            return false;
        }
        
        return $this->isUpdate ? $data[$this->pk] : $this->getLastInsID();
    }
    
    public function checkUserExists (array $where) {
        if (!empty($where)) {
            return self::where($where)->find();
        }
        return true;
    }
}

<?php
/**
 * User: 周星
 * Date: 2019/4/19
 * Time: 10:16
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\db\Query;
use think\Model;

class Cate extends Model {

    protected $table = 'cate';
    protected $pk = 'cate_id';
    
    // 修改器
    public function setTagAttr ($value) {
        return trim(str_replace('，', ',', $value), ',');
    }

    // 查询商品页面分类
    public function viewCate ($selectedId = null, $disabledId = null, array $where = []) {
        $where = array_merge($where, [
            ['cate_is_delete', '=', config('un_deleted')]
        ]);
        $whereOr = [
            ['cate_id', 'in', $selectedId],
            ['cate_is_delete', '=', config('un_deleted')]
        ];

        $data = self::field(['cate_id' => 'value', 'cate_title' => 'name', 'cate_icon'])->where($where)->whereOr(function (Query $query) use ($whereOr) {
            return $query->where($whereOr);
        })->select()->each(function ($item) use ($selectedId, $disabledId) {
            if ($selectedId) {
                if (!is_array($selectedId)) {
                    $selectedId = explode(',', $selectedId);
                }
                if ($selectedId && in_array($item['value'], $selectedId)) {
                    $item['selected'] = 'selected';
                }
            }
            if ($disabledId) {
                if (!is_array($disabledId)) {
                    $disabledId = explode(',', $disabledId);
                }
                if ($disabledId && in_array($item['value'], $disabledId)) {
                    $item['disabled'] = 'disabled';
                }
            }

            $item['cate_icon'] = get_resources_by_id($item['cate_icon']);
            return $item;
        })->toArray();

        return $data;
    }

    // 查询活动页面
    public function activityViewCate ($selectedId = null, $disabledId = null) {
        // 查询有商品活动的分类id
        $cateId = app()->model('common/Product')->where([
            ['product_is_delete', '=', config('un_deleted')],
            ['activity_id', '>', 0],
        ])->whereNotIn('cate_id', $selectedId)->column('cate_id');
        $cateId = array_unique($cateId);

        $data = $this->alias('cate')->field([
            'cate.cate_id' => 'value', 'cate_title' => 'name', 'cate_icon'
        ])->where(cateBaseWhere('cate', false))->whereNotIn('cate.cate_id', $cateId)->join([
            ['products pro', 'cate.cate_id = pro.cate_id'],
        ])->group('cate.cate_id')->select()->each(function ($item)  use ($selectedId, $disabledId) {
            if ($selectedId) {
                if (!is_array($selectedId)) {
                    $selectedId = explode(',', $selectedId);
                }
                if ($selectedId && in_array($item['value'], $selectedId)) {
                    $item['selected'] = 'selected';
                }
            }
            if ($disabledId) {
                if (!is_array($disabledId)) {
                    $disabledId = explode(',', $disabledId);
                }
                if ($disabledId && in_array($item['value'], $disabledId)) {
                    $item['disabled'] = 'disabled';
                }
            }

            $item['cate_icon'] = get_resources_by_id($item['cate_icon']);
            return $item;
        })->toArray();

        return $data;
    }

    public function getCateByWhere (array $where = []) {
        if (empty($where)) {
            return false;
        }

        $where = array_merge($where, ['cate_is_delete' => config('un_deleted')]);

        return self::where($where)->find();
    }

    public function getCateIconUrlAttr ($value, $data) {
        return get_resources_by_id($data['cate_icon']);
    }

    public function getCateIsShowTitleAttr ($value, $data) {
        return config('show_status_title')[$data['cate_is_show']];
    }

    public function getCateDescriptionPreviewAttr ($value, $data) {
        if (mb_strlen($data['cate_description']) > 12) {
            return mb_substr($data['cate_description'], 0, 11) . '...';
        }
        return $data['cate_description'];
    }
}
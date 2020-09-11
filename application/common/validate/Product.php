<?php
/**
 * User: 周星
 * Date: 2018/9/17
 * Time: 9:39
 * Email: zhouxing@benbang.com
 */

namespace app\common\validate;

use think\Db;
use think\Validate;

class Product extends Validate {

    const TABLE = 'products';

    protected $rule = [
        'product_title|商品名称' => 'require|length:1,20|unique:' . self::TABLE,
        // 'product_code|商品货号' => 'require|alphaDash|length:1,20|unique:' . self::TABLE,
        'cate_id|分类'      => 'require|checkCate',
        // 'product_price|价格'     => 'require|float|between:1,999999',
        'product_cover_img|封面图' => 'require',
        'product_tag|标签' => 'max:100',
        'product_description|商品描述' => 'max:200',
        'product_note|商品备注' => 'max:200',
        'product_sort|排序' => 'require|number|between:1,99999',
        'product_stock|库存' => 'require|number|between:1,99999999',
        'product_attribute|商品属性' => 'checkAttribute',
    ];

    protected $message = [
        // 'name.checkLength' => '商品名称长度为1-13字符',
        // 'product_price.min'        => '商品价格最小为1',
        'cate_id.checkCate' => '分类错误',
    ];

    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];

    protected function checkCarded ($carded) {
        $rule = '/^[1-9]\d{7}((0[1-9])|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/';
        return preg_match($rule, $carded) > 0;
    }

    protected function checkLength ($value, $length, $data) {
        return mb_strlen($value) > $length ? false : true;
    }

    // 判断分类是否存在
    protected function checkCate ($value) {
        if (!is_numeric($value)) {
            return false;
        }
        $isSet = app()->model('common/Cate')->getCateByWhere(['cate_id' => $value]);
        if (!$isSet) {
            return false;
        }
        return true;    
    }

    protected function checkAttribute ($value, $rule, $data) {
        $value = array_filter(array_map('array_filter', array_values($value)));
        if (empty($value)) {
            return '商品属性最少要有一个';
        }
        if (!is_array($value)) {
            return '商品属性传参错误';
        }
        $attrCode = array_column($value, 'attr_code');
        // 如果有重复code
        if (count($attrCode) != count(array_unique($attrCode))) {
            return '商品属性型号存在重复';
        }
        return true;
    }
}

<?php

namespace app\common\model;

use think\db\Query;
use think\facade\Cache;
use think\Model;
use util\ErrorCode;
use util\Pay;

class Product extends Model {

    protected $table = 'products';
    protected $pk = 'product_id';

    public static function init() {

        self::beforeInsert(function ($data) {
            // 价格处理
            $price = self::handleProductPrice($data);
            if (false === $price) {
                return false;
            }
            if ($price) {
                $data['product_price'] = $price;
            }
        });

        /*self::afterInsert(function ($data) {
            self::setTagListCache($data['product_id'], $data['product_tag']);
        });*/

        self::beforeUpdate(function ($data) {
            // 更新tag缓存
            if (isset($data['product_tag'])) {
                self::setTagListCache($data['product_id'], $data['product_tag']);
            }
            if (isset($data['product_title'])) {
                self::setTitleListCache($data['product_id'], $data['product_title']);
            }
            // 价格处理
            $price = self::handleProductPrice($data);
            if (false === $price) {
                return false;
            }
            if ($price) {
                $data['product_price'] = $price;
            }
        });
    }

    private static function handleProductPrice ($data) {
        if (!isset($data['product_attribute'])) {
            return null;
        }
        // $attr = json_decode($data['product_attribute'], true);
        $attr = $data['product_attribute'];
        $attrPrice = array_column($attr, 'attr_price');
        if (empty($attrPrice)) {
            return false;
        }

        $minPrice = min($attrPrice);
        $maxPrice = max($attrPrice);

        if (count($attrPrice) > 1 && $minPrice != $maxPrice) {
            return $minPrice . '-' . $maxPrice;
        }

        return $attrPrice[0];
    }

    // 总长度 11
    public static function makeProductCode ($cateId) {
        // 取分类 md5 中的字母 中的前三位
        $md5Letter = substr(preg_replace("/\\d+/",'', md5($cateId)), 0, 3);
        // 3 + 两位随机 + 时间戳后6位
        return strtoupper($md5Letter . random_int(11, 99) . substr(getMillisecond(), -6));
    }

    // 总长度 10
    public static function makeProductAttrCode ($productCode, $i) {
        // 取商品code md5字母中的后4位
        $md5Letter = substr(preg_replace("/\\d+/",'', md5($productCode)), -4);
        // 4 + 两位随机 + 时间戳后4位
        return strtoupper($md5Letter . random_int(11, 99) . $i . substr(getMillisecond(), -5));
    }

    // 修改器
    /*public function setProductPriceAttr ($value) {
        return Pay::saveMoneyChange($value);
    }*/

    public function setProductCodeAttr ($value, $data) {
        return $value ? $value : app()->model('common/Product')::makeProductCode($data['cate_id']);
    }

    public function setProductDetailImgAttr ($value, $data) {
        if (isset($data['product_detail_img_ids']) && is_array($data['product_detail_img_ids'])) {
            return implode(',', $data['product_detail_img_ids']);
        }

        return '';
    }

    public function setProductPreviewImgAttr ($value, $data) {
        if (isset($data['product_preview_img_ids']) && is_array($data['product_preview_img_ids'])) {
            return implode(',', $data['product_preview_img_ids']);
        }
        return '';
    }

    public function setProductTagAttr ($value) {
        return trim(str_replace(['，', '，，', ',,'], ',', $value), ',');
    }

    public function setProductAttributeAttr ($value, $data) {
        $value = array_filter(array_map('array_filter', array_values($value)));
        if (empty($value)) {
            throw new \Exception('商品属性输错', ErrorCode::PARAMS_ERROR);
        }

        // 将空商品属性code自动生成
        $i = 0;
        foreach ($value as $k => &$attrItem) {
            if (!isset($attrItem['attr_code']) || !$attrItem['attr_code']) {
                $attrItem['attr_code'] = app()->model('common/Product')::makeProductAttrCode($data['product_code'], $i++);
            }
            $attrItem['attr_code'] = trim($attrItem['attr_code']);
            $attrItem['attr_price'] = Pay::saveMoneyChange($attrItem['attr_price']);
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // 获取器
    public function getCateIdAttr ($value) {
        return (string) $value;
    }

    /*public function getProductPriceAttr ($value) {
        return Pay::drawMoneyChange($value);
    }*/

    public function getProductCoverImgUrlAttr ($value, $data) {
        return get_resources_by_id($data['product_cover_img']);
    }

    public function getProductDetailImgUrlAttr ($value, $data) {
        return (array) get_resources_by_id($data['product_detail_img']);
    }

    public function getProductDetailImgInfoAttr ($value, $data) {
        return app('AssetModel')->getImgInfo($data['product_detail_img']);
    }

    public function getProductPreviewImgUrlAttr ($value, $data) {
        return (array) get_resources_by_id($data['product_preview_img']);
    }

    public function getProductPreviewImgInfoAttr ($value, $data) {
        return app('AssetModel')->getImgInfo($data['product_preview_img']);
    }

    // 查询页面商品
    public function activityViewProduct ($selectedId = null, $disabledId = null) {
        $baseWhere = array_merge(productBaseWhere('pro'), cateBaseWhere('cate'));

        $where = array_merge([['activity_id', '=', 0]], $baseWhere);

        $whereOr = array_merge([['product_id', 'in', $selectedId]], $baseWhere);

        $data = $this->alias('pro')->field([
            'product_id' => 'value', 'product_title' => 'name', 'product_code'
        ])->where($where)->whereOr(function (Query $query) use ($whereOr) {
            return $query->where($whereOr);
        })->join([
            ['cate cate', 'pro.cate_id = cate.cate_id'],
        ])->select()->each(function ($item) use ($selectedId, $disabledId) {
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
            return $item;
        })->toArray();

        return $data;
    }

    // 查询商品信息
    public function getProductByWhere (array $where = []) {
        if (empty($where)) {
            return false;
        }

        return self::where(['product_is_delete' => config('un_deleted')])->where([$where])->select();
    }

    public function getProductAttributeAttr ($value) {
        if ($value) {
            $value = json_decode($value, true);
            foreach ($value as &$vItem) {
                $vItem['attr_price'] = Pay::drawMoneyChange($vItem['attr_price']);
            }
        }
        return $value;
    }

    public function getProductOnSaleTitleAttr($value, $data) {
        return config('product_sale_title')[$data['product_on_sale']];
    }

    public function getProductVipExclusiveTitleAttr ($value, $data) {
        return config('vip_exclusive_title')[$data['product_is_vip_exclusive']];
    }

    public function getProductDescriptionPreviewAttr ($value, $data) {
        if (mb_strlen($data['product_description']) > 12) {
            return mb_substr($data['product_description'], 0, 11) . '...';
        }
        return $data['product_description'];
    }

    public function setProductDescriptionAttr ($value, $data) {
        return str_replace('\\', '、', $value);
    }

    public function setProductActivity ($productId, $activityId) {
        return $this->whereIn('product_id', $productId)->setField('activity_id', $activityId);
    }

    public function setCateActivity ($cateId, $activityId) {
        return $this->whereIn('cate_id', $cateId)->setField('activity_id', $activityId);
    }

    // 减少库存
    public static function decStock ($productCode, $productNum) {
        // 根据商品扣除库存
        return self::where([
            'product_code' => $productCode,
            // 'product_is_verify_stock' => config('product_verify_stock')
        ])->setDec('product_stock', $productNum);
    }

    // 增加库存
    public static function incStock ($productCode, $productNum) {
        // 根据商品增加库存
        return self::where([
            'product_code' => $productCode,
            // 'product_is_verify_stock' => config('product_verify_stock')
        ])->setInc('product_stock', $productNum);
    }

    // 获取商品标签
    public static function getTagList ($productId = null) {
        // 未删除，已上架
        $tagList = Cache::get('product_search_tag_list_' . $productId);
        if (!$tagList) {
            $where = [
                ['product_is_delete', '=', config('un_deleted'),],
                ['product_on_sale', '=', config('on_sale')],
            ];
            if ($productId) {
                array_unshift($where, ['product_id', '=', $productId]);
            }
            $tagList = self::where($where)->column('product_tag', 'product_id');
            Cache::set('product_search_tag_list_' . $productId, array_filter($tagList), 3600);
        }
        return array_filter($tagList);
    }

    // 存储商品标签缓存
    public static function setTagListCache ($productId, $tag) {
        Cache::set('product_search_tag_list_' . $productId, [$productId => $tag]);
        $tagList = Cache::get('product_search_tag_list_');
        $tagList[$productId] = $tag;
        Cache::set('product_search_tag_list_', array_filter($tagList));
    }

    // 删除商品标签缓存
    public static function delTagListCache ($productId) {
        Cache::rm('product_search_tag_list_' . $productId);
        $tagList = Cache::get('product_search_tag_list_');
        $tagList[$productId] = null;
        Cache::set('product_search_tag_list_', array_filter($tagList));
    }

    // 获取商品标题
    public static function getTitleList ($productId = null) {
        // 未删除，已上架
        $titleList = Cache::get('product_search_title_list_' . $productId);
        if (!$titleList) {
            $where = [
                ['product_is_delete', '=', config('un_deleted'),],
                ['product_on_sale', '=', config('on_sale')],
            ];
            if ($productId) {
                array_unshift($where, ['product_id', '=', $productId]);
            }
            $titleList = self::where($where)->column('product_title', 'product_id');
            Cache::set('product_search_title_list_' . $productId, array_filter($titleList), 3600);
        }
        return array_filter($titleList);
    }

    // 存储商品标题缓存
    public static function setTitleListCache ($productId, $title) {
        Cache::set('product_search_title_list_' . $productId, [$productId => $title]);
        $titleList = Cache::get('product_search_title_list_');
        $titleList[$productId] = $title;
        Cache::set('product_search_title_list_', array_filter($titleList));
    }

    // 删除商品标题缓存
    public static function delTitleListCache ($productId) {
        Cache::rm('product_search_title_list_' . $productId);
        $titleList = Cache::get('product_search_title_list_');
        $titleList[$productId] = null;
        Cache::set('product_search_title_list_', array_filter($titleList));
    }
}

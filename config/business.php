<?php
/**
 * User: 周星
 * Date: 2019/4/12
 * Time: 15:07
 * Email: zhouxing@benbang.com
 */

return [
    //--------------------------------------AES加密相关----------------------------------//
    'aes_ak'                        => 'tBbghnio9Akm_182',
    'aes_sk' => '9649aefv9362e572',

    //-----------------------------------公共----------------------------------------//
    'un_deleted'                    => 0,   // 未删除
    'deleted'                       => 1,     // 已删除

    // 性别
    'sex_title'                     => [
        0 => '保密', 1 => '男', 2 => '女',
    ],

    // 是否显示
    'show_status_display'           => 1, // 显示
    'show_status_hide'              => 0, // 隐藏
    'show_status_title'             => [
        0 => '隐藏', 1 => '显示',
    ],

    // 分页
    'default_paginate_rows'         => 15, // 默认上传上限
    'upload_default_size'           => 2097152, // 2m 单位 b(字节)

    // 默认送货人
    'default_delivery_man'          => '店家负责', // 默认送货方式
    'default_delivery_type'         => '自营',

    //-----------------------------------用户相关--------------------------------------//
    'user_disable'                  => 0,    // 禁用
    'user_open'                     => 1,       // 开启
    'user_deleted'                  => 2,   // 删除

    // 用户状态
    'user_status_title'             => [
        0 => '禁用', 1 => '正常', 2 => '删除',
    ],

    'user_not_authorize' => 0, // 未授权
    'user_authorized' => 1, // 已授权
    'user_is_authorize_title' => [
        0 => '未授权', 1 => '已授权',
    ],

    //-----------------------------------订单相关--------------------------------------//
    // 主订单订单状态
    'main_order_status_title'       => [
        0 => '待付款', 1 => '待发货', 2 => '待收货', 3 => '已完成', 4 => '售后中 ', 5 => '订单关闭', 6 => '订单超时', 7 => '已取消'
    ],

    // 订单状态 0 未支付， 1 支付完成， 2 已发货， 3 订单完成，4 售后中， 5 订单关闭，6 订单超时，7 用户取消订单
    'main_order_unpaid'             => 0,       // 未支付
    'main_order_pay_complete'       => 1,       // 支付完成
    'main_order_delivered'          => 2,       // 已发货
    'main_order_complete'           => 3,       // 订单完成
    'main_order_after_sale_ing'     => 4,       // 售后中
    'main_order_close'              => 5,       // 订单关闭
    'main_order_out_time'           => 6,       // 订单超时
    'main_order_user_cancel'        => 7,       // 用户取消订单

    // 用户是否确认订单, 0 未确认， 1 确认
    'main_order_user_not_confirm' => 0, // 未确定
    'main_order_user_confirmed' => 1, // 已确定
    'main_order_user_confirm_title' => [
        0 => '未确认',
        1 => '已确认',
    ],

    // 订单可删除状态
    'main_order_deletable_status'   => [
        0, 6, 7
    ],
    // 订单前端可删除状态
    'main_order_web_deletable_status'   => [
        0, 3, 5, 6, 7
    ],

    // 子订单状态
    'sub_order_status_title'        => [
        0 => '未支付', 1 => '待发货', 2 => '已发货', 3 => '已收货', 4 => '已评价', 5 => '退款', 6 => '退货退款', 7 => '换货', 8 => '售后完', 9 => '已取消'
    ], // 订单状态 0 未支付 1 支付完成 2 已发货 3 已收货 4 已评价 5 退款 6 退货退款 7 换货 8 售后完成 9 用户取消订单
    'sub_order_unconfirmed'         => 0,           // 未确认
    'sub_order_deliver'             => 1,               // 待发货(支付完成)
    'sub_order_delivered'           => 2,             // 已发货
    'sub_order_receipted'           => 3,             // 已收货
    'sub_order_evaluated'           => 4,             // 已评价
    'sub_order_refund'              => 5,                // 退款
    'sub_order_return_goods_refund' => 6,   // 退货退款
    'sub_order_exchange_goods'      => 7,        // 换货
    'sub_order_after_sale_complete' => 8,   // 售后完成
    'sub_order_user_cancel'         => 9,   // 用户取消订单

    'sub_order_unreview'               => 0,    // 未追评
    'sub_order_reviewed'               => 1,    // 已追评
    'sub_order_review_title'           => [
        0 => '未追评', 1 => '已追评',
    ],

    // 订单查询状态值
    'search_order_status' => [
        0, 1, 2, 3,
    ],
    'search_order_status_title' => [
        0 => '待付款',
        1 => '待发货',
        2 => '待收货',
        3 => '已完成',
    ],
    //-----------------------------------支付相关------------------------------------//
    // 支付状态
    'pay_status_title'                 => [
        0 => '未支付 ', 1 => '支付中 ', 2 => '支付成功 ', 3 => '支付失败',
    ], 'pay_type_unknown'              => 0,    // 未知
    'pay_type_alipay'                  => 1,     // 支付宝
    'pay_type_wechat'                  => 2,     // 微信
    'pay_type_union_pay'               => 3,  // 银联

    // 支付类型
    'pay_type_title'                   => [
        0 => '未知', 1 => '支付宝', 2 => '微信', 3 => '银联',
    ],
    'pay_status_unpay'                 => 0,    // 未支付
    'pay_status_ing'                   => 1,    // 支付中
    'pay_status_succ'                  => 2,    // 支付成功
    'pay_status_error'                 => 3,    // 支付失败

    //---------------------------------商品相关------------------------------------//
    // 是否上架
    'on_sale'                          => 1, 'down_sale' => 0, 'product_sale_title' => [
        0 => '已下架', 1 => '已上架',
    ],

    // 是否会员专属
    'no_vip_exclusive'                 => 0, 'vip_exclusive' => 1, 'vip_exclusive_title' => [
        0 => '否', 1 => '是',
    ],

    // 是否验证库存
    'product_verify_stock' => 1,
    'product_not_verify_stock' => 0,
    'product_verify_stock_title' => [
        0 => '不验证',
        1 => '验证',
    ],

    //-----------------------------------地址相关--------------------------------------//
    // 是否默认
    'address_defaulted'                => 1,     // 默认
    'address_not_default'              => 0,   // 非默认
    'address_default_title'            => [
        0 => '非默认', 1 => '默认',
    ],

    // 是否完善
    'address_perfected'                => 1,       // 已完善
    'address_not_perfect'              => 0,     // 未完善
    'address_perfect_title'            => [
        0 => '未完善', 1 => '已完善',
    ],

    // 地址状态
    'address_status_succ'              => 0,     // 可用
    'address_status_fail'              => 1,     // 不可用
    'address_status_title'             => [
        0 => '可用', 1 => '不可用',
    ],

    //-----------------------------------活动相关--------------------------------------//
    // 活动状态
    'activity_status_close'            => 0, // 未开始(关闭)
    'activity_status_start'            => 1, // 开始
    'activity_status_suspend'          => 2, // 暂停
    'activity_status_end'              => 3, // 结束
    'activity_status_title'            => [
        0 => '未开始', 1 => '已开始', 2 => '活动暂停', 3 => '活动结束',
    ],

    // 是否自启动
    'activity_self_start_fail'         => 0, // 未设置自启动
    'activity_self_start_succ'         => 1, // 已设置自启动
    'activity_self_start_status_title' => [
        0 => '未设置', 1 => '已设置',
    ],

    // 活动可删除状态
    'activity_deletable_status'        => [
        0,
    ],

    // 活动可结束状态
    'activity_ended_status'            => [
        1, 2,
    ],

    // 自启动队列状态
    'activity_self_start_status_not'   => 0, // 未自启动
    'activity_self_start_status_ing'   => 1, // 自启动中
    'activity_self_start_status_succ'  => 2, // 自启动成功
    'activity_self_start_status_fail'  => 3, // 自启动失败

    'activity_self_restart_max' => 3, // 自启动重试次数

    //-----------------------------------提现相关--------------------------------------//
    // 提现状态 0未提现(num超过三次为失败) 1提现中 2提现成功 3 提现失败， 4 提现异常
    'queue_withdraw_status_not' => 0,
    'queue_withdraw_status_ing' => 1,
    'queue_withdraw_status_succ' => 2,
    'queue_withdraw_status_title' => [
        0 => '未提现',
        1 => '提现中',
        2 => '提现成功',
        3 => '提现失败',
        4 => '提现异常'
    ],

    // 用户提现记录表提现成功
    'user_withdrawal_status_succ' => 1,
    'user_withdrawal_status_fail' => 2,

    //-----------------------------------活动相关--------------------------------------//
    // 后台用户状态
    'admin_user_disable'        => 0,    // 禁用
    'admin_user_open'           => 1,       // 开启
    'admin_user_status_title'   => [
        0 => '禁用', 1 => '开启',
    ],

    // 活动类型
    // 0宣传活动， 1 商品活动，  2 分类活动
    'activity_type_extension'        => 0, // 宣传活动
    'activity_type_product'          => 1, // 商品活动
    'activity_type_cate'             => 2, // 分类活动

    'activity_type_title' => [
        0 => '宣传活动', 1 => '商品活动', 2 => '分类活动',
    ],

    //--------------------------------------业务配置----------------------------------//
    'login_app' => 1, // app token
    'login_xcx' => 2, // 小程序token
    'login_xcx_expire' => 3600, // 小程序token有效期
];
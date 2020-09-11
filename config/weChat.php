<?php
/**
 * User: 周星
 * Date: 2019/11/7
 * Time: 11:22
 * Email: zhouxing@benbang.com
 */

use think\facade\Env;

return [
    //-----------------------------------微信相关----------------------------------------//
    'easy_weixin' => [
        /**
         * 账号基本信息，请从微信公众平台/开放平台获取
         */
        'app_id'        => 'wxa15b3d29f66c9db4',         // AppID
        'secret'        => 'efb553e764f6a906b80d83f3b5429528',     // AppSecret
        'token'         => 'easywechat',          // Token
        'aes_key'       => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

        /**
         * 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
         * 使用自定义类名时，构造函数将会接收一个 `EasyWeChat\Kernel\Http\Response` 实例
         */
        'response_type' => 'array',

        /**
         * 日志配置
         *
         * level: 日志级别, 可选为：
         *         debug/info/notice/warning/error/critical/alert/emergency
         * path：日志文件位置(绝对路径!!!)，要求可写权限
         */
        'log'           => [
            'default'  => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
            'channels' => [
                // 测试环境
                'dev'  => [
                    'driver' => 'single',
                    'path' => '/tmp/easywechat.log',
                    'level' => 'debug',
                ], // 生产环境
                'prod' => [
                    'driver' => 'daily',
                    'path' => '/tmp/easywechat.log',
                    'level' => 'info',
                ],
            ],
        ],

        /**
         * OAuth 配置
         *
         * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
         * callback：OAuth授权完成后的回调页地址
         */
        'oauth'         => [
            'scopes' => ['snsapi_userinfo'], 'callback' => '',
        ],
    ],


    //-----------------------------------小程序相关----------------------------------------//
    'wx_xcx_config' => [
        // 'appid' => 'wx31c256cc9d87d335',
        // 'app_id' => 'wx31c256cc9d87d335',
        // 'secret' => 'e22b730f35cfb3fc9a833a8f896a2f25',

        'appid' => 'wxe03baaac041b0de0',
        'app_id' => 'wxe03baaac041b0de0',
        'secret' => '975280639eed35c447c989e0eedd0a05',

        'js_code' => '',
        'grant_type' => 'authorization_code',
        'mch_id' => '1555848341',
        'key' => 'LFs6GmzZSI5Esu3rkKAmeXY8qgxyZjbB',
        'payment' => [
            'merchant_id'        => '1555848341',
            'key'                => 'LFs6GmzZSI5Esu3rkKAmeXY8qgxyZjbB',
            'cert_path' => Env::get('root_path') . 'pay_key/cert.pem',
            // XXX: 绝对路径！！！！
            'key_path' => Env::get('root_path') . 'pay_key/key.pem',
            // 'notify_url'         => '默认的订单回调地址',       // 你也可以在下单时单独设置来想覆盖它
        ],
	'cert_path' => Env::get('root_path') . 'pay_key/cert.pem',
        'key_path' => Env::get('root_path') . 'pay_key/key.pem',
    ],
];

<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用容器绑定定义
return [
    'UserInfoModel' => app\common\helper\UserInfo::class,
    'UserThirdPartyModel' => app\common\helper\UserThirdParty::class,
    'UserTokenModel' => app\common\helper\UserToken::class,
    'AssetModel' => app\common\helper\Asset::class,
    'Snowflake' => util\Snowflake::class,
    'CipherSalt' => util\CipherSalt::class,
];

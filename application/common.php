<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Db;
use think\facade\Request;
use think\facade\Cache;

// 应用公共文件
/**
 * 获取网站根目录
 * @return string 网站根目录
 */
function get_root()
{
    $root    = Request::root();
    $root    = str_replace('/index.php', '', $root);
    if (defined('APP_NAMESPACE') && APP_NAMESPACE == 'api') {
        $root = preg_replace('/\/api$/', '', $root);
        $root = rtrim($root, '/');
    }

    return $root;
}

/**
 * 返回带协议的域名
 */
function get_domain()
{
    return Request::domain();
}

/**
 * 验证码检查，验证完后销毁验证码
 * @param string $value
 * @param string $id
 * @return bool
 */
function tpl_captcha_check($value, $id = "")
{
    $captcha = new \think\captcha\Captcha();
    return $captcha->check($value, $id);
}

/**
 * 随机字符串生成
 * @param int $len 生成的字符串长度
 * @return string
 */
function common_random_string($len = 6)
{
    $chars    = [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    ];
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

/**
 * 获取CMF上传配置
 */
function cmf_get_upload_setting()
{
    $uploadSetting = cmf_get_option('upload_setting');
    if (empty($uploadSetting) || empty($uploadSetting['file_types'])) {
        $uploadSetting = [
            'file_types' => [
                'image' => [
                    'upload_max_filesize' => '10240',//单位KB
                    'extensions'          => 'jpg,jpeg,png,gif,bmp4'
                ],
                'video' => [
                    'upload_max_filesize' => '10240',
                    'extensions'          => 'mp4,avi,wmv,rm,rmvb,mkv'
                ],
                'audio' => [
                    'upload_max_filesize' => '10240',
                    'extensions'          => 'mp3,wma,wav'
                ],
                'file'  => [
                    'upload_max_filesize' => '10240',
                    'extensions'          => 'txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar'
                ]
            ],
            'chunk_size' => 512,//单位KB
            'max_files'  => 20 //最大同时上传文件数
        ];
    }

    if (empty($uploadSetting['upload_max_filesize'])) {
        $uploadMaxFileSizeSetting = [];
        foreach ($uploadSetting['file_types'] as $setting) {
            $extensions = explode(',', trim($setting['extensions']));
            if (!empty($extensions)) {
                $uploadMaxFileSize = intval($setting['upload_max_filesize']) * 1024;//转化成B
                foreach ($extensions as $ext) {
                    if (!isset($uploadMaxFileSizeSetting[$ext]) || $uploadMaxFileSize > $uploadMaxFileSizeSetting[$ext] * 1024) {
                        $uploadMaxFileSizeSetting[$ext] = $uploadMaxFileSize;
                    }
                }
            }
        }

        $uploadSetting['upload_max_filesize'] = $uploadMaxFileSizeSetting;
    }

    return $uploadSetting;
}

/**
 * 获取系统配置，通用
 * @param string $key 配置键值,都小写
 * @return array
 */
function cmf_get_option($key)
{
    if (!is_string($key) || empty($key)) {
        return [];
    }

    static $cmfGetOption;

    if (empty($cmfGetOption)) {
        $cmfGetOption = [];
    } else {
        if (!empty($cmfGetOption[$key])) {
            return $cmfGetOption[$key];
        }
    }

    $optionValue = cache('cmf_options_' . $key);

    if (empty($optionValue)) {
        $optionValue = Db::name('option')->where('option_name', $key)->value('option_value');
        if (!empty($optionValue)) {
            $optionValue = json_decode($optionValue, true);

            cache('cmf_options_' . $key, $optionValue);
        }
    }

    $cmfGetOption[$key] = $optionValue;

    return $optionValue;
}

/**
 * 获取文件扩展名
 * @param string $filename 文件名
 * @return string 文件扩展名
 */
function cmf_get_file_extension($filename)
{
    $pathinfo = pathinfo($filename);
    return strtolower($pathinfo['extension']);
}

/**
 * 获取CMF系统的设置，此类设置用于全局
 * @param string $key 设置key，为空时返回所有配置信息
 * @return mixed
 */
function cmf_get_cmf_settings($key = "")
{
    $cmfSettings = cache("cmf_settings");
    if (empty($cmfSettings)) {
        $objOptions = new \app\open\model\Option();
        $objResult  = $objOptions->where("option_name", 'cmf_settings')->find();
        $arrOption  = $objResult ? $objResult->toArray() : [];
        if ($arrOption) {
            $cmfSettings = json_decode($arrOption['option_value'], true);
        } else {
            $cmfSettings = [];
        }
        cache("cmf_settings", $cmfSettings);
    }

    if (!empty($key)) {
        if (isset($cmfSettings[$key])) {
            return $cmfSettings[$key];
        } else {
            return false;
        }
    }
    return $cmfSettings;
}

/**
 * 获取文件相对路径
 * @param string $assetUrl 文件的URL
 * @return string
 */
function cmf_asset_relative_url($assetUrl)
{
    if (strpos($assetUrl, "http") === 0) {
        return $assetUrl;
    } else {
        return str_replace('/upload/', '', $assetUrl);
    }
}

/**
 * CMF Url生成
 * @param string $url 路由地址
 * @param string|array $vars 变量
 * @param bool|string $suffix 生成的URL后缀
 * @param bool|string $domain 域名
 * @return string
 */
function cmf_url($url = '', $vars = '', $suffix = true, $domain = false)
{
    static $routes;

    if (empty($routes)) {
        $routeModel = new app\common\model\Route();
        $routes     = $routeModel->getRoutes();
    }

    if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
        $info = parse_url($url);
        $url  = !empty($info['path']) ? $info['path'] : '';
        if (isset($info['fragment'])) {
            // 解析锚点
            $anchor = $info['fragment'];
            if (false !== strpos($anchor, '?')) {
                // 解析参数
                list($anchor, $info['query']) = explode('?', $anchor, 2);
            }
            if (false !== strpos($anchor, '@')) {
                // 解析域名
                list($anchor, $domain) = explode('@', $anchor, 2);
            }
        } elseif (strpos($url, '@') && false === strpos($url, '\\')) {
            // 解析域名
            list($url, $domain) = explode('@', $url, 2);
        }
    }

    // 解析参数
    if (is_string($vars)) {
        // aaa=1&bbb=2 转换成数组
        parse_str($vars, $vars);
    }

    if (isset($info['query'])) {
        // 解析地址里面参数 合并到vars
        parse_str($info['query'], $params);
        $vars = array_merge($params, $vars);
    }

    if (!empty($vars) && !empty($routes[$url])) {

        foreach ($routes[$url] as $actionRoute) {
            $sameVars = array_intersect_assoc($vars, $actionRoute['vars']);

            if (count($sameVars) == count($actionRoute['vars'])) {
                ksort($sameVars);
                $url  = $url . '?' . http_build_query($sameVars);
                $vars = array_diff_assoc($vars, $sameVars);
                break;
            }
        }
    }

    if (!empty($anchor)) {
        $url = $url . '#' . $anchor;
    }

    if (!empty($domain)) {
        $url = $url . '@' . $domain;
    }

    return Url::build($url, $vars, $suffix, $domain);
}

/**
 * 替换编辑器内容中的文件地址
 * @param string $content 编辑器内容
 * @param boolean $isForDbSave true:表示把绝对地址换成相对地址,用于数据库保存,false:表示把相对地址换成绝对地址用于界面显示
 * @return string
 */
function cmf_replace_content_file_url($content, $isForDbSave = false)
{
    import('phpQuery.phpQuery', env('root_path') . 'extend/');
    \phpQuery::newDocumentHTML($content);
    $pq = pq(null);

    $storage       = Storage::instance();
    $localStorage  = new app\common\controller\lib\storage\Local([]);
    $storageDomain = $storage->getDomain();
    $domain        = request()->host();

    $images = $pq->find("img");
    if ($images->length) {
        foreach ($images as $img) {
            $img    = pq($img);
            $imgSrc = $img->attr("src");

            if ($isForDbSave) {
                if (preg_match("/^\/upload\//", $imgSrc)) {
                    $img->attr("src", preg_replace("/^\/upload\//", '', $imgSrc));
                } elseif (preg_match("/^http(s)?:\/\/$domain\/upload\//", $imgSrc)) {
                    $img->attr("src", $localStorage->getFilePath($imgSrc));
                } elseif (preg_match("/^http(s)?:\/\/$storageDomain\//", $imgSrc)) {
                    $img->attr("src", $storage->getFilePath($imgSrc));
                }

            } else {
                $img->attr("src", cmf_get_image_url($imgSrc));
            }

        }
    }

    $links = $pq->find("a");
    if ($links->length) {
        foreach ($links as $link) {
            $link = pq($link);
            $href = $link->attr("href");

            if ($isForDbSave) {
                if (preg_match("/^\/upload\//", $href)) {
                    $link->attr("href", preg_replace("/^\/upload\//", '', $href));
                } elseif (preg_match("/^http(s)?:\/\/$domain\/upload\//", $href)) {
                    $link->attr("href", $localStorage->getFilePath($href));
                } elseif (preg_match("/^http(s)?:\/\/$storageDomain\//", $href)) {
                    $link->attr("href", $storage->getFilePath($href));
                }

            } else {
                if (!(preg_match("/^\//", $href) || preg_match("/^http/", $href))) {
                    $link->attr("href", cmf_get_file_download_url($href));
                }

            }

        }
    }

    $content = $pq->html();

    \phpQuery::$documents = null;


    return $content;

}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return string
 */
function get_client_ip($type = 0, $adv = true, $isToLong = false)
{
    $ip = request::ip($type, $adv);
    return $isToLong ? ipToLong($ip) : $ip;
}

// 加密
function encryptQues($data) {
    $json = json_encode($data);
    $str = openssl_encrypt($json, "AES-128-CBC", config('aes_ak'), OPENSSL_RAW_DATA, config('aes_sk'));
    return base64_encode($str);
}
// 解密
function decryptQues($encryptedRaw) {
    $str = base64_decode($encryptedRaw);
    $json = openssl_decrypt($str, "AES-128-CBC", config('aes_ak'), OPENSSL_RAW_DATA, config('aes_sk'));

    return json_decode($json, true);
}

// 公共model默认回调
function defaultCallBack ($item) {
    return $item;
}

/**
 * 获取当前登录的管理员ID
 * @return int
 */
function get_current_admin_id()
{
    return session('ADMIN_ID');
}

// 查询资源表资源
/**
 * @param $id
 * @return array|mixed|string
 */
function get_resources_by_id ($id, $isReset = true) {
    if (is_array($id) && empty($id)) {
        return [];
    }

    if (!$id) {
        return '';
    }

    if (is_string($id)) {
        if (strpos($id, ',')) {
            $id = explode(',', $id);
        }
    } else {
        return '';
    }

    if (is_array($id)) {
        $paths = [];
        foreach ($id as $idk => $idv) {
            $cacheKey = 'asset_id_' . $idv;
            if (Cache::has($cacheKey)) {
                $paths[$idv] = handle_image_url(Cache::get($cacheKey));
                continue;
            }
            $path = Db::table('asset')->where(['asset_id' => $idv])->value('asset_file_path');
            $path = $path ?? '/static/image/image_lose.png';

            Cache::set($cacheKey, $path);
            $paths[$idv] = handle_image_url($path);
        }
        if ($isReset) {
            $paths= array_values($paths);
        }
        return $paths;
    }

    $cacheKey = 'asset_id_' . $id;
    if (Cache::has($cacheKey)) {
        return handle_image_url(Cache::get($cacheKey));
    }
    $path = Db::table('asset')->where(['asset_id' => $id])->value('asset_file_path');
    $path = $path ?? '/static/image/image_lose.png';

    Cache::set($cacheKey, $path);
    return handle_image_url($path);
}

// 处理文件成可以访问
function handle_image_url ($file) {
    if ($file) {
        if (strpos($file, "http") === 0 || strpos($file, "https") === 0) {
            return $file;
        }

        if (strpos($file, "/") === 0) {
            return get_domain() . $file;
        }

        $url = get_domain() . get_root() . '/' . $file;
        $url = str_replace('\\', '/', $url);

        return $url;
    }
    return $file;
}

// 验证长度
function checkStrLen ($value, $length) {
    return mb_strlen($value) > $length ? true : false;
}

// 是否本地
function is_local() {
    $httpHost = get_client_ip(0, true);
    $localIp = [
        '0.0.0.0',
        'localhost',
        '127.0.0.1',
    ];
    if (in_array(strtolower($httpHost), $localIp)) {
        return true;
    }

    $toks = explode('.', $httpHost);
    if ($toks[0] == '127' || $toks[0] == '10' || $toks[0] == '192') {
        return true;
    }

    return false;
}

// ip处理为字符串形式
function ipToLong ($ip) {
    $long = ip2long($ip);
    if ($long < 1) {
        // return sprintf("%u\n", ip2long($ip));
        return sprintf("%u", ip2long($ip));
    }
    return $long;
}

// 处理查询字段
function handleField (array $field, $alias) {
    if (!is_array($field)) {
        throw new \ErrorException('字段列表异常', -1);
    }
    if (!$alias) {
        throw new \ErrorException('别名错误', -1);
    }
    $fieldMerge = [];
    foreach ($field as $k => $v) {
        if (false === strpos($k, '(') && false === strpos($v, '(')) {
            if (false === strpos($k, '.') && false === strpos($v, '.')) {
                if (!is_numeric($k)) {
                    $fieldMerge[$alias . '.' . $k] = $v;
                } else {
                    $fieldMerge[$k] = $alias . '.' . $v;
                }
            } else {
                $fieldMerge[$k] = $v;
            }
        } else {
            if (false === strpos($k, '(')) {
                $sqlStr = $v;
                $sqlStr1 = strstr($v, '(');
            } else {
                $sqlStr = $k;
                $sqlStr1 = strstr($k, '(');
            }
            // 正则匹配括号中的内容，获取字段
            preg_match_all("/(?:\()(.*)(?:\))/i", $sqlStr1, $result);
            $sqlStr2 = $result[1][0];
            // 拆分处理表名
            $rawField = explode(',', $sqlStr2);
            $rawField = array_map(function ($value) use ($alias) {
                if (false === strpos($value, '.')) {
                    $value = $alias . '.' . $value;
                }

                return $value;
            }, $rawField);
            // 拼装带表名的
            $rawField = implode(',', $rawField);

            // 字符串替换，将不带表名的字段替换为处理过的
            $fieldMerge[] = str_replace($sqlStr2, $rawField, $sqlStr);
        }
    }

    return $fieldMerge;
}

// 获取分类参数
function getPageParams () {
    $pageNum = input('page_num/d', 1);
    $pageSize = input('page_size/d');
    if ($pageSize < 1) {
        $pageSize = config('paginate.list_rows');
    }

    return [$pageNum, $pageSize ? $pageSize : config('default_paginate_rows')];
}

// 处理ajax返回含有分页
function handleApiReturn (array $data) {
    if (isset($data['data'])) {
        $data['page_size'] = $data['per_page'];
        unset($data['per_page']);
        $data['items'] = handleItemImg($data['data']);
        unset($data['data']);

        return $data;
    }

    return ['items' => handleItemImg($data)];
}

// 商品查询基础条件
function productBaseWhere (string $alias = null, bool $isArray = true) : array {
    $alias = ($alias ? $alias . '.' : '');
    if ($isArray) {
        $where = [
            [$alias . 'product_on_sale', '=',  config('on_sale')],
            [$alias . 'product_is_delete', '=',  config('un_deleted')],
        ];
    } else {
        $where = [
            $alias . 'product_on_sale' => config('on_sale'),
            $alias . 'product_is_delete' => config('un_deleted'),
        ];
    }
    return $where;
}

// 分类查询基础条件
function cateBaseWhere (string $alias = null, bool $isArray = true) : array {
    $alias = ($alias ? $alias . '.' : '');
    if ($isArray) {
        $where = [
            [$alias . 'cate_is_delete', '=', config('un_deleted')],
            [$alias . 'cate_is_show', '=', config('show_status_display')],
        ];
    } else {
        $where = [
            $alias . 'cate_is_show' => config('show_status_display'),
            $alias . 'cate_is_delete' => config('un_deleted'),
        ];
    }
    return $where;
}

// 处理图片地址
function handleItemImg (array $data) : array {
    $imgKey = [
        'product_cover_img', 'product_preview_img', 'product_detail_img',
        'cate_icon', 'activity_cover_img',
    ];

    foreach ($imgKey as $key) {
        if (count($data) === count($data, true)) {
            if (isset($data[$key])) {
                $urlKey = $key . '_url';
                if (!isset($data[$urlKey])) {
                    $data[$urlKey] = get_resources_by_id($data[$key]);
                }
                unset($data[$key]);
            }
        } else {
            foreach ($data as $vk => &$vo) {
                if (is_array($vo)) {
                    $vo = handleItemImg($vo);
                } else if (isset($data[$key])) {
                    $urlKey = $key . '_url';
                    if (!isset($data[$urlKey])) {
                        $data[$urlKey] = get_resources_by_id($data[$key]);
                    }
                    unset($data[$key]);
                }
            }
        }
    }

    return $data;
}

/**
 * desription 压缩图片
 *
 * @param string $imgSrc    图片路径
 * @param string $imgDst    压缩后保存路径
 * @param string $maxWidth  最大宽度
 * @param string $quality   质量、压缩图片容量大小
 */
function compressedImage ($imgSrc, $imgDst, $maxWidth, $quality = 90) {
    list($width, $height, $type) = getimagesize($imgSrc);

    if ($width > $maxWidth) {
        $per = $maxWidth / $width;//计算比例
        $new_width = $width * $per;
        $new_height = $height * $per;
    } else {
        // 将旧图cp到新地址
        copy($imgSrc, $imgDst);
        return true;
    }

    switch ($type) {
        case 1:
            $giftype = check_gifcartoon($imgSrc);
            if ($giftype) {
                header('Content-Type:image/gif');
                $image_wp = imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromgif($imgSrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                //90代表的是质量、压缩图片容量大小
                imagejpeg($image_wp, $imgDst, $quality);
                imagedestroy($image_wp);
            }
        break;
        case 2:
            header('Content-Type:image/jpeg');
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($imgSrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            //90代表的是质量、压缩图片容量大小
            imagejpeg($image_wp, $imgDst, $quality);
            imagedestroy($image_wp);
        break;
        case 3:
            header('Content-Type:image/png');
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefrompng($imgSrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            //90代表的是质量、压缩图片容量大小
            imagejpeg($image_wp, $imgDst, $quality);
            imagedestroy($image_wp);
        break;
    }

    return file_exists($imgDst);
}

/**
 * desription 判断是否gif动画
 *
 * @param string $image_file图片路径
 *
 * @return boolean t 是 f 否
 */

function check_gifcartoon ($image_file) {
    $fp = fopen($image_file, 'rb');
    $image_head = fread($fp, 1024);
    fclose($fp);

    return preg_match("/" . chr(0x21) . chr(0xff) . chr(0x0b) . 'NETSCAPE2.0' . "/", $image_head) ? false : true;
}

// 获取小程序配置
function getAppletConfig ($field = null, $default = null) {
    return app()->model('common/AppletConfig')->getAppletConfig($field, $default);
}

// 获取毫秒
function getMillisecond () {
    return (float) sprintf("%.0f", microtime(true) * 1000);
}
<?php

namespace util;

use think\File;
use think\Log;

// 上传文件
class Upload {
    // base64图片的处理
    public static function uploadBase64Image($imgStream, $dir = '', $size = 0) {
        $img = htmlspecialchars_decode(trim($imgStream));
        if (!$img) {
            throw new \Exception("上传图片失败", ErrorCode::PARAMS_ERROR);
        }
        preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result);
        if (!$result) {
            if (!preg_match('/^(data:\s*image\/(\w+);ba-x-se64,)/', $img, $result)) {
                Log::record('[upload-error] img:' . $img);
                throw new \Exception("图片格式错误", ErrorCode::PARAMS_ERROR);
            }
        }

        $type = $result[2];
        if (!in_array($type, array('jpg', 'png', 'jpeg'))) {
            throw new \Exception("上传图片格式错误", ErrorCode::PARAMS_ERROR);
        }

        if (!$size) {
            $size = config('upload_default_size');
        }
        if ($dir) {
            $dir = trim($dir, '/');
            $dir = trim($dir, '\\');
            $dir = 'uploads/' . $dir . '/';
        } else {
            $dir = 'uploads/' . date('Ymd') . '/';
        }
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }

        $filename = date('Ymd') . uniqid() . '.' . $type;
        $filePath = $dir . $filename;

        $imgSize = file_put_contents($filePath, base64_decode(str_replace($result[1], '', $img)));
        if ($imgSize > $size) {
            Log::record('[upload-error] imgSize:' . $imgSize);
            throw new \Exception("图片太大了", ErrorCode::PARAMS_ERROR);
        }
        if (!$imgSize) {
            throw new \Exception("上传图片失败", ErrorCode::PARAMS_ERROR);
        }
        // $filePath = substr($filePath, 7);
        return $filePath;
    }

    public static function uploadImg (File $image, $dir = '', $size = 0) {
        if(!$image){
            throw new \Exception('请上传图片', ErrorCode::PARAMS_ERROR);
        }
        if (!$size) {
            $size = config('upload_default_size');
        }

        $dir = trim($dir, '/');
        $dir = trim($dir, '\\');
        
        $realDir = env('root_path') . 'public/uploads/' . $dir . '/';
        if (!file_exists($realDir)) {
            mkdir($realDir, 0755, true);
        }
        $info = $image->validate(['size' => $size])->move($realDir);
        if ($info) {
            if ($dir) {
                $filePath = 'uploads/' . $dir . '/' . $info->getSaveName();
            } else {
                $filePath =  'uploads/' . $info->getSaveName();
            }
            return str_replace('\\', '/', $filePath);
        } else {
            throw new \Exception($image->getError(), ErrorCode::ADD_FAILED);
        }
    }

    public static function base64ToImage ($imgStream, $title = '图片', $dir = null, $size = 0, $filename = null) {
        // try {
            $img = htmlspecialchars_decode(trim($imgStream));
            if (!$img) {
                throw new \Exception("上传" . $title . "失败", ErrorCode::PARAMS_ERROR);
            }
            
            preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result);
            if (!$result) {
                if (!preg_match('/^(data:\s*image\/(\w+);ba-x-se64,)/', $img, $result)) {
                    Log::record('[upload-error] img:' . $img);
                    throw new \Exception($title . "格式错误", ErrorCode::PARAMS_ERROR);
                }
            }

            $type = $result[2];
            if (!in_array($type, array('jpg', 'png', 'jpeg',))) {
                throw new \Exception("上传" . $title . "格式错误", ErrorCode::PARAMS_ERROR);
            }

            if (!$size) {
                $size = config('upload_file_default_size');
            }
            if ($dir) {
                $dir = 'uploads/' . $dir . '/';
            } else {
                $dir = 'uploads/' . date('Ymd') . '/';
            }
            $path = $dir . '/';
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            if (!$filename) {
                $filename = date('Ymd') . uniqid() . '.' . $type;
            }
            $filePath = $path . $filename;
            $imgSize = file_put_contents($filePath, base64_decode(str_replace($result[1], '', $img)));
            // // 获取详细信息
            // $imgInfo = getimagesize($filePath);
            // $width = $imgInfo[0];
            // $height = $imgInfo[1];
            // if ($width > 800 || $height > 800) {
            //     throw new \Exception("分辨率不合格", ErrorCode::PARAMS_ERROR);
            // }

            if ($imgSize > $size) {
                Log::record('[upload-error] imgSize:' . $imgSize);
                throw new \Exception($title . "太大了", ErrorCode::PARAMS_ERROR);
            }
            if (!$imgSize) {
                throw new \Exception("上传" . $title . "失败", ErrorCode::PARAMS_ERROR);
            }
            return $filePath;
            // 处理成百度api可用的字符串
            // $base64 = str_replace($result[0], '', $img);
            // if (false !== strpos($result[0], $base64)) {
            //     throw new \Exception('图片错误', ErrorCode::PARAMS_ERROR);
            // }

            // return [
            //     'extension' => $type,
            //     'base64Str' => $base64,
            //     'filename'  => $filename,
            //     'abs_path'  => $filePath,
            // ];
        // } catch (\Exception $e) {
        //     ReqResp::outputFail($e);
        // }
    }
}

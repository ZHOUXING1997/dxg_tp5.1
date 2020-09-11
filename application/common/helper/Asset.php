<?php

namespace app\common\helper;

use think\File;
use think\Model;

class Asset extends Model {

    protected $table = 'asset';
    protected $pk = 'asset_id';

    const STATUS_ONE = 1; // 可用
    const STATUS_ZROE = 0; // 不可用

    public function addData (File $file, $filePath) {
        $fileMd5 = $file->hash('md5');
        $fileSha1 = $file->hash('sha1');
        $fileKey = $fileMd5 . md5($fileSha1);

        $where = [
            'asset_file_key' => $fileKey,
            'admin_user_id' => get_current_admin_id() ? get_current_admin_id() : '',
        ];
        $fileExists = $this->checkFileExists($where);
        if (!$fileExists) {
            $data = [
                'admin_user_id' => get_current_admin_id() ? get_current_admin_id() : '',
                'asset_status' => self::STATUS_ONE,
                'asset_file_size' => $file->getInfo('size'),
                'asset_file_key' => $fileKey,
                'asset_filename' => $file->getInfo('name'),
                'asset_file_path' => $filePath,
                'asset_file_md5' => $fileMd5,
                'asset_file_sha1' => $fileSha1,
                'asset_suffix' => pathinfo($filePath)['extension'],
                'asset_create_time' => time(),
            ];

            $info = self::create($data, true);

            return [
                'asset_id' => $info['asset_id'],
                'asset_file_path' => $info['asset_file_path'],
            ];
        }

        if (file_exists($fileExists['asset_file_path'])) {
            return $fileExists;
        }

        return $this->upData($fileExists['asset_id'], $filePath);
    }

    public function upData ($id, $filePath) {
        if ($id && file_exists($filePath)) {
            if (self::where(['asset_id' => $id])->setField('asset_file_path', $filePath)) {
                return [
                    'asset_id' => $id,
                    'asset_file_path' => $filePath,
                ];
            }
            return false;
        }

        return false;
    }

    public function checkFileExists (array $where) {
        $info = self::where($where)->find();
        if ($info) {
            return [
                'asset_id' => $info['asset_id'],
                'asset_file_path' => $info['asset_file_path'],
            ];
        }
        
        return $info;
    }

    public function getImgInfo ($ids) {
        if (is_string($ids) && $ids) {
            if (strpos($ids, ',')) {
                $ids = explode(',', $ids);
            } else {
                $ids = [$ids];
            }
        }
        if (!$ids) {
            return [];
        }

        $data = $this->whereIn('asset_id', $ids)->column('asset_id,asset_filename,asset_file_size,asset_file_path', 'asset_id');

        $result = [];
        foreach ($ids as $v) {
            if (isset($data[$v])) {
                $data[$v]['asset_file_path'] = handle_image_url($data[$v]['asset_file_path']);
                $result[] = $data[$v];
            } else {
                $result[] = [
                    'asset_id' => $v,
                    'asset_filename' => '图片丢失',
                    'asset_file_size' => 0,
                    'asset_file_path' => handle_image_url('/static/image/image_lose.png')
                ];
            }
        }

        return $result;
    }
}
<?php
namespace util;

// 图片管理库
class ImageManager {

    /**
     * Top left of the canvas.
     */
    const TOP_LEFT = 'top-left';
    /**
     * Top center of the canvas.
     */
    const TOP_CENTER = 'top-center';
    /**
     * Top right of the canvas.
     */
    const TOP_RIGHT = 'top-right';
    /**
     * Center left of the canvas.
     */
    const CENTER_LEFT = 'center-left';
    /**
     * Center of the canvas.
     */
    const CENTER = 'center';
    /**
     * Center right of the canvas.
     */
    const CENTER_RIGHT = 'center-right';
    /**
     * Center left of the canvas.
     */
    const BOTTOM_LEFT = 'bottom-left';
    /**
     * Bottom center of the canvas.
     */
    const BOTTOM_CENTER = 'bottom-center';
    /**
     * Bottom right of the canvas.
     */
    const BOTTOM_RIGHT = 'bottom-right';

    public $im;
    private $fontWidth;
    private $fontHeight;

    protected function __construct(\SplFileInfo $file) {
        // 获取图像信息
        $info = getimagesize($file->getPathname());
        //检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new \Exception('Illegal image file');
        }
        //设置图像信息
        $this->info = [
            'width'  => $info[0],
            'height' => $info[1],
            'type'   => image_type_to_extension($info[2], false),
            'mime'   => $info['mime'],
        ];
        //打开图像
        $fun      = "imagecreatefrom{$this->info['type']}";
        $this->im = $fun($file->getPathname());
        if (empty($this->im)) {
            throw new \Exception('Failed to create image resources!');
        }
    }

    // 打开图片
    public static function open($imgName, $imgExt='jpeg') {
        if (is_string($imgName)) {
            $file = new \SplFileInfo($imgName);
        }
        if (!$file->isFile()) {
            throw new \Exception('image file not exist');
        }
        return new self($file);
    }

    /**
     * [text 写入文字]
     * @param  array $parameter keys:size（字体大小）、angle（角度）、x（x坐标）、y（y坐标）、position（文字位置，优先取x,y坐标）、color(颜色)、fontfile（字体文件）
     * @param  [string] $text 写入的文字
     */
    public function text($parameter = [], $text) {
        $size = $parameter['size'];
        $angle = $parameter['angle'];
        $x = isset($parameter['x']) ? $parameter['x'] : 0;
        $y = isset($parameter['y']) ? $parameter['y'] : 0;
        $position = isset($parameter['position']) ? $parameter['position'] : '';
        $fontfile = $parameter['fontfile'];
        $color = $parameter['color'] ? $parameter['color'] : '#000000';
        // 将16进制颜色转为rgb索引
        $rgbColor = $this->hexToRgb($color);
        list($r, $g, $b) = $rgbColor;
        $color = ImageColorAllocate($this->im, $r, $g, $b);
        imagefttext($this->im, $size, $angle, $x, $y, $color, $fontfile, $text);
    }

    /**
     * [getFontWidth 获取字体的长度]
     * @param  [string] $string 文字
     * @param  [string] $fontPath 字体文件路径
     * @param  [integer] $fontSize 字体大小
     * @param  [integer] $angle 角度
     * @return [integer] 字体宽度
     */
    public function setFontWidthHeight($text, $fontPath, $fontSize, $angle = 0){
        if (empty($text)) {
            return 0;
        }
        $fontCoordinate = imagettfbbox($fontSize, $angle, $fontPath, $text);
        $this->fontWidth = abs($fontCoordinate[2] - $fontCoordinate[0]);
        $this->fontHeight = abs($fontCoordinate[5] - $fontCoordinate[3]);
        return [
            'width' => $this->fontWidth,
            'height' => $this->fontHeight,
        ];
    }

    /**
     * [imgMerge 图片合成]
     * @param  [resource] $img1
     * @param  [resource] $img2
     * @param  string $position 图片位置
     * @param  integer $offsetX 偏移量，作用在position
     * @param  integer $offsetY 偏移量，作用在position
     * @param  integer $x  x坐标
     * @param  integer $y  y坐标
     * @param  integer $pct 透明度 0-100
     */
    public function imgMerge($img1, $img2, $x = 0, $y = 0, $position = '', $offsetX = 0, $offsetY = 0, $pct = 100) {
        $dst = $this->getXy($x, $y, $position, $img1, $img2, $offsetX, $offsetY);
        imagecopymerge($img1, $img2, $dst[0], $dst[1], 0, 0, imagesx($img2), imagesy($img2) , $pct);
    }

    // 保存
    public function save($filePath) {
        imagepng($this->im, $filePath);
    }

    // 获取图片宽度
    public function getWidth() {
        return $this->info['width'];
    }

    // 获取图片高度
    public function getHeight() {
        return $this->info['height'];
    }

    private function getXy($x = 0, $y = 0, $position = '', $img1, $img2, $offsetX, $offsetY) {
        if (!$position) {
            return [$x, $y];
        }
        if ($x != 0 || $y != 0) {
            return [$x, $y];
        }
        $canvasWidth = imagesx($img1);
        $canvasHeight = imagesy($img1);
        $imageWidth = imagesx($img2);
        $imageHeight = imagesy($img2);
        return $this->getCoordinate($canvasWidth, $canvasHeight, $imageWidth, $imageHeight, $position, $offsetX, $offsetY);
    }

    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#'); // remove #
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }

    /**
     * [getCoordinate 获取坐标]
     * @param  [integer] $canvasWidth 画布宽
     * @param  [integer] $canvasHeight 画布高
     * @param  [integer] $imageWidth 写入资源宽
     * @param  [integer] $imageHeight 写入资源高
     * @param  [string] $position 定位
     * @param  [integer] $offsetX 偏移量x
     * @param  [integer] $offsetY 偏移量y
     * @return [array] [x, y]
     */
    private function getCoordinate($canvasWidth, $canvasHeight, $imageWidth, $imageHeight, $position, $offsetX = 0, $offsetY = 0) {
        switch ($position) {
            case self::TOP_LEFT:
                $x = 0;
                $y = 0;
                break;
            case self::TOP_CENTER:
                $x = (int)round(($canvasWidth / 2) - ($imageWidth / 2));
                $y = 0;
                break;
            case self::TOP_RIGHT:
                $x = $canvasWidth - $imageWidth;
                $y = 0;
                break;
            case self::CENTER_LEFT:
                $x = 0;
                $y = (int)round(($canvasHeight / 2) - ($imageHeight / 2));
                break;
            case self::CENTER_RIGHT:
                $x = $canvasWidth - $imageWidth;
                $y = (int)round(($canvasHeight / 2) - ($imageHeight / 2));
                break;
            case self::BOTTOM_LEFT:
                $x = 0;
                $y = $canvasHeight - $imageHeight;
                break;
            case self::BOTTOM_CENTER:
                $x = (int)round(($canvasWidth / 2) - ($imageWidth / 2));
                $y = $canvasHeight - $imageHeight;
                break;
            case self::BOTTOM_RIGHT:
                $x = $canvasWidth - $imageWidth;
                $y = $canvasHeight - $imageHeight;
                break;
            case self::CENTER:
                $x = (int)round(($canvasWidth / 2) - ($imageWidth / 2));
                $y = (int)round(($canvasHeight / 2) - ($imageHeight / 2));
                break;
            default:
                throw new \Exception(sprintf( 'Invalid position "%s".', $position ) );
                break;
        }
        return [
            $x + $offsetX,
            $y + $offsetY
        ];
    }

    public function __destruct() {
        imagedestroy($this->im);
    }
}

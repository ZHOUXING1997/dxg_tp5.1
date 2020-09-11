;

var global = {
    // 商品相关
    product : {
        product_detail_img_num_max: 20,
        product_preview_img_num_max: 15,
    },

    // 活动相关
    activity: {
        product_num_max: 3,
    },

    // 指定允许上传时校验的文件类型，可选值有：
    // images（图片）、file（所有文件）、video（视频）、audio（音频）
    up_file_option: {
        up_file_default_size_max: 1024 * 3,

        up_file_default_accept: 'file',
        up_file_video_accept: 'video',
        up_file_audio_accept: 'audio',
        up_file_images_accept: 'images',

        up_image_default_acceptMime: 'image/jpeg, .png, .bmp',
        up_image_default_suffix: 'jpg|jpeg|png|bmp',
        up_image_all_acceptMime: 'image/*',

        up_video_all_acceptMime: 'video/*',

        up_audio_all_acceptMime: 'audio/*',
    },

};
;

// form表单使用ajax提交
/*
* @params object    data            layuiFormData
* @params function  succCallBack    成功回调函数
* @params function  errorCallBack   失败回调函数
* */
function formAjax(data, succCallBack, errorCallBack) {
    var formData = data.field;
    var method = data.form.method ? data.form.method : 'post';
    var url = data.form.action;
    $.ajax({
        url: url,
        type: method,
        data: formData,
        beforeSend: function () {
            this.layerIndex = layer.load(0, {shade: [0.5, '#393D49']});
        },
        success: function (result, statusText, xhr, $form) {
            if (result.code == 0) {
                layer.msg(result.msg, {icon: 1, time: 2000});
                if (result.data && result.data.url) {
                    location.href = result.data.url;
                } else {
                    if (succCallBack && typeof (succCallBack) == "function") {
                        succCallBack(result);
                    } else {
                        window.parent.location.reload();
                    }
                }
            } else {
                layer.msg(result.msg, {icon: 2, time: 2000});
                if (errorCallBack && typeof (errorCallBack) == "function") {
                    errorCallBack(result);
                } else {
                    window.parent.location.reload();
                }
            }
        },
        error: function (error) {
        },
        complete: function (result) {
            layer.close(this.layerIndex);
        },
    });
}

// ajax提交
/*
* @params url       url             Url
* @params object    data            提交数据
* @oarams string    method          提交方式
* @params function  succCallBack    成功回调函数
* @params function  errorCallBack   失败回调函数
* */
function ajaxCommon(url, data, method = 'post', succCallBack, errorCallBack, isShowToast = true) {
    $.ajax({
        url: url,
        type: method,
        data: data,
        beforeSend: function () {
            this.layerIndex = layer.load(0, {shade: [0.5, '#393D49']});
        },
        success: function (result, statusText, xhr, $form) {
            if (result.code == 0) {
                if (isShowToast) {
                    layer.msg(result.msg, {icon: 1, time: 2000});
                }
                if (result.data && result.data.url) {
                    location.href = result.data.url;
                } else {
                    if (succCallBack && typeof (succCallBack) == "function") {
                        succCallBack(result);
                    } else {
                        window.parent.location.reload();
                    }
                }
            } else {
                if (isShowToast) {
                    layer.msg(result.msg, {icon: 2, time: 2500});
                }
                if (errorCallBack && typeof (errorCallBack) == "function") {
                    errorCallBack(result);
                } else {
                    window.parent.location.reload();
                }
            }
        },
        error: function (error) {
        },
        complete: function (result) {
            layer.close(this.layerIndex);
        },
    });
}

// 刷新页面数据
function refreshViewData (tableElemId, formElemId) {
    if (!tableElemId) {
        window.location.reload();
    }
    layui.use(['form', 'table'], function () {
        var option = {};
        var formElem = formElemId ? '#' + formElemId : 'form';
        var formOption = $(formElem).serializeArray();
        $.each(formOption, function() {
            option[this.name] = this.value;
        });

        var table = layui.table;
        table.reload(tableElemId, {
            where: option,
        });
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });
}

// 单文件上传
function upFile(elem, url, fileSize, accept, acceptMime, suffix) {
    layui.use('upload', function () {
        var $ = layui.jquery
            , upload = layui.upload;
        //普通图片上传
        var uploadInst = upload.render({
            elem: elem,
            url: url,
            size: fileSize,
            acceptMime: acceptMime,
            accept: accept, // 文件类型 file|普通文件， video|视频， audio|音频
            exts: suffix, //只允许上传压缩文件
            choose: function (obj) {
                //预读本地文件示例，不支持ie8
                obj.preview(function (index, file, result) {
                    // console.log(result);
                    $(elem).parent('.layui-upload').children('div:first').find('img:first').attr('src', result); //图片链接（base64）
                });
            },
            done: function (res) {
                if (res.code == 0) {
                    layer.msg(res.msg, {icon: 1, time: 2000});
                    $(elem).parent('.layui-upload').children('div:first').find('input:first').val(res.data.asset_id); //图片链接（base64）
                } else {
                    layer.msg(res.msg, {icon: 2, time: 2000});
                }

                layer.photos({
                    photos: $(elem).parent('.layui-upload').children('div:first'),
                });
            },
            error: function () {
                //演示失败状态，并实现重传
                var demoText = $('#demoText');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs up-file-reload">重试</a>');
                demoText.find('.up-file-reload').on('click', function () {
                    uploadInst.upload();
                });
            }
        });
    });
}

//多图片上传
function upManyFile(elem, imageList, url, formKey, startAction, fileNum, fileSize, accept, acceptMime, suffix) {
    // console.log(fileNum);
    layui.use('upload', function () {
        var $ = layui.jquery,
            upload = layui.upload;
        //多文件列表示例
        var imageListView = $(imageList);
        var uploadListIns = upload.render({
            elem: elem,
            url: url,
            size: fileSize,
            accept: accept, // 文件类型 file|普通文件， video|视频， audio|音频
            acceptMime: acceptMime, // 可选择类型
            exts: suffix, // 文件后缀
            multiple: true,     // 是否开启多文件
            auto: true,      // 是否开启自动上传
            bindAction: startAction,
            choose: function (obj) {
                var files = this.files = obj.pushFile(); //将每次选择的文件追加到文件队列
                //读取本地文件
                obj.preview(function (index, file, result) {
                    // 判断图片数量
                    if (imageListView.children().length >= (fileNum)) {
                        layer.msg(`最多可以上传张${fileNum}图片`, {icon: 7, time: 2000});
                        delete files[index]; //删除对应的文件
                        return false;
                    }

                    // 移动按钮
                    var moveStr = `<button type="button" class="layui-btn layui-btn-xs btn-move-up">上移</button>
                        <button type="button" class="layui-btn layui-btn-xs btn-move-down">下移</button>`;

                    // 预览信息
                    var tr = $([
                        `<tr id="upload-${index}">
                                 <td>${file.name}</td>
                                 <td><img height="80px" src="${result}" alt="${file.name}" class="layui-upload-img"></td>
                                 <td>${(file.size / 1014).toFixed(1)}kb</td>
                                 <td><span class="up-image" data-up="false">等待上传</span></td>
                                 <td>
                                    <button type="button" class="layui-btn layui-btn-xs up-file-reload layui-hide">重传</button>
                                    <button type="button" class="layui-btn layui-btn-xs layui-btn-danger img-delete">删除</button>
                                    ${moveStr}
                                 </td>
                             </tr>`
                    ].join(''));

                    //单个重传
                    tr.find('.up-file-reload').on('click', function () {
                        obj.upload(index, file);
                    });

                    //删除
                    tr.find('.img-delete').on('click', function () {
                        delete files[index]; //删除对应的文件
                        // 删除节点
                        tr.remove();
                        uploadListIns.config.elem.next()[0].value = ''; //清空 input file 值，以免删除后出现同名文件不可选
                        // 更新图片数量
                        $(elem).siblings('.file-count').text(imageListView.children().length);
                    });

                    // 插入信息
                    imageListView.append(tr);
                    // 更新图片数量
                    $(elem).siblings('.file-count').text(imageListView.children().length);
                });
            }
            , done: function (res, index, upload) {
                if (res.code == 0) { //上传成功
                    var tr = imageListView.find('tr#upload-' + index)
                        , tds = tr.children();
                    // 更改文本内容
                    tds.eq(3).html('<span class="up-image" data-up="true" style="color: #5FB878;">上传成功</span>');
                    // 插入form数据
                    tds.eq(4).append(`<input type="hidden" name="${formKey}[]" value="${res.data.asset_id}">`); //清空操作
                    return delete this.files[index]; //删除文件队列已经上传成功的文件
                }
                this.error(index, upload);
            }
            , error: function (index, upload) {
                var tr = imageListView.find('tr#upload-' + index)
                    , tds = tr.children();
                tds.eq(3).html('<span style="color: #FF5722;">上传失败</span>');
                tds.eq(4).find('.up-file-reload').removeClass('layui-hide'); //显示重传
            }
        });
    });
}

// 图片列表渲染
function imgList (elem, data, formKey) {
    var length = data ? data.length : 0;
    $(elem).parents('.img-list').prev().children('.file-count').text(length);

    var imageListView = $(elem);
    // 如果没有图片更改提示
    if (length <= 0) {
        var tr = `<tr>
                        <td colspan="5">您还没有上传图片</td>
                    </tr>`;
        imageListView.append(tr);
    } else {
        // 渲染预览信息
        data.forEach(function(v,i){
            /*var moveStr = '';
            if (i <= 0) {
                moveStr = `<button type="button" class="layui-btn layui-btn-xs btn-move-down">下移</button>`;
            } else if (i >=4) {
                moveStr = `<button type="button" class="layui-btn layui-btn-xs btn-move-up">上移</button>`;
            } else {
                moveStr = `<button type="button" class="layui-btn layui-btn-xs btn-move-up">上移</button>
                        <button type="button" class="layui-btn layui-btn-xs btn-move-down">下移</button>`;
            }*/
            var moveStr = `<button type="button" class="layui-btn layui-btn-xs btn-move-up">上移</button>
                        <button type="button" class="layui-btn layui-btn-xs btn-move-down">下移</button>`;
            var tr = $([
                `<tr id="upload-${i}">
                     <td>${v.asset_filename}</td>
                     <td><img height="80px" src="${v.asset_file_path}" alt="${v.asset_filename}" class="layui-upload-img"></td>
                     <td>${(v.asset_file_size / 1014).toFixed(1)}kb</td>
                     <td><span class="up-image" data-up="${v.asset_id < 1 ? 'false' : 'true'}">${v.asset_id < 1 ? '丢失' : '已上传'}</span></td>
                     <td class="td-action">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-danger img-delete">删除</button>
                        ${moveStr}
                        <input type="hidden" name="${formKey}[]" value="${v.asset_id}">
                     </td>
                 </tr>`
            ].join(''));

            //删除
            tr.find('.img-delete').on('click', function () {
                tr.remove();
                $(elem).siblings('.file-count').text(imageListView.children().length);
            });

            imageListView.append(tr);
        });
    }
}
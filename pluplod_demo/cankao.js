//实例化一个plupload上传对象
var uploader = new plupload.Uploader({
    runtimes: 'html5,flash,silverlight,html4',
    browse_button: 'pickfiles',
    // you can pass an id...
    container: document.getElementById('container'),
    // ... or DOM Element itself
    url: 'up.php',
    flash_swf_url: '../js/Moxie.swf',
    silverlight_xap_url: '../js/Moxie.xap',
    multi_selection: true,
    //是否可以在文件浏览对话框中选择多个文件，true为可以，false为不可以
    unique_names: true,
    filters: {
        max_file_size: '10mb',
        mime_types: [{
            title: "Image files",
            extensions: "jpg,gif,png"
        },
            {
                title: "Zip files",
                extensions: "zip"
            }],
        prevent_duplicates: true //不允许选取重复文件
    },
    init: {
        PostInit: function() {
            document.getElementById('filelist').innerHTML = '';

            document.getElementById('uploadfiles').onclick = function() {
                uploader.start();
                return false;
            };
        },

        FilesAdded: function(up, files) {
            plupload.each(files,
                function(file) {
                    //document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
                    var html = '<li id="file-' + file.id + '" delid=' + file.id + '><p class="file-name">' + file.name + '</p><p></p></li>';
                    $(html).appendTo('#file-list');
                    previewImage(file,
                        function(imgsrc) {
                            $('#file-' + file.id).append('<img src="' + imgsrc + '" />');
                        })
                });
        },

        UploadProgress: function(up, file) {
            document.getElementById('file-' + file.id).getElementsByTagName('p')[1].innerHTML = '<span>' + file.percent + "%</span>";
        },

        Error: function(up, err) {
            document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
        }
    }
});

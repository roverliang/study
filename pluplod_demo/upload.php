<?php
/**
 * 这是个上传测试文件，作为研究分片上传原理使用
 *
 */

if (!is_array($_FILES) || empty($_FILES)) {
    die();  //输出报错的话术
}

$destication = "/usr/local/var/www/uploads/";     //上传文件的最终文件夹
$destication_frag_path = "/usr/local/var/www/uploads_tmp/";  //分片上传的临时文件夹


if (!is_dir($destication) || !is_dir($destication_frag_path)) {
   @mkdir($destication, 0755);
   @mkdir($destication_frag_path, 0755);
}


if ($_REQUEST['chunks'] == 1) {
    //文件很小，无需分片上传。
    $tmp_file_path = $_FILES['file']['tmp_name'];  //上传的临时文件
    $save_file_name = $destication.$_REQUEST['name'];
    move_uploaded_file($tmp_file_path,  $save_file_name);
} else {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis_key = $_REQUEST['name'];

    $file_name     = explode('.', $_REQUEST['name']);
    $save_tmp_name = $destication_frag_path.$file_name[0]."_".$_REQUEST['chunk'];   //文件名拼接成第几块
    $tmp_file_path = $_FILES['file']['tmp_name'];           //上传的临时文件
    move_uploaded_file($tmp_file_path, $save_tmp_name);

    $redis->setTimeout($redis_key, 3600);  //一个小时后过期
    $redis->zAdd($redis_key, $_REQUEST['chunk'], $save_tmp_name);
    $uploaded_count = $redis->zCard($redis_key);

    //分片资源上传完毕后，开始分片合并工作
    if ($uploaded_count == $_REQUEST['chunks']) {

        //获取经过排序后的分片资源
        $all_files_fen_pian = $redis->zRange($redis_key, 0, -1);

        if ($all_files_fen_pian && is_array($all_files_fen_pian)) {

            //创建要合并的最终文件资源
            $final_file         = $destication.$_REQUEST['name'];
            $final_file_handler = fopen($final_file, 'wb');

            //开始合并文件分片
            foreach ($all_files_fen_pian as $fragmentation_file) {
                $frag_file_handler  = fopen($fragmentation_file, 'rb');
                $frag_file_content = fread($frag_file_handler, filesize($fragmentation_file));
                fwrite($final_file_handler, $frag_file_content);

                unset($frag_file_content);
                fclose($frag_file_handler);      //销毁分片文件资源
                unlink($fragmentation_file);     //删除已经合并的分片文件
                usleep(10000);
            }
        }
    }
}




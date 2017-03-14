<?php
require('./func.php');


if (!empty($_POST)) {
    // 开始处理用户请求
    $code = $_POST['code'];
    $mode = $_POST['mode'];
    if ($mode == 'normal') {
        $result = origin_query($code);
        $result = mb_split("\n", $result);
        $result = implode('<br />', $result);
        echo $result;
    } elseif ($mode == 'magnet') {
        // 只查询磁链
        echo get_magnet($code);
    } else {
        // 暂时没有第三种情况了
        exit;
    }
} else {
    // 非法访问
    exit;
}
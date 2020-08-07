<?php 
//git webhook 自动部署脚本
$requestBody = file_get_contents("php://input"); //该方法可以接收post传过来的json字符串
if (empty($requestBody)) { //判断数据是不是空
    exit('send fail');
}
// file_put_contents("debug.txt", $requestBody . PHP_EOL, FILE_APPEND);
$p = isset($_GET['project']) ? $_GET['project'] : '';
// file_put_contents("debug.txt", $p . PHP_EOL, FILE_APPEND);
if (empty($p)) { //判断数据是不是空
    exit('project fail');
}


$content = json_decode($requestBody, true); //数据转换
//若是主分支且提交数大于0
if ($content['ref'] == 'refs/heads/master') {
    $signature = isset($_SERVER['HTTP_X_HUB_SIGNATURE']) ? $_SERVER['HTTP_X_HUB_SIGNATURE'] : '';
    // file_put_contents("debug.txt", json_encode($_SERVER) . PHP_EOL, FILE_APPEND);
    if (!verify_signature($signature, $requestBody, $p)) {
        exit('verify failure');
    }
    //PHP函数执行git命令
    $project = getProject($p, 'path');
    $res = shell_exec('cd ' . $project . '
               && git reset --hard origin/master 2>&1 && git clean -f
               && git pull 2>&1 && git checkout master');

    $res_log = '-------------------------' . PHP_EOL;
    $res_log.= ' 在' . date('Y-m-d H:i:s') . '向' . $content['repository']['name']
               . '项目的' . $content['ref'] . '分支push' . $res;
    //将每次拉取信息追加写入到hook.txt日志里
    file_put_contents("hook.txt", $res_log, FILE_APPEND);
    exit('success');
} else {
    exit('error');
}

function verify_signature($signature, $payload_body, $project) {
    $key = getProject($project, 'secret');
    $verify_signature = 'sha1=' . hash_hmac('sha1', $payload_body, $key);
    return $signature === $verify_signature;
}

/**
 * 针对多项目自动部署，可以设置不同的自动hook路径，请求的时候get传参加个project值获取
 */
function getProject($p, $field=false) {
    $keys = [
        'default' => [
            'path' => '/www/wwwroot/test',
            'secret' => ''
        ],
        'video' => [
            'path' => 'v.yifangchen.cn',
            'secret' => 'control8888video'
        ]
    ];
    $project = isset($keys[$p]) ? $keys[$p] : $keys['default'];
    if ($field) {
        return isset($project[$field]) ? $project[$field] : '';
    } else {
        return $project;
    }
}

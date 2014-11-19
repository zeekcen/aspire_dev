<?php

if ($argc < 3) {
    die("Error:No enough arguments\nusage:\n\tphp collector.php biz logtype\n");
}

$biz = $argv[1];
$logtype = $argv[2];

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/class/setlog.class.php';
require_once dirname(__FILE__) . '/class/collector.class.php';
require_once dirname(__FILE__) . '/../lib/pheanstalk/pheanstalk_init.php';

// 日志收集配置信息
$collector = new Collector($config);
$logtypes = $collector->get_logtypes();

// 检查参数是否正确
if ( ! array_key_exists($biz, $logtypes)) {
	die("Error:biz {$biz} is invalid\n".print_r($logtypes, TRUE));
}

if ( ! in_array($logtype, $logtypes[$biz])) {
	die("Error:logtype {$logtype} is invalid\n".print_r($logtypes, true));
}

define('BEAN_HOST', $config['collector_host']);
define('BEAN_PORT', 11300);

define('COLLECTOR_HOST', $config['collector_host']);
define('DATA_PATH', $config['data_path']);

// 收集日志类列表
$setlog = new Setlog();
$logger = $setlog->get_log_instance($logtype);

$pheanstalk = new Pheanstalk(BEAN_HOST, BEAN_PORT);
while ($job = $pheanstalk->watch('queue'.$biz.$logtype)->reserve()) {
	$res = $job->getData();
	$arr = json_decode($res, TRUE);

	$type = $arr['type'];
	$content = trim($arr['content']);
	// 如果日志记录为空，则跳过
	if ( ! $content) continue;
	// 写入日志文件
	receiver($logger, $content, $biz, $logtype);

	//删除本次任务
	$pheanstalk->delete($job);
}

// -----------------------------func--------------------------------
function receiver($logger, $content, $biz, $logtype) {
	$logger->split_by_day($content, $biz, $logtype);
}

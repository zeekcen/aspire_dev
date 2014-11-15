<?php
require_once dirname(__FILE__) . '/../lib/pheanstalk/pheanstalk_init.php';
define('BEAN_HOST', '127.0.0.1');
define('BEAN_PORT', '11300');

$setlog = new Setlog();
$signup_log = $setlog->get_log_instance('signup');
$upload_log = $setlog->get_log_instance('upload');

$pheanstalk = new Pheanstalk(BEAN_HOST, BEAN_PORT);
while ( $job = $pheanstalk->watch('queue'.$type)->reserve() )
{
	$res = $job->getData();
	$arr = json_decode($res, TRUE);

	$type = $arr['type'];
	$content = trim($arr['content']);

	if (!$content) continue;

	switch ($type) {
		case '100':
			$signup_log->split_by_hour($content, 'www.raidcall.com.br', 'br');
			break;
		case '101':
			$signup_log->split_by_hour($content, 'api1.raidcall.com.br', 'br');
			break;
		default:
			log_message('other: log - '.$content);
			break;
	}
	//删除本次任务
	$pheanstalk->delete($job);
}

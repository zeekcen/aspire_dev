<?php
// 操作类型数组
$opt_type_arr = array(
		'首次关注',
		'取消更注',
		'首次绑定手机',
		'取消绑定',
		'发送消息',
		'重新关注',
		'重新绑定手机'
		);

// 交互类型数组
$interact_arr = array(
		'文本消息',
		'菜单点击',
		'图片',
		'位置',
		'视频',
		'语音',
		'关键字'
		);

// 访问url类型
$visit_url_arr = array(
		'关注',
		'取消更注',
		'绑定手机',
		'取消绑定'
		);

// 活动类型
$act_arr = array(
		'抽奖',
		'红包',
		'刮刮卡'
		);

// 菜单key数组
$menu_key_arr = array(
		'RWOLPYTC',
		'SVSCCJHY',
		'TMAZOPGX',
		'TUADUIDA',
		'UCAVZIDL',
		'ULTHGBRZ',
		'VZUKHAHM',
		'WONKMRCK',
		'XDTOBABK',
		'XKLCBUTX',
		'XQQKIPLG',
		'XTVRWHJF'
		);

// 关键词数组
$keyword_arr = array(
		'双十一',
		'英超',
		'忍者神龟',
		'银河护卫队',
		'普吉岛',
		'婚纱摄影'
		);

// 二维码描述
$qrcode_desc_arr = array(
		'AAA市',
		'BBB市',
		'DEF市',
		'CCC市',
		);

$start_time = "2014-11-01 00:05:00";
$stamp = strtotime($start_time);
$now = time() + 5*3600;

$branch = '东莞';
$merchant_id = '0769';
$merchant_code = 'dg';

do {
	$datetime = date('Y-m-d H:i:00', $stamp);
	//echo $datetime."\n";
	$stamp = $stamp + 1*60;
	$user_id = mt_rand(1, 99999);
	$open_id = md5($user_id);
	$phone = str_pad($user_id, 11, '1', STR_PAD_LEFT);
	$ctime = date('YmdHis', $stamp);
	$date = date('Ymd', $stamp);
	// 生成用户操作日志
	$idx = mt_rand(0, 5);
	$opt = $opt_type_arr[$idx];
	$idx = mt_rand(0, 6);
	$act = $interact_arr[$idx];

	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $opt, $ctime, $act);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'operate', $line, $date);
	// 生成用户访问日志
	$idx = mt_rand(0, 3);
	$visit_url = $visit_url_arr[$idx];
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $visit_url, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'visit', $line, $date);
	// 生成活动记录日志
	$idx = mt_rand(0, 2);	
	$act = $act_arr[$idx];
	$status = mt_rand(0, 2);
	$send_user_id = mt_rand(1, 99999);
	$rece_user_id = mt_rand(1, 99999);
	$share_num = uniqid();
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $act, $status, $send_user_id, $rece_user_id, $share_num, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'activity', $line, $date);
	// 生成菜单统计日志
	$idx = mt_rand(0, 11);
	$menu_key = $menu_key_arr[$idx];
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $menu_key, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'menu', $line, $date);
	// 生成关键词统计日志
	$idx = mt_rand(0, 5);
	$keyword = $keyword_arr[$idx];	
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $keyword, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'keyword', $line, $date);
	// 生成二维码统计日志
	$idx = mt_rand(0, 3);
	$desc = $qrcode_desc_arr[$idx];
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $desc, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'qrcode', $line, $date);	
	usleep(20);
}while($stamp < $now);

function write_log($merchant_code, $log_type, $line, $date) {
	$path = '/home/benny/log';
	$ip = '10.9.113.128';
	$filename = "{$path}/{$merchant_code}_{$log_type}_{$ip}_{$date}.log";
	file_put_contents($filename, $line."\r\n", FILE_APPEND);
}

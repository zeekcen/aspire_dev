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

// 访问url
$visit_url_arr = array(
		'http://www.36kr.com/p/217017.html?ref=index_feature_topics',
		'http://www.primesplus.com/zh/',
		'http://socketo.me/docs/hello-world',
		'https://packagist.org/packages/monolog/monolog'
		);

// 页面标题
$page_title_arr = array(
		'撸管',
		'iphone6',
		'积分购机',
		'饭盒'
	);

// 活动url
$act_url_arr = array(
	'http://www.baidu.com',
	'http://www.36kr.com',
	'http://www.qq.com'
);

// 活动标题
$act_title_arr = array(
	'百度',
	'36氪',
	'腾讯'
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

// 菜单名称数组
$menu_name_arr = array(
	'菜单1',
	'菜单2',
	'菜单3',
	'菜单4',
	'菜单5',
	'菜单6',
	'菜单7',
	'菜单8',
	'菜单9',
	'菜单10',
	'菜单11',
	'菜单12'
);

// 父菜单名称数组
$parent_name_arr = array(
	'父菜单1',
	'父菜单2',
	'父菜单3'
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
$now = time() - 11*86400;

$branch = '2';
$merchant_id = '0769';
$merchant_code = 'dg';

do {
	$datetime = date('Y-m-d H:i:00', $stamp);
	//echo $datetime."\n";
	$stamp = $stamp + 1*2;
	$user_id = mt_rand(1, 99999);
	$open_id = md5($user_id);
	$phone = str_pad($user_id, 11, '1', STR_PAD_LEFT);
	$ctime = date('YmdHis', $stamp);
	$date = date('Ymd', $stamp);
	$visit_src = mt_rand(1,2); // 1.内部访问;2.外部访问
	$user_type = mt_rand(0,1); 
	// 生成用户操作日志
	$idx = mt_rand(0, 5);
	$opt = $idx + 1;
	$idx = mt_rand(0, 6);
	$act = $idx + 1;
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $opt, $ctime, $act);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'operate', $line, $date);
	// 生成用户访问日志
	$idx = mt_rand(0, 3);
	$page_title = $page_title_arr[$idx];
	$visit_url = $visit_url_arr[$idx];
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $user_type, $visit_src, $page_title, $visit_url, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'visit', $line, $date);
	// 生成活动记录日志
	$idx = mt_rand(0, 2);	
	$act_id = $idx + 1;
	$act = $idx + 1;
	$act_url = $act_url_arr[$idx];
	$act_title = $act_title_arr[$idx];
	$status = mt_rand(0, 2);
	$send_user_id = mt_rand(1, 99999);
	$rece_user_id = mt_rand(1, 99999);
	$share_num = uniqid();
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $user_type, $open_id, $phone, $visit_src, $act_id, $act_url, $act_title, $act, $status, $send_user_id, $rece_user_id, $share_num, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'activity', $line, $date);
	// 生成菜单统计日志
	$idx = mt_rand(0, 11);
	$menu_key = $menu_key_arr[$idx];
	$menu_name = $menu_name_arr[$idx];
	$idx = mt_rand(0, 2);
	$parent_name = $parent_name_arr[$idx];
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $menu_key, $menu_name, $parent_name, $ctime);
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
	$qrcode_id = $idx + 1;
	$desc = $qrcode_desc_arr[$idx];
	$qrcode_type = mt_rand(1, 2);	// 1.扫码进入；2.扫码关注
	$log_arr = array($branch, $merchant_id, $merchant_code, $user_id, $open_id, $phone, $qrcode_id, $desc, $qrcode_type, $ctime);
	$line = implode('|', $log_arr);
	write_log($merchant_code, 'qrcode', $line, $date);	
	usleep(10);
}while($stamp < $now);

function write_log($merchant_code, $log_type, $line, $date) {
	$path = '/home/benny/log';
	$ip = '10.9.113.128';
	$filename = "{$path}/{$merchant_code}_{$log_type}_{$ip}_{$date}.log";
	file_put_contents($filename, $line."\r\n", FILE_APPEND);
}

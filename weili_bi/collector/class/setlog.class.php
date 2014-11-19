<?php
require_once(dirname(__FILE__) . '/operate.class.php');
/*
require_once(dirname(__FILE__) . '/activity.class.php');
require_once(dirname(__FILE__) . '/visit.class.php');
require_once(dirname(__FILE__) . '/menu.class.php');
require_once(dirname(__FILE__) . '/keyword.class.php');
require_once(dirname(__FILE__) . '/qrcode.class.php');
*/
interface LogFactory{
    public function get_log_instance($type);
}

class Setlog implements LogFactory{
	public function get_log_instance($type) {
		switch($type) {
			case 'operate':
				// 返回操作日志类
				return new Operate();
/*
			case 'activity':
				// 返回活动日志类
				return new Activity();
			case 'visit':
				// 返回访问日志类
				return new Visit();
			case 'menu':
				// 返回菜单日志类
				return new Menu();
			case 'keyword':
				// 返回关键词日志类
				return new Keyword();
			case 'qrcode':
				// 返回二维码日志类
				return new Qrcode();
*/
			default:
				return FALSE;
		}
	}
}

<?php
require_once(dirname(__FILE__).'/Upload.class.php');
require_once(dirname(__FILE__).'/Signup.class.php');

interface LogFactory{
    public function get_log_instance($type);
}

class Setlog implements LogFactory{
	public function get_log_instance($type) {
		switch($type) {
			case 'upload':
				// 返回客户端用户行为上传日志类
				return new Upload();
			case 'signup':
				// 返回注册日志类
				return new Signup();
		}
	}
}

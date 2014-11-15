<?php
require_once(dirname(__FILE__).'/SplitLog.class.php');

class Signup extends SplitLog{
	/**
	 * 分时注册日志存储路径数组
	 * @var array
	 */
	private $_hour_dir_arr = array();

	function __construct() {
		$this->_hour_dir_arr['br'] = SPLIT_LOG_PATH . '/brazil/signup_hour';
		$this->_hour_dir_arr['ru'] = SPLIT_LOG_PATH . '/russia/signup_hour';
	}

	/**
	 * 按小时分割日志
	 * @param  [type] $line    [description]
	 * @param  [type] $domain  [description]
	 * @param  string $version [description]
	 * @return [type]          [description]
	 */
	public function split_by_hour($line, $domain, $version='br') {
		$log_path = $this->_hour_dir_arr[$version];
		$ret = $this->check_dir($log_path);
		if ( ! $ret) die($log_path.' can not be created!');
		// 从日志中找出日志所属的分时
		$line = trim($line);
		$parts = explode("\t", $line);
		// 按小时存储到日志文件中
		$datetime = $parts[0];
		$date = substr($datetime, 0, 10);
		$hour = substr($datetime, 11, 2);

		$filename = $log_path . '/' . $domain . '_' . $date . '_' . $hour . '.log';

		// 写入文件
		@file_put_contents($filename, $line."\n", FILE_APPEND);
	}	
}

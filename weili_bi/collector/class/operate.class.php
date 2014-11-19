<?php
require_once(dirname(__FILE__) . '/splitLog.class.php');

class Operate extends SplitLog{
	function __construct() {}

	/**
	* 按天分割日志
	*/
	public function split_by_day($line, $biz, $logtype) {
		$dir = DATA_PATH."/{$biz}/{$logtype}";
		$ret = 	$this->check_dir($dir);	
		if ( ! $ret) die("Error:log dir {$dir} can not be created!");
		// 从日志中找出日志所属的天
		$line = trim($line);
		$parts = explode('|', $line);
		// 按天存储到日志中
		$datetime = $parts[7];
		$date = substr($datetime, 0, 8);
		$filename = $biz . '_' . $logtype . '_' . COLLECTOR_HOST . '_' . $date . '.log';

		@file_put_contents($dir.'/'.$filename, $line."\n", FILE_APPEND);
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

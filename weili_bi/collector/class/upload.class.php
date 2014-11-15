<?php
require_once(dirname(__FILE__).'/SplitLog.class.php');

/**
 * 客户单用户行为上报日志处理
 */
class Upload extends SplitLog {
	/**
	 * 各版本分分钟目录
	 * @var array
	 */
	private $_minute_dir_arr = array();

	function __construct() {
		$this->_minute_dir_arr['br'] = SPLIT_LOG_PATH . '/brazil/upload_minute';
		$this->_minute_dir_arr['ru'] = SPLIT_LOG_PATH . '/russia/upload_minute';
	}


	/**
	 * 解析日志内容并写入文件
	 * @param  [type] $line [description]
	 * @return [type]       [description]
	 */
	public function split_by_ten_minutes($line, $ip, $version = 'br') {
		$log_path = $this->_minute_dir_arr[$version];
		$ret = $this->check_dir($log_path);
		if ( ! $ret) die($log_path.' can not be created!');
		// 解析日志，分离出日期、小时和分钟
		if(preg_match("/\[([^\s]*) .*\] .*actstate\.php\?param=(.*)HTTP/", $line, $tmp)) {
			$request_time = trim($tmp[1]);
			$datetime_info = $this->_formate_datetime($request_time);

			$param = trim($tmp[2]);
			$first = substr($param, 0,1);
			if( ! $first)
			{
				$new = substr($param, 1); 
				$str = $this->_blowfish_decrypt($new);
			}else{
				$str = $this->_virify($param);
			}
		}

		if ($str){
			if (preg_match("/uid=(.+)&act=([^&]+)&/i", $str, $tmp)) {
				$uid = trim($tmp[1]);
				$act = trim($tmp[2]);
				$line = 'time='.$datetime_info['datetime'].'`uid='.$uid.'`act='.$act."\n";
				$filename = $log_path.'/upload_'.$ip.'_'.$datetime_info['date'].'_'.$datetime_info['hour'].'_'.$datetime_info['minute'].'.log';

				@file_put_contents($filename, $line, FILE_APPEND);
			}
		}
	}

	/**
	 * 格式化日期时间
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	private function _formate_datetime($str) {
		$parts = explode(':', $str);
		$hour = $parts[1];
		$minute = $parts[2];
		$second = $parts[3];

		$parts = explode('/', $parts[0]);
		$day = $parts[0];
		$month = $this->str_to_month($parts[1]);
		$year = $parts[2];

		$datetime = $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second; 
		$minute = $minute[0].'0';

		$datetime_info =  array(
				'hour' => $hour,
				'minute' => $minute,
				'date' => $year.$month.$day,
				'datetime' => $datetime
				);
		return $datetime_info;
	}

	/**
	 * 解密客户端字符串
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	private function _blowfish_decrypt($str) {
		$data = base64_decode(urldecode($str));
		return mcrypt_decrypt(MCRYPT_BLOWFISH, 'raidcall version is %s', $data, MCRYPT_MODE_ECB, "123");
	}

	/**
	 * 判定行为参数字符串
	 * @param  [type]  $str   [description]
	 * @param  integer $index [description]
	 * @return [type]         [description]
	 */
	private function _virify($str, $index=0) {
		$str = trim((urldecode($str)));
		$len = strlen($str);

		$rang = 128 - 32;

		$p = str_split($str);
		$key = ord($p[0]);

		$new = "";
		for($i=1;$i<=$len-2;$i++) {
			$char = ((ord($p[$i])-32)-($key-32)+$rang)%$rang + 32;
			$ss = chr($char);
			$key = $char;
			$new .= $ss;
		}

		return base64_decode($new);
	}

}

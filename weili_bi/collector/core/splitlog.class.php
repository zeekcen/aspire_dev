<?php
/**
 * 功能类
 */
class Splitlog{
	/**
	 * 字符串转月份
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	protected function str_to_month($str) {
		$month_config = array(
				'Jan' => '01',
				'Feb' => '02',
				'Mar' => '03',
				'Apr' => '04',
				'May' => '05',
				'Jun' => '06',
				'Jul' => '07',
				'Aug' => '08',
				'Sep' => '09',
				'Oct' => '10',
				'Nov' => '11',
				'Dec' => '12'
				);

		if (isset($month_config[$str])) return $month_config[$str];
		else return FALSE;
	}

	/**
	 * 检查目录存在性
	 * @param  [type] $dir [description]
	 * @return [type]      [description]
	 */
	protected function check_dir($dir) {
		if ( ! is_dir($dir) || ! is_writable($dir)) {
			mkdir($dir, 0755, true);
			chmod($dir, 0755);
		}

		if (file_exists($dir)) return TRUE;
		else return FALSE;
	} 
}

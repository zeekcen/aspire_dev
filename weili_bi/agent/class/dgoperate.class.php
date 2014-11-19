<?php

class Dgoperate {
	function __construct() {}

	public function get_file() {
		$date = date('Ymd', time() - 15* 86400);
		$file_path = "/home/benny/log/dg_operate_10.9.113.128_{$date}.log";
		return $file_path;
	}
}

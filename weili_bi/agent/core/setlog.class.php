<?php
interface LogFactory{
	public function get_log_instance($type);
}

class Setlog implements LogFactory{
	public function get_log_instance($type) {
		$type = ucfirst($type);
		return new $type();
	}
}

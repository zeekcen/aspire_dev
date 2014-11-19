<?php
/**
* 自动加载类
*/
class Autoloader {

	public static $loader;

	public static function init() {
		if (self::$loader == NULL)
			self::$loader = new self();

		return self::$loader;
	}

	public function __construct() {
		spl_autoload_register(array($this, 'load_class'));
	}

	public function load_class($class) {
		$class_dir = dirname(__FILE__) . '/../class/';
		set_include_path(get_include_path().PATH_SEPARATOR.$class_dir);
		spl_autoload_extensions('.class.php');
		spl_autoload($class);
	}
}

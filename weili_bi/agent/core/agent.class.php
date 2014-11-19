<?php
class Agent {
	private $_socket = NULL;
	private $_conn = NULL;
	private $_agent_host = '';
	private $_master_host = '';
	private $_master_port = '';
	private $_log_path = '';

	function __construct($config) {
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// 检查配置信息
		foreach ($config as $key=>$val) {
			if ( ! $val) {
				die("Config variable {$key} is invalid\n");
			}
		}

		$this->_master_host = $config['master_host'];
		$this->_master_port = $config['master_port'];
		$this->_agent_host = $config['agent_host'];
		$this->_log_path = $config['log_path'];
		$this->_cache_dir = $config['cache_dir'];
	}

	/**
	 * 链接socket
	 */
	public function sock_conn() {
		$conn = @socket_connect($this->_socket, $this->_master_host, $this->_master_port);
		if ($conn) {
			$msg = "Info:Agent connect socket to master successfully";
		}else{
			$msg = "Error:Agent can't connect socket to master"; 
		}
		$this->log_msg($msg);
		return $conn ? $conn : FALSE;
	}

	/**
	 * 断开链接
	 */
	public function sock_close() {
		if (is_resource($this->_socket)) socket_close($this->_socket);
	}

	/**
	 * 发送心跳
	 */
	public function heartbeat() {
		$conn = $this->sock_conn();
		while ($conn) {
			$info = array(
					'type' => 'agent_heartbeat',
					'agent' => $this->_agent_host
				     );
			$msg = json_encode($info)."\n";
			if( ! socket_write($this->_socket, $msg)) {
				$msg = "Error:Agent send heartbeat to master failed";
				$this->log_msg($msg);
				break;
			}
			sleep(1);
		}
		$this->sock_close();
	}


	/**
	 * 获取collector的ip地址
	 */
	public function get_collector() {
		$conn = $this->sock_conn();
		$request_time = 0;
		while($conn && $request_time < 5) {
			$info = array(
					'type' => 'collector',
					'agent' => $this->_agent_host
				     );
			$msg = json_encode($info)."\n";
			if( ! socket_write($this->_socket, $msg)) {
				$msg = "Error:Agent request master for collector IP failed"; 
				$this->log_msg($msg);
			}
			while($buffer = @socket_read($this->_socket, 1024, PHP_NORMAL_READ)) {
				$json_arr = json_decode(trim($buffer), TRUE);
				if (isset($json_arr['collector'])) {
					$this->sock_close();
					return $json_arr['collector'];
				}
				break;
			}
			$request_time++;
		}
		$this->sock_close();
		return FALSE;
	}

	/**
	 * 获取logtypes配置信息
	 */
	public function get_logtypes() {
		$logtypes_file = $this->_cache_dir . 'logtypes.cache';
		// 检查是否有缓存，如果缓存文件存在且是当天缓存，则加载
		if (file_exists($logtypes_file)) {
			$ctime = filectime($logtypes_file);
			$now_date = date('Y-m-d', $ctime);	
			$create_date = date('Y-m-d');
			if ($create_date == $now_date) {
				$json_str = file_get_contents($logtypes_file);
				$json_arr = json_decode($json_str, TRUE);
				return $json_arr;
			}else{
				unlink($logtypes_file);
			}
		}

		// 向master获取logtypes配置信息
		$conn = $this->sock_conn();
		$request_time = 0;
		while($conn && $request_time < 5) {
			$info = array(
					'type' => 'logtypes',
					'collector' => $this->_agent_host
				     );
			$msg = json_encode($info)."\n";
			if( ! socket_write($this->_socket, $msg)) {
				$msg = "Error:Agent request master for logtypes failed"; 
				$this->log_msg($msg);
			}
			// 从socket获取字符串
			while($buffer = @socket_read($this->_socket, 1024, PHP_NORMAL_READ)) {
				$json_arr = json_decode(trim($buffer), TRUE);
				if (isset($json_arr['logtypes'])) {
					$this->sock_close();
					file_put_contents($logtypes_file, json_encode($json_arr['logtypes']));
					return $json_arr['logtypes'];
				}
				break;
			}
			$request_time++;
		}
		$this->sock_close();
		return FALSE;
	}

	/**
	 * 记录日志
	 * @param string $msg 日志内容
	 */
	public function log_msg($msg) {
		$dir = dirname($this->_log_path);
		// 创建目录
		if ( !file_exists($dir) ) {
			mkdir($dir, 0777);
		}
		$line = date('Y-m-d H:i:s')."\t".$msg."\n";
		file_put_contents($this->_log_path, $line, FILE_APPEND);
	}

	public function __destruct() {
		if (is_resource($this->_socket)) socket_close($this->_socket);
	}
}

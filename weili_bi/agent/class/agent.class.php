<?php
class Agent {
	private $_socket = NULL;
	private $_conn = NULL;
	private $_agent_host = '';
	private $_master_host = '';
	private $_master_port = '';
	private $_error_log = '';

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
		$this->_error_log = $config['error_log'];
	}

	/**
	 * 链接socket
	 */
	public function sock_conn() {
		$conn = @socket_connect($this->_socket, $this->_master_host, $this->_master_port);
		if ( ! $conn) {
			$msg = "Agent can't connect socket to master"; 
			$this->log_msg($msg);
			return FALSE;
		}

		return $conn;
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
					'type' => 'heartbeat',
					'agent' => $this->_agent_host
				     );
			$msg = json_encode($info)."\n";
			if( ! socket_write($this->_socket, $msg)) {
				$msg = "Agent send heartbeat to master failed";
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
				$msg = "Agent request master for collector IP failed"; 
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
	 * 记录日志
	 * @param string $msg 日志内容
	 */
	public function log_msg($msg) {
		$dir = dirname($this->_error_log);
		// 创建目录
		if ( !file_exists($dir) ) {
			mkdir($dir, 0777);
		}

		$line = date('Y-m-d H:i:s')."\t".$msg."\n";
		file_put_contents($this->_error_log, $line, FILE_APPEND);
	}

	public function __destruct() {
		if (is_resource($this->_socket)) socket_close($this->_socket);
	}
}

<?php
$agent = new Agent('127.0.0.1', 10008);
$agent->heartbeat();
#echo $agent->get_collector();

class Agent {
	var $_socket = NULL;
	var $_conn = NULL;
	var $_agent_host = '127.0.0.1';
	var $_master_host = NULL;
	var $_master_port = NULL;

	function __construct($master_host, $master_port) {
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$this->_master_host = $master_host;
		$this->_master_port = $master_port;
	}

	/**
	 * 链接socket
	 */
	public function sock_conn() {
		return socket_connect($this->_socket, $this->_master_host, $this->_master_port);
	}

	/**
	 * 断开链接
	 */
	public function sock_close() {
		socket_close($this->_socket);
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
				echo("Write failed\n");
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
		while($conn) {
			$info = array(
					'type' => 'collector',
					'agent' => $this->_agent_host
				     );
			$msg = json_encode($info)."\n";
			if( ! socket_write($this->_socket, $msg)) {
				echo("Write failed\n");
			}
			while($buffer = @socket_read($this->_socket, 1024, PHP_NORMAL_READ)) {
				$json_arr = json_decode(trim($buffer), TRUE);
				if (isset($json_arr['collector'])) {
					return $json_arr['collector'];
				}
				break;
			}
		}
		$this->sock_close();
	}

	public function __destruct() {
		if ($this->_socket) socket_close($this->_socket);				
	}
}

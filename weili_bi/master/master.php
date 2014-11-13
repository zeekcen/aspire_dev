<?php  

$master = new Master('127.0.0.1', 10008);
$master->run();

class Master {
	var $_socket = NULL;
	var $_master_host = NULL;
	var $_master_port = NULL;
	/**
	* @var mem缓存示例
	*/
	var $_mem = NULL;
	/**
	* @var agent分配给Collector的映射数组
	*/
	var $_agent_distr_arr = array();
	/**
	* @var 心跳时间间隔，单位秒
	*/
	var $_interval = 10;
 

	function __construct($master_host, $master_port) {
		$this->_master_host = $master_host;
		$this->_master_port = $master_port;
		
		// 初始化处理
		$this->_init_mem();
		$this->_load_ini();
	}

	
	/**
	* 初始化memcached
	*/
	private function _init_mem() {
		$this->_mem = new Memcache();
		$this->_mem->connect($this->_master_host, 11211);
	}

	/**
	 * 加载配置文件 
	 */
	private function _load_ini() {
		$ini_file = dirname(__FILE__) . '/agent2collector.ini';
		$this->_agent2collector = parse_ini_file($ini_file);
	}

	/**
	 * 初始化socket
	 */
	private function _init_socket() {
		// 建立server端socket  
		$tcp = getprotobyname("tcp");  
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, $tcp);  
		if ( ! $this->_socket) {
			echo "Create socket failed\n";
		}
		socket_bind($this->_socket, $this->_master_host, $this->_master_port);       //绑定要监听的端口  
		socket_listen($this->_socket);       //监听端口  
	}

	/**
	 * 运行master
	 */
	public function run() {
		$this->_init_socket();
		// 不断监听端口
		while (TRUE) {  
			// 接受一个socket连接  
			$connection = socket_accept($this->_socket);  
			// 从客户端取得信息  
			while ($buffer = @socket_read($connection, 1024, PHP_NORMAL_READ)) {  
				$json_arr = json_decode(trim($buffer), TRUE);
				if ( ! isset($json_arr['type'])) break;
				$info = '';
				switch ($json_arr['type']) {
					case 'collector':
						$agent = $json_arr['agent'];
						$collector = $this->_get_collector($agent);
						if ($collector) $info = array('collector' => $collector);
						break;
					case 'heartbeat':
						$agent = $json_arr['agent'];
						$this->_set_heartbeat($agent);
						break;
					default:
						break;
				}

				$msg = '';
				if ($info) $msg = json_encode($info)."\n";
				if ($msg) socket_write($connection, $msg);  
			}  
			socket_close($connection);  
		}
	}	

	/**
	 * 获取收集器的IP
	 * @param string $agent agentIP
	 * @return string $collector collectorIP
	 */
	private function _get_collector($agent) {
		$collector_arr = array(
				'127.0.0.1' => '127.0.0.1'
				);

		if (isset($collector_arr[$agent])) return $collector_arr[$agent];
		else return FALSE;
	}

	/**
	* 更新心跳
	*/
	private function _set_heartbeat($agent) {
		$key = md5($agent);
		$val = $this->_mem->get($key);
		$now = time();
		if ( ! $val) {
			$this->_mem->set($key, $now, 0, 0);
		}else{
			$this->_mem->replace($key, $now, 0, 0);
		}
	}

	/**
	* 心跳监控
	*/
	public function heartbeat_monitor() {
		while(TRUE) {
			// 遍历agents，检查心跳更新时间
			$now = time();
			foreach ($this->_agent2collector as $agent=>$collector) {
				$key = md5($agent);
				$val = $this->_mem->get($key);
				$interval = $now - $val;
				if ($interval > $this->_interval) {
					// 发送监控告警信息
					echo "Agent:{$agent} is down";	
				}	
			}

			sleep($this->_interval);
		}
	}

	public function __destruct() {
		if ($this->_socket) socket_close($this->_socket);
	}

}

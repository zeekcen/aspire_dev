<?php  
class Master {
	private $_socket = NULL;
	private $_master_host = '';
	private $_master_port = '';
	/**
	 * @var mem缓存示例
	 */
	private $_mem = '';
	/**
	 * @var agent分配给Collector的映射数组
	 */
	private $_agent2collector = array();
	/**
	* @var 分支日志类型数组
	*/ 
	private $_logtypes = array();
	/**
	 * @var 心跳时间间隔，单位秒
	 */
	private $_interval = 10;
	/**
	* @var 日志路径
	*/
	private $_log_path = '';


	function __construct($config) {
		$this->_master_host = $config['master_host'];
		$this->_master_port = $config['master_port'];
		$this->_log_path = $config['log_path'];

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
		// agent与collector对应关系配置
		$ini_file = dirname(__FILE__) . '/../ini/agent2collector.ini';
		if ( ! file_exists($ini_file)) {
			$msg = "Error:{$ini_file} no such file";
		}else{
			$msg = "Info:{$ini_file} is loaded successfully";
			$this->_agent2collector = parse_ini_file($ini_file);
		}
		$this->log_msg($msg);
		// 商户日志类型登记配置
		$ini_file = dirname(__FILE__) . '/../ini/logtypes.ini';
		if ( ! file_exists($ini_file)) {
			$msg = "Error:{$ini_file} no such file";
		}else{
			$msg = "Info:{$ini_file} is loaded successfully";
			$this->_logtypes = parse_ini_file($ini_file, TRUE);
		}
		$this->log_msg($msg);
	}

	/**
	 * 初始化socket
	 */
	private function _init_socket() {
		// 建立server端socket  
		$tcp = getprotobyname("tcp");  
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, $tcp);  
		if ( ! $this->_socket) {
			die("Master create socket failed\n");
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
					case 'agent_heartbeat':
						$agent = $json_arr['agent'];
						$this->_set_heartbeat($agent);
						break;
					case 'collector_heartbeat':
						$collector = $json_arr['collector'];
						$this->_set_heartbeat($collector);
						break;
					case 'logtypes':
						$logtypes = $this->_get_logtypes();
						if ($logtypes) $info = array('logtypes' => $logtypes);
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
		if (isset($this->_agent2collector[$agent])) return $this->_agent2collector[$agent];
		else return FALSE;
	}

	/**
	* 获取服务器提供的日志类型收集信息
	* @return array
	*/
	private function _get_logtypes() {
		if ( ! empty($this->_logtypes)) return $this->_logtypes;
		else return FALSE;
	}

	/**
	 * 更新心跳
	* @param string $agent ip
	 */
	private function _set_heartbeat($ip) {
		$key = md5($ip);
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
				// 检查agent的心跳
				$key = md5($agent);
				$val = $this->_mem->get($key);
				$interval = $now - $val;
				if ( ! $val || $interval > $this->_interval) {
					// 发送监控告警信息
					echo "Error:Agent {$agent} is down\n";	
				}	

				// 检查collector的心跳
				$key = md5($collector);
				$val = $this->_mem->get($key);
				$interval = $now - $val;
				if ( ! $val || $interval > $this->_interval) {
					// 发送监控告警信息
					echo "Error: collector {$collector} is down\n";	
				}
			}
			sleep($this->_interval);
		}
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
		if ($this->_socket) socket_close($this->_socket);
	}

}

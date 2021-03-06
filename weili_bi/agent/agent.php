<?php
declare(ticks = 1);
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ($argc < 3) {
    die("Error:No enough arguments\nusage:\n\tphp collector.php biz logtype\n");
}

// 业务类型
$biz = $argv[1];
// 日志类型
$logtype = $argv[2];

// 加载配置文件
require_once dirname(__FILE__) . '/config.php';
// agent通信类
require_once dirname(__FILE__) . '/core/agent.class.php';
// 日志类型工厂类
require_once dirname(__FILE__) . '/core/setlog.class.php';
// 自动加载类
require_once dirname(__FILE__) . '/core/autoloader.class.php';
// beanstalk接口类
require_once dirname(__FILE__) . '/../lib/pheanstalk/pheanstalk_init.php';

// 初始化类自动加载函数
Autoloader::init();

$agent = new Agent($config);
$logtypes = $agent->get_logtypes();
if ( ! $logtypes) {
	die("Error:can't get logtypes, please check the master service.\n");
}

// 检查参数是否正确
if ( ! array_key_exists($biz, $logtypes)) {
        die("Error:biz {$biz} is invalid\n".print_r($logtypes, TRUE));
}

if ( ! in_array($logtype, $logtypes[$biz])) {
        die("Error:logtype {$logtype} is invalid\n".print_r($logtypes, TRUE));
}

$collector = $agent->get_collector();
if ( ! $collector) {
	die("Error:can't get collector IP, please check the master service.\n");
}

// 配置日志收集服务器
define('BEAN_HOST', $collector);
define('BEAN_PORT', '11300');

// 初始化监控对象
$monitor = new LogMonitor($biz.$logtype);

// 信号控制
pcntl_signal(SIGINT, array($monitor, "sig_handler"));
pcntl_signal(SIGTERM, array($monitor, "sig_handler"));
pcntl_signal(SIGHUP, array($monitor, "sig_handler"));
// 致命错误控制
register_shutdown_function(array($monitor, "fatalErrorHandler"));
// 一般错误处理，一般不需要开启
//set_error_handler(array($monitor, "errorHandler"));
// 异常处理
set_exception_handler(array($monitor, "exceptionHandler"));
// 开始监控日志
$monitor->run();
/**
 * 日志监控类
 */
class LogMonitor {
	// 文件类型
	private $type = '';
	// 日志文件路径
	private $file = '';
	// 记录当前读取位置的文件路径
	private $pos_log = '';
	// 当前读取的日志位置
	private $pos = 0;
	// 当前读取的日志行数
	private $count = 0;
	// 队列名
	private $queue = '';

	/**
	 * 构造函数
	 */
	public function __construct($type) {
		// 初始化变量
		$this->type  = $type;
		$this->queue = 'queue' . $this->type;
		$this->pos   = 0;
		$this->count = 0;
		$this->initLog();
		$this->initPosLog();
	}
	/**
	 * 获取log文件路径
	 */
	protected function initLog() {
		// 收集日志类列表
		$setlog = new Setlog();
		$logger = $setlog->get_log_instance($this->type);
		$this->file = $logger->get_file();

		// 创建文件
		if ( ! file_exists($this->file)) {
			$this->create($this->file);
		}
	}
	/**
	 * 初始化位置记录文件
	 */
	protected function initPosLog() {
		$dir = dirname(__FILE__).'/logs';
		// 创建目录
		if ( !file_exists($dir) ) {
			mkdir($dir, 0777);
		}
		$this->pos_log = $dir.'/'.$this->queue.'_pos.log';
		// 创建记录长度日志文件
		if ( !file_exists($this->pos_log) ) {
			$this->create($this->pos_log);
		}
		// 获取上一次读取的位置
		$content = file_get_contents($this->pos_log);
		if (empty($content)) {
			$this->recordPos();
		} else {
			$arr = explode(':', $content);
			// 如果不是上次读取的文件则将位置清零
			if ($this->file != $arr[0]) {
				$this->recordPos();
			} else {
				$this->pos = $arr[1];
				$this->count = $arr[2];
			}
		}
	}
	/**
	 * 开始运行监控
	 */
	public function run() {
		// 循环读取
		while (true) {
			clearstatcache();
			$currentSize = $this->getSize($this->file);
			if ($this->pos == $currentSize) {
				sleep(1);
				continue;
			}
			// 从上次的位置开始读取
			$fh = fopen($this->file, "r");
			fseek($fh, $this->pos);
			$retry = 0;
			while ($d = fgets($fh)) {
				$pos = ftell($fh);
				// 异常处理
				try {
					$arr = array(
							'type' => $this->type,
							'content' => rtrim($d, "\r\n")
						    );
					$buffer = json_encode($arr) . "\n";
					// 平缓入队列，延迟20ms
					usleep(20000);
					$this->send_to_queue($this->queue, $buffer);
				} catch(exception $e) {
					echo $e->getMessage()."\n";
					fseek($fh, $this->pos);
					$retry++;
					// 重试100次
					if ($retry >= 100) {
						$this->recordPos();
						die("Retry $retry times, now exists...\n");
					}
					continue;
				}
				// 记住上一次正确处理完成的位置
				$this->pos = $pos;
				$this->count++;
				// 每读取100条记录一次，减少损失
				if ($this->count % 100 == 0) {
					$this->recordPos();
				}
			}
			fclose($fh);
			$this->pos = $currentSize;
			$this->recordPos();
		}
	}

	/**
	 * 发送到队列函数
	 *
	 * @param string $tube     队列名称
	 * @param string $info     写入到队列的信息
	 * @param int    $priority 队列优先级
	 * @param int    $delay    迁移到正式发送队列的延迟时间
	 * @param int    $ttr      队列reserve后的过期时间
	 * @return bool
	 */
	public function send_to_queue($tube, $info, $priority = 1024, $delay = 0, $ttr = 60) {
		$pheanstalk = new Pheanstalk(BEAN_HOST, BEAN_PORT);
		$res = $pheanstalk->useTube($tube)->put($info, $priority, $delay, $ttr);
		return $res;
	}
	/**
	 * 获取文件大小(可获取32位系统下超过2G的文件大小)
	 */
	public function getSize($file) {
		$size = filesize($file);
		if ($size < 0) {
			if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) {
				$size = trim(`stat -c%s $file`);
			} else {
				$fsobj = new COM("Scripting.FileSystemObject");
				$f = $fsobj->GetFile($file);
				$size = $file->Size;
			}
		}
		return $size;
	}
	/**
	 * 创建文件
	 */
	public function create($file) {
		$fp = fopen($file, 'ab') or die("can't create file $file\n");
		fclose($fp);
		chmod($file, 0777);
	}
	/**
	 * 记录当前读取位置到文件
	 */
	public function recordPos() {
		$content = $this->file.':'.$this->pos.':'.$this->count;
		file_put_contents($this->pos_log, $content);
	}
	/**
	 * 信号处理函数
	 */
	public function sig_handler($signo) {
		switch ($signo) {
			case SIGINT:
				echo "Caught SIGINT...\n";
				break;
			case SIGTERM:
				// 处理SIGTERM信号
				echo "Caught SIGTERM...\n";
				break;
			case SIGHUP:
				echo "Caught SIGHUP...\n";
				break;
			default:
				// 处理所有其他信号
				echo "Caught $signo...\n";
		}
		$this->recordPos();
		exit;
	}
	/**
	 * 致命错误处理函数
	 */
	public function fatalErrorHandler() {
		if ($error = error_get_last()) {
			var_dump($error);
		}
		$this->recordPos();
		exit;
	}
	/**
	 * 错误处理函数
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline) {
		echo "$errstr in $errfile at $errline\n";
		$this->recordPos();
		exit;
	}
	/**
	 * 异常处理函数
	 */
	public function exceptionHandler($e) {
		echo "Uncaught exception: " , $e->getMessage(), "\n";
		$this->recordPos();
		exit;
	}
}
?>

<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/class/master.class.php';

$master = new Master($config);
$master->run();


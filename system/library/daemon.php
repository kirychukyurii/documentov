<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

/**
 * Cache class
 */

require "goridge/RPC.php";
require "goridge/RelayInterface.php";

require "goridge/StreamRelay.php";
require "goridge/Exceptions/GoridgeException.php";
require "goridge/Exceptions/RelayException.php";
require "goridge/Exceptions/TransportException.php";
require "goridge/Exceptions/InvalidArgumentException.php";
require "goridge/Exceptions/PrefixException.php";
require "goridge/Exceptions/RPCException.php";
require "goridge/Exceptions/ServiceException.php";
require "goridge/SocketRelay.php";


use Spiral\Goridge;

class Daemon
{

  private $db, $log, $status, $rpc, $variable, $config;

  public function __construct($registry)
  {
    $this->db = $registry->get('db');
    $this->log = $registry->get('log');
    $this->config = $registry->get('config');
    if (!defined("VERSION") || $this->config->get('version_db') != VERSION) {
      $this->status = false;
      return;
    }

    $this->variable = $registry->get('variable');
    $request = $registry->get('request');
    $this->status = $this->checkStatus();
    $daemon_started = (int) $this->variable->get('daemon_started');
    if ($daemon_started <= 0 && $this->status) {
      if ($daemon_started < 0) {
        $this->setDaemonStarted(++$daemon_started);
        return;
      }
      $this->runDaemon("stop");
      return;
    }

    if ($this->status) {
      // демон должен быть запущен и он запущен
      return;
    }
    // демон должен быть запущен, но не запущен, пробуем запустить, если последняя попытка предпринималась более 30 сек назад
    if ($daemon_started > 0 && (time() - $daemon_started) > 30) {
      $route = $request->get['route'] ?? "";
      if ($route && !isset($route->exception_route[$route])) {
        $this->runDaemon("start");
        $this->status = $this->checkStatus();
      }
    }
  }

  private function connect()
  {
    if (strtoupper(substr(php_uname("s"), 0, 3)) === 'WIN') {
      $this->rpc = new Goridge\RPC(new Goridge\SocketRelay("127.0.0.1", "49130"));
    } else {
      $this->rpc = new Goridge\RPC(new Goridge\SocketRelay(DIR_STORAGE . "var/rpc.sock", null, Goridge\SocketRelay::SOCK_UNIX));
    }
  }

  private function checkStatus()
  {
    $this->connect();
    try {
      @$this->rpc->call("RPC.Ping", "1");
    } catch (\Throwable $th) {
      return false;
    }
    return true;
  }

  public function getStatus()
  {
    return $this->status;
  }

  public function exec($method, $params)
  {
    // return null;
    if (!$this->status) {
      return null;
    }
    try {
      // БАГ В ГОРИДЖЕ
      // первое использование соединения и второе при работе через tcp:
      // 1: 0.00051403045654297 2: 0.043377876281738
      // пересоздаем подключение, если работаем под Вин
      if (strtoupper(substr(php_uname("s"), 0, 3)) === 'WIN') {
        $this->connect();
      }
      if (is_array($params)) {
        $params['language'] = $this->config->get("config_language_name");
      }
      // if (count($params) > 3) {
      //   print_r($params);
      //   exit;
      // }
      $result = @$this->rpc->call("RPC." . $method, $params);
    } catch (\Throwable $th) {
      $this->log->write(sprintf("Error in RPC.%s(%s): %s", $method, print_r($params, true), $th->getMessage()));
      $result = "";
    }
    return $result;
  }


  public function addTask($action, $params, $priority = 0, $exec_date = 'NULL')
  {
    /*
     * статусы задачи: 
     * 0 - задача в очереди
     * 1 - задача исполняется
     * 2 - задача исполнена
     * 
     * приоритеты:
     * 0 - самый низкий
     * > 0 выше 
     * 
     * task_id, action, action_params, priority, exec_date, status, exec_attempt
     */

    $params = serialize($params);
    $sql =  "INSERT INTO " . DB_PREFIX . "daemon_queue SET "
      . "action = '" . $this->db->escape($action)
      . "', action_params = '" . $this->db->escape($params)
      . "', priority = '" . (int) $priority
      . "', start_time = '" . $this->db->escape($exec_date) . "'";
    $this->db->query($sql);
    $query = $this->db->query("SELECT LAST_INSERT_ID()");
    $last_id = $query->row['LAST_INSERT_ID()'];
    return $last_id;
  }

  private function  isExecutable($file, $fp, $time)
  {
    clearstatcache();
    $file = trim($file);

    if (!is_executable($file)) {
      // файл неисполняемый
      fwrite($fp, "\n\ndaemon " . $time->format('Y/m/d H:i:s') . "\tDAEMON: " . sprintf("Daemon file %s is not executable.\nRun chmod +x %s", $file, $file));
      fclose($fp);
      sleep(5);
      return false;
    }
    return true;
  }

  private function setDaemonStarted($time)
  {
    $this->db->query("REPLACE INTO variable SET `name` = 'daemon_started', `value` = '" . $time . "'");
  }

  public function runDaemon($command = "start")
  {
    if ($command == "stop") {
      $this->setDaemonStarted(-5); //пауза 5 сек на остановку, чтобы конструктор не пытался повторно остановить
    }
    if ($command == "start" || $command == "restart") {
      $this->setDaemonStarted(time());
    }
    $server_name = $_SERVER['HTTP_HOST'];
    // $name = htmlentities(explode(".", $server_name)[0], ENT_QUOTES);
    $now = new DateTime('now');
    $logfile = DIR_STORAGE . "logs/daemontask.log";
    $fp = fopen($logfile, 'a');
    $daemon_file = DIR_APPLICATION . 'daemon/documentov';
    if (strtoupper(substr(php_uname("s"), 0, 3)) === 'WIN') {
      $daemon_file .= '.exe ';
    } else {
      $daemon_file .= ' ';
    }
    if (!$this->isExecutable($daemon_file, $fp, $now)) {
      return;
    }

    $run_comm = $daemon_file . $command . ' ' . $server_name;
    fwrite($fp, "\ndaemon " . $now->format('Y/m/d H:i:s') . "\tDAEMON: " . $run_comm . "\nResponse: ");
    $output = [];
    // echo "1";
    // exit;

    exec($run_comm, $output);
    // $result = exec($run_comm, $output);
    // fwrite($fp, $result . "\r\n");
    fwrite($fp, implode("\r\n", $output) . "\r\n");
    fclose($fp);
    // sleep(3);
    return;
  }
}

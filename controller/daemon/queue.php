<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerDaemonQueue extends Controller
{

  public function index()
  {
  }

  public function runTask($task_id)
  {
    $this->load->model('daemon/queue');
    print "runTask $task_id";
    if ($task_id > 0 && $task_id != "service") {
      //получили номер задачи
      $sql = "SELECT * FROM " . DB_PREFIX . "daemon_queue WHERE "
        . "task_id = '" . (int) $task_id . "'";
      $query = $this->db->query($sql);
      if (!$query->num_rows) {
        print(" TASK: " . $task_id . " not found");
      } else {
        $action = $query->row['action'];

        $params = @unserialize($query->row['action_params']);
        if ($params === FALSE) {
          $params = @json_decode($query->row['action_params'], true);
        }
        $exec_attempt = (int) $query->row['exec_attempt'];
        $exec_attempt++;
        $this->db->query("UPDATE " . DB_PREFIX . "daemon_queue SET "
          . "start_time=NOW(), "
          . "status=1, "
          . "exec_attempt=" . $exec_attempt . ", "
          . "pid=" . getmypid() . " "
          . "WHERE task_id=" . (int) $task_id);

        try {
          // throw new Exception("Ой!");
          $this->load->controller($action, $params);
          $this->db->query("DELETE FROM " . DB_PREFIX . "daemon_queue WHERE "
            . "task_id=" . (int) $task_id);
        } catch (Exception $e) {
          print(" TASK: " . $task_id . " Exception in " . $action . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        } catch (Error $e) {
          print(" TASK: " . $task_id . " Exception in " . $action . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
      }
    }
  }
}

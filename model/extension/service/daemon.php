<?php

class ModelExtensionServiceDaemon extends Model
{

  public function getDaemonTasksLog()
  {
    $logfile = DIR_STORAGE . "logs/daemontask.log";
    if (file_exists($logfile)) {
      $fp = fopen($logfile, 'r');
      if (filesize($logfile)) {
        $log = fread($fp, filesize($logfile));
        fclose($fp);
      } else {
        $log = "";
      }
      return $log;
    } else {
      return "";
    }
  }

  // public function getStatus() {
  //     $daemon_loc = DIR_STORAGE . "logs/daemon.lock";
  //     $started = false;
  //     $daemon_pid = 0;
  //     if (is_file($daemon_loc)) {
  //         $dl = fopen($daemon_loc, 'r');
  //         $daemon_time_stamp = fgets($dl);
  //         $daemon_pid = fgets($dl);
  //         if ((time() - (int)$daemon_time_stamp) < 5) {
  //             $started = true;
  //         }
  //         fclose($dl);
  //     }
  //     return $started;
  // }    
}

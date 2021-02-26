<?php

namespace DB;

final class MySQLi
{

  private $connection;

  public function __construct($hostname, $username, $password, $database, $port = '3306')
  {
    $this->connection = new \mysqli($hostname, $username, $password, $database, $port);

    if ($this->connection->connect_error) {
      echo "<br><br>ERROR DB CONNECTION<br><br>";
      exit;
      //			throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno);
    }

    $this->connection->set_charset("utf8");
    $this->connection->query("SET SQL_MODE = ''");
    $db_prefix = "";
    if (defined('DB_PREFIX')) {
      $query = $this->connection->query("SELECT value FROM " . $db_prefix . "setting WHERE `key`='date.timezone'");
      if (!$this->connection->errno) {
        if ($query instanceof \mysqli_result) {
          $result = $query->fetch_assoc();
          if ($result['value']) {
            try {
              $tz = new \DateTime($result['value']);
              $this->connection->query("SET time_zone = '" . $this->connection->real_escape_string($tz->format("P")) . "' ");
              date_default_timezone_set($result['value']);
            } catch (\Throwable $th) {
              //throw $th;
            }
          }
          $query->close();
        }
      }
    }
  }

  public function query($sql)
  {
    //                if ($this->tz) {
    //                    $this->connection->query("SET time_zone = '" . $this->connection->real_escape_string($this->tz) ."' ");
    //                }            
    //                $query = $this->connection->query("select timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')) as timezone");
    //                print_r($query->fetch_assoc());
    $query = $this->connection->query($sql);

    if (!$this->connection->errno) {
      if ($query instanceof \mysqli_result) {
        $data = array();

        while ($row = $query->fetch_assoc()) {
          $data[] = $row;
        }

        $result = new \stdClass();
        $result->num_rows = $query->num_rows;
        $result->row = isset($data[0]) ? $data[0] : array();
        $result->rows = $data;

        $query->close();

        return $result;
      } else {
        return true;
      }
    } else {
      //                    echo 'Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql;
      //                    debug_print_backtrace();                    
      //                    exit;
      echo 'Error: ' . $this->connection->error . '<br>Error No: ' . $this->connection->errno . '<br>' . $sql . '<br>';
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      exit;
      throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
    }
  }

  public function multi_query($sql)
  {
    //                if ($this->tz) {
    //                    $this->connection->query("SET time_zone = '" . $this->connection->real_escape_string($this->tz) ."' ");
    //                }            
    //                $query = $this->connection->query("select timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')) as timezone");
    //                print_r($query->fetch_assoc());
    $query = $this->connection->multi_query($sql);
    if (!$this->connection->errno) {
      $data = array();
      do {
        if ($result = $this->connection->store_result()) {
          while ($row = $result->fetch_assoc()) {
            $data[] = $row;
          }
          $result->free();
        }
        if (!$this->connection->more_results()) break;
      } while ($this->connection->next_result());
      $result = new \stdClass();
      $result->num_rows = count($data);
      $result->row = isset($data[0]) ? $data[0] : array();
      $result->rows = $data;
      //$query->close();

      return $result;
    } else {
      //                    echo 'Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql;
      //                    debug_print_backtrace();                    
      //                    exit;
      echo 'Error: ' . $this->connection->error . '<br>Error No: ' . $this->connection->errno . '<br>' . $sql . '<br>';
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      exit;
      throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
    }
  }

  public function escape($value)
  {
    if (is_string($value) || is_int($value) || is_float($value)) {
      return $this->connection->real_escape_string($value);
    } else {
      echo "VALUE=";
      print_r($value) . "; ";
      debug_print_backtrace();
      exit;
    }
  }

  public function getServerVersion()
  {
    return $this->connection->server_version;
  }

  public function countAffected()
  {
    return $this->connection->affected_rows;
  }

  public function getLastId()
  {
    return $this->connection->insert_id;
  }

  public function connected()
  {
    return $this->connection->ping();
  }

  public function __destruct()
  {
    $this->connection->close();
  }
}

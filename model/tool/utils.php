<?php

class ModelToolUtils extends Model
{

  public function getUseFieldDisk()
  {
    $result = 0;
    $query_tables = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND COLUMN_NAME = 'size' AND DATA_TYPE = 'int' AND TABLE_NAME LIKE '%file%' ");
    foreach ($query_tables->rows as $table) {
      $query = $this->db->query("SELECT SUM(size) as sum_size FROM " . DB_PREFIX . $table['TABLE_NAME']);
      $result += (int) $query->row['sum_size'];
    }
    return $result;
  }

  public function isTable($table)
  {
    $query = $this->db->query("SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . $this->db->escape(trim($table)) . "'");
    return $query->num_rows;
  }

  /**
   * Метод изменяет тип поля с 1 на 2
   * @param type $type1
   * @param type $type2
   */
  public function changeFieldType($type1, $type2)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "field SET type='" . $this->db->escape($type2) . "' WHERE type='" . $this->db->escape($type1) . "' ");
    //проверяем использование поля в настройках
    $query_s = $this->db->query("SELECT setting_id FROM setting WHERE `key` LIKE '%type' AND `value` = '" . $this->db->escape($type1) . "' ");
    if ($query_s->num_rows) {
      $setting_ids = array();
      foreach ($query_s->rows as $row) {
        $setting_ids[] = $row['setting_id'];
      }
      $this->db->query("UPDATE setting SET value='" . $this->db->escape($type2) . "' WHERE setting_id IN (" . implode(",", $setting_ids) . ") ");
    }
    $query1 = $this->db->query("SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "field_value_" . $this->db->escape($type1) . "'");
    $query2 = $this->db->query("SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "field_value_" . $this->db->escape($type2) . "'");
    if ($query1->num_rows && $query2->num_rows) {
      //таблица для второго типа есть, переносим данные
      $this->db->query("INSERT INTO field_value_" . $this->db->escape($type2) . " SELECT * FROM field_value_" . $this->db->escape($type1));
    }
  }
  /**
   * Метод проверки UID
   * @param type $value
   * @return type
   */
  public function validateUID($value)
  {
    return preg_match("/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/", $value);
  }


  public function addLog($doc_uid, $type, $module, $object_uid, $value)
  {
    if (!empty($this->config->get('debugger_status'))) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "debugger_log SET "
        . "date = NOW(3), "
        . "user_uid = '" . ($this->customer->getStructureId() ?? "") . "', "
        . "doc_uid = '" . $this->db->escape($doc_uid) . "', "
        . "type = '" . $this->db->escape($type)  . "', "
        . "module = '" . $this->db->escape($module)  . "', "
        . "object_uid = '" . $this->db->escape($object_uid)  . "', "
        . "value = '" . $this->db->escape(is_array($value) ? serialize($value) : $value)  . "' "
        . "");
    }
  }

  /**
   * Метод удаления директории с файлами; передается директория со слэшем в конце названия
   * @param type $dir
   */
  public function removeDirectory($src)
  {
    $this->load->model('tool/utils');
    if (!$src || !is_dir($src)) {
      return;
    }
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        $full = $src . '/' . $file;
        if (is_dir($full)) {
          $this->model_tool_utils->removeDirectory($full);
        } else {
          unlink($full);
        }
      }
    }
    closedir($dir);
    rmdir($src);
  }

  public function isWindows()
  {
    if (strtoupper(substr(php_uname("s"), 0, 3)) === 'WIN') {
      return true;
    }
    return false;
  }

  /**
   * Метод преобразует многомерный массив параметров методов полей в одномерный с ключами вида [ [k1][k1] ] = value 
   * @param type $value
   * @return type
   */
  public function array2single($value)
  {
    $result = array();
    if (is_array($value)) {
      foreach ($value as $k1 => $v1) {
        if (is_array($v1)) {
          foreach ($this->array2single($v1) as $k2 => $v2) {
            $result["[" . $k1 . "]" . "[" . $k2 . "]"] = $v2;
          }
        } else {
          $result[$k1] = $v1;
        }
      }
    } else {
      $result = $value;
    }
    return $result;
  }

  /**
   * Метод возвращает дату со временем по установленному в настройках часовому поясу в соответствующем формате
   * @param type $date
   * @return type
   */
  public function getDateTime($date = "now")
  {

    try {
      $timezone = new DateTimeZone($this->config->get('date.timezone'));
    } catch (\Throwable $th) {
      $this->load->language('tool/utils');
      echo $this->language->get('error_language');
      exit;
    }
    $date2 = new DateTime($date, $timezone);
    return $date2->format($this->language->get('datetime_format'));
  }

  /**
   * Сортировка - латинские названия в конце списка
   */
  public function sortCyrLat($a, $b)
  {
    $la = mb_substr($a, 0, 1);
    if (ord($la) < 122) {
      $a = "\u{FFFF}" . $a;
    }
    $lb = mb_substr($b, 0, 1);
    if (ord($lb) < 122) {
      $b = "\u{FFFF}" . $b;
    }
    return $a <=> $b;
  }

  private function utf8_str_split($str)
  {
    // place each character of the string into and array 
    $split = 1;
    $array = array();
    for ($i = 0; $i < strlen($str);) {
      $value = ord($str[$i]);
      if ($value > 127) {
        if ($value >= 192 && $value <= 223)
          $split = 2;
        elseif ($value >= 224 && $value <= 239)
          $split = 3;
        elseif ($value >= 240 && $value <= 247)
          $split = 4;
      } else {
        $split = 1;
      }
      $key = NULL;
      for ($j = 0; $j < $split; $j++, $i++) {
        $key .= $str[$i];
      }
      array_push($array, $key);
    }
    return $array;
  }

  public function clearstr($str)
  {
    $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
    $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'), range('a', 'z'), range('0', '9'), array('&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\'));
    $codes = array();
    for ($i = 0; $i < count($s1); $i++) {
      $codes[] = ord($s1[$i]);
    }
    $str_s = $this->utf8_str_split($str);
    for ($i = 0; $i < count($str_s); $i++) {
      if (!in_array(ord($str_s[$i]), $codes)) {
        $str = str_replace($str_s[$i], '', $str);
      }
    }
    return $str;
  }
}

<?php

class ModelExtensionFieldDatetime extends FieldModel
{

  private $dbformat = null;

  function __construct($registry)
  {
    parent::__construct($registry);
    $this->dbformat = 'Y-m-d H:i:s';
  }

  public function editValue($field_uid, $document_uid, $field_value)
  {
    if (strcmp(preg_replace("#[:\._\-/ ]#", '', $field_value), '') == 0) {
      $display_value = '';
      $value = '';
    } else {
      $value = $this->getValue($field_uid, $document_uid, $field_value);
      $value_original = $value;
      if ($value) {
        $format = $this->dbformat;
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($field_uid, 0);
        if (!empty($field_info['params']['format'])) {
          $format = $field_info['params']['format'];
        }
        $date = new DateTime($value);
        $display_value = $date->format($format);
        //если формат не содержит времени, обнуляем время для записи в базу
        //необходимо для корректной работы фильтров по дате, потому что иначе мешает время
        if (strpos($format, "H") === false) {
          $time = empty($field_info['params']['time']) ? "start" : $field_info['params']['time'];
          switch ($time) {
            case "start":
              $value_original = substr($value, 0, 10);
              $value = $value_original . " 00:00:00";
              $value_original .= date(' H:i:s');
              break;
            case "current":
              $value = substr($value, 0, 10) . date(' H:i:s');
              $value_original = $value;
              break;
            case "end":
              $value_original = substr($value, 0, 10);
              $value = $value_original . " 23:59:59";
              $value_original .= date(' H:i:s');
              break;
          }
        }
      }
    }

    if ($value) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_datetime WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");

      if ($query->num_rows) {
        $this->db->query("UPDATE " . DB_PREFIX . "field_value_datetime SET "
          . "value='" . $this->db->escape($value) . "', "
          . "value0='" . $this->db->escape($value_original) . "', "
          . "display_value='" . $this->db->escape($display_value) . "', "
          . "time_changed=NOW() "
          . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
      } else {
        $this->db->query("INSERT INTO " . DB_PREFIX . "field_value_datetime SET "
          . "document_uid='" . $this->db->escape($document_uid) . "', "
          . "field_uid='" . $this->db->escape($field_uid) . "', "
          . "value='" . $this->db->escape($value) . "', "
          . "value0='" . $this->db->escape($value_original) . "', "
          . "display_value='" . $display_value . "'");
      }
    } else {
      //если значение нет просто удаляем, чтобы не плодить записи в БД
      $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_datetime "
        . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    }
  }

  /**
   * Возвращает значение поля
   * @param type $field_uid
   * @param type $document_uid
   * @param type $widget_value - значение, получаемое от виджета поля; возвращается value, которое пишется в базу данных
   * @return type
   */
  public function getValue($field_uid, $document_uid, $widget_value = NULL, $field_info = [])
  {
    if ($widget_value === NULL) {
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_datetime WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows > 0) {
        return $query->row['value'];
      } else {
        return null;
      }
    } else {
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      // $format = $this->dbformat;
      $formats = [];
      if (empty($field_info)) {
        $field_info = $this->model_doctype_doctype->getField($field_uid, 0);
      }
      if (!empty($field_info['params']['format'])) {
        $formats[] = $field_info['params']['format'];
      }
      if (strlen($widget_value) == 10) {
        //передана дата без времени, меняем формат, чтобы получить дату в 0 часов 0 минут 0 секунд
        $dbformat = 'Y-m-d 00:00:00';
        $formats[] = 'Y-m-d';
      } else if (strlen($widget_value) >= 19 && strpos($widget_value, "T") !== false) {
        //2020-01-10T15:55:00+03:00
        $wva = explode("T", $widget_value);
        $widget_value = $wva[0];
        if (strpos($wva[1], "+")) {
          $wva1 = explode("+", $wva[1]);
          $widget_value .= " " . $wva1[0];
        }
        $dbformat = $this->dbformat;
      } else {
        $dbformat = $this->dbformat;
      }
      $formats[] = $dbformat;
      foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $widget_value);
        if ($date) {
          return $date->format($dbformat);
        }
      }
      return null;
    }
  }

  public function refreshDisplayValues($data)
  {
    $format = $data['params']['format'] ?? "";
    $time = $data['params']['time'] ?? "";
    $new_format = $data['new_params']['format'] ?? "";
    $new_time = $data['new_params']['time'] ?? "";

    if (strcmp($new_format, $format) !== 0 || strcmp($new_time, $time) !== 0) {
      //ИЗМЕНИЛСЯ ФОРМАТ ДАТЫ
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_datetime WHERE "
        . "field_uid='" . $this->db->escape($data['field_uid']) . "' ");
      if (strpos($new_format, "H") === false) {
        //НОВЫЙ ФОРМАТ БЕЗ ВРЕМЕНИ, ИЗМЕНЯЕМ VALUE
        $time_format = $data['new_params']['time'] ?? "start";
        switch ($time_format) {
          case "start":
            $add_time = " 00:00:00";
            break;
          case "current":
            $add_time = "";
            break;
          case "end":
            $add_time = " 23:59:59";
            break;
        }
        foreach ($query->rows as $row) {
          if ($row['value'] !== null) {
            if (!$add_time) {
              //добавляемого времени нет, значит, нужно использовать value0, т.к. нужно текущее время изменения значения
              $value = $row['value0'];
            } else {
              $value = substr($row['value'], 0, 10) . $add_time;
            }

            $date = DateTime::createFromFormat($this->dbformat, $row['value']);
            $display_value = $date->format($new_format);

            $this->db->query("UPDATE " . DB_PREFIX . "field_value_datetime SET "
              . "value='" . $value . "', "
              . "value0='" . $row['value0'] . "', "
              . "display_value='" . $display_value . "' "
              . "WHERE document_uid = '" . $row['document_uid'] . "' "
              . "AND field_uid='" . $this->db->escape($data['field_uid']) . "'");
          }
        }
      } else {
        //новый формат со временем
        //возможно ранее было преобразование со временем => без времени; если так, то оригинал даты value0 содержит время
        foreach ($query->rows as $row) {
          if ($row['value'] !== null && $row['value'] !== "0000-00-00 00:00:00") {
            if ($row['value0']) {
              $value = $row['value0'];
            } else {
              $value = $row['value'];
            }
            $date = DateTime::createFromFormat($this->dbformat, $value);
            $display_value = $date->format($new_format);
            $this->db->query("UPDATE " . DB_PREFIX . "field_value_datetime SET "
              . "value='" . $value . "', "
              . "display_value='" . $display_value . "' "
              . "WHERE document_uid = '" . $row['document_uid'] . "' "
              . "AND field_uid='" . $this->db->escape($data['field_uid']) . "'");
          }
        }
      }
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_datetime WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_datetime WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_datetime")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE `documentov`.`field_value_datetime` ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` DATETIME , `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_datetime ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_datetime ADD INDEX( `value`);");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_datetime ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_datetime ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $this->db->query("DROP TABLE " . DB_PREFIX . "field_datetime");
  }
}

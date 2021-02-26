<?php

class ModelExtensionFieldInt extends FieldModel
{

  public function formatDisplayValue($value, $delimiter)
  {
    if (!$value) {
      return '0';
    }
    $value = (int) preg_replace('/[,. ]/', '', $value);
    if ($value > 2147483647) {
      $value = 2147483647;
    }
    if ($value < -2147483648) {
      $value = -2147483648;
    }

    return number_format($value, 0, "", $delimiter);
  }


  public function editValue($field_uid, $document_uid, $field_value)
  {
    // echo "|$field_uid : $field_value|";
    if ($field_value === "") {
      $this->load->model('document/document');
      $this->model_document_document->removeFieldValue($field_uid, $document_uid);
      return;
    }
    $delimiter = "";
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid, 0);

    $value = $this->getValue($field_uid, $document_uid, $field_value, $field_info);
    // echo "|$value|";
    if (!empty($field_info['params']['delimiter'])) {
      $delimiter = $field_info['params']['delimiter'];
    }
    $display_value = $this->formatDisplayValue($value, $delimiter);
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_int WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");

    if ($query->num_rows) {
      $this->db->query("UPDATE " . DB_PREFIX . "field_value_int SET "
        . "value='" . (int) $value . "', "
        . "display_value='" . $display_value . "', "
        . "time_changed=NOW() "
        . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    } else {
      $this->db->query("INSERT INTO " . DB_PREFIX . "field_value_int SET "
        . "document_uid='" . $this->db->escape($document_uid) . "', "
        . "field_uid='" . $this->db->escape($field_uid) . "', "
        . "value='" . (int) $value . "', "
        . "display_value='" . $display_value . "'");
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
    // var_dump($widget_value);
    // exit;

    if ($widget_value === NULL) {
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_int WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows > 0) {
        return $query->row['value'];
      }
    } else if ($widget_value === "") {
      return $widget_value;
    } else {
      if (empty($field_info)) {
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($field_uid);
      }
      if (!empty($field_info['params']['delimiter'])) {
        $widget_value = str_replace($field_info['params']['delimiter'], '', $widget_value);
      }
      $value = (int) $widget_value;
      if (isset($field_info['params']['min']) && $field_info['params']['min'] !== "" && $value < (int) $field_info['params']['min']) {
        $value = (int) $field_info['params']['min'];
      }
      if (isset($field_info['params']['max']) && $field_info['params']['max'] !== "" && $value > (int) $field_info['params']['max']) {
        $value = (int) $field_info['params']['max'];
      }
      return $value;
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_int WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_int WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  public function refreshDisplayValues($data)
  {

    $delimiter = "";
    if (!empty($data['params']) and !empty($data['params']['delimiter'])) {
      $delimiter = $data['params']['delimiter'];
    }
    $new_delimiter = "";
    if (!empty($data['new_params']) and !empty($data['new_params']['delimiter'])) {
      $new_delimiter = $data['new_params']['delimiter'];
    }
    if (strcmp($new_delimiter, $delimiter) !== 0) {
      $query = $this->db->query("SELECT value, document_uid FROM " . DB_PREFIX . "field_value_int WHERE "
        . "field_uid='" . $this->db->escape($data['field_uid']) . "' ");
      foreach ($query->rows as $row) {
        $display_value = number_format($row['value'], 0, "", $this->db->escape($new_delimiter));
        $this->db->query("UPDATE " . DB_PREFIX . "field_value_int SET "
          . "display_value='" . $display_value . "' "
          . "WHERE document_uid = '" . $row['document_uid'] . "' "
          . "AND field_uid='" . $this->db->escape($data['field_uid']) . "'");
      }
    }
  }

  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_int")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_int ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` INT, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_int ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_int` ADD INDEX( `value`);");
    $this->db->query("ALTER TABLE `field_value_int` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_int` ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $this->db->query("DROP TABLE field_int");
  }
}

<?php

class ModelExtensionFieldViewdoc extends FieldModel
{

  public function editValue($field_uid, $document_uid, $field_value)
  {
    $this->load->model('tool/utils');
    if ($field_value && !$this->model_tool_utils->validateUID($field_value)) {
      $field_value = "";
    }

    $this->db->query(
      "REPLACE INTO " . DB_PREFIX . "field_value_viewdoc SET "
        . "field_uid = '" . $this->db->escape($field_uid) . "',"
        . "document_uid = '" . $this->db->escape($document_uid) . "', "
        . "value='" . $this->db->escape($field_value) . "', "
        . "display_value='', "
        . "time_changed=NOW() "

    );
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
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_viewdoc WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows > 0) {
        return ($query->row['value']);
      }
    } else {
      if ($widget_value && !$this->model_tool_utils->validateUID($widget_value)) {
        $widget_value = "";
      }
      return $widget_value;
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_viewdoc WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_viewdoc WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  public function refreshDisplayValues($data)
  {
  }

  public function install()
  {
    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable("field_value_viewdoc")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_viewdoc ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` MEDIUMTEXT, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_viewdoc ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_viewdoc` ADD INDEX( `value`(250));");
    $this->db->query("ALTER TABLE `field_value_viewdoc` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_viewdoc` ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $query = $this->db->query("SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME = 'field_value_viewdoc'");
    if ($query->num_rows) {
      $this->db->query("DROP TABLE field_value_viewdoc");
    }
  }
}

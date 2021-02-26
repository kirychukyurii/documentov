<?php

class ModelExtensionFieldString extends FieldModel
{

  public function editValue($field_uid, $document_uid, $value)
  {
    $value = htmlentities(html_entity_decode(html_entity_decode($value))); //двойное преобразование, т.к. htmlentities могло быть уже выполнено + htmlentities выполняется в fieldcontoller
    $this->db->query("REPLACE INTO " . DB_PREFIX . "field_value_string SET "
      . "field_uid = '" . $this->db->escape($field_uid) . "', "
      . "document_uid = '" . $this->db->escape($document_uid) . "', "
      . "value='" . $this->db->escape($value) . "', "
      . "display_value='" . $this->db->escape(trim($value)) . "', "
      . "time_changed=NOW() ");
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
      // $cache_name = "field_value_" . $document_uid;
      // $cache = $this->cache->get($cache_name, $field_uid);
      // if ($cache) {
      //   return $cache;
      // }
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_string WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      $result = $query->row['value'] ?? "";
      // $this->cache->set($cache_name, $result, $field_uid);
      return $result;
    } else {
      return $widget_value;
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_string WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_string WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }



  public function install()
  {
    $this->load->model('tool/utils');
    if (!$this->model_tool_utils->isTable('field_value_string')) {
      $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_string ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` VARCHAR(255) NOT NULL, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_string ADD PRIMARY KEY field_uid (field_uid,document_uid)");
      $this->db->query("ALTER TABLE `field_value_string` ADD INDEX( `value`(250));");
      $this->db->query("ALTER TABLE `field_value_string` ADD INDEX( `display_value`(250));");
      $this->db->query("ALTER TABLE `field_value_string` ADD INDEX( `time_changed`);");
    }
  }

  public function uninstall()
  {
    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable('field_value_string')) {
      $this->db->query("DROP TABLE field_value_string");
    }
  }
}

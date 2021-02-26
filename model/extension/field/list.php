<?php

class ModelExtensionFieldList extends FieldModel
{

  public function editValue($field_uid, $document_uid, $field_value)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_list WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    // if ($values) == n
    if (!is_array($field_value)) {
      $values = explode(",", $field_value);
    } else {
      $values = $field_value;
    }
    if ($values) {
      $displays = array();
      if (!empty($field_info['params']['source_type']) && $field_info['params']['source_type'] == "field") {
        $source_values = $this->load->controller('extension/field/list/getValuesFromField', array_merge($field_info['params'], array('document_uid' => $document_uid)));
      } else {
        $source_values = $field_info['params']['values'];
      }
      foreach ($values as $value) {
        foreach ($source_values as $variant) {
          if ($variant['value'] === $value) {
            $displays[] = $variant['title'];
          }
        }
      }
      $display = implode(",", $displays);
      if ($query->num_rows) {
        $this->db->query("UPDATE " . DB_PREFIX . "field_value_list SET "
          . "value='" . $this->db->escape(implode(',', $values)) . "', "
          . "display_value='" . $this->db->escape($display) . "', "
          . "time_changed=NOW()  "
          . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
      } else {
        $this->db->query("INSERT INTO " . DB_PREFIX . "field_value_list SET "
          . "document_uid='" . $this->db->escape($document_uid) . "', "
          . "field_uid='" . $this->db->escape($field_uid) . "', "
          . "value='" . $this->db->escape(implode(',', $values)) . "', "
          . "display_value='" . $this->db->escape($display) . "' ");
      }
    } else {
      $query = $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_list WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
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
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_list WHERE "
        . "document_uid = '" . $this->db->escape($document_uid) . "' AND "
        . "field_uid = '" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows) {
        if (!isset($query->row['value']) || strtoupper($query->row['value']) == "NULL") {
          return "";
        }
        return $query->row['value'];
      }
    } else {
      if (is_array($widget_value)) {
        $values = implode(",", $widget_value);
      } else {
        $values = $widget_value;
      }
      return $values;
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_list WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_list WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }


  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_list")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_list ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` VARCHAR(255) NOT NULL, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_list ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_list` ADD INDEX( `value`);");
    $this->db->query("ALTER TABLE `field_value_list` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_list` ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $this->db->query("DROP TABLE " . DB_PREFIX . "field_value_list");
  }

  public function get_ftsearch_index($field_uid, $document_uid)
  {
    $query = $this->db->query("SELECT DISTINCT display_value FROM " . DB_PREFIX . "field_value_list WHERE "
      . "document_uid='" . $this->db->escape($document_uid) . "' AND "
      . "field_uid='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows) {
      return strip_tags(htmlspecialchars_decode($query->row['display_value']));
    }
    return "";
  }
}

<?php

class ModelExtensionFieldViewfield extends FieldModel
{

  public function editValue($field_uid, $document_uid, $field_value)
  {
    //стандартный сеттер
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    $field_with_field_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_field_uid'], $document_uid));
    $real_field_uid = $field_with_field_value[0] ?? "";
    $field_with_document_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_document_uid'], $document_uid));
    $real_document_uid = $field_with_document_value[0] ?? "";
    if (empty($field_info['params']['viewfield_type'])) {
      // не выбран тип поля для образа
      return "";
    }
    $model = "model_extension_field_" . $field_info['params']['viewfield_type'];
    $this->load->model('extension/field/' . $field_info['params']['viewfield_type']);
    $this->$model->editValue($real_field_uid, $real_document_uid, $field_value);
    $this->cache->delete("field_value_" . $real_document_uid, $real_field_uid);
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
    //стандартный геттер
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (empty($field_info['params']['viewfield_type'])) {
      // не выбран тип поля для образа
      return "";
    }
    $field_with_field_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_field_uid'], $document_uid));
    $real_field_uid = $field_with_field_value[0] ?? "";
    $field_with_document_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_document_uid'], $document_uid));
    $real_document_uid = $field_with_document_value[0] ?? "";
    $model = "model_extension_field_" . $field_info['params']['viewfield_type'];
    $this->load->model('extension/field/' . $field_info['params']['viewfield_type']);
    return $this->$model->getValue($real_field_uid, $real_document_uid, $widget_value);
  }

  public function removeValue($field_uid, $document_uid)
  {
  }

  public function removeValues($field_uid)
  {
  }

  public function refreshDisplayValues($data)
  {
  }

  public function install()
  {
    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable("field_value_viewfield")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_viewfield ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` MEDIUMTEXT, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_viewfield ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_viewfield` ADD INDEX( `value`(250));");
    $this->db->query("ALTER TABLE `field_value_viewfield` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_viewfield` ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $query = $this->db->query("SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME = 'field_value_viewfield'");
    if ($query->num_rows) {
      $this->db->query("DROP TABLE field_value_viewfield");
    }
  }
}

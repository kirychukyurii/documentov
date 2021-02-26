<?php


class ModelExtensionFieldHidden extends FieldModel
{
  public function editValue($field_uid, $document_uid, $value)
  {
    $data = [
      'field_uid' => $field_uid,
      'document_uid' => $document_uid,
      'value' => $value,
      'session' => [
        'user_uid'  => $this->customer->getStructureId(),
        'language_id'   => (int) $this->config->get('config_language_id'),
        'pressed_button_uid' => $this->session->data['current_button_uid'] ?? "",
        'changed_field_uid' => $this->request->get['field_uid'] ?? "",
        'changed_field_value' => $this->request->get['field_value'] ?? "",
        'folder_uid' => $this->request->get['folder_uid'] ?? "",
      ]
    ];
    $this->daemon->exec("SaveFieldValue", $data);
    return;

    //проверяем $value
    // if ($value != "*******" && $value != "") {
    //   //проверяем не хэш ли пароля пришел
    //   if (!empty(password_get_info($value)['algo'])) {
    //     $value_hash = $value;
    //   } else {
    //     //получаем параметры поля
    //     $this->load->model('doctype/doctype');
    //     $field_info = $this->model_doctype_doctype->getField($field_uid);
    //     if ($field_info['params']['type_hash'] == 1) {
    //       //однонаправленное хеширование
    //       $value_hash = password_hash($value, PASSWORD_DEFAULT);
    //     } else {
    //       //двунаправленное хеширование
    //       $value_hash = openssl_encrypt($value, "AES-256-CBC", $this->config->get('hidden_field_password'), 0, $this->config->get('hidden_field_iv'));
    //     }
    //   }
    //   $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_hidden WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    //   if ($query->num_rows) {
    //     $this->db->query("UPDATE " . DB_PREFIX . "field_value_hidden SET "
    //       . "value='" . $value_hash . "', "
    //       . "display_value='*******', "
    //       . "time_changed=NOW() "
    //       . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    //   } else {
    //     $this->db->query(
    //       "INSERT INTO " . DB_PREFIX . "field_value_hidden SET "
    //         . "document_uid='" . $this->db->escape($document_uid) . "', "
    //         . "field_uid='" . $this->db->escape($field_uid) . "', "
    //         . "value='" . $value_hash . "', "
    //         . "display_value='*******' "
    //     );
    //   }
    // }
  }

  public function getValue($field_uid, $document_uid, $widget_value = NULL, $field_info = [])
  {
    if ($widget_value === NULL) {
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_hidden WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows > 0) {
        return $query->row['value'];
      }
    } else {
      return $widget_value;
    }
  }

  public function refreshDisplayValues($data)
  {
  }

  public function getDecryptValue($field_uid, $document_uid)
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if ($field_info['params']['type_hash'] == 2) {
      //двунаправленный хеш
      $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_hidden WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "'");
      if ($query->num_rows) {
        return openssl_decrypt($query->row['value'], "AES-256-CBC", $this->config->get('hidden_field_password'), 0, $this->config->get('hidden_field_iv'));
      } else {
        return '';
      }
    } else {
      return '';
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_hidden WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_hidden WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_hidden")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_hidden ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` VARCHAR(255) NOT NULL, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_hidden ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_hidden` ADD INDEX( `value`);");
    $this->db->query("ALTER TABLE `field_value_hidden` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_hidden` ADD INDEX( `time_changed`);");
    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET code='dv_field_hidden', key='hidden_field_password', value = '" . token() . "', serialized=0");
    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET code='dv_field_hidden', key='hidden_field_iv', value = '" . token(16) . "', serialized=0");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $this->db->query("DROP TABLE field_value_hidden");
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code='dv_field_hidden' AND key='hidden_field_password'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code='dv_field_iv' AND key='hidden_field_password'");
  }
}

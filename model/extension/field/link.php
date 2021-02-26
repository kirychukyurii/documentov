<?php

class ModelExtensionFieldLink extends FieldModel
{

  public function editValue($field_uid, $document_uid, $field_value)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    $display_value = array();
    $field_values = array();
    $tmp_field_values = array();
    if (!is_array($field_value)) {
      $tmp_field_values = explode(",", $field_value);
    } else {
      $tmp_field_values = $field_value;
    }
    foreach ($tmp_field_values as $value) {
      if ($this->model_tool_utils->validateUID(trim($value))) {
        $field_values[] = trim($value);
      }
    }
    $field_values = array_unique($field_values);
    $doctype_field_uid = $this->getDoctypeFieldUid($field_info);

    if (!$field_info['params']['multi_select'] && !empty($field_values[0])) {
      $field_value_db = $field_values[0];
    } else {
      $field_value_db = implode(",", $field_values);
    }

    if ($field_value_db) {
      $display_value = $this->getDisplay($document_uid, $field_uid, $field_value_db, $field_info);
    } else {
      $display_value = "";
    }
    $this->db->query("REPLACE INTO " . DB_PREFIX . "field_value_link SET " //`name` = '" . $this->db->escape($name) . "', `value` = '" . $this->db->escape($value) . "'");
      . "field_uid = '" . $this->db->escape($field_uid) . "', "
      . "document_uid = '" . $this->db->escape($document_uid) . "', "
      . "value='" . $this->db->escape($field_value_db) . "', "
      . "display_value='" . $this->db->escape(trim(strip_tags($display_value))) . "', "
      . "time_changed=NOW(), "
      . "full_display_value='" . $this->db->escape(str_replace(", ", $field_info['params']['delimiter'], $display_value)) . "' ");


    //подписка на изменение отображаемого поля целевого документа
    $this->model_doctype_doctype->delSubscription($field_uid, $document_uid);
    if (empty($field_info['params']['disabled_actualize'])) {
      $this->model_doctype_doctype->addSubscription($field_uid, $document_uid, $doctype_field_uid, $field_values);
    }
  }

  /**
   * Метод возвращает полный дисплей ссылки (включая гиперссылки, если так стоит в настройках); множественное значение разделено запятыми
   * @param type $document_uid
   * @param type $field_uid
   * @param type $field_value - если не передано, получаем из БД
   * @param type $field_info - если не педеано, метод получает сам
   * @return type
   */
  public function getDisplay($document_uid, $field_uid, $field_value = "", $field_info = "")
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (!$field_value && $field_uid && $document_uid) {
      $field_value = $this->getValue($field_uid, $document_uid);
    }
    if (!$field_info) {
      $field_info = $this->model_doctype_doctype->getField($field_uid);
    }
    if (!$field_value || !$field_info) {
      return "";
    }
    $display_value = array();
    $doctype_field_uid = $this->getDoctypeFieldUid($field_info);

    if (is_array($field_value)) {
      $field_values = $field_value;
    } else {
      $field_values = explode(",", $field_value);
    }
    if (!$field_info['params']['multi_select'] && !empty($field_values[0])) {
      $field_values = array($field_values[0]);
    }

    foreach ($field_values as $value) {
      if (!$value) {
        continue;
      }
      $data = array(
        'url'           => $this->url->link('document/document', 'document_uid=' . $value, true, true), //относительный урл, чтобы при смене доменного имени в системе не сохранились старые урлы
        'text'          => htmlentities(str_replace(",", "&#44;", strip_tags(html_entity_decode($this->model_document_document->getFieldDisplay($doctype_field_uid, $value))))), //вырезаем теги и обратно преобразуем " в &quote;  и пр
        'document_uid'  => $value,
        'href'          => $field_info['params']['href'] ?? 0
      );
      $display_value[] = trim($this->load->view('field/link/link_widget_view_link', $data));
    }
    return implode(", ", $display_value);
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
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_link WHERE "
        . "field_uid = '" . $this->db->escape($field_uid) . "' AND "
        . "document_uid = '" . $this->db->escape($document_uid) . "' ");
      if ($query->num_rows) {
        $result = $query->row['value'] ?? "";
        // $this->cache->set($cache_name, $result, $field_uid);
        return $result;
      }
    } else {
      if (is_array($widget_value)) {
        return implode(",", $widget_value);
      }
      return $widget_value ?? "";
    }
    return "";
  }

  /**
   * Метод возвращает value + все дисплеи
   * @param type $field_uid
   * @param type $document_uid
   * @return type
   */
  public function getFieldValue($field_uid, $document_uid)
  {
    if (strlen($field_uid) > 36) {
      return [];
    }
    $data = [
      'field_uid' => $field_uid,
      'document_uid' => (string)$document_uid
    ];
    $field_value = $this->daemon->exec("GetFieldValue", $data);
    // print_r($field_value);
    // exit;
    if (is_array($field_value)) {
      $field_value['full_display_value'] = $field_value['cache'] ?? "";
    }
    return $field_value ?? [];
  }

  public function removeValue($field_uid, $document_uid)
  {
    // $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_link WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    // $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_link WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  /**
   * Обновление отображаемого значения
   * @param type $data = array(
   *              field_uid - идентификаторы поля
   *              new_params - если установлен, значит это новые параметры поля
   *              document_uids - если установлен, обновляются не все документы, а только указанные в этом массиве         
   * )
   * @return type
   */
  public function refreshDisplayValues($data)
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
    if (!$field_info) {
      //удалить подписку
      $this->model_doctype_doctype->delSubscription($data['field_uid']);
      return "Field not found";
    }
    if (!empty($field_info['params']['disabled_actualize'])) {
      $this->model_doctype_doctype->delSubscription($data['field_uid']);
    }
    $this->cache->delete('', $field_info['doctype_uid']);
    if (empty($data['document_uids'])) {
      //обновление полей всех документов из-за изменения параметров ссылочного поля, получаем их
      $query = $this->db->query("SELECT value, document_uid FROM " . DB_PREFIX . "field_value_link WHERE "
        . "field_uid='" . $this->db->escape($data['field_uid']) . "' ");
      if ($query->num_rows) {
        $field_info['params'] = $data['new_params'];
        $doctype_field_uid = $this->getDoctypeFieldUid($field_info);
        foreach ($query->rows as $field) {
          $display_value = $this->getDisplay($data['field_uid'], $field['document_uid'], $field['value'], $field_info);
          $this->db->query("UPDATE " . DB_PREFIX . "field_value_link SET "
            . "display_value='" . $this->db->escape(trim(strip_tags($display_value))) . "', "
            . "full_display_value='" . $this->db->escape(str_replace(", ", $field_info['params']['delimiter'], $display_value)) . "' "
            . "WHERE document_uid = '" . $field['document_uid'] . "' "
            . "AND field_uid = '" . $this->db->escape($data['field_uid']) . "' ");
          if (empty($field_info['params']['disabled_actualize'])) {
            $this->model_doctype_doctype->addSubscription($data['field_uid'], $field['document_uid'], $doctype_field_uid, $field['value']);
          }
        }
      }
    } else {
      //обновление дисплея конкретных документов по подписке на изменение отображаемого поля
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_link WHERE "
        . "field_uid='" . $this->db->escape($data['field_uid']) . "' AND document_uid IN ('" . implode("','", $data['document_uids']) . "') ");
      if (!$query->num_rows) {
        return;
      }

      foreach ($query->rows as $field) {
        $display_value = $this->getDisplay($field['field_uid'], $field['document_uid'], $field['value'], $field_info);
        $this->db->query("UPDATE " . DB_PREFIX . "field_value_link SET "
          . "display_value='" . $this->db->escape(trim(strip_tags($display_value))) . "', "
          . "full_display_value='" . $this->db->escape(str_replace(", ", $field_info['params']['delimiter'], $display_value)) . "' "
          . "WHERE document_uid = '" . $field['document_uid'] . "' "
          . "AND field_uid = '" . $this->db->escape($field['field_uid']) . "' ");
      }
    }
    $this->cache->delete("", $data['field_uid']);
    $this->cache->delete("", $field_info['doctype_uid']);
  }

  public function install()
  {
    $this->load->model('tool/utils');
    if (!$this->model_tool_utils->isTable('field_value_link')) {
      $this->db->query("CREATE TABLE field_value_link ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` MEDIUMTEXT NOT NULL, `display_value` VARCHAR(256), `full_display_value` MEDIUMTEXT NOT NULL, `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
      $this->db->query("ALTER TABLE field_value_link ADD PRIMARY KEY field_uid (field_uid,document_uid)");
      $this->db->query("ALTER TABLE field_value_link ADD INDEX( `value`(250));");
      $this->db->query("ALTER TABLE field_value_link ADD INDEX( `display_value`);");
      $this->db->query("ALTER TABLE field_value_link ADD INDEX( `time_changed`);");
    }
  }

  public function uninstall()
  {
    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable('field_value_link')) {
      $this->db->query("DROP TABLE field_value_link");
    }
  }

  /**
   * Этот метод вызывается, когда изменится целевое поле по подписке
   * @param type $field_uid - идентификатор ссылочного поля
   * @param type $document_uids - документы, для которых сработала подписка
   */
  public function subscription($field_uid, $document_uids)
  {
    return $this->refreshDisplayValues(array('field_uid' => $field_uid, 'document_uids' => $document_uids));
  }

  public function get_ftsearch_index($field_uid, $document_uid)
  {
    $query = $this->db->query("SELECT DISTINCT display_value FROM " . DB_PREFIX . "field_value_link WHERE "
      . "document_uid='" . $this->db->escape($document_uid) . "' AND "
      . "field_uid='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows) {
      return strip_tags(htmlspecialchars_decode($query->row['full_display_value']));
    }
    return "";
  }

  /**
   * Метод возвращает идентификатор отображаемого поля
   */
  private function getDoctypeFieldUid($field_info)
  {
    if (empty($field_info['params']['doctype_field_uid'])) {
      //не задано отображаемое поле
      //проверяем наличие заголовка
      $doctype_descriptions = $this->model_doctype_doctype->getDoctypeDescriptions($field_info['params']['doctype_uid']);
      if (!empty($doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'])) {
        $doctype_field_uid = $doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'];
      } else {
        //заголовка нет, используем первое поле доктайпа              
        $data_f = array(
          'doctype_uid'   => $field_info['params']['doctype_uid'],
          'setting'       => 0,
          'limit'         => 1
        );
        $fields = $this->model_doctype_doctype->getFields($data_f);
        $doctype_field_uid = $fields[0]['field_uid'] ?? "";
      }
    } else {
      $doctype_field_uid = $field_info['params']['doctype_field_uid'];
    }
    return $doctype_field_uid;
  }
}

<?php

class ModelExtensionFieldTable extends FieldModel
{

  public function editValue($field_uid, $document_uid, $field_value)
  {
    $this->load->model('doctype/doctype');
    $data = json_decode(htmlspecialchars_decode($field_value), true);
    $display_values = array();
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    foreach ($field_info['params']['inner_fields'] as $field) {
      $this->load->model('extension/field/' . $field['field_type']);
    }
    $result_value = array();
    if ($data && is_array($data)) {
      foreach ($data as &$values) {

        $values = array_values($values); //сброс нумерации ключей на случай изменений составов полей при записи из одной таблицы в другую   

        $rows = array();
        $i = 0;
        $temp_value = array();
        foreach ($field_info['params']['inner_fields'] as $inner_field) {
          if (isset($values[$i])) {
            $temp_value[] = (string)$values[$i];
            $inner_field['params'] = $inner_field['inner_field_params'];
            $field_param = $inner_field['params'];
            $model = "model_extension_field_" . $inner_field['field_type'];
            $field_param['field_value']  = $this->$model->getValue('', 0, $values[$i], $inner_field);
            $rows[] = $this->load->controller('extension/field/' . $inner_field['field_type'] . '/getView', $field_param);
          }

          $i++;
        }
        $result_value[] = $temp_value;
        $display_values[] = implode(" - ", $rows);
      }
    }
    $display_value = strip_tags(implode("; ", $display_values));
    if (mb_strlen($display_value) > 255) {
      $display_value = mb_substr($display_value, 0, 252) . "...";
    }
    $db_value = "";
    if ($result_value) {
      $db_value = json_encode($result_value);
    }
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_table WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    if ($query->num_rows) {
      $this->db->query("UPDATE " . DB_PREFIX . "field_value_table SET "
        . "value='" . $this->db->escape($db_value) . "', "
        . "display_value='" . $this->db->escape($display_value) . "', "
        . "view='', "
        . "time_changed=NOW() "
        . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    } else {
      $this->db->query("INSERT INTO " . DB_PREFIX . "field_value_table SET "
        . "document_uid='" . $this->db->escape($document_uid) . "', "
        . "field_uid='" . $this->db->escape($field_uid) . "', "
        . "value='" . $this->db->escape($db_value) . "', "
        . "view='', "
        . "display_value='" . $this->db->escape($display_value) . "'");
    }
    //обновляем view
    if ($result_value) {
      $table_view = [];
      foreach ($result_value as $key => $row_data) {
        $data_row = [
          'row_data' => $row_data,
          'inner_fields_info' => $field_info['params']['inner_fields'],
          'table_uid' =>  $field_uid,
          'row' => $key,
          'mode' => 'view'
        ];
        $table_view[$key] = $this->load->controller('extension/field/table/getTableRowView', $data_row);
      }
      $this->setView($field_uid, $document_uid, $table_view);
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
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_table WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' ");
      if ($query->num_rows) {
        return $query->row['value'];
      }
    } else {
      return $widget_value;
    }
  }

  /**
   * Метод возвращает строки таблицы с виджетами просмотра полей
   */
  public function getView($field_uid, $document_uid)
  {
    return [];
    // $cache_name = "field_table_view_" . $document_uid;
    // $cache = $this->cache->get($cache_name, $field_uid);
    // if ($cache) {
    //   return $cache;
    // }
    $query = $this->db->query("SELECT view FROM " . DB_PREFIX . "field_value_table WHERE "
      . "document_uid='" . $this->db->escape($document_uid) . "' AND "
      . "field_uid='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows && !empty($query->row['view'])) {

      $result = json_decode(html_entity_decode($query->row['view']), true);
      // $this->cache->set($cache_name, $result, $field_uid);
      return $result;
    }
    return [];
  }

  /**
   * Метод записывает строки таблицы с виджетами просмотра полей
   */
  public function setView($field_uid, $document_uid, $view)
  {
    return;
    $this->db->query(
      "UPDATE " . DB_PREFIX . "field_value_table SET "
        . "view = '" . $this->db->escape(json_encode($view)) . "' "
        . "WHERE "
        . "document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "field_uid='" . $this->db->escape($field_uid) . "' "
    );
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_table WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
  }

  public function removeValues($field_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_table WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
  }

  public function refreshDisplayValues($data)
  {
    if (!empty($data['params']['inner_fields'])) {
      $nochange = true;
      foreach ($data['params']['inner_fields'] as $key => $inner_field) {
        if (isset($data['new_params']['inner_fields'][$key]) && $inner_field['inner_field_uid'] != $data['new_params']['inner_fields'][$key]['inner_field_uid']) {
          $nochange = false;
          break;
        }
      }
      if ($nochange) {
        return;
      }
      $field_values = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_table WHERE field_uid = '" . $this->db->escape($data['field_uid']) . "' ");
      foreach ($field_values->rows as $field_value) {
        $table_value = json_decode($field_value['value'], true);
        $new_value = array();
        foreach ($table_value as $table_row) {
          $new_row = array();
          foreach ($data['new_params']['inner_fields'] as $new_key => $new_inner_field) {
            $newcol = true;
            foreach ($data['params']['inner_fields'] as $key => $inner_field) {
              if ($new_inner_field['inner_field_uid'] == $inner_field['inner_field_uid']) {
                if (isset($table_row[$key])) {
                  $new_row[] = $table_row[$key];
                } else {
                  $new_row[] = '';
                }
                $newcol = false;
                break;
              }
            }
            if ($newcol) {
              $new_row[] = '';
            }
          }
          $new_value[] = $new_row;
        }
        $this->load->model('document/document');
        $this->model_document_document->editFieldValue($field_value['field_uid'], $field_value['document_uid'], json_encode($new_value));
      }
    }
  }

  public function appendLogValue($field_uid, $document_uid, $log)
  {
    $this->load->model('doctype/doctype');
    $this->load->language('document/document');
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_table WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    $log_arr = array();
    if (!empty($query->row['value'])) {
      $value = json_decode($query->row['value']);
      if ($value) {
        $log_arr = $value;
      }
    }
    $log_arr[] = $log;
    $row_value = json_encode($log_arr);
    $this->model_document_document->editFieldValue($field_uid, $document_uid, $row_value);
  }


  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_table")) {
      return;
    }
    //создаем таблицу табличного поля. Поле value хранит значения ключа, по который соттветствует table_id  в таблице ячеек
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_table ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` MEDIUMTEXT, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_table ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_table` ADD INDEX( `value`(250));");
    $this->db->query("ALTER TABLE `field_value_table` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_table` ADD INDEX( `time_changed`);");
  }

  public function uninstall()
  {
    //удаляем таблицу поля
    $this->db->query("DROP TABLE field_value_table");
  }
}

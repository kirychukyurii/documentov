<?php

/**
 * @package		Documentov
 * @author		Romav V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionFieldTable extends FieldController
{

  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_num_rows'),
      array('type' => 'getter', 'name' => 'get_cell', 'params' => array('row', 'col')),
      array('type' => 'setter', 'name' => 'set_row', 'params' => array('row', 'col')),
      array('type' => 'setter', 'name' => 'add_row', 'params' => array('row', 'col')),
      array('type' => 'setter', 'name' => 'del_row', 'params' => array('row')),
    ),
    'work_progress_log' => true,
    'compound' => true,
  );

  public function index()
  {
  }

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/table', $data));
  }

  public function install()
  {
    $this->load->model('extension/field/table');
    $this->model_extension_field_table->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/table');
    $this->model_extension_field_table->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldTable::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/table');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $language_id = $this->config->get('config_language_id');
    $result = array();
    if (!empty($params['inner_fields'])) {
      $inner_fields = "";
      if (is_array($params['inner_fields'])) {
        $inner_fields = $params['inner_fields'];
      } else {
        $inner_fields = json_decode(htmlspecialchars_decode($params['inner_fields']), true);
      }

      foreach ($inner_fields as $ifld) {
        $result[] = '"' . $ifld['column_title'][$language_id] . '" (' . $this->load->controller('extension/field/' . $ifld['field_type'] . "/getTitle") . ')';
      }
    }
    return implode("; ", $result);
  }

  public function getAdminForm($data)
  {
    // print_r($data);
    // exit;

    $ifld_descriptions = array();
    if (!empty($data['params']) && !empty($data['params']['inner_fields'])) {
      foreach ($data['params']['inner_fields'] as &$ifld) {
        $ifld_descriptions[] = array(
          'name' => $this->load->controller('extension/field/' . $ifld['field_type'] . "/getTitle")
        );
        if (isset($ifld['inner_field_params'])) {
          $ifld['params'] = $ifld['inner_field_params'];
          unset($ifld['inner_field_params']);
        }
      }
      $data['params']['inner_fields'] = $this->jsonEncode($data['params']['inner_fields']);
      $data['inner_fields_descriptions'] = $this->jsonEncode($ifld_descriptions);
    }
    $data['language_id'] = $this->config->get('config_language_id');

    return $this->load->view($this->config->get('config_theme') . '/template/field/table/table_form', $data);
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    //$this->load->model('doctype/doctype');
    $language_id = $this->config->get('config_language_id');
    $data['language_id'] = $language_id;
    $table_view = array();
    if (isset($data['field_value']) && empty($data['filter_form'])) { //filter_form - запрос формы для фильтра журнала, это форма из простого input
      if (is_array($data['field_value'])) {
        $table_data = $data['field_value'];
        $data['field_value'] = $this->jsonEncode($table_data);
      } else {
        // $table_data = json_decode(htmlspecialchars_decode($data['field_value']), true);
        $table_data = json_decode(($data['field_value']), true);
      }

      if (is_array($table_data)) {

        foreach ($table_data as $key => $row_data) {
          $data_row = [
            'row_data' => $row_data,
            'inner_fields_info' => $data['inner_fields'],
            'table_uid' => $data['field_uid'],
            'row' => $key,
            'mode' => 'form'
          ];
          $table_view[$key] = $this->getTableRowView($data_row);
        }
      }
    }
    if (!empty($data['field_value'])) {
      $result_value = array();
      $field_value = json_decode($data['field_value'], TRUE);
      if (is_array($field_value)) {
        foreach ($field_value as $row_key => $row_data) {
          $result_value[$row_key] = $this->getTableRowData($row_data, $data['inner_fields']);
        }
      }

      $data['field_value'] = $result_value ? $this->jsonEncode($result_value) : "";
    }
    $data['table_view'] = $table_view;

    return $this->load->view('field/table/table_widget_form', $data);
  }

  private function getTableRowData($row_data, $inner_fields_info)
  {
    ksort($row_data);
    $row_data = array_values($row_data);
    // $row_view = '';
    $result = array();
    foreach ($inner_fields_info as $key => $field_info) {
      // $inner_field_uid = $field_info['inner_field_uid'];
      if (isset($row_data[$key])) {
        $result[] =  $row_data[$key];
      }
    }
    return $result;
  }

  public function getTableRowView($data)
  {
    $row_data = $data['row_data'];
    $inner_fields_info = $data['inner_fields_info'];
    $table_uid = $data['table_uid'];
    $row = $data['row'];
    $mode = $data['mode'] ?? "form";
    $row_data = array_values($row_data);
    $row_view = '';
    if (!$inner_fields_info || !is_array($inner_fields_info)) {
      return $row_view;
    }
    foreach ($inner_fields_info as $key => $field_info) {
      $field_type = $field_info['field_type'];
      if (isset($field_info['inner_field_params'])) {
        $field_param = $field_info['inner_field_params'];
      } else {
        $field_param = json_decode($field_info['params'], true);
      }

      $row_view .= '<td class="' . ($field_info['field_' . $mode . '_display'] ?? "") . '">';
      $inner_field_uid = $field_info['inner_field_uid'];
      if (isset($row_data[$key])) {
        $this->load->model('extension/field/' . $field_type);
        $model = "model_extension_field_" . $field_type;

        $field_info['params'] = $field_param;
        $cell_value = $this->$model->getValue('', 0, $row_data[$key], $field_info);
        $field_param['field_value'] = html_entity_decode($cell_value);
      }
      $field_param['document_uid'] = $this->request->get['document_uid'] ?? 0;
      $field_param['field_uid'] = $table_uid . '_' . $row . '_' . $inner_field_uid;
      $row_view .= $this->load->controller('extension/field/' . $field_type . '/getView', $field_param) . '</td>';
    }
    return $row_view;
  }


  //Возвращает форму для редактирования строки таблицы
  public function getTableRowForm()
  {
    $this->load->model('doctype/doctype');
    $data = array();
    $field_uid = $this->request->get['field_uid'];
    //$data['doctype_uid'] = $doctype_uid;
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $inner_fields = $this->model_doctype_doctype->getField($field_uid)['params']['inner_fields'];

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      // редактирование вложенных полей
      $row_data = json_decode(htmlspecialchars_decode($this->request->post['row_data']), true);
      foreach ($inner_fields as $key => $field) {
        if (isset($field['inner_field_params'])) {
          $field['params'] = $field['inner_field_params'];
        }
        $field_type = $field['field_type'];
        $field_param = $field['params'];
        $inner_field_uid = $field['inner_field_uid'];
        $field_param['field_uid'] = 'inner' . $inner_field_uid . $field_type;

        $this->load->model('extension/field/' . $field_type);
        $model = "model_extension_field_" . $field_type;
        if ($row_data[$key]) {
          $field_param['field_value'] = $this->$model->getValue('', 0, $row_data[$key], $field);
        } else {
          $field_param['field_value'] = '';
        }
        $inner_fields[$key]['field_form'] = $this->load->controller('extension/field/' . $field_type . '/getForm', $field_param);
      }
      $data['language_id'] = $this->config->get('config_language_id');
      $data['inner_fields'] = $inner_fields;
      $this->response->setOutput($this->load->view('field/table/table_row_widget_form', $data));
      return;
    } else {
      // добавление вложенных полей
      $cnt = 0;
      foreach ($inner_fields as $key => $field) {
        $field_type = $field['field_type'];
        if (isset($field['inner_field_params'])) {
          $field['params'] = $field['inner_field_params'];
        }
        $field_param = $field['params'];
        $inner_field_uid = $field['inner_field_uid'];
        $field_param['field_uid'] = 'inner' . $inner_field_uid . $field_type;
        $inner_fields[$key]['field_form'] = $this->load->controller('extension/field/' . $field_type . '/getForm', $field_param);
        $cnt++;
      }
      $data['language_id'] = $this->config->get('config_language_id');
      $data['inner_fields'] = $inner_fields;
      $this->response->setOutput($this->load->view('field/table/table_row_widget_form', $data));
      return;
    }

    //$this->response->setOutput($this->load->view('field/table/table_row_widget_form', $data));
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {

    $this->load->model('extension/field/table');
    $data = $this->setDefaultTemplateParams($data);
    $language_id = $this->config->get('config_language_id');
    $data['language_id'] = $language_id;
    $table_view = [];
    if (!empty($data['field_value']) && !isset($data['filter_view'])) { //наличие значения, вызов не из фильтров админки журнала (filter_view)
      $table_view = $this->model_extension_field_table->getView($data['field_uid'] ?? "", $data['document_uid'] ?? "");

      if (!$table_view || !count($table_view)) {
        $table_data = json_decode(($data['field_value']), true);

        if ($table_data) {
          foreach ($table_data as $key => $row_data) {
            $data_row = [
              'row_data' => $row_data,
              'inner_fields_info' => $data['inner_fields'],
              'table_uid' => $data['field_uid'],
              'row' => $key,
              'mode' => 'view'
            ];
            $table_view[$key] = $this->getTableRowView($data_row);
          }
          $this->model_extension_field_table->setView($data['field_uid'] ?? "", $data['document_uid'] ?? "", $table_view);
        } else {
          $data['field_value'] = ""; //массив с данными пустой, устанавливаем строковое значение поля пустым, чтобы в УШ работала проверка на пустоту
        }
      }
    }
    $data['table_view'] = $table_view;
    return $this->load->view('field/table/table_widget_view', $data);
  }

  //получить форму для вложенных полей
  public function getInnerFieldForm()
  {
    $this->load->model('doctype/doctype');
    $this->load->language('doctype/doctype');
    $doctype_uid = $this->request->get['doctype_uid'];
    $data = array();

    $avaliable_field_types = $this->load->controller('doctype/doctype/getFieldtypes');
    //убираем из списка составные поля
    $param = 'compound';
    foreach ($avaliable_field_types as $key => $field_type) {
      $field_info = $this->load->controller('extension/field/' . $field_type['name'] . '/getFieldInfo');
      if (isset($field_info[$param]) && $field_info[$param] === true) {
        unset($avaliable_field_types[$key]);
      }
    }
    $data['inner_fields'] = $avaliable_field_types;
    $data['doctype_uid'] = $doctype_uid;
    $data['languages'] = $this->model_localisation_language->getLanguages();

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      // редактирование вложенного поля
      $params = $this->request->post['params'];
      $field_type = $this->request->post['field_type'];
      $field_data = array();
      $field_data['params'] = $params;
      $field_data['doctype_uid'] = $doctype_uid;
      $data['inner_field_form'] = $this->load->controller('extension/field/' . $field_type . '/getAdminForm', $field_data);
      $data['inner_field_type'] = $field_type;
      $data['inner_field_form_display'] = $this->request->post['field_form_display'] ?? "";
      $data['inner_field_form_required'] = $this->request->post['field_form_required'] ?? "";
      $data['inner_field_view_display'] = $this->request->post['field_view_display'] ?? "";
      $data['column_title'] = $this->request->post['column_title'];

      $this->response->setOutput($this->load->view('field/table/table_inner_field_form', $data));
      return;
    } else {
      // добавление вложенного поля (после выбора типа поля подгружаем форму поля)
      if (isset($this->request->get['field_type'])) {
        $field_type = $this->request->get['field_type'];
        $this->response->setOutput($this->load->controller('extension/field/' . $field_type . '/getAdminForm', $data));
        return;
      }
    }
    // добавление вложенного поля, тип поля еще не выбран

    $this->response->setOutput($this->load->view('field/table/table_inner_field_form', $data));
  }

  public function getInnerFieldDescription()
  {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $field_type = $this->request->post['field_type'];
      $if_description = array('name' => $this->load->controller('extension/field/' . $field_type . "/getTitle"));
      $this->response->setOutput($this->jsonEncode($if_description));
    }
  }

  //Поле является составным
  public function isCompound()
  {
    return true;
  }

  /**
   * Метод возвращает методы поля (геттеры или сеттеры)
   */

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/table');
    switch ($data['method_name']) {
      case "get_cell":
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($data['field_uid'], 1);
        if (!empty($field_info['params']['inner_fields'])) {
          $data['columns'] = array();
          foreach ($field_info['params']['inner_fields'] as $column) {
            $data['columns'][] = array(
              'id'    => $column['inner_field_uid'],
              'name'  => $column['column_title'][$this->config->get('config_language_id')] ?? ""
            );
          }
        }
        return $this->load->view('field/table/method_get_cell_form', $data);
      case "add_row":
      case "set_row":
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($data['field_uid'], 1);
        if (!empty($field_info['params']['inner_fields'])) {
          $data['columns'] = array();
          foreach ($field_info['params']['inner_fields'] as $column) {
            $data['columns'][] = array(
              'id'    => $column['inner_field_uid'],
              'name'  => $column['column_title'][$this->config->get('config_language_id')] ?? ""
            );
          }
        }
        //проверяем наличие заполненных сопоставлений
        if (!empty($data['method_params']['accordance']['value'])) {
          $accordances = json_decode(htmlspecialchars_decode($data['method_params']['accordance']['value']), true);
          foreach ($accordances as &$accordance) {
            if (!empty($accordance['fieldUid'])) {
              $accordance['fieldName'] = $this->model_doctype_doctype->getFieldName($accordance['fieldUid']);
            }
          }
          $data['method_params']['accordance'] = $accordances;
        }
        return $this->load->view('field/table/method_set_row_form', $data);
      case "del_row":
        return $this->load->view('field/table/method_del_row_form', $data);
      default:
        return '';
    }
  }

  /**
   * Метод возрвращает количество строк в таблице
   */
  public function get_num_rows($params)
  {
    $this->load->model('document/document');
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $values = json_decode($field_value, TRUE);
    if ($values) {
      return count($values);
    }
    return 0;
  }

  /**
   * Метод получения значения ячейки по номеру строки и столбцу таблицы
   */
  public function get_cell($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');

    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);

    $values = json_decode($field_value, TRUE);
    if (!empty($values) && !empty($field_info['params']['inner_fields']) && isset($params['method_params']['row']) && isset($params['method_params']['col'])) {
      $order_num = -1;
      foreach ($field_info['params']['inner_fields'] as $column) { //вычисляем порядковый номер поля
        $order_num++;
        if ($column['inner_field_uid'] == $params['method_params']['col']) {
          break;
        }
      }
      return $values[--$params['method_params']['row'] ?? ""][$order_num] ?? "";
    }
    return "";
  }
  /**
   * Метод для записи значения в ячейку по номеру строки и столбцу таблицы
   */
  public function set_row($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');

    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);

    if (!empty($field_info['params']['inner_fields']) && isset($params['method_params']['accordance'])) {
      $accordance = json_decode(htmlspecialchars_decode($params['method_params']['accordance']), true); // значения новой строки

      if (!$accordance) {
        return;
      }
      $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']); //содержимое таблицы из БД
      $values = $field_value ? json_decode($field_value, TRUE) : array(); //текущая таблица
      $new_row = array(); //формируем новое значение строки $params['method_params']['row']

      $new_row_key = !empty($params['method_params']['row']) ? (int) $params['method_params']['row'] - 1 : -1;

      $field_order = 0; //порядковый номер поля
      foreach ($field_info['params']['inner_fields'] as $column) { //перебираем все поля
        foreach ($accordance as $acc) {
          if ($acc['fieldUid'] && $column['inner_field_uid'] == $acc['columnId']) { //для этого поля есть значение
            //получаем значение из поля
            $new_row[$column['inner_field_uid']] = $this->model_document_document->getFieldValue($acc['fieldUid'], $params['current_document_uid']);
          }
        }
        $new_row[$column['inner_field_uid']] = $new_row[$column['inner_field_uid']] ?? ($params['method_name'] == "set_row" ? $values[$new_row_key][$field_order] ?? "" : "");
        $field_order++;
      }

      switch ($params['method_name']) {
        case "set_row":
          if (isset($values[$new_row_key])) {
            $values[$new_row_key] = $new_row;
          }
          break;
        case "add_row":
          $new_values = array();
          foreach ($values as $key => $row) {
            if ($key == $new_row_key) {
              $new_values[] = $new_row;
            }
            $new_values[] = $row;
          }
          if (count($values) == count($new_values)) {
            //строку по индексу не добавили (возможно передано слишком большое число) - добавляем строку в конец
            $new_values[] = $new_row;
          }
          $values = $new_values;
          break;
      }
    }

    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $this->jsonEncode($values));
  }

  public function add_row($params)
  {
    return $this->set_row($params);
  }

  public function del_row($params)
  {
    $this->load->model('document/document');
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $values = json_decode($field_value, TRUE);
    if (isset($values[(int) $params['method_params']['row'] - 1])) {
      unset($values[(int) $params['method_params']['row'] - 1]);
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $this->jsonEncode($values));
    }
  }

  private function jsonEncode($value)
  {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  }
  public function setParams($data)
  {
    if (!$data['inner_fields']) {
      $data['inner_fields'] = [];
    }
    if (!is_array($data['inner_fields'])) {
      $data['inner_fields'] = json_decode(htmlspecialchars_decode($data['inner_fields']), true);
    }


    foreach ($data['inner_fields'] as &$inner_field) {
      $inner_field['field_form_required'] = (int) $inner_field['field_form_required'];
      $inner_field['inner_field_uid'] = (int) $inner_field['inner_field_uid'];
      if (isset($inner_field['params']['column_title'])) {
        unset($inner_field['params']['column_title']);
      }
      if (isset($inner_field['inner_field_params'])) {
        unset($inner_field['inner_field_params']);
      }
      $inner_field['params'] = $this->load->controller('extension/field/' . $inner_field['field_type'] . '/setParams', $inner_field['params']); // json_encode($this->load->controller('extension/field/' . $inner_field['field_type'] . '/setParams', $inner_field['params']), JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
    }
    $data['inner_fields'] = array_values($data['inner_fields']);
    // print_r($data);
    // exit;
    return $data;
  }
}

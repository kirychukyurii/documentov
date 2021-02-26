<?php

/**
 * @package		Documentov
 * @author		Romav V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerFieldTable extends Controller
{


  //возвращает проверенные данные строки таблицы и ее представление
  public function getTableRow()
  {
    $this->load->model('doctype/doctype');
    $this->load->language('extension/field/table');
    $field_uid = $this->request->get['field_uid'];
    $row_data = $this->request->post['field'];
    $inner_fields_info = $this->model_doctype_doctype->getField($field_uid)['params']['inner_fields'];
    $cnt = 0;
    $row_values = array();
    $errors = array();
    $data = array('rows' => array());

    foreach ($inner_fields_info as $key => $field_info) {
      $field_type = $field_info['field_type'];
      $field_info['params'] = $field_info['inner_field_params'];
      $field_param = $field_info['params'];
      $inner_field_uid = $field_info['inner_field_uid'];
      $field_name = 'inner' . $inner_field_uid . $field_type;

      if (isset($row_data[$field_name])) {
        $this->load->model('extension/field/' . $field_type);
        $model = "model_extension_field_" . $field_type;
        if ($row_data[$field_name]) {
          $cell_value = $this->$model->getValue('', 0, $row_data[$field_name], $field_info);
        } else {
          $cell_value = '';
        }
        if (
          (empty($field_info['field_form_display']) ||  $field_info['field_form_display'] !== 'hidden')
          && !empty($field_info['field_form_required']) && !$cell_value
        ) {
          $errors[] = $field_info['column_title'][$this->config->get('config_language_id')] ?? $field_name;
        }
        $row_values[] = $cell_value;
        $field_param['field_value'] = html_entity_decode($cell_value);
      } else {
        $row_values = '';
      }
      $cnt++;
      $field_param['document_uid'] = $this->request->get['document_uid'] ?? 0;
      $field_param['field_uid'] = $field_uid . '_' . $inner_field_uid;

      $data['rows'][] = array(
        'value' => $this->load->controller('extension/field/' . $field_type . '/getView', $field_param),
        'display' => $field_info['field_form_display'] ?? "",
        'required' => $field_info['field_form_required'] ?? ""
      );
    }
    $this->response->addHeader('Content-type: application/json');
    $result = array(
      'row_values' => $row_values,
      'row_views' => $this->load->view('field/table/table_widget_row', $data)
    );
    if ($errors) {
      $result['error'] = $this->language->get('error_required') . implode(", ", $errors);
    }
    $this->response->setOutput(json_encode($result));
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
        $field_type = $field['field_type'];
        $field['params'] = $field['inner_field_params'];
        $field_param = $field['params'];
        $inner_field_uid = $field['inner_field_uid'];
        $field_param['field_uid'] = 'inner' . $inner_field_uid . $field_type;
        $field_param['document_uid'] = $this->request->get['document_uid'] ?? "";
        $this->load->model('extension/field/' . $field_type);
        $model = "model_extension_field_" . $field_type;
        if (isset($row_data[$key])) {
          $field_param['field_value'] = html_entity_decode($this->$model->getValue('', 0, $row_data[$key], $field));
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
        $field['params'] = $field['inner_field_params'];
        $field_param = $field['params'];
        $inner_field_uid = $field['inner_field_uid'];
        $field_param['field_uid'] = 'inner' . $inner_field_uid . $field_type;
        $field_param['document_uid'] = $this->request->get['document_uid'] ?? "";
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
}

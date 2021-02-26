<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov 
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionFieldViewfield extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(),
    'compound' => true,
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    // $data['version'] = $this::version;
    $this->response->setOutput($this->load->view('extension/field/viewfield', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/viewfield');
    $this->model_extension_field_viewfield->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/viewfield');
    $this->model_extension_field_viewfield->uninstall();
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/viewfield');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $this->load->model('doctype/doctype');
    $descriptions = array();
    if ($params['viewfield_type']) {
      $descriptions[] = sprintf($this->language->get('text_source_field_type'), $this->load->controller('extension/field/' . $params['viewfield_type'] . '/getTitle'));
    }
    if ($params['field_with_field_uid']) {
      $descriptions[] = sprintf($this->language->get('text_source_value'), $this->model_doctype_doctype->getFieldName($params['field_with_field_uid']), $this->model_doctype_doctype->getFieldName($params['field_with_document_uid']));
    }
    return implode("; ", $descriptions);
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $this->load->model('doctype/doctype');
    $data['fields'] = $this->load->controller('doctype/doctype/getFieldtypes');
    if (!empty($data['params']['field_with_field_uid'])) {
      $data['field_with_field_name'] = $this->model_doctype_doctype->getFieldName($data['params']['field_with_field_uid']);
    }
    if (!empty($data['params']['field_with_document_uid'])) {
      $data['field_with_document_name'] = $this->model_doctype_doctype->getFieldName($data['params']['field_with_document_uid']);
    }
    return $this->load->view($this->config->get('config_theme') . '/template/field/viewfield/viewfield_form', $data);
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    if (empty($data['document_uid'])) {
      return $this->load->controller("extension/field/" . $data['viewfield_type'] . "/getForm", $data);
    } else {
      return "";
    }
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $d = array();
    $field_with_field_value = explode(",", $this->model_document_document->getFieldValue($data['field_with_field_uid'], $data['document_uid']));
    if (!empty($field_with_field_value[0])) {
      $field_info = $this->model_doctype_doctype->getField($field_with_field_value[0]);
      $d = $field_info['params'];
      $d['field_uid'] = $field_with_field_value[0];
      $field_with_document_value = explode(",", $this->model_document_document->getFieldValue($data['field_with_document_uid'], $data['document_uid']));
      $d['document_uid'] = $field_with_document_value[0] ?? "";
      $d['field_value'] = $this->model_document_document->getFieldValue($d['field_uid'], $d['document_uid']);
    } else {
      $d['field_uid'] = "";
      $d['document_uid'] = "";
    }


    return $this->load->controller("extension/field/" . $data['viewfield_type'] . "/getView", $d);
  }

  /**
   * Переопределение метода, возвращающего список методов
   * @param type $data => 
   *      method_type
   *      field_uid
   * @return type
   */
  public function getFieldMethods($data)
  {
    $this->load->model('doctype/doctype');
    $result = array();

    $field_info = $this->model_doctype_doctype->getField($data['field_uid']);

    if (empty($field_info['params']['viewfield_type'])) {
      return $result;
    }
    if (!file_exists(DIR_APPLICATION . "controller/extension/field/" . $field_info['params']['viewfield_type'] . ".php")) {
      return $result;
    }
    foreach ($this->load->controller("extension/field/" . $field_info['params']['viewfield_type'] . "/getFieldInfo")['methods'] as $method) {
      //если не задан тип метода, то возвращать все методы
      if (!$data['method_type'] || $data['method_type'] === $method['type']) {
        $method['alias'] = $this->language->get('text_method_' . $method['name']);
        $result[] = $method;
      }
    }
    return $result;
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {

    $this->load->model('doctype/doctype');
    if (empty($data['field_uid'])) {
      return "";
    }

    $field_info = $this->model_doctype_doctype->getField($data['field_uid']);

    if (!$field_info || !$field_info['params']['field_with_field_uid'] || !$field_info['params']['viewfield_type']) {
      return "";
    }
    // $this->load->language('extension/field/' . $field_info['params']['viewfield_type']);
    // $data2 = $data;
    // $data['method_params'] = $data['method_params']['TargetMethod'];
    // unset($data['method_params']['FUID']);
    // print_r($data);
    // exit;
    return $this->load->controller("extension/field/" . $field_info['params']['viewfield_type'] . "/getFieldMethodForm", $data);
  }

  private function getFieldMethodParams($field_type, $method_name, $method_params)
  {
    $this->load->model('doctype/doctype');

    $method_data = array(
      'method_name'   => $method_name,
      'method_params' => $method_params,
      'field_uid' => ""
    );

    // проверяем вложенные параметры на наличие методов
    foreach ($method_data['method_params'] as &$mp) {
      if (!empty($mp['field_uid'])  && !empty($mp['method_name']) && !empty($mp['method_params'])) {
        $mp_field_info = $this->model_doctype_doctype->getField($mp['field_uid']);
        if (empty($mp_field_info['params']['viewfield_type'])) {
          continue;
        }
        // метод есть, уходим в рекурсию
        $mp['method_params'] = $this->getFieldMethodParams($mp_field_info['params']['viewfield_type'], $mp['method_name'], $mp['method_params']);
      }
    }

    $method_params = $this->load->controller('extension/field/' . $field_type . "/setMethodParams", $method_data);


    if ($method_params !== NULL) {
      return $method_params;
    }
  }

  public function setMethodParams($data)
  {
    $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
    return $this->getFieldMethodParams($field_info['params']['viewfield_type'], $data['method_name'], $data['method_params']);
  }

  /**
   * Любой вызов метода поля обрабатывается этим методом
   * @param type $data
   * @return string
   */
  public function defaultMethod($data)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $result = "";
    $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
    if (!$field_info || !$field_info['params']['viewfield_type']) {
      return "";
    }
    $methods = $this->load->controller("extension/field/" . $field_info['params']['viewfield_type'] . "/getFieldInfo")['methods'];
    if (!$methods) {
      return $result;
    }
    foreach ($this->load->controller("extension/field/" . $field_info['params']['viewfield_type'] . "/getFieldInfo")['methods'] as $method) {
      if ($data['method_name'] === $method['name']) {
        if ($method['type'] === 'setter') {
          foreach ($data['document_uids'] as $document_uid) {
            $field_with_field_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_field_uid'], $document_uid));
            $data['field_uid'] = $field_with_field_value[0] ?? "";
            $field_with_document_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_document_uid'], $document_uid));
            $data['target_document_uid'] = $field_with_document_value[0] ?? "";
            $this->prepareParameters($data);
            $data['document_uid'] = $field_with_document_value[0] ?? "";
            $result = $this->load->controller('extension/field/' . $field_info['params']['viewfield_type'] . "/" . $data['method_name'], $data);
          }
        } else {
          $field_with_field_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_field_uid'], $data['document_uids'][0] ?? ""));
          $data['field_uid'] = $field_with_field_value[0] ?? "";
          $this->prepareParameters($data);
          $field_with_document_value = explode(",", $this->model_document_document->getFieldValue($field_info['params']['field_with_document_uid'], $data['document_uids'][0] ?? ""));
          unset($data['document_uids']);
          $data['document_uid'] = $field_with_document_value[0] ?? "";
          $result = $this->load->controller('extension/field/' . $field_info['params']['viewfield_type'] . "/" . $data['method_name'], $data);
        }
        break;
      }
    }
    return $result;
  }
}

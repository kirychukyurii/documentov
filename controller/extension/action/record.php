<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionActionRecord extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'record',
    'inRouteContext' => true,
    'inRouteButton' => true,
    'inFolderButton' => true
  );
  const max_length_description_manual = 70;

  public function index()
  {
    $this->load->language('extension/action/record');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/record', $data));
  }

  public function install()
  {
  }

  public function uninstall()
  {
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {

    if (isset($params['method'])) {
      $params['method_params'][$params['target_field_method_name'] . "_param"] = $params['method'];
    }
    $this->load->language('action/record');
    $this->load->model('doctype/doctype');

    //запись из: поля 'sf' документа по ссылке из поля 'slf' текущего документа
    //запись из: поля 'sf' текущего документа
    //запись из настроечного поля 'nf' типа документа
    //запись из переменной 'var'
    //в поле 'tf' документа по ссылке из поля 'tlf'
    //в поле 'tf' текущего документа

    $source_info = "";

    if (isset($params['method_params']) && isset($params['method_params']['standard_setter_param']['type'])) {
      switch ($params['method_params']['standard_setter_param']['type']) {
        case 'variable':
          if (!empty($params['method_params']['standard_setter_param']['var_id'])) {
            $source_variable_name = $this->language->get('text_' . $params['method_params']['standard_setter_param']['var_id']);
            $source_info = sprintf($this->language->get('text_description_source_4'), $source_variable_name);
          } else {
            $source_info = sprintf($this->language->get('text_description_source_4'), "");
          }
          break;
        case 'document':
          if (!empty($params['method_params']['standard_setter_param']['field_uid'])) {
            $source_field_description = $this->model_doctype_doctype->getField($params['method_params']['standard_setter_param']['field_uid']);
            $source_field_name = $source_field_description['name'];
            if (strcmp($params['method_params']['standard_setter_param']['doclink_field_uid'], '0') === 0) {
              $source_info = sprintf($this->language->get('text_description_source_2'), '"' . $source_field_name . '"');
            } elseif ($params['method_params']['standard_setter_param']['doclink_field_uid'] === 1) {
              $source_info = sprintf($this->language->get('text_description_source_5'), '"' . $source_field_name . '"');
            } else {
              $source_doclink_field_description = $this->model_doctype_doctype->getField($params['method_params']['standard_setter_param']['doclink_field_uid']);
              $source_doclink_field_name = $source_doclink_field_description['name'];
              $source_info = sprintf($this->language->get('text_description_source_1'), '"' . $source_field_name . '"', '"' . $source_doclink_field_name . '"');
            }
          } else {
            $source_info = sprintf($this->language->get('text_description_source_2'), "");
          }
          break;
        case 'doctype':
          if (!empty($params['method_params']['standard_setter_param']['field_uid'])) {
            $source_field_description = $this->model_doctype_doctype->getField($params['method_params']['standard_setter_param']['field_uid']);
            if ($source_field_description) {
              $source_field_name = $source_field_description['name'];
              $source_info = sprintf($this->language->get('text_description_source_3'), '"' . $source_field_name . '"');
            }
          } else {
            $source_info = sprintf($this->language->get('text_description_source_3'), "", "");
          }
          break;
        case 'value':
          if (isset($params['method_params']['standard_setter_param']['value'])) {
            if (is_array($params['method_params']['standard_setter_param']['value'])) {
              $value = implode(", ", $params['method_params']['standard_setter_param']['value']);
            }
            $source_info = strip_tags(html_entity_decode($value ?? $params['method_params']['standard_setter_param']['value']));
            if (mb_strlen($source_info) > $this::max_length_description_manual) {
              $source_info = mb_substr($source_info, 0, $this::max_length_description_manual) . "...";
            }
          }
      }
    }
    $target_info = "";
    if (!empty($params['target_field_uid'])) {
      $target_field_description = $this->model_doctype_doctype->getField($params['target_field_uid']);
      $this->load->language('extension/field/' . $target_field_description['type']);
      $target_field_name = $target_field_description['name'];
      if ($params['target_doclink_field_uid'] === '0') {
        if (!empty($params['target_type']) && $params['target_type'] === 'fielduid') {
          $target_info = sprintf($this->language->get('text_description_target_4'), '"' . $target_field_name . '"');
        } else {
          $target_info = sprintf($this->language->get('text_description_target_2'), '"' . $target_field_name . '"');
        }
      } else if ($params['target_type'] !== "doctype") { // для настроечного поля не указывается доктайп target_doclink_field_uid

        $target_doclink_field_description = $this->model_doctype_doctype->getField($params['target_doclink_field_uid']);
        $target_doclink_field_name = $target_doclink_field_description['name'];
        if ($params['target_type'] === 'fielduid') {
          $target_info = sprintf($this->language->get('text_description_target_3'), '"' . $target_field_name . '"', '"' . $target_doclink_field_name . '"');
        } else {
          $target_info = sprintf($this->language->get('text_description_target_1'), '"' . $target_field_name . '"', '"' . $target_doclink_field_name . '"');
        }
      }
      if (!empty($params['target_field_method_name']) && $params['target_field_method_name'] !== 'standard_setter') {
        $target_info .= " (" . $this->language->get('text_method_' . $params['target_field_method_name']) . ")";
      }
    } else {
      $target_info = sprintf($this->language->get('text_description_target_1'), "", "");
    }

    return $source_info . ' ' . $target_info;
  }

  private function viewfield($method_data)
  {
    if (isset($method_data['method_params']['TargetMethod'])) {
      $method_data['method_params'] = $method_data['method_params']['TargetMethod'];
      unset($method_data['method_params']['FUID']);
    }
    if (empty($method_data['method_params']) || !is_array($method_data['method_params'])) {
      return $method_data;
    }
    foreach ($method_data['method_params'] as &$mp) {
      $mp = $this->viewfield($mp);
    }
    return $method_data;
  }

  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    // print_r($data['action']);
    if (isset($data['action']['target_field_method_name'])) {
      $data['action'] = $this->restoreFieldMethodParams($data['action']);
    }
    // print_r($data['action']);
    // exit;

    $this->load->model('doctype/doctype');
    $this->load->language('action/record');
    $this->load->language('doctype/doctype');
    $lang_id = (int) $this->config->get('config_language_id');

    //приемник

    $data['target_doclink_field_name'] = $this->language->get('text_currentdoc');

    if (isset($data['action']['target_type']) && $data['action']['target_type'] != "doctype") {
      if (empty($data['action']['target_doclink_field_uid'])) {
        $data['action']['target_doclink_field_uid'] = '0';
      } else {
        $target_doclink_field = $this->model_doctype_doctype->getField($data['action']['target_doclink_field_uid']);
        $target_doclink_field_name = $this->language->get('text_by_link_in_field') . ' &quot;' . $this->model_doctype_doctype->getFieldName($data['action']['target_doclink_field_uid']) . '&quot;';
        $data['target_doclink_field_name'] = $target_doclink_field_name;
        $data['target_doclink_field_setting'] = $target_doclink_field['setting'];
      }
    } else {
      $data['action']['target_doclink_field_uid'] = '0';
    }


    if (!empty($data['action']['target_field_uid'])) {
      $target_field_description = $this->model_doctype_doctype->getField($data['action']['target_field_uid']);

      if ($target_field_description) {
        $target_field_type = $target_field_description['type'];
        $target_field_doctype_uid = $target_field_description['doctype_uid'];
        $doctypename = $this->model_doctype_doctype->getDoctypeDescriptions($target_field_doctype_uid)[$lang_id]['name'] ?? "";
        if ($data['action']['target_doclink_field_uid'] === '0' || $data['action']['target_type'] === 'fielduid') {
          $data['target_field_name'] = $this->model_doctype_doctype->getFieldName($data['action']['target_field_uid']);
        } else {
          $data['target_field_name'] = $doctypename . ' - ' . $target_field_description['name'];
        }
        $methods_data = array(
          'method_type'   => 'setter',
          'field_uid'     => $data['action']['target_field_uid']
        );
        $data['avaliable_setters'] = $this->load->controller('extension/field/' . $target_field_type . '/getFieldMethods', $methods_data);
        $method_data = array();
        $method_data['doctype_uid'] = $data['doctype_uid'];
        $method_data['field_uid'] = $data['action']['target_field_uid'];
        if (isset($data['action']['target_field_method_name'])) {
          $method_data['method_name'] = $data['action']['target_field_method_name'];
        } else {
          $method_data['method_name'] = 'standard_setter';
          $data['target_field_method_name'] = 'standard_setter';
        }
        if (isset($data['action']['method_params'])) {
          $method_data['method_params'] = $data['action']['method_params'];
        } else {
          $method_data['method_params'] = array();
        }
        $method_data['method_params_name_hierarchy'] = '[method_params]';
        if ($target_field_type == "viewfield") {
          $method_data = $this->viewfield($method_data);
        }

        $data['target_method_form'] = $this->load->controller('extension/field/' . $target_field_type . '/getMethodForm', $method_data);
      }
    } else {
      $data['target_method_form'] = $this->language->get('text_select_field');
    }

    return $this->load->view('action/record/record_context_form', $data);
  }



  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionRecord::ACTION_INFO;
  }

  /**
   * Вызывает setMethodParams метода поля при его наличии и передает ему сохраняемые параметры метода
   */
  private function getFieldMethodParams($field_uid, $method_name, $method_params)
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (empty($field_info['type'])) {
      return NULL;
    }
    $method_data = array(
      'method_name'   => $method_name,
      'method_params' => $method_params,
      'field_uid' => $field_uid
    );

    // проверяем вложенные параметры на наличие методов
    foreach ($method_data['method_params'] as &$mp) {
      if (!empty($mp['field_uid'])  && !empty($mp['method_name']) && !empty($mp['method_params'])) {
        // метод есть, уходим в рекурсию
        $mp['method_params'] = $this->getFieldMethodParams($mp['field_uid'], $mp['method_name'], $mp['method_params']);
      }
    }

    $method_params = $this->load->controller('extension/field/' . $field_info['type'] . "/setMethodParams", $method_data);

    if ($method_params !== NULL) {
      return $method_params;
    }
  }

  public function setParams($data)
  {
    // print_r($data['params']['action']);
    $data['params']['action']['history'] = empty($data['params']['action']['history']) ? 0 : 1;
    $action_info = $data['params']['action'];

    if (empty($action_info['method_params'])) {
      return $action_info;
    }

    // параметры для сеттера; возможно методу поля необходимо обработать параметры перед сохранением
    $method_params = $this->getFieldMethodParams($action_info['target_field_uid'], $action_info['target_field_method_name'], $action_info['method_params']);
    if ($method_params !== NULL) {
      $action_info['method_params'] = $method_params;
    }

    foreach ($action_info['method_params'] as &$method) {
      if (empty($method['type']) || ($method['type'] !== "document" && $method['type'] !== "doctype") || empty($method['field_uid'])) {
        continue;
      }
      $method_params = $this->getFieldMethodParams($method['field_uid'], $method['method_name'], $method['method_params'] ?? []);
      if ($method_params !== NULL && count($method_params)) {
        $method['method_params'] = $method_params;
      }
    }
    // print_r($action_info);
    // exit;
    return $action_info;
  }

  private function restoreFieldMethodParams($params)
  {
    $this->load->model("doctype/doctype");
    if (!empty($params['field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      if ($field_info['type'] == "viewfield") {
        $params = $this->viewfield($params);
      }
    }
    if (!empty($params['target_field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($params['target_field_uid']);
      if ($field_info['type'] == "viewfield") {
        $params = $this->viewfield($params);
      }
    }
    $method_name = $params['target_field_method_name'] ?? $params['method_name'] ?? "";
    if ($method_name === "insert_textes") {
      $params['method_params'] = $params['method_params']['textes'];
    }

    if ($method_name === "get_key_array" && isset($params['method_params']['elements'])) {
      foreach ($params['method_params']['elements'] as $el_name => $el_value) {
        $params['method_params'][$el_name] = $el_value;
      }
      unset($params['method_params']['elements']);
    }

    if ($method_name === "get_json" && isset($params['method_params']['elements'])) {
      foreach ($params['method_params']['elements'] as $el_name => $el_value) {
        $params['method_params'][$el_name] = $el_value;
      }
      unset($params['method_params']['elements']);
    }

    if ($method_name === "add_document" && isset($params['method_params']['fields'])) {
      $params['method_params'] = array_merge($params['method_params'], $params['method_params']['fields']);
      unset($params['method_params']['fields']);
    }

    if ($method_name === "add_key" && isset($params['method_params']['children'])) {
      $children = [];
      foreach ($params['method_params']['children'] as $i => $child) {

        $children["child_key_" . $i] = $child['name'];
        $children["child_field_" . $i] = $child['value'];
      }

      unset($params['method_params']['children']);
      $params['method_params'] = array_merge($params['method_params'], $children);
      // print_r($params['method_params']);
      // exit;
    }

    if ($method_name === "add_element" && isset($params['method_params']['children'])) {
      $children = [];
      foreach ($params['method_params']['children'] as $i => $child) {
        $children["child_type_" . $i] = $child['type'];
        $children["child_field_" . $i] = $child['value'];
        $children["child_branch_" . $i] = $child['branch'];
      }
      unset($params['method_params']['children']);
      $params['method_params'] = array_merge($params['method_params'], $children);
    }

    if (isset($params['method_params']) && is_array($params['method_params'])) {
      foreach ($params['method_params'] as &$param) {
        if (isset($param['method_params'])) {
          $param = $this->restoreFieldMethodParams($param);
        }
      }
    }

    return $params;
  }


  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    if (isset($data['params']['method'])) {
      if ($data['params']['target_field_method_name'] == "standard_setter") {
        $data['params']['method_params']['standard_setter_param'] = $data['params']['method'];
      } else {
        $data['params']['method_params'] = $data['params']['method']; // +записать множество значений
      }
    }
    $result = array();
    if (isset($data['document_uid'])) { //есть document_uid - запуск действия из документ
      $result = $this->executeRoute($data);
    } else { //запуск из журнала
      foreach ($data['document_uids'] as $document_uid) {
        $data['document_uid'] = $document_uid;
        $result = $this->executeRoute($data);
      }
    }
    return $result;
  }

  public function executeRoute($data)
  {

    $data['params'] = $this->restoreFieldMethodParams($data['params']);
    $data_daemon = [
      'uid' => $data['params']['uid'],
      'document_uid' => $data['document_uid'],
      'session' => [
        'user_uid'  => $this->customer->getStructureId(),
        'language_id'   => (int) $this->config->get('config_language_id'),
        'pressed_button_uid' => $this->session->data['current_button_uid'] ?? "",
        'changed_field_uid' => $this->request->get['field_uid'] ?? "",
        'changed_field_value' => $this->request->get['field_value'] ?? "",
        'folder_uid' => $this->request->get['folder_uid'] ?? "",
      ],
    ];

    $result = $this->daemon->exec("ExecuteAction", $data_daemon);
    return $result;
    ///////////////// the end /////////////////

    if (empty($data['params']['target_field_uid']) || empty($data['params']['target_type'])) {
      return;
    }
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');

    $method_params = array(
      'type' => $data['params']['target_type'],
      //'document_uid' => $data['document_uid'],
      'doclink_field_uid' => $data['params']['target_doclink_field_uid'],
      'field_uid' => $data['params']['target_field_uid'],
      'method_name' => $data['params']['target_field_method_name'],
      'current_document_uid' => $data['document_uid'],
      'method_params' => $data['params']['method_params']
    );
    if ($data['params']['target_type'] === 'fielduid') {
      $field_uid = explode(',', $this->model_document_document->getFieldValue($data['params']['target_field_uid'], $data['document_uid']));
      $method_params['field_uid'] = $field_uid[0];
    }

    $target_field_info = $this->model_doctype_doctype->getField($method_params['field_uid']);
    $result_execute_method = $this->load->controller('extension/field/' . $target_field_info['type'] . '/executeMethod', $method_params);
    if (!empty($data['params']['history'])) {
      $this->model_document_document->addDocumentHistory($data['document_uid']);
    }
    $result = array();
    $this->load->language('extension/action/record');
    $result['log'] = sprintf($this->language->get('text_log'), $target_field_info['name'], $this->model_document_document->getFieldDisplay($data['params']['target_field_uid'], $data['document_uid']));
    if (!empty($result_execute_method['append'])) {
      $result['append'] = $result_execute_method['append'];
    }
    return $result;
  }
}

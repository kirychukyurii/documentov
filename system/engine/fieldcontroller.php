<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class FieldController extends Controller
{

  const FIELD_INFO = null;
  public $lang = [];

  function __construct($reg)
  {
    parent::__construct($reg);
    $this->lang = $this->load->language('extension/field/' . $this->getFieldType());
  }

  public function executeMethod($data)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $method_name = '';

    if (!empty($data['method_name'])) {
      $method_name = $data['method_name'];
    }
    $field_type = '';
    if (!empty($data['type'])) {
      $field_type = $data['type'];
    }

    $result = '';
    $c = get_called_class();
    $document_uids = array('0');
    if ($field_type === 'document' || $field_type === 'fielduid') {
      $doclink_field_uid = '0';
      if (isset($data['doclink_field_uid'])) {
        $doclink_field_uid = $data['doclink_field_uid'];
      }
      if ($doclink_field_uid == '0') {
        $document_uids = array($data['current_document_uid']);
      } else if ($doclink_field_uid == '1') {
        if (isset($data['target_document_uid'])) {
          $document_uids = array($data['target_document_uid']);
        } else {
          $document_uids = array($data['current_document_uid']);
        }
      } else {
        $document_uids = explode(",", $this->model_document_document->getFieldValue($doclink_field_uid, $data['current_document_uid']));
      }
    }

    //Подготвка параметров
    //вызов метода
    if ($method_name == 'standard_getter' || $method_name == '') {
      $result = $this->model_document_document->getFieldValue($data['field_uid'], $document_uids[0]);
    } else {
      if ($method_name == "standard_setter") {

        if (!empty($data['method_params']) && !empty($data['method_params']['standard_setter_param'])) {
          foreach ($document_uids as $document_uid) {
            $target_data = $data;
            $target_data['target_document_uid'] = $document_uid;
            $this->prepareParameters($target_data);
            $value = '';
            if ($target_data['method_params']['standard_setter_param'] !== NULL) {
              $value = $target_data['method_params']['standard_setter_param'];
            }
            if (!is_array($value)) {
              $value = htmlentities($value); //необходим для полей, использующих теги, например, Текст, чтобы &lt; от &amplt;
            }
            $result = $this->model_document_document->editFieldValue($target_data['field_uid'], $document_uid, $value);
          }
        }
      } else {
        $is_method = false;
        $target_data = $data;
        if (!isset($target_data['method_params'])) {
          $target_data['method_params'] = [];
        }
        foreach ($c::FIELD_INFO['methods'] as $method) {
          if ($method_name === $method['name']) {
            if ($method['type'] === 'setter') {
              foreach ($document_uids as $document_uid) {
                // $target_data = $data;
                $target_data['target_document_uid'] = $document_uid;
                $this->prepareParameters($target_data);
                $target_data['document_uid'] = $document_uid;
                $result = $this->$method_name($target_data);
              }
            } else {
              $target_data['document_uid'] = $document_uids[0];
              $target_data['target_document_uid'] = $document_uids[0]; //ошибка на 157 стр
              $this->prepareParameters($target_data);
              $result = $this->$method_name($target_data);
            }
            $is_method = true;
            break;
          }
        }
        if (!$is_method) {
          //если метод не был найден, вызываем дефолтный метод
          $target_data['method_name'] = $method_name;
          $target_data['document_uids'] = $document_uids;
          $result = $this->defaultMethod($target_data);
        }
      }
    }
    return $result;
  }

  public function prepareParameters(&$data)
  {
    if (isset($data['method_params'])) {
      if ($data['method_params'] == null || !is_array($data['method_params'])) {
        $data['method_params'] = [];
      }
      foreach ($data['method_params'] as $aliace => &$param) {
        $param_type = 'value';
        if (!empty($param['type'])) {
          $param_type = $param['type'];
        }
        $document_uid = null;

        switch ($param_type) {
          case 'value':
            $param_value = NULL;
            if (isset($param['value'])) {
              if (!is_array($param['value'])) {
                $param_value = html_entity_decode($param['value']); //чтобы при ручной Записи в текст исполнялись теги, введенные через исх код редактора
              } else {
                $param_value = $param['value']; //чтобы при ручной Записи в текст исполнялись теги, введенные через исх код редактора
              }
            }
            $param = $param_value;
            break;
          case 'variable':
            if (!empty($param['var_id'])) {
              $doclink_field_uid = '0';
              if (!empty($param['doclink_field_uid'])) {
                $doclink_field_uid = $param['doclink_field_uid'];
              }
              if ($doclink_field_uid == '0') {
                $document_uid = $data['current_document_uid'];
              } else if ($doclink_field_uid == '1') {
                $document_uid = $data['target_document_uid'];
                //$document_uid = $target_document_uids[0]; 
              } else {
                $document_uids = explode(',', $this->model_document_document->getFieldValue($doclink_field_uid, $data['current_document_uid']));
                $document_uid = $document_uids[0];
              }
              $param = $this->model_document_document->getVariable($param['var_id'], $document_uid);
            }
            break;
          case 'doctype':
            $document_uid = '0';
          case 'document':
            if (is_null($document_uid)) {
              $doclink_field_uid = '0';
              if (!empty($param['doclink_field_uid'])) {
                $doclink_field_uid = $param['doclink_field_uid'];
              }
              if ($doclink_field_uid == '0') {
                $document_uid = $data['current_document_uid'];
              } else if ($doclink_field_uid == '1') {
                $document_uid = $data['target_document_uid'];
                //$document_uid = $target_document_uids[0]; 
              } else {
                $document_uids = explode(',', $this->model_document_document->getFieldValue($doclink_field_uid, $data['current_document_uid']));
                $document_uid = $document_uids[0];
              }
            }
            $param['current_document_uid'] = $data['current_document_uid'];
            $param['target_document_uid'] = $data['target_document_uid'];
            //$param['current_document_uid'] = $document_uid;
            //$param['current_document_uid'] = $data['document_uid'];
            $param_method_name = 'standard_getter';
            if (!empty($param['method_name'])) {
              $param_method_name = $param['method_name'];
            }
            if ($param_method_name === 'standard_getter') {
              $param = $this->model_document_document->getFieldValue($param['field_uid'], $document_uid);
            } else {
              if (!empty($param['method_params'])) {
                foreach ($param['method_params'] as &$inner_param) {
                  if (isset($inner_param['type'])) {
                    if ($inner_param['type'] === 'document' || $inner_param['type'] === 'doctype') {
                      $inner_param['current_document_uid'] = $param['current_document_uid'];
                      $inner_param['target_document_uid'] = $param['target_document_uid'];
                    }
                  }
                }
              }
              if (!empty($param['field_uid'])) {
                //рекурсивный вызов для получения значения параметра из цепочки
                $field_info = $this->model_doctype_doctype->getField($param['field_uid']);
                $param = $this->load->controller('extension/field/' . $field_info['type'] . '/executeMethod', $param);
              } else {
                $param = '';
              }
            }
        }
      }
    }
  }

  /**
   * Возвращает список методов
   * @param type $method_data = array(
   *      method_type => setter || getter
   *      field_uid   => для полей, переопределяющих этот метод (напр., Образ поля)
   * )
   * @return type
   */
  public function getFieldMethods($method_data)
  {
    $result = array();
    $c = get_called_class();
    foreach ($c::FIELD_INFO['methods'] as $method) {
      //если не задан тип метода, то возвращать все методы      
      if (!$method_data['method_type'] || $method_data['method_type'] === $method['type']) {
        $method['alias'] = $this->language->get('text_method_' . $method['name']);
        $result[] = $method;
      }
    }
    return $result;
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    $c = get_called_class();
    return $c::FIELD_INFO;
  }

  public function getFieldType()
  {
    $c = get_called_class();
    $reflector = new ReflectionClass($c);
    $filename = basename($reflector->getFileName());
    return substr($filename, 0, strlen($filename) - 4);
  }

  public function getMethodForm($data)
  {

    $lang_id = (int) $this->config->get('config_language_id');
    $method_name = 'standard_getter';

    $name_hierarchy = $data['method_params_name_hierarchy'];
    $prefix_hierarchy = preg_replace('/^\[|\]$/', '', preg_replace('/\]\[/', '_', $name_hierarchy));
    $data['method_params_prefix_hierarchy'] = $prefix_hierarchy;

    if (!empty($data['method_name'])) {
      $method_name = $data['method_name'];
    };
    if (!empty($data['field_uid'])) {
    }

    if (isset($data['method_params']) && is_null($data['method_params'])) {
      $data['method_params'] = [];
    }
    //Подготовка параметров
    if (isset($data['method_params'])) {
      // print_r($data['method_params']);
      // exit;
      foreach ($data['method_params'] as $alias => &$param) {
        $method_params_prefix_hierarchy = $prefix_hierarchy . '_' . $alias;
        $method_params_name_hierarchy = $name_hierarchy . '[' . $alias . ']';
        $param_type = $param['type'] ?? '';

        switch ($param_type) {
          case 'value':
            if (is_array($param['value'])) {
              $json_val = json_encode($param['value']);
              if ($json_val) {
                $param['value'] = $json_val;
              }
            }
            $param['value'] = urlencode($param['value']); //ручное значение будет передаваться в ReloadFieldWidget и может содержать переводы строк и пр
            break;
          case 'variable':
            if (empty($param['doclink_field_uid'])) {
              $param['doclink_field_uid'] = '0';
            }
            if ($param['doclink_field_uid'] == '0') {
              $param['doclink_field_name'] = $this->language->get('text_currentdoc');
            } elseif ($param['doclink_field_uid'] == '1') {
              $param['doclink_field_name'] = $this->language->get('text_addressdoc');
            } else {
              $param['doclink_field_name'] = $this->language->get('text_by_link_in_field') . ' &quot;' . $this->model_doctype_doctype->getField($param['doclink_field_uid'])['name'] . '&quot;';
            }
            break;
          case 'doctype':
          case 'document':
            if (empty($param['doclink_field_uid'])) {
              $param['doclink_field_uid'] = '0';
            }
            if ($param['doclink_field_uid'] == '0') {
              $param['doclink_field_name'] = $this->language->get('text_currentdoc');
            } elseif ($param['doclink_field_uid'] == '1') {
              $param['doclink_field_name'] = $this->language->get('text_addressdoc');
            } else {
              $param['doclink_field_name'] = $this->language->get('text_by_link_in_field') . ' &quot;' . $this->model_doctype_doctype->getField($param['doclink_field_uid'])['name'] . '&quot;';
            }
            if (!empty($param['field_uid'])) {
              $field_description = $this->model_doctype_doctype->getField($param['field_uid']);
              if ($field_description) {
                $field_type = $field_description['type'];
                $field_doctype_uid = $field_description['doctype_uid'];
                if (strcmp($param['doclink_field_uid'], '0') === 0) {
                  $param['field_name'] = $field_description['name'];
                } else {
                  $doctypename = $this->model_doctype_doctype->getDoctypeDescriptions($field_doctype_uid)[$lang_id]['name'];
                  $param['field_name'] = $doctypename . ' - ' . $field_description['name'];
                }
                $methods_data = array(
                  'method_type'   => '',
                  'field_uid'     => $param['field_uid']
                );
                $param['avaliable_methods'] = $this->load->controller('extension/field/' . $field_type . '/getFieldMethods', $methods_data);
              }

              if ($param['method_name']) {
                $form_data = array();
                $form_data['doctype_uid'] = $data['doctype_uid'];
                $form_data['method_name'] = $param['method_name'];
                if (isset($param['method_params'])) {
                  $form_data['method_params'] = $param['method_params'];
                }
                $form_data['field_uid'] = $param['field_uid'];
                $form_data['method_params_prefix_hierarchy'] = $method_params_prefix_hierarchy . '_method_params';
                $form_data['method_params_name_hierarchy'] = $method_params_name_hierarchy . '[method_params]';
                $form_data['method_data'] = array('doctype_uid' => $data['doctype_uid']);
                $param['method_form'] = $this->getMethodForm($form_data);
              }
            }
        }
      }
    }
    //загрузка формы
    if (isset($data['method_params']) && !is_array($data['method_params'])) {
      $data['method_data']['vars'] = $this->model_doctype_doctype->getVariables();
    } else {
      $data['method_data'] = array(
        'vars' => $this->model_doctype_doctype->getVariables(),
        'doctype_uid' => $data['doctype_uid'],
        'widget_field_uid' => $data['field_uid']
      );
    }

    if (!empty($data['field_uid'])) {
      if (!isset($data['method_params'])) {
        $data['method_params'] = array(
          'standard_setter_param' => array(
            'doclink_field_uid' => '0',
            'doclink_field_name' => $this->language->get('text_currentdoc')
          )
        );
      }
      $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
      $data['method_data']['info'] = $this->language->load('extension/field/' . $field_info['type']);
      $form = '';
      if ($data['method_name'] === 'standard_setter') {
        $data['param_name'] = 'standard_setter_param';
        return $this->load->view('doctype/standard_setter_form', $data);
      }

      $form = $this->load->controller('extension/field/' . $field_info['type'] . '/getFieldMethodForm', $data);

      if (!$form) {
        $methods_data = array(
          'method_type'   => 'setter',
          'field_uid'     => $data['field_uid']
        );
        $field_setters = $this->load->controller('extension/field/' . $field_info['type'] . '/getFieldMethods', $methods_data);
        if ($field_setters) {
          foreach ($field_setters as $setter) {
            if ($setter['name'] === $data['method_name']) {
              return $this->load->view('doctype/standard_setter_form', $data);
            }
          }
        }
      }

      return $form;
    } else {
      $data['param_name'] = 'standard_setter_param';
      return $this->load->view('doctype/standard_setter_form', $data);
    }
  }
  /**
   * Реализуя этот метод, поле может обработать свои параметры перед их сохранением
   * @param type $param
   * @return type
   */
  public function setParams($param)
  {
    return $param;
  }
  /**
   * Реализуя этот метод, поле может обработать параметры метода перед их сохранением. Актуально при использовании поля в д. Запись и иже с ним
   * @param type $param
   * @return type
   */
  public function setMethodParams($param)
  {
    return $param['method_params'] ?? NULL;
  }


  public function defaultMethod($data)
  {
    return "";
  }

  /**
   * Метод устанавливает обязательные параметры для виджетов полей
   */
  public function setDefaultTemplateParams($data)
  {
    if (isset($data['field_uid'])) {
      $data['unique'] = token(16);
      if (!empty($data['widget_name'])) {
        $data['NAME'] = $data['widget_name'];
        $data['BLOCK'] = "field_block_" . $data['widget_name'];
        $data['ID'] = 'field_id_' . $data['unique'];
      } else {
        $data['NAME'] = "field[" . $data['field_uid'] . "]";
        $data['BLOCK'] = "field_block_" . $data['field_uid'];
        $data['ID'] = 'field_id_' . $data['field_uid'];
      }
    }
    return $data;
  }
}

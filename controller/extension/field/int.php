<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright  Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionFieldInt extends FieldController
{

  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'setter', 'name' => 'add', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'subtract', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'divide', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'multiply', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'remainder', 'params' => array('standard_setter_param')),
      array('type' => 'getter', 'name' => 'add_leading_zeros', 'params' => array('count')),
      array('type' => 'getter', 'name' => 'get_sum', 'params' => array('source', 'save')),
      array('type' => 'getter', 'name' => 'get_sub', 'params' => array('source', 'save')),
      array('type' => 'getter', 'name' => 'get_mult', 'params' => array('source', 'save')),
      array('type' => 'getter', 'name' => 'get_div', 'params' => array('source', 'save')),
    )
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/int', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/int');
    $this->model_extension_field_int->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/int');
    $this->model_extension_field_int->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldInt::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/int');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    //        $params = unserialize($params);
    $result = array();
    if (!empty($params['delimiter'])) {
      $delimiter = "";
      switch ($params['delimiter']) {
        case " ":
          $delimit23er = $this->language->get('text_space');
          break;
        case ".":
          $delimiter = $this->language->get('text_dot');
          break;
        case ",":
          $delimiter = $this->language->get('text_comma');
          break;
        default:
          $this->language->get('text_without_delimiter');
      }
      $result[] = sprintf($this->language->get('text_description_delimiter'), $delimiter);
    }
    if (!empty($params['min'])) {
      $result[] = sprintf($this->language->get('text_description_min'), $params['min']);
    }
    if (!empty($params['max'])) {
      $result[] = sprintf($this->language->get('text_description_max'), $params['max']);
    }

    return implode("; ", $result);
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    return $this->load->view($this->config->get('config_theme') . '/template/field/int/int_form', $data);
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    return $this->load->view('field/int/int_widget_form', $data);
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('extension/field/int');
    if (isset($data['field_value'])) {
      $this->load->model("doctype/doctype");
      $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
      $data['field_value'] = $this->model_extension_field_int->formatDisplayValue($data['field_value'], $field_info['params']['delimiter'] ?? $data['delimiter']);
    }
    return $this->load->view('field/int/int_widget_view', $data);
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/int');
    switch ($data['method_name']) {
      case "add_leading_zeros":
        return $this->load->view('field/int/method_one_int_param_form', $data);
      case "get_sum":
      case "get_sub":
      case "get_mult":
      case "get_div":
        return $this->load->view('field/int/method_operation_argument_form', $data);
      case "add":
      case "subtract":
      case "multiply":
      case "divide":
      case "remainder":
      default:
        return '';
    }
  }

  public function setMethodParams($data)
  {
    if (isset($data['method_params']['char_count']['value'])) {
      $data['method_params']['char_count']['value'] = (int)$data['method_params']['char_count']['value'];
    }
    return $data['method_params'] ?? [];
  }

  //сеттеры
  public function add($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!$params['method_params']['standard_setter_param']) {
      $addval = 0;
    } else {
      $addval = $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param']);
    }
    $newval = $val + $addval;
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  public function subtract($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!$params['method_params']['standard_setter_param']) {
      $subval = 0;
    } else {
      $subval = $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param']);
    }
    $newval = $val - $subval;
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  public function divide($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!$params['method_params']['standard_setter_param']) {
      $divval = 0;
    } else {
      $divval = $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param']);
    }

    if ($divval) {
      $newval = intdiv($val, $divval);
    } else {
      $newval = 0;
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  public function multiply($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!$params['method_params']['standard_setter_param']) {
      $mulval = 0;
    } else {
      $mulval = $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param']);
    }

    $newval = $val * $mulval;
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  public function remainder($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $divval = $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param']);
    $newval = $val % $divval;
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  //геттеры
  public function add_leading_zeros($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!empty($params['method_params']['char_count'])) {
      $count = $params['method_params']['char_count'];
      if (intval($count) === 0) {
        $count = mb_strlen($val);
      } else {
        $count = intval($count);
      }
    } else {
      $count = mb_strlen($val);
    }
    return str_pad($val, $count, "0", STR_PAD_LEFT);
  }

  public function get_sum($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($params['method_params']['source']) {
      $addval = (int) $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['source']);
    } else {
      $addval = 0;
    }
    $newval = $val + $addval;
    if (!empty($params['method_params']['save'])) {
      //сохраняем результат вычисления в самом поле
      $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
    }
    return $newval;
  }

  public function get_sub($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = (int) $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($params['method_params']['source']) {
      $subval = (int) $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['source']);
    } else {
      $subval = 0;
    }
    $newval = $val - $subval;
    if (!empty($params['method_params']['save'])) {
      //сохраняем результат вычисления в самом поле
      $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
    }
    return $newval;
  }

  public function get_div($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = (int) $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($params['method_params']['source']) {
      $divval = (int) $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['source']);
    } else {
      $divval = 0;
    }
    $newval = $divval ? intdiv($val, $divval) : 0;
    if (!empty($params['method_params']['save'])) {
      //сохраняем результат вычисления в самом поле
      $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
    }
    return $newval;
  }

  public function get_mult($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/int');
    $val = (int) $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($params['method_params']['source']) {
      $mulval = (int) $this->model_extension_field_int->getValue($params['field_uid'], $params['document_uid'], $params['method_params']['source']);
    } else {
      $mulval = 0;
    }
    $newval = $val * $mulval;
    if (!empty($params['method_params']['save'])) {
      //сохраняем результат вычисления в самом поле
      $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
    }
    return $newval;
  }
}

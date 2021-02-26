<?php

/**
 * @package		StringField
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */
class ControllerExtensionFieldString extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_first_chars', 'params'         => array('count')),
      array('type' => 'getter', 'name' => 'get_substr', 'params'              => array('field_separator', 'field_part')),
      array('type' => 'getter', 'name' => 'get_uppercase'),
      array('type' => 'getter', 'name' => 'get_lowercase'),
      array('type' => 'getter', 'name' => 'get_random_str', 'params'          => array('count')),

      array('type' => 'setter', 'name' => 'append_text', 'params'              => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'insert_text', 'params'              => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'append_text_separator', 'params'   => array('standard_setter_param', 'separator')),
      array('type' => 'setter', 'name' => 'insert_textes', 'params'           => array('textes')),
      array('type' => 'setter', 'name' => 'delete_first_chars', 'params'      => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'delete_last_chars', 'params'       => array('standard_setter_param')),

    )
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/string', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/string');
    $this->model_extension_field_string->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/string');
    $this->model_extension_field_string->uninstall();
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/field/string');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $result = array();
    if (!empty($params['mask'])) {
      $result[] = sprintf($this->language->get('text_description_mask'), $params['mask']);
    }
    if (!empty($params['default'])) {
      $result[] = sprintf($this->language->get('text_description_default'), $params['default']);
    }
    return implode("; ", $result);
  }


  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    return $this->load->view($this->config->get('config_theme') . '/template/field/string/string_form', $data);
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    if (!empty($data['field_value'])) {
      if (is_array($data['field_value'])) {
        $data['field_value'] = json_encode($data['field_value']); //если в строку пытаются записать, скажем, таблицу
      }
      $data['field_value'] = str_replace("&amp;", "&", htmlentities($data['field_value'])); //если в поле записать, скажем, гиперссылку, то при отображении ссылка будет интерпретирована htmlentities 
    }
    $data = $this->setDefaultTemplateParams($data);
    return $this->load->view('field/string/string_widget_form', $data);
  }
  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('extension/field/string');
    // print_r($data);
    // exit;
    return $this->load->view('field/string/string_widget_view', $data);
  }

  public function setMethodParams($data)
  {

    switch ($data['method_name']) {
      case "insert_textes":
        $data['method_params'] = ['textes' => $data['method_params']];
        break;
    }

    if (isset($data['method_params']['char_count']['value'])) {
      $data['method_params']['char_count']['value'] = (int)$data['method_params']['char_count']['value'];
    }
    return $data['method_params'];
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/string');
    // if (!isset($data['method_name'])) {
    //   print_r($data);
    //   debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    //   exit;
    // }
    switch ($data['method_name']) {
      case "get_first_chars":
        return $this->load->view('field/string/method_one_int_param_form', $data);
      case "get_random_str":
        return $this->load->view('field/string/method_one_int_param_form', $data);
      case "get_substr":
        return $this->load->view('field/string/method_field_substr_form', $data);
      case "append_text_separator":
        return $this->load->view('field/string/method_append_text_separator_form', $data);
      case "insert_textes":
        if (!isset($data['method_params']['standard_setter_param'])) {
          $textes = array();
          $i = 1;
          foreach ($data['method_params'] as $name => $param) {
            if (strpos($name, "text") === 0) {
              $textes['text' . $i++] = $param;
            }
          }
          $data['method_params'] = $textes;
        }
        return $this->load->view('field/string/method_insert_textes_form', $data);
      case "delete_first_chars":
        return $this->load->view('field/string/method_delete_chars_form', $data);
      case "delete_last_chars":
        return $this->load->view('field/string/method_delete_chars_form', $data);
      default:
        return '';
    }
  }

  //геттеры
  public function get_first_chars($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = html_entity_decode($this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid'])); //декодируем value, чтобы &quote; превратить в " и вернуть ", а не & (при n=1) 
    if (!empty($params['method_params']['char_count'])) {
      $count = $params['method_params']['char_count'];
      if (intval($count) === 0) {
        $count = mb_strlen($val);
      } else {
        $count = intval($count);
      }
    } else {
      $count = 0;
    }
    $val = mb_substr($val, 0, $count);
    return htmlentities($val);
  }

  public function get_random_str($params)
  {
    return token(!empty($params['method_params']['char_count']) ? $params['method_params']['char_count'] : 32);
  }

  public function get_substr($params)
  {
    $this->load->model('document/document');
    $row_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_separator_value = $params['method_params']['field_separator'];
    $field_part_value = $params['method_params']['field_part'];
    if (!$field_separator_value) {
      $field_separator_value = ","; //если разделитель пуст, используем разделитель по умолчанию - запятую
    }
    $array_value = explode($field_separator_value, $row_value);
    return $array_value[(int) $field_part_value] ?? "";
  }

  public function get_uppercase($params)
  {
    $this->load->model('document/document');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    return mb_convert_case($value, MB_CASE_UPPER);
  }

  public function get_lowercase($params)
  {
    $this->load->model('document/document');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    return mb_convert_case($value, MB_CASE_LOWER);
  }

  //cеттеры
  public function append_text($params)
  {
    // print_r($params);
    // exit;
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $val = $val .  $params['method_params']['standard_setter_param'];
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
  }

  public function insert_textes($params)
  {
    // if (!is_array($params['method_params'])) {
    //   // print_r($params);
    //   var_dump($params['method_params']);
    //   exit;
    // }
    $this->load->model('document/document');
    $values = array();
    foreach ($params['method_params'] as $value) {
      $values[] = $value;
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], implode("", $values));
  }

  public function append_text_separator($params)
  {
    $this->load->model('document/document');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $val = $val . ($params['method_params']['separator'] ?? " ") . $params['method_params']['standard_setter_param'];
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
  }

  public function insert_text($params)
  {
    $this->load->model('document/document');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $val =  $params['method_params']['standard_setter_param'] . $val;
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
  }

  public function delete_first_chars($params)
  {
    $this->load->model('document/document');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $count_chars = (int) $params['method_params']['standard_setter_param'];
    if ($count_chars) {
      $v = mb_substr(html_entity_decode($val), $count_chars);
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $v);
    }
  }

  public function delete_last_chars($params)
  {
    $this->load->model('document/document');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $count_chars = (int) $params['method_params']['standard_setter_param'];
    if ($count_chars) {
      $v = mb_substr(html_entity_decode($val), 0, - ($count_chars));
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $v);
    }
  }
}

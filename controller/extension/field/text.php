<?php

/**
 * @package		TextField
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

class ControllerExtensionFieldText extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_first_chars', 'params' => array('count')),
      array('type' => 'getter', 'name' => 'get_line', 'params' => ('field_line')),
      array('type' => 'getter', 'name' => 'get_total_line'),
      array('type' => 'getter', 'name' => 'get_substr', 'params' => array('field_separator', 'field_part')),
      array('type' => 'getter', 'name' => 'get_text', 'params' => array('standard_setter_param')),

      array('type' => 'setter', 'name' => 'append_text', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'append_text_new_line', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'insert_text', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'insert_text_new_line', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'append_text_separator', 'params' => array('standard_setter_param', 'separator')),
      array('type' => 'setter', 'name' => 'insert_textes', 'params'           => array('textes')),
      array('type' => 'setter', 'name' => 'set_value_from_template', 'params' => array('template', 'mode'))
    ),
    'work_progress_log' => true,
    'MODULE_NAME' => 'FieldText',
    'FILE_NAME'   => 'text'
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/text', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/text');
    $this->model_extension_field_text->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/text');
    $this->model_extension_field_text->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return $this::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/text');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $result = array();
    if (!empty($params['default'])) {
      $result[] = sprintf($this->language->get('text_ field_description_default'));
    }
    if (!empty($params['editor_enabled']) && $params['editor_enabled'] == "true") {
      $result[] = sprintf($this->language->get('text_description_editor_enabled'), $params['editor_enabled']);
    }
    return implode("; ", $result);
  }

  public function setParams($params)
  {
    foreach (['editor_enabled', 'script_enabled'] as $param) {
      if ($params[$param] == "true") {
        $params[$param] = (bool)true;
      } else {
        $params[$param] = (bool)false;
      }
    }

    foreach (['image_width', 'image_height', 'size_file'] as $param) {
      if ($params[$param]) {
        $params[$param] = (float)$params[$param];
      } else {
        $params[$param] = 0;
      }
    }

    return $params;
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/text');
    switch ($data['method_name']) {
      case "get_first_chars":
        return $this->load->view('field/text/method_one_int_param_form', $data);
      case "get_line":
        $this->load->model('doctype/doctype');
        return $this->load->view('field/text/method_field_line_form', $data);
      case "get_substr":
        $this->load->model('doctype/doctype');
        return $this->load->view('field/text/method_field_substr_form', $data);
      case "append_text_separator":
        return $this->load->view('field/text/method_append_text_separator_form', $data);
      case "insert_textes":

        if (!isset($data['method_params']['standard_setter_param'])) {
          $textes = array();
          foreach ($data['method_params'] as $k => $v) {
            $textes[$k] = $v;
          }
          $data['method_params'] = $textes;
        }
        return $this->load->view('field/text/method_insert_textes_form', $data);
      case "set_value_from_template":
        if (!empty($data['method_params']['template']['value'])) {
          $data['method_params']['template']['value'] = $this->model_doctype_doctype->getNamesTemplate($data['method_params']['template']['value'], $data['doctype_uid'], $this->model_doctype_doctype->getTemplateVariables());
        }

        return $this->load->view('field/text/method_set_value_from_template', $data);
      default:
        return '';
    }
  }


  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    // var_dump($data);
    // exit;
    $data['file_extes'] = array();
    $data['file_mimes'] = array();
    if (isset($data['params'])) {
      //редактируется существующее поле
      foreach (explode(',', $this->config->get('config_file_ext_allowed')) as $ext) {
        $data['file_extes'][$ext] = FALSE;
      }
      if (!empty($data['params']['file_extes'])) {
        foreach ($data['params']['file_extes'] as $ext) {
          $data['file_extes'][$ext] = TRUE;
        }
      }
      foreach (explode(',', $this->config->get('config_file_mime_allowed')) as $mime) {
        $data['file_mimes'][$mime] = FALSE;
      }
      if (!empty($data['params']['file_mimes'])) {
        foreach ($data['params']['file_mimes'] as $mime) {
          $data['file_mimes'][$mime] = TRUE;
        }
      }
    } else {
      //создается новое поле
      foreach (explode(',', $this->config->get('config_file_ext_allowed')) as $ext) {
        $data['file_extes'][$ext] = TRUE;
      }
      foreach (explode(',', $this->config->get('config_file_mime_allowed')) as $mime) {
        $data['file_mimes'][$mime] = TRUE;
      }
    }
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return  $this->load->view('field/text/text_form', $data)
      . $this->load->view('field/common_admin_form', array('data' => $data));
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $this->load->model('tool/utils');
    if (isset($data['field_value'])) {
      $data['field_value'] = html_entity_decode($data['field_value']);
      $data['value'] =  html_entity_decode(str_replace('&', '&#38;', $data['field_value']));
    }
    $data = $this->setDefaultTemplateParams($data);
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    if (!$this->model_tool_utils->validateUID($data['field_uid'])) {
      $data['field_uid'] = ""; //например, если поле нах-ся в таблице UID фактически отсуствует
    }
    $common = $this->load->view('field/common_widget_form', array('data' => $data));
    $template = $this->load->view('field/text/text_widget_form', $data);
    return  $template . $common;
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('extension/field/text');
    $this->load->model('doctype/doctype');
    if (isset($data['field_value'])) {
      $data['field_value'] = html_entity_decode($data['field_value']);
      if (empty($data['editor_enabled'])) {
        //редактора нет, отображатаблем пробелы и переводы строк
        $data['field_value'] = str_replace(["  ", "\n"], [" &nbsp;", "<br>"], htmlentities($data['field_value']));
      } else if (isset($data['field_uid'])) {

        $data['field_value'] = str_replace('src="/index.php?route=field/text/file&amp;file_uid=', ' style="max-width:100%;" src="/index.php?route=field/text/file&field_uid=' . $data['field_uid'] . '&file_uid=', $data['field_value']);
        $data['field_value'] = str_replace('src="/index.php?route=field/text_plus/file&amp;file_uid=', ' style="max-width:100%;" src="/index.php?route=field/text/file&field_uid=' . $data['field_uid'] . '&file_uid=', $data['field_value']);
      }
    }
    return $this->load->view('field/text/text_widget_view', $data);
  }


  //геттеры
  public function get_first_chars($params)
  {
    $this->load->model('document/document');
    $row_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $val = strip_tags(htmlspecialchars_decode($row_value));
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
    return $val;
  }

  public function get_line($params)
  {
    $this->load->model('document/document');
    $row_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_line_value = $params['method_params']['field_line'];
    $array_value = explode("\n", $row_value);
    return $array_value[(int) $field_line_value] ?? "";
  }

  public function get_total_line($params)
  {
    $this->load->model('document/document');
    $row_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $array_value = explode("\n", $row_value);
    return count($array_value);
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

  public function get_text($params)
  {
    $this->load->model('document/document');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    return strip_tags(html_entity_decode($value));
  }

  //cеттеры
  public function append_text($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    if (!empty($field_info['params']['editor_enabled'])) {
      // if (!isset($params['method_params']['standard_setter_param'])) {
      //   print_r($params);
      //   exit;
      // }
      $val = $val . html_entity_decode($params['method_params']['standard_setter_param'] ?? "");
    } else {
      $val = $val . $params['method_params']['standard_setter_param'];;
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($val));
  }

  public function append_text_new_line($params)
  {

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    if (!empty($field_info['params']['editor_enabled'])) {
      $val = $val . "<br>\r\n" . html_entity_decode($params['method_params']['standard_setter_param']);
    } else {
      $val = $val . "\r\n" . $params['method_params']['standard_setter_param'];;
    }

    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($val));
  }

  public function append_text_separator($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    if (!empty($field_info['params']['editor_enabled'])) {
      $val = $val .  html_entity_decode(($params['method_params']['separator'] ?? " ")) . html_entity_decode($params['method_params']['standard_setter_param']);
    } else {
      $val = $val . ($params['method_params']['separator'] ?? " ") . $params['method_params']['standard_setter_param'];;
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($val));
  }

  public function insert_textes($params)
  {
    $this->load->model('document/document');
    $values = array();
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    foreach ($params['method_params'] as $value) {
      if (!empty($field_info['params']['editor_enabled'])) {
        $values[] = html_entity_decode($value);
      } else {
        $values[] = $value;
      }
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities(implode("", $values)));
  }

  public function set_value_from_template($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $document_info = $this->model_document_document->getDocument($params['document_uid'], false);
    if ($document_info) {
      $data = array(
        'document_uid' => $params['document_uid'],
        'doctype_uid' => $document_info['doctype_uid'],
        'template' => htmlspecialchars_decode($params['method_params']['template']),
        'mode' => 'view_clear',
      );
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      $value = $this->load->controller('document/document/renderTemplate', $data);
      $value = html_entity_decode($value);
      if (empty($field_info['params']['editor_enabled'])) {
        $value = strip_tags(html_entity_decode($value));
      }

      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($value));
    }
  }

  public function insert_text($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);

    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    if (!empty($field_info['params']['editor_enabled'])) {
      $val = html_entity_decode($params['method_params']['standard_setter_param']) . $val;
    } else {
      $val = $params['method_params']['standard_setter_param'] . $val;
    }


    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($val));
  }

  public function insert_text_new_line($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    if (!empty($field_info['params']['editor_enabled'])) {
      $val = html_entity_decode($params['method_params']['standard_setter_param']) . "<br>\r\n" . $val;
    } else {
      $val = $params['method_params']['standard_setter_param'] . "\r\n" . $val;
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], htmlentities($val));
  }

  /**
   * Метод для обработки параметров метода перед сохранением
   * @param type $data = array( 
   *      'method_name' 
   *      'method_params' 
   *      'field_uid' 
   *  )
   * @return int
   */
  public function setMethodParams($data)
  {
    $this->load->model('doctype/doctype');
    switch ($data['method_name']) {
      case "insert_textes":
        $data['method_params'] = ['textes' => $data['method_params']];
        // print_r($data['method_params']);
        // exit;
        break;
      case "set_value_from_template":
        if ($data['method_params']['template']) {
          $field_info = $this->model_doctype_doctype->getField($data['field_uid'], 1);
          return array('template' => $this->model_doctype_doctype->getIdsTemplate($data['method_params']['template'], $field_info['doctype_uid']));
        }
        break;
    }
    if (isset($data['method_params']['char_count']['value'])) {
      $data['method_params']['char_count']['value'] = (int)$data['method_params']['char_count']['value'];
    }

    return $data['method_params'] ?? [];
  }
}

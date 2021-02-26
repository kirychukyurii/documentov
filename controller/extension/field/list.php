<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionFieldList extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_display_value'),
      array('type' => 'setter', 'name' => 'set_by_title', 'params' => array('standard_setter_param')),
    ),
    'MODULE_NAME' => 'FieldList',
    'FILE_NAME'   => 'list'
  );

  /**
   * Настройки поля в Модулях
   */
  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/list', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/list');
    $this->model_extension_field_list->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/list');
    $this->model_extension_field_list->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldList::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/field/list');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    if (empty($params['source_type']) || $params['source_type'] == 'table') {
      if (!empty($params['values'])) {
        $values = array();
        foreach ($params['values'] as $value) {
          if (isset($value['title'])) {
            $values[] = $value['title'];
          }
        }
        return $this->language->get('description_list') . ": " . implode(", ", $values);
      } else {
        return $this->language->get('description_list_empty');
      }
    } else {
      if (!empty($params['source_field_uid'])) {
        $this->load->model('doctype/doctype');
        $source_field_info = $this->model_doctype_doctype->getField($params['source_field_uid'], 1);
        return sprintf($this->language->get('description_list_field'), $source_field_info['name'] ?? "", $params['separator_values'] ?? "||", $params['separator_value_title'] ?? "::");
      } else {
        return $this->language->get('description_list_field_empty');
      }
    }
  }

  public function setParams($data)
  {
    //дефолтное значение привязано к value, которое могло быть и изменено
    if (isset($data['default_value']) && $data['default_value'] != "") {
      if (!is_array($data['default_value'])) {
        //одиночный выбор
        $data['default_value'] = [$data['values'][$data['default_value']]['value']];
        // $data['default_value'] = [$data['default_value']];
      } else {
        //множественный выбор
        $default_values = [];
        foreach ($data['default_value'] as $value) {
          foreach ($data['values'] as $val) {
            if ($val["value"] == $value) {
              $default_values[] = $value;
            }
          }
          $data['default_value'] = $default_values;
        }
      }
    }
    if (!empty($data['values'])) {
      foreach ($data['values'] as &$param) {
        if (isset($param['default_value'])) {
          $param['default_value'] = (int) $param['default_value'];
        }
      }
      $data['values'] = array_values($data['values']);
    }

    $data['multi_select'] = empty($data['multi_select']) ? 0 : 1;
    $data['visualization'] = (int) $data['visualization'];

    // print_r($data);
    // exit;


    return $data;
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $this->load->model('localisation/language');
    $this->load->model('doctype/doctype');
    if (!empty($data['params']['source_type']) && $data['params']['source_type'] == "field") {
      if (!empty($data['params']['source_field_uid'])) {
        $source_field_info = $this->model_doctype_doctype->getField($data['params']['source_field_uid'], 1);
        $data['source_field_name'] = $source_field_info['name'] ?? "";
      }
    } else {
      //в зависимости от типа выбора (множественный или одиночный) значение по умолчанию может быть массивом или строкой
      if (!empty($data['params']['values'])) {
        if (isset($data['params']['default_value'])) {
          $default_values = $data['params']['default_value'];
          if (!$default_values) {
            $default_values = [];
          }
          foreach ($data['params']['values'] as &$value) {
            if (array_search($value['value'], $default_values) !== FALSE) {
              $value['checked'] = 1;
            }
          }
        }
      }
    }
    $data['text'] = $this->language;
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];

    return  $this->load->view('field/list/list_form', $data)
      . $this->load->view('field/common_admin_form', array('data' => $data));
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $data['text'] = $this->language;
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data = $this->setDefaultTemplateParams($data);

    if (!empty($data['widget_name']) && !empty($data['source_type']) && $data['source_type'] == "field") {
      return $this->load->view('field/list/list_widget_form', $data);
    }
    if (isset($data['field_value']) && $data['field_value'] != "" && !isset($data['widget_name'])) {

      $value = $data['field_value'];
      $data['field_value'] = array();
      if (!is_array($value)) {
        $value = explode(',', $value);
      }
      foreach ($value as $val) {
        $data['field_value'][$val] = '1';
      }
    }
    if (!empty($data['source_type']) && $data['source_type'] == "field") {
      //значения для списка получаем из поля
      if (!empty($data['source_field_uid'])) {
        if (!empty($data['filter_form'])) {
          //фильтр журнала, документ-июда нет, а данные нужно получать из поля, возможно НЕнастроечного. Проверяем
          $this->load->model('doctype/doctype');
          $this->load->model('document/document');
          $source_field_info = $this->model_doctype_doctype->getField($data['source_field_uid']);
          if ($source_field_info['setting']) {
            //повезло, поле настроечное
            $data['values'] = $this->getValuesFromField($data);
          } else {
            //не повезло - поле-источник обычное
            //получаем все уникальные пары value-display данного поля списка, чтобы показать их 
            //в фильтре  
            $result = array();
            $data['values'] = array();
            foreach ($this->model_document_document->getUniqueFieldValues($data['field_uid']) as $val) {
              if ($val['value']) {
                if (isset($result[$val['value']])) {
                  if (array_search($val['display_value'], $result[$val['value']]) === false) {
                    $result[$val['value']][] = $val['display_value'];
                  }
                } else {
                  $result[$val['value']][] = $val['display_value'];
                }
              }
            }
            foreach ($result as $value => $titles) {
              $data['values'][] = array(
                'value' => $value,
                'title' => implode("/", $titles)
              );
            }
          }
        } else {
          $data['values'] = $this->getValuesFromField($data);
        }
      }
    } else {
      //значения для списка введены в таблице
      if (!isset($data['field_value']) || $data['field_value'] == "") {
        //документ создается (а не редактируется), устанавливаем значение по умолчанию
        $data['field_value'] = array();
        if (isset($data['default_value']) && isset($data['values'])) {
          $default_values = $data['default_value'];
          foreach ($data['values'] as &$value) {
            if (array_search($value['value'], $default_values) !== FALSE) {
              $data['field_value'][$value['value']] = 1;
            }
          }
        }
      }
    }
    if (isset($data['widget_name']) && isset($data['field_value']) && is_array($data['field_value'])) {
      $data['field_value'] = implode(",", $data['field_value']);
    }

    return  $this->load->view('field/list/list_widget_form', $data)
      . $this->load->view('field/common_widget_form', array('data' => $data));
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (isset($data['field_value'])) {
      $values = explode(',', $data['field_value']);
      $displays = array();
      if (!empty($data['source_type']) && $data['source_type'] == "field") {
        //значения для списка получаем из поля
        if (!empty($data['source_field_uid'])) {
          foreach ($this->getValuesFromField($data) as $value) {
            foreach ($values as $value_f) {
              if ($value['value'] == $value_f) {
                $displays[] = $value['title'];
              }
            }
          }
        }
      } else {
        $available_values = [];
        if (!empty($data['values'])) {
          foreach ($data['values'] as $val) {
            $available_values[$val['value']] = $val['title'];
          }
        }
        foreach ($values as $value) {
          if (isset($available_values[$value])) {
            $displays[] = $available_values[$value];
          }
        }
      }
      $data['text'] = implode(",", $displays);
      if (!$data['text'] && !empty($data['source_type']) && $data['source_type'] == 'field') {
        $data['text'] = $data['field_value']; //если не найден вариант, просто показываем 
        //значение (для, н-р, Условия, если список берется из поля)
      }
    }

    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    return $this->load->view('field/list/list_widget_view', $data);
  }

  public function getValuesFromField($data)
  {
    $this->load->model('document/document');
    $source_field_value = $this->model_document_document->getFieldValue($data['source_field_uid'], $data['document_uid'] ?? "");
    $values = explode($data['separator_values'] ?? "||", $source_field_value);
    $result = array();
    if ($values) {
      foreach ($values as $val) {
        $value_title = explode($data['separator_value_title'] ?? "::", $val);
        if (!empty($value_title[0])) {
          $result[] = array(
            'value'  => $value_title[0],
            'title'  => $value_title[1] ?? ""
          );
        }
      }
    }
    return $result;
  }


  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/file');
    switch ($data['method_name']) {
      case "get_display_value":
      default:
        return '';
    }
  }

  public function get_display_value($params)
  {
    $this->load->model('document/document');
    return $this->model_document_document->getFieldDisplay($params['field_uid'], $params['document_uid']);
  }

  public function set_by_title($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    $result = array();
    foreach (explode(",", $params['method_params']['standard_setter_param']) as $value) {
      foreach ($field_info['params']['values'] as $list_value) {
        if ($value == $list_value['title']) {
          $result[] = $list_value['value'];
        }
      }
    }
    if ($result && $field_info['params']['multi_select']) {
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], implode(",", $result));
    } elseif ($result && !$field_info['params']['multi_select']) {
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $result[0]);
    } else {
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], "");
    }
  }
}

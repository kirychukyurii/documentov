<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionFieldHidden extends FieldController
{

  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_decrypt_value') //возвращает расшифрованный пароль, если установлено двунаправленное шифрование
    ),
    'compound' => TRUE,
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);

    $this->response->setOutput($this->load->view('extension/field/hidden', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/hidden');
    $this->model_extension_field_hidden->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/hidden');
    $this->model_extension_field_hidden->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldHidden::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/hidden');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    if (!empty($params['type_hash']) && $params['type_hash'] == '2') {
      return $this->language->get('text_2_hash_description');
    } else {
      return $this->language->get('text_1_hash_description');
    }
  }


  public function setParams($params)
  {
    $params['type_hash'] = (int) $params['type_hash'];
    return $params;
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    return $this->load->view($this->config->get('config_theme') . '/template/field/hidden/hidden_form', $data);
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    if (!empty($data['filter_form'])) {
      return $this->language->get('text_field_does_not_support_filter');
    }
    $data = $this->setDefaultTemplateParams($data);
    return $this->load->view('field/hidden/hidden_widget_form', $data);
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    return $this->load->view('field/hidden/hidden_widget_view', $data);
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/hidden');
    switch ($data['method_name']) {
      case "get_decrypt_value":
      default:
        return '';
    }
  }

  //геттеры
  public function get_decrypt_value($params)
  {
    $this->load->model('extension/field/hidden');
    return $this->model_extension_field_hidden->getDecryptValue($params['field_uid'], $params['document_uid']);
  }
}

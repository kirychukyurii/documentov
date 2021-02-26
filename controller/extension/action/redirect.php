<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionActionRedirect extends ActionController
{

  const ACTION_INFO = array(
    'name'              => 'redirect',
    'inRouteContext'    => true,
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/redirect');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/redirect', $data));
  }

  public function install()
  { }

  public function uninstall()
  { }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/redirect');
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($params['field_document_uid']);
    if (isset($field_info['name'])) {
      return sprintf($this->language->get('text_description'), $field_info['name']);
    }
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/redirect');
    $this->load->language('doctype/doctype');
    if (!empty($data['context']) && $data['context'] !== 'view') {
      //действие может быть применено только в контексте активности
      $data['error'] = $this->language->get('error_context');
    } else {
      if (empty($data['action']['field_document_uid'])) {
        $data['action']['field_document_uid'] = 0;
      } else {
        $field_document_info = $this->model_doctype_doctype->getField($data['action']['field_document_uid']);
        if ($field_document_info['setting']) {
          $field_doctype_info = $this->model_doctype_doctype->getDoctypeDescriptions($field_document_info['doctype_uid']);
          $name = $field_doctype_info[$this->config->get('config_language_id')]['name'] . " - " . $field_document_info['name'];
        }
        $data['field_document_name'] = $this->language->get('text_by_link_in_field') . ' ' . ($name ?? $field_document_info['name']);
        $data['field_document_setting'] = $field_document_info['setting'];
      }

      if (!empty($data['action']['document_route_uid'])) {
        $route_info = $this->model_doctype_doctype->getRoute($data['action']['document_route_uid']);
        $data['document_route_name'] = isset($route_info['name']) ? $route_info['name'] : "";
      }
    }
    return $this->load->view('action/redirect/redirect_form', $data);
  }


  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    return $data['params']['action'];
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return $this::ACTION_INFO;
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $result = $this->executeRoute($data);
    return ($result);
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    $result = array();

    if (!empty($data['params']['field_document_uid'])) {
      $value = $this->model_document_document->getFieldValue($data['params']['field_document_uid'], $data['document_uid'] ?? $data['document_uids'][0] ?? 0);
      //уточняем что в поле перенаправления - идентификатор документа или урл
      if (strpos($value, ".") !== FALSE || strpos($value, "/") !== FALSE) {
        //перенаправляем на урл
        $result['redirect'] = str_replace('&amp;', '&', $value);
      } else {
        //перенаправляем на документ
        $values = explode(",", $value);
        if (count($values) > 0) {
          $document_uid = $values[0];
          if (!$document_uid) {
            $result['error'] = $this->language->get('error_document_not_found');
            $result['log'] = $this->language->get('error_document_not_found');
          } else {
            $result['redirect'] = str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $document_uid . '&nocache=' . rand(100000000, 999999999)));
            $result['document_uid'] = $document_uid;
          }
        }
      }
    }
    return $result;
  }
}

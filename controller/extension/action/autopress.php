<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */


class ControllerExtensionActionAutopress extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'autopress',
    'inRouteContext' => true,
  );

  public function index()
  {
    $this->load->language('extension/action/autopress');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/autopress', $data));
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
    $this->load->language('action/autopress');
    $this->load->model('doctype/doctype');
    if (!empty($params['autopress_button_uid'])) {
      $atopress_button_info = $this->model_doctype_doctype->getRouteButton($params['autopress_button_uid']);
    }
    return sprintf($this->language->get('text_description'), $atopress_button_info['name'] ?? "");
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/autopress');
    if ($data['context'] !== 'view') {
      //действие может быть применено только в контексте активности
      $data['error'] = $this->language->get('error_context');
    } elseif (!empty($data['action']['autopress_button_uid'])) {
      $this->load->model('doctype/doctype');
      $atopress_button_info = $this->model_doctype_doctype->getRouteButton($data['action']['autopress_button_uid']);
      $data['autopress_button_name'] = $atopress_button_info['name'] ?? "";
    }
    return $this->load->view('action/autopress/autopress_form', $data);
  }


  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    return $data['params']['action'] ?? array();
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
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    if (!empty($data['params']['autopress_button_uid'])) {
      return array(
        'append' => $this->load->view('action/autopress/autopress_append_block', $data)
      );
    }
  }
}

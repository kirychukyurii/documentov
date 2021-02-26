<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */


class ControllerExtensionActionMessage extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'message',
    'inRouteContext' => true,
  );

  public function index()
  {
    $this->load->language('extension/action/message');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/message', $data));
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
    $this->load->language('action/message');
    return sprintf($this->language->get('text_description'), $params['title'][$this->config->get('config_language_id')] ?? "");
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/message');
    $this->load->language('doctype/doctype');
    if (isset($data['action']['template'])) {
      if (isset($data['route_uid'])) {
        $route_info = $this->model_doctype_doctype->getRoute($data['route_uid']);
        $doctype_uid = $route_info['doctype_uid'];
      } elseif (isset($data['doctype_uid'])) {
        $doctype_uid = $data['doctype_uid'];
      } elseif (isset($data['folder_uid'])) {
        $this->load->model('doctype/folder');
        $folder_info = $this->model_doctype_folder->getFolder($data['folder_uid']);
        $doctype_uid = $folder_info['doctype_uid'];
      }

      foreach ($data['action']['template'] as &$template) {
        $template = $this->model_doctype_doctype->getNamesTemplate($template, $doctype_uid, $this->model_doctype_doctype->getTemplateVariables());
      }
    }

    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    return $this->load->view('action/message/message_form', $data);
  }


  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    $this->load->model('doctype/doctype');
    if (!empty($data['route_button_uid'])) {
      $button_info = $this->model_doctype_doctype->getRouteButton($data['route_button_uid']);
      $route_info = $this->model_doctype_doctype->getRoute($button_info['route_uid']);
      $doctype_uid = $route_info['doctype_uid'];
    } elseif (!empty($data['route_action_uid'])) {
      $route_action_info = $this->model_doctype_doctype->getRouteAction($data['route_action_uid']);
      $route_info = $this->model_doctype_doctype->getRoute($route_action_info['route_uid']);
      $doctype_uid = $route_info['doctype_uid'];
    } elseif (!empty($data['route_uid'])) {
      $route_info = $this->model_doctype_doctype->getRoute($data['route_uid']);
      $doctype_uid = $route_info['doctype_uid'];
    } elseif (!empty($data['folder_uid'])) {
      $this->load->model('doctype/folder');
      $folder_info = $this->model_doctype_folder->getFolder($data['folder_uid']);
      $doctype_uid = $folder_info['doctype_uid'];
    } elseif (!empty($data['folder_button_uid'])) {
      $this->load->model('doctype/folder');
      $button_info = $this->model_doctype_folder->getButton($data['folder_button_uid']);
      $folder_info = $this->model_doctype_folder->getFolder($button_info['folder_uid']);
      $doctype_uid = $folder_info['doctype_uid'];
    }

    foreach ($data['params']['action']['template'] as &$template) {
      $template = $this->model_doctype_doctype->getIdsTemplate($template, $doctype_uid);
    }

    if (!empty($data['params']['action']['template'])) {
      $templates = [];
      foreach ($data['params']['action']['template'] as $lang_id => $templ) {
        $templates[(int) $lang_id] = $templ;
      }
      $data['params']['action']['template'] = $templates;
    }
    if (!empty($data['params']['action']['title'])) {
      $templates = [];
      foreach ($data['params']['action']['title'] as $lang_id => $templ) {
        $templates[(int) $lang_id] = $templ;
      }
      $data['params']['action']['title'] = $templates;
    }

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
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    $this->load->language('action/message');
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');

    //готовим окно для вывода     
    if (!empty($data['folder_uid'])) {
      //запуск через журнал
      $this->load->model('document/folder');
      $folder_info = $this->model_document_folder->getFolder($data['folder_uid']);
      $doctype_uid = $folder_info['doctype_uid'];
    } else {
      $document_info = $this->model_document_document->getDocument($data['document_uid'], false);
      if (!$document_info) {
        //нет информации о документе, возможно, нет прав доступа к документу
        return array();
      }
      $doctype_uid = $document_info['doctype_uid'];
    }

    $data_template = array(
      'document_uid'      => empty($data['folder_uid']) ? ($data['document_uid'] ?? 0) : 0,
      'doctype_uid'       => $doctype_uid,
      'draft'             => true,
      'mode'              => 'view',
      'template'          => htmlspecialchars_decode($data['params']['template'][$this->config->get('config_language_id')]),
    );

    $data_message = array(
      'content'           => $this->load->controller('document/document/renderTemplate', $data_template),
      'token'             => token(),
      'title'             => $data['params']['title'][$this->config->get('config_language_id')] ?? "",
      'document_uid'      => isset($data['document_uid']) ? $data['document_uid'] : 0,
    );
    return array(
      'append' => $this->load->view('action/message/message_modal_window', $data_message)
    );
  }
}

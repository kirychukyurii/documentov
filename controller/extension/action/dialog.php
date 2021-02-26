<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionActionDialog extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'dialog',
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/dialog');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/dialog', $data));
  }

  public function install()
  {
  }

  public function uninstall()
  {
  }

  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    $this->load->model('doctype/doctype');
    $doctype_uid = parent::getDoctypeUid($data);

    foreach ($data['params']['action']['template'] as &$template) {
      $template = $this->model_doctype_doctype->getIdsTemplate($template, $doctype_uid);
    }
    $data['params']['action']['history'] = empty($data['params']['action']['history']) ? 0 : 1;
    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/dialog');
    return $this->language->get('text_description');
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/dialog');
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
    return $this->load->view('action/dialog/dialog_button_form', $data);
  }


  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionDialog::ACTION_INFO;
  }


  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $this->load->language('action/dialog');
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->get['save'])) {
      $result = array();
      //в окне действия нажали на Сохранить
      //получаем шаблон
      $template = $data['params']['template'][$this->config->get('config_language_id')];
      if (isset($data['document_uid']) && isset($this->request->post['field'])) { //есть document_uid - запуск действия из документа
        $data_eft = array(
          'fields'         => $this->request->post['field'],
          'template'      => $template,
          'document_uid'  => $data['document_uid']
        );
        $result_save_fields = $this->load->controller('document/document/editFieldsTemplate', $data_eft);
        if (!empty($data['params']['history'])) {
          $this->model_document_document->addDocumentHistory($data['document_uid']);
        }
        $this->load->language('action/dialog');
        if (isset($result_save_fields['success'])) {
          $field_values = array();
          foreach ($this->request->post['field'] as $field_uid => $field_value) {
            $field_info = $this->model_doctype_doctype->getField($field_uid);
            if ($field_info) {
              $field_values[] = $field_info['name'] . ": " . $this->model_document_document->getFieldDisplay($field_uid, $data['document_uid']);
            }
          }
          $result = array(
            'log'      => $this->language->get('text_log') . implode("; ", $field_values)
          );
          if (!empty($result_save_fields['append'])) {
            $result['append'] = $result_save_fields['append'];
          }
        }
      } elseif (isset($this->request->post['field'])) { //запуск действия из журнала
        foreach ($data['document_uids'] as $document_uid) {
          $data_eft = array(
            'fields'         => $this->request->post['field'],
            'template'      => $template,
            'document_uid'  => $document_uid
          );
          $result_save_fields = $this->load->controller('document/document/editFieldsTemplate', $data_eft);
          if (!empty($data['params']['history'])) {
            $this->model_document_document->addDocumentHistory($document_uid);
          }
          if (isset($result_save_fields['success'])) {
            $result = array(
              'log'       => $this->language->get('text_log')
            );
          } else {
            break;
          }
          if (!empty($result_save_fields['append'])) {
            $result['append'] = $result_save_fields['append'];
          }
        }
      }
      return $result;
    } elseif (isset($this->request->get['cancel']) && !empty($data['document_uid'])) {
      //в диалоговом окне нажата отмена, удаляем черновик
      $this->model_document_document->removeDraftDocument($data['document_uid']);
      return array('stop' => "1"); //возвращаем пустой массив замены, чтобы обработчик кнопки остановил обработку (не выполнил перемещение, если оно есть в точке)
    } else {
      //готовим окно для вывода     
      if (!empty($data['folder_uid'])) {
        //запуск через журнал
        $this->load->model('document/folder');
        $folder_info = $this->model_document_folder->getFolder($data['folder_uid']);
        $doctype_uid = $folder_info['doctype_uid'];
      } else {
        $document_info = $this->model_document_document->getDocument($data['document_uid']);
        $doctype_uid = $document_info['doctype_uid'];
      }

      $data_template = array(
        'document_uid'      => empty($data['folder_uid']) ? ($data['document_uid'] ?? 0) : 0,
        'doctype_uid'       => $doctype_uid,
        'draft'             => true,
        'mode'              => 'form',
        'template'          => htmlspecialchars_decode($data['params']['template'][$this->config->get('config_language_id')]),
      );

      $data_header = array(
        'title' => $data['params']['title'][$this->config->get('config_language_id')] ?? ""
      );
      $data_field = array(
        'doctype_uid'    => $doctype_uid,
        'required'       => 1
      );
      $required_fields = $this->model_doctype_doctype->getFields($data_field);
      $req_fields = array();
      foreach ($required_fields as $field) {
        $req_fields[] = $field['field_uid'];
      }
      $data_footer = array(
        'button_uid'        => $data['button_uid'],
        'document_uid'      => isset($data['document_uid']) ? $data['document_uid'] : 0,
        'document_uids'     => isset($data['document_uids']) ? implode(",", $data['document_uids']) : 0,
        'required_fields'   => "'" . implode("','", $req_fields) . "'"
      );

      $header = $this->load->view('action/dialog/dialog_window_header', $data_header);
      $footer = $this->load->view('action/dialog/dialog_window_footer', $data_footer);
      return array(
        'window' => $header . $this->load->controller('document/document/renderTemplate', $data_template) . $footer
      );
    }
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
  }
}

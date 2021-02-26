<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionActionEmail extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'email',
    'inRouteContext' => true,
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/email');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/email', $data));
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

    foreach ($data['params']['action']['template_message'] as &$template) {
      $template = $this->model_doctype_doctype->getIdsTemplate($template, $doctype_uid);
    }
    foreach ($data['params']['action']['template_subject'] as &$template) {
      $template = $this->model_doctype_doctype->getIdsTemplate($template, $doctype_uid);
    }
    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/email');
    $result = $this->language->get('description_none_email');
    if (!empty($params['field_email_id'])) {
      $params['field_email_uid'] = $params['field_email_id']; //до 0.9.8 field_email_id
    }
    if (!empty($params['field_email_uid'])) {
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($params['field_email_uid']);
      if (isset($field_info['name'])) {
        $result = sprintf($this->language->get('description_send_email'), $field_info['name']);
        if (!empty($params['template_subject'][$this->config->get('config_language')])) {
          $result .= " " . sprintf($this->language->get('description_email_subject'), $params['template_subject'][$this->config->get('config_language')]);
        }
      }
    }
    return $result;
  }



  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    // print_r($data);
    // exit;

    $this->load->language('action/email');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');

    if (isset($data['action']['field_email_method_params']['FUID'])) {
      foreach ($data['action']['field_email_method_params'] as $n => $v) {
        if ($n != "FUID") {
          $data['action']['field_email_method_params'] = [$n => $v];
          break;
        }
      }
    }
    if (isset($data['action']['attached_file_method_params']['FUID'])) {
      foreach ($data['action']['attached_file_method_params'] as $n => $v) {
        if ($n != "FUID") {
          $data['action']['attached_file_method_params'] = [$n => $v];
          break;
        }
      }
    }

    $data['fields'] = $this->model_doctype_doctype->getFields(array('doctype_uid' => $data['doctype_uid']));
    if (!empty($data['action']['field_email_id'])) {
      $data['action']['field_email_uid'] = $data['action']['field_email_id']; //до 0.9.8 field_email_id
    }
    if (!empty($data['action']['field_email_uid'])) {

      $field_email_info = $this->model_doctype_doctype->getField($data['action']['field_email_uid']);
      if ($field_email_info) {
        if ($field_email_info['setting']) {
          $data['action']['field_email_type'] = 1;
          $data['action']['field_email_name'] = $this->model_doctype_doctype->getFieldName($data['action']['field_email_uid']);
        } else {
          $data['action']['field_email_name'] = $field_email_info['name'];
        }
        if (isset($data['action']['field_email_method']) && isset($data['action']['field_email_method_params'])) {
          $data['action']['field_email_method_params'] = $this->model_tool_utils->array2single($data['action']['field_email_method_params']);
        }
      } else {
        $data['action']['field_email_uid'] = '';
        $data['action']['field_email_name'] = '';
        $data['action']['field_email_name_method'] = '';
        $data['action']['field_email_name_method_params'] = '';
      }
    }
    if (isset($data['action']['template_message'])) {
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

      foreach ($data['action']['template_message'] as &$template) {
        $template = $this->model_doctype_doctype->getNamesTemplate($template, $doctype_uid, $this->model_doctype_doctype->getTemplateVariables());
      }
      foreach ($data['action']['template_subject'] as &$template) {
        $template = $this->model_doctype_doctype->getNamesTemplate($template, $doctype_uid, $this->model_doctype_doctype->getTemplateVariables());
      }
    }
    if (!empty($data['action']['attached_file_uid'])) {

      $attached_file_info = $this->model_doctype_doctype->getField($data['action']['attached_file_uid']);
      if ($attached_file_info) {
        if ($attached_file_info['setting']) {
          $data['action']['attached_file_type'] = 1;
          $data['action']['attached_file_name'] = $this->model_doctype_doctype->getFieldName($data['action']['attached_file_uid']);
        } else {
          $data['action']['attached_file_name'] = $attached_file_info['name'];
        }
        if (!empty($data['action']['attached_file_method']) && !empty($data['action']['attached_file_method_params'])) {
          $data['action']['attached_file_method_params'] = $this->model_tool_utils->array2single($data['action']['attached_file_method_params']);
        }
      } else {
        $data['action']['attached_file_uid'] = '';
        $data['action']['attached_file_name'] = '';
        $data['action']['attached_file_name_method'] = '';
        $data['action']['attached_file_name_method_params'] = '';
      }
    }
    // print_r($data['action']);
    // exit;

    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    return $this->load->view('action/email/email_form', $data);
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return $this::ACTION_INFO;
  }

  public function executeButton($data)
  {
    if (isset($data['document_uid'])) { //есть document_uid - запуск действия из документ
      $result = $this->executeRoute($data);
    } else { //запуск из журнала
      foreach ($data['document_uids'] as $document_uid) {
        $data['document_uid'] = $document_uid;
        $result = $this->executeRoute($data);
      }
    }
    return ($result);
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    $data_daemon = [
      'uid' => $data['params']['uid'],
      'document_uid' => $data['document_uid'],
      'session' => [
        'user_uid'  => $this->customer->getStructureId(),
        'language_id'   => (int) $this->config->get('config_language_id'),
        'pressed_button_uid' => $this->session->data['current_button_uid'] ?? "",
        'changed_field_uid' => $this->request->get['field_uid'] ?? "",
        'changed_field_value' => $this->request->get['field_value'] ?? "",
        'folder_uid' => $this->request->get['folder_uid'] ?? "",
      ],
    ];

    $result = $this->daemon->exec("ExecuteAction", $data_daemon);
    return $result;
    ///////////////// the end /////////////////
    $log = array();
    $this->language->load('action/email');
    if (!empty($data['params']['field_email_id'])) {
      $data['params']['field_email_uid'] = $data['params']['field_email_id']; //до 0.9.8 field_email_id
    }
    if (!empty($data['params']['field_email_uid'])) {
      $this->load->model('doctype/doctype');
      //кому отправляем почту
      if (empty($data['params']['field_email_uid']) && !empty($data['params']['field_email_id'])) {
        //до 0.9.8 field_email_id
        $data['params']['field_email_uid'] =  $data['params']['field_email_id'];
      }

      $method_params = array(
        'type' => 'document',
        'current_document_uid'  => $data['document_uid'],
        'field_uid' => $data['params']['field_email_uid'],
        'method_name' => $data['params']['field_email_method'] ?? "",
      );
      if (isset($data['params']['field_email_method_params'])) {
        $method_params['method_params'] = $data['params']['field_email_method_params'];
      }
      $field_email_info = $this->model_doctype_doctype->getField($method_params['field_uid']);
      $to = $this->load->controller('extension/field/' . $field_email_info['type'] . '/executeMethod', $method_params);
      if (!$to) {
        $log[] = $this->language->get('text_error_empty_to');
      } else {
        //определяем языки для писем, для этого ищем адреса почты в справочнике Пользователи
        $addresses = array();
        $templates = array();
        $this->load->model('extension/action/email');
        foreach (explode(",", $to) as $email) {
          //адресатов может быть несколько
          $email = trim($email);
          if (preg_match('/[\w+\._-]+@[\w+\._-]+\.\w+/', $email)) {
            //пытаемся найти емайл в справочнике пользователей, чтобы определить язык
            $language_id = $this->model_extension_action_email->getLanguageId($email) ?? $this->config->get('config_language_id');
            if (!$language_id) {
              $language_id = $this->config->get('config_language_id');
            }
            if (!$language_id) {
              $language_id = $this->config->get('config_language_id');
            }
            $addresses[$email] = $language_id;
            $templates[$language_id] = array(
              'subject' => "",
              'message' => ""
            );
          } else {
            $log[] = sprintf($this->language->get('text_error_wrong_email'), $email);
          }
        }
        //формируем шаблоны
        $document_info = $this->model_document_document->getDocument($data['document_uid'], false);
        $data_template = array(
          'document_uid'      => $data['document_uid'],
          'doctype_uid'       => $document_info['doctype_uid'],
          'draft'             => FALSE,
          'mode'              => 'view'
        );

        $header = $this->load->view('action/email/mail_header', array());
        $footer = $this->load->view('action/email/mail_footer', array());
        foreach ($templates as $language_id => &$template) {
          $data_template['template'] = $data['params']['template_subject'][$language_id] ?? $data['params']['template_subject'][$this->config->get('config_language_id')] ?? current($data['params']['template_subject']);
          $template['subject'] = strip_tags(htmlspecialchars_decode($this->load->controller('document/document/renderTemplate', $data_template)));
          $data_template['template'] = $data['params']['template_message'][$language_id] ?? $data['params']['template_message'][$this->config->get('config_language_id')] ?? current($data['params']['template_message']);
          $template['message'] = $header . str_replace(array("<br>", "<p>"), array("<br>\r\n", "\r\n<p>"), htmlspecialchars_decode($this->load->controller('document/document/renderTemplate', $data_template))) . $footer;
        }


        //добавляем задание в очередь демона
        $this->load->model('daemon/queue');
        $emails = array();
        foreach ($addresses as $email => $language_id) {
          $data_daemon = array(
            'to' => $email,
            'subject' => $templates[$language_id]['subject'],
            'message' => $templates[$language_id]['message']
          );
          $this->model_daemon_queue->addTask('tool/mail/send', $data_daemon);
          $emails[] = $email;
        }
        $log[] = $this->language->get('text_mail_send') . " " . implode(", ", $emails);
      }
    }
    return array(
      'log'   => implode(". ", $log)
    );
  }
}

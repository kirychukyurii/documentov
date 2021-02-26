<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionActionNotification extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'notification',
    'inRouteContext' => true,
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/notification');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);
    $data['config_notification_pooling_period'] = $this->config->get('notification_pooling_period');
    $this->response->setOutput($this->load->view('extension/action/notification', $data));
  }

  public function install()
  {
  }

  public function uninstall()
  {
  }

  public function setting()
  {
    /*$this->load->language('extension/action/notification');
        if ($this->request->server['REQUEST_METHOD'] == "POST") {
            //сохраняем настройки
            $this->load->model('setting/setting');           
            $setting = array();
            if (isset($this->request->post['config_notification_pooling_period'])) {
                $setting['config_notification_pooling_period'] = $this->request->post['config_notification_pooling_period'];
            }

            if ($setting) {
                $this->model_setting_setting->editSetting('dv_action_notification', $setting);
            }
            $this->response->addHeader("Content-type: application/json");
            $this->response->setOutput(json_encode(array('success' => 1)));
        } else {
            $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
            $data['action'] = $this->url->link('extension/field/file/setting22', '', true);
            $data['config_notification_pooling_period'] = $this->config->get('config_notification_pooling_period');
            $this->response->setOutput($this->load->view('extension/action/notification', $data));
        }*/
  }

  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    $this->load->model('doctype/doctype');
    if (!empty($data['route_action_uid'])) {
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
    } elseif (!empty($data['route_button_uid'])) {
      $button_info = $this->model_doctype_doctype->getRouteButton($data['route_button_uid']);
      $route_info = $this->model_doctype_doctype->getRoute($button_info['route_uid']);
      $doctype_uid = $route_info['doctype_uid'];
    }

    if (!empty($data['params']['action']['notification_template'])) {
      $templates = [];
      foreach ($data['params']['action']['notification_template'] as $lang_id => $templ) {
        $templates[(int) $lang_id] = $this->model_doctype_doctype->getIdsTemplate($templ, $doctype_uid);
      }
      $data['params']['action']['notification_template'] = $templates;
    }

    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/notification');
    $result = $this->language->get('description_no_recipient');
    if (!empty($params['recipient_field_uid'])) {
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($params['recipient_field_uid']);
      if (isset($field_info['name'])) {
        $result = sprintf($this->language->get('description_send_notification'), $field_info['name']);
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
    $this->load->language('action/notification');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');
    $data['fields'] = $this->model_doctype_doctype->getFields(array('doctype_uid' => $data['doctype_uid']));
    if (isset($data['action']['recipient_field_uid'])) {
      $data['recipient_field_name'] = $this->model_doctype_doctype->getFieldName($data['action']['recipient_field_uid']);
    }

    if (isset($data['action']['notification_template'])) {
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
      $data['target_doclink_field_name'] = $this->language->get('text_currentdoc');
      if (empty($data['action']['target_doclink_field_uid'])) {
        $data['action']['target_doclink_field_uid'] = '0';
      } else {
        $target_doclink_field = $this->model_doctype_doctype->getField($data['action']['target_doclink_field_uid']);
        $target_doclink_field_name = $this->language->get('text_by_link_in_field') . ' &quot;' . $target_doclink_field['name'] . '&quot;';
        $data['target_doclink_field_name'] = $target_doclink_field_name;
        $data['target_doclink_field_setting'] = $target_doclink_field['setting'];
      }

      foreach ($data['action']['notification_template'] as &$template) {
        $template = $this->model_doctype_doctype->getNamesTemplate($template, $doctype_uid, $this->model_doctype_doctype->getTemplateVariables());
      }
    }

    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    return $this->load->view('action/notification/notification_form', $data);
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
    $this->language->load('action/notification');
    $this->load->model('document/document');
    if (!isset($data['params']['target_doclink_field_uid'])) {
      return;
    }
    $document_uids = array();
    if ($data['params']['target_doclink_field_uid'] != '0') {
      //проверяем на наличие документов по ссылкам из поля
      foreach (explode(",", $this->model_document_document->getFieldValue($data['params']['target_doclink_field_uid'], $data['document_uid'])) as $document_uid) {
        if ($this->model_document_document->getDocument($document_uid, false) || $data['params']['action_type'] === 'remove') {
          $document_uids[] = $document_uid;
        }
      }
    } else {
      if ($this->model_document_document->getDocument($data['document_uid'], false) || $data['params']['action_type'] === 'remove') {
        $document_uids[] = $data['document_uid'];
      }
    }

    if (empty($document_uids)) {
      return;
    }

    if (!empty($data['params']['recipient_field_uid'])) {
      //получаем всех получателей
      $recipient_value = $this->model_document_document->getFieldValue($data['params']['recipient_field_uid'], $data['document_uid']);
      $structure_uids = array();
      if ($recipient_value) {
        foreach (explode(",", $recipient_value) as $structure_uid) {
          if ($structure_uid) {
            $children = $this->model_document_document->getDescendantsDocuments($structure_uid, $this->config->get('structure_field_parent_id'));
            if (!empty($children)) {
              $structure_uids = array_merge($structure_uids, $children);
            }
            $structure_uids[] = $structure_uid;
            //'structure_field_user_id'
          }
        }
      } else {
        return;
      }
    } else {
      return;
    }

    //формируем сообщение по шаблону
    $message = array();
    if ($data['params']['action_type'] !== 'remove') {

      $document_info = $this->model_document_document->getDocument($data['document_uid'], false);
      $data_template = array(
        'document_uid' => $data['document_uid'],
        'doctype_uid' => $document_info['doctype_uid'],
        'draft' => FALSE,
        'mode' => 'view'
      );
      foreach ($this->model_localisation_language->getLanguages() as $language) {
        if (isset($data['params']['notification_template'][$language['language_id']])) {
          $data_template['template'] = $data['params']['notification_template'][$language['language_id']];
          $message[$language['language_id']] = strip_tags(htmlspecialchars_decode($this->load->controller('document/document/renderTemplate', $data_template)));
        } else {
          $message[$language['language_id']] = "";
        }
      };
    }
    $user_field_uid = $this->config->get('structure_field_user_id');
    $user_filed_language_id = $this->config->get('user_field_language_id');
    $config_language_id = $this->config->get('config_language_id');

    $user_uids = $this->model_document_document->getFieldValues($user_field_uid);
    $language_ids = $this->model_document_document->getFieldValues($user_filed_language_id);

    foreach ($document_uids as $document_uid) {
      foreach ($structure_uids as $structure_uid) {
        if (!empty($user_uids[$structure_uid])) {
          if ($data['params']['action_type'] === 'remove') {
            $this->model_document_document->removeNotifications($document_uid, $structure_uid);
          } else {
            $language_id = $language_ids[$user_uids[$structure_uid]] ?? $config_language_id;
            $language_id = $language_id && is_numeric($language_id) ? $language_id : $config_language_id;
            $msgloc = $message[$config_language_id];
            if (isset($message[$language_id])) {
              $msgloc = $message[$language_id];
            }
            $this->model_document_document->setNotification($document_uid, $structure_uid, $msgloc);
          }
        }
      }
    }

    return array(
      'log' => implode(". ", $log)
    );
  }
}

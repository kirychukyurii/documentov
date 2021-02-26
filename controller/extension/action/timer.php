<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
/*
 *  Действие таймер. Таймер позволяет произвести запись в определенное поле документа (либо настроечное поле) в определенное время.
 *  Таймер должен быть привязан как к конкретному документу.
 *  Таймер можно отключить, можно включить. Для выключения, необходимо указать, где брать UUID документа (документов) и идентификатор таймера. UUID можно брать из текущего документа, либо из ссылочного поля. После записи в документа, срабатывает контекст активности.
 *  Идентификаторы таймеров выбираются из списка. Количество таймеров можно задавать в настройках действия.
 */

class ControllerExtensionActionTimer extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'timer',
    'inRouteContext' => true,
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/timer');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/timer', $data));
  }

  public function install()
  {
    $this->db->query("CREATE TABLE `action_timer` (`identifier` varchar(255) NOT NULL, `document_uid` varchar(36) NOT NULL, `task_id` int(11) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
  }

  public function uninstall()
  {
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/timer');
    $this->load->model('doctype/doctype');
    $doclink_field_name = "";
    $identifier_field_name = "";
    $result = "";

    if (!empty($params['timer_doclink_field_uid']) && $params['timer_doclink_field_uid'] !== '0') {
      $doclink_field_name = $this->model_doctype_doctype->getField($params['timer_doclink_field_uid'])['name'];
    }

    if (!empty($params['identifier_field_uid'])) {
      $identifier_field_name = $this->model_doctype_doctype->getField($params['identifier_field_uid'])['name'];
    }

    switch ($params['timer_action']) {
      case "0":
        $exectime_field_name = "";
        if (!empty($params['exectime_field_uid'])) {
          $exectime_field_name = $this->model_doctype_doctype->getField($params['exectime_field_uid'])['name'];
        }

        if ($doclink_field_name === "") {
          if ($identifier_field_name === "") {
            $result = sprintf($this->language->get('text_description_start_3'), $exectime_field_name);
          } else {
            $result = sprintf($this->language->get('text_description_start_1'), $exectime_field_name, $identifier_field_name);
          }
        } else {
          if ($identifier_field_name === "") {
            $result = sprintf($this->language->get('text_description_start_4'), $doclink_field_name, $exectime_field_name);
          } else {
            $result = sprintf($this->language->get('text_description_start_2'), $doclink_field_name, $exectime_field_name, $identifier_field_name);
          }
        }
        break;
      case "1":
        if ($doclink_field_name === "") {
          $result = sprintf($this->language->get('text_description_stop_1'), $identifier_field_name);
        } else {
          $result = sprintf($this->language->get('text_description_stop_2'), $doclink_field_name, $identifier_field_name);
        }
        break;
      case "2":
        if ($doclink_field_name === "") {
          $result = sprintf($this->language->get('text_description_stop_all_1'));
        } else {
          $result = sprintf($this->language->get('text_description_stop_all_2'), $doclink_field_name);
        }
        break;
    }
    return $result;
  }

  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/timer');
    $this->load->language('doctype/doctype');
    $lang_id = (int) $this->config->get('config_language_id');

    if (empty($data['action']['timer_doclink_field_uid'])) {
      $data['action']['timer_doclink_field_uid'] = "0";
      $data['timer_doclink_field_name'] = $this->language->get('text_currentdoc');
    } else {
      $timer_doclink_field = $this->model_doctype_doctype->getField($data['action']['timer_doclink_field_uid']);
      $timer_doclink_field_name = $this->language->get('text_by_link_in_field') . ' &quot' . $timer_doclink_field['name'] . '&quot';
      $data['timer_doclink_field_name'] = $timer_doclink_field_name;
      $data['timer_doclink_field_setting'] = $timer_doclink_field['setting'];
    }

    if (!empty($data['action']['exectime_field_uid'])) {
      $exectime_field_description = $this->model_doctype_doctype->getField($data['action']['exectime_field_uid']);
      $data['exectime_field_name'] = $exectime_field_description['name'];
    }

    if (!empty($data['action']['identifier_field_uid'])) {
      $identifier_field_description = $this->model_doctype_doctype->getField($data['action']['identifier_field_uid']);
      $identifier_field_type = $identifier_field_description['type'];
      $identifier_field_doctype_uid = $identifier_field_description['doctype_uid'];
      $doctypename = $this->model_doctype_doctype->getDoctypeDescriptions($identifier_field_doctype_uid)[$lang_id]['name'];
      if (strcmp($data['action']['timer_doclink_field_uid'], '0') === 0) {
        $data['identifier_field_name'] = $identifier_field_description['name'];
      } else {
        $data['identifier_field_name'] = $doctypename . ' - ' . $identifier_field_description['name'];
      }
    } else {
      $data['action']['identifier_field_uid'] = "0";
      //$data['identifier_field_name'] = $this->language->get('text_none');
    }

    if (!empty($data['action']['target_field_uid'])) {
      $target_field_description = $this->model_doctype_doctype->getField($data['action']['target_field_uid']);
      $target_field_type = $target_field_description['type'];
      $target_field_doctype_uid = $target_field_description['doctype_uid'];
      $doctypename = $this->model_doctype_doctype->getDoctypeDescriptions($target_field_doctype_uid)[$lang_id]['name'];
      if (strcmp($data['action']['timer_doclink_field_uid'], '0') === 0) {
        $data['target_field_name'] = $target_field_description['name'];
      } else {
        $data['target_field_name'] = $doctypename . ' - ' . $target_field_description['name'];
      }

      /* $data['avaliable_setters'] = $this->load->controller('extension/field/' . $target_field_type . '/getFieldMethods', 'setter');
              if (!empty($data['action']['target_field_setter'])) {
              $method_info = array(
              'method' => $data['action']['target_field_setter'],
              'field_uid' => $data['action']['target_field_uid']
              );
              if (!empty($data['action']['method'][$data['action']['target_field_setter']])) {
              $method_info['method_params'] = $data['action']['method'][$data['action']['target_field_setter']];
              }
              $data['setter_form'] = $this->load->controller('extension/field/' . $target_field_type . '/getFieldMethodForm', $method_info);
              } */
    }

    if (!empty($data['action']['document_route_uid'])) {
      $route_info = $this->model_doctype_doctype->getRoute($data['action']['document_route_uid']);
      $doctype_name = '';
      if ($data['action']['timer_doclink_field_uid'] !== "0") {
        $doctype_uid = $route_info['doctype_uid'];
        $language_id = $this->config->get('config_language_id');
        $doctype_info = $this->model_doctype_doctype->getDoctypeDescriptions($doctype_uid)[$language_id];
        $doctype_name = $doctype_info['name'] . ' - ';
      }
      $data['document_route_name'] = $doctype_name . (isset($route_info['name']) ? $route_info['name'] : "");
    }

    return $this->load->view('action/timer/timer_form', $data);
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
    $dd = [
      'uid' => $data['button_uid'],
      'session' => $this->getSession()
    ];
    if (!empty($data['document_uid'])) {
      $dd['document_uid'] = $data['document_uid'];
    }
    if (!empty($data['document_uids'])) {
      $dd['document_uids'] = $data['document_uids'];
    }

    $result = $this->daemon->exec("ExecuteButtonAction", $dd);

    return $result;
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
  }

  public function executeDeferred($data)
  {
    $this->load->model('extension/action/timer');
    $this->load->model('document/document');
    if (isset($data['timer_document_uid'])) {
      // проверяем наличие документа, вдруг, удалили
      $timer_document_info = $this->model_document_document->getDocument($data['timer_document_uid'], false);
      if (!$timer_document_info) {
        $this->model_extension_action_timer->unsetTimer($data['timer_document_uid']);
      } else if (isset($data['identifier'])) {
        if (!empty($data['target_field_uid'])) {
          $this->model_document_document->editFieldValue($data['target_field_uid'], $data['timer_document_uid'], $data['identifier']);
        }
        $this->model_extension_action_timer->unsetTimer($data['timer_document_uid'], $data['identifier']);
      }
      if (!empty($data['document_route_uid'])) {
        $this->model_document_document->moveRoute($data['timer_document_uid'], $data['document_route_uid']);
        $params = array("document_uid" => $data['timer_document_uid'], "context" => 'jump');
        $this->load->controller("document/document/route_cli", $params);
      }
      //повторная проверка на наличие документа после отрабатывания маршрута
      $timer_document_info = $this->model_document_document->getDocument($data['timer_document_uid'], false);
      if (!$timer_document_info) {
        $this->model_extension_action_timer->unsetTimer($data['timer_document_uid']);
      }
    }
  }

  private function getSession()
  {
    return [
      'user_uid'  => $this->customer->getStructureId(),
      'language_id'   => (int) $this->config->get('config_language_id'),
      'pressed_button_uid' => $this->session->data['current_button_uid'] ?? "",
      'changed_field_uid' => $this->request->get['field_uid'] ?? "",
      'changed_field_value' => $this->request->get['field_value'] ?? "",
      'folder_uid' => $this->request->get['folder_uid'] ?? "",
    ];
  }
}

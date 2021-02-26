<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionActionMove extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'move',
    //        'inRouteButton' => true,
    //        'inFolderButton' => true,
    'inRouteContext' => true
  );

  public function index()
  {
    $this->load->language('extension/action/move');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/move', $data));
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
    $this->load->language('action/move');
    $this->load->model('doctype/doctype');
    $description_move = "";
    if (!empty($params['field_document_uid'])) {
      //перемещение по ссылке из поля
      $field_info = $this->model_doctype_doctype->getField($params['field_document_uid']);
      $description_move = $this->language->get('text_description_move_doctype') . ' ' . $this->language->get('text_by_link_in_field') . " " . $field_info['name'];
    } else {
      $description_move = $this->language->get('text_description_move') . ' ' . $this->language->get('text_currentdoc');
    }
    if (isset($params['routeSource'])) {
      switch ($params['routeSource']) {
        case 'fuid':
          if (!empty($params['document_route_field_uid'])) {
            $field_name = $this->model_doctype_doctype->getFieldName($params['document_route_field_uid']);
            $description_move .= $this->language->get('text_description_move_route_field') . ' ' . $field_name;
          }
          break;
        default:
          if ($params['document_route_uid']) {
            $route_info = $this->model_doctype_doctype->getRoute($params['document_route_uid']);
            if ($route_info) {
              $description_move .= $this->language->get('text_description_move_route') . ' ' . $route_info['name'];
            }
          }
          break;
      }
    }

    // if ($params['document_route_uid']) {
    //   $route_info = $this->model_doctype_doctype->getRoute($params['document_route_uid']);
    //   if ($route_info) {
    //     $description_move .= $this->language->get('text_description_move_route') . ' ' . $route_info['name'];
    //   }
    // } else if (!empty($params['document_route_field_uid'])) {
    //   $field_name = $this->model_doctype_doctype->getFieldName($params['document_route_field_uid']);
    //   $description_move .= $this->language->get('text_description_move_route_field') . ' ' . $field_name;
    // }
    return $description_move;
  }

  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    // print_r($data);
    // exit;

    $this->load->language('action/move');
    $this->load->language('doctype/doctype');

    $data['field_document_name'] = $this->language->get('text_currentdoc');
    $field_document_info = '';
    if (empty($data['action']['field_document_uid'])) {
      $data['action']['field_document_uid'] = 0;
    } else {
      $field_document_info = $this->model_doctype_doctype->getField($data['action']['field_document_uid']);
      $data['field_document_name'] = $this->language->get('text_by_link_in_field') . ' &quot;' . $field_document_info['name'] . '&quot;';
      $data['field_document_setting'] = $field_document_info['setting'];
    }

    if (!empty($data['action']['document_route_uid'])) {
      $route_info = $this->model_doctype_doctype->getRoute($data['action']['document_route_uid']);
      if ($route_info) {
        $doctype_name = '';
        if ($field_document_info) {
          $doctype_uid = $route_info['doctype_uid'];
          $language_id = $this->config->get('config_language_id');
          $doctype_info = $this->model_doctype_doctype->getDoctypeDescriptions($doctype_uid)[$language_id];
          $doctype_name = $doctype_info['name'] . ' - ';
        }
        $data['document_route_name'] = $doctype_name . (isset($route_info['name']) ? $route_info['name'] : "");
      }
    } elseif (!empty($data['action']['document_route_field_uid'])) {
      //такой точки нет, возможно, перемещение по полю с идентификатором точки
      $field_name = $this->model_doctype_doctype->getFieldName($data['action']['document_route_field_uid']);
      $data['document_route_name'] = $field_name;
      $data['action']['document_route_uid'] = $data['action']['document_route_field_uid'];
      // $data['document_route_type'] = 1;
    }

    return $this->load->view('action/move/move_form', $data);
  }

  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {

    if (isset($data['params']['action']['routeSource']) && $data['params']['action']['routeSource'] == "fuid" && empty($data['params']['action']['document_route_field_uid'])) {
      $data['params']['action']['document_route_field_uid'] =  $data['params']['action']['document_route_uid'];
      $data['params']['action']['document_route_uid'] = "";
    }
    // [field_document_uid] => 0
    // [routeSource] => fuid
    // [document_route_uid] => 
    // [type_move] => 0
    // $data['params']['action']['document_route_type'] = !empty($data['params']['action']['document_route_type']) ? 1 : 0;
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
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    exit;
    $this->load->language('action/move');
    if (!empty($data['params']['field_document_uid'])) { //перемещаем документы по ссылке из поля
      $value = $this->model_document_document->getFieldValue($data['params']['field_document_uid'], $data['document_uid']);
      $document_uids = explode(",", $value);
    } else {
      $document_uids = array($data['document_uid']);
    }
    if ($document_uids && ($data['params']['document_route_uid'] || $data['params']['document_route_field_uid'])) {
      $daemon_queue_move = array();
      foreach ($document_uids as $document_uid) {
        if ($data['document_uid'] == $document_uid) {
          //перемещается текущий документ
          if ($data['params']['document_route_uid']) {
            $this->model_document_document->moveRoute($document_uid, $data['params']['document_route_uid']);
          } else {
            // if (!$this->model_document_document->moveRoute($document_uid, $data['params']['document_route_uid'])) {
            $field_value = $this->model_document_document->getFieldValue($data['params']['document_route_field_uid'], $data['document_uid']);
            $route_uids = explode(",", $field_value);
            if (!empty($route_uids[0]) && $this->model_tool_utils->validateUID($route_uids[0])) {
              $this->model_document_document->moveRoute($document_uid, $route_uids[0]);
            }
          }
        } else {
          //перемещается другой документ
          if ($data['params']['document_route_uid']) {
            $daemon_queue_move[$data['params']['document_route_uid']][] = $document_uid;
          } else {
            // if (!$this->model_document_document->isDocumentRoute($document_uid, $data['params']['document_route_uid'])) {
            //точки нет, возможно перемещаем из поля
            $field_value = $this->model_document_document->getFieldValue($data['params']['document_route_field_uid'], $data['document_uid']);
            $route_uids = explode(",", $field_value);
            if (!empty($route_uids[0]) && $this->model_tool_utils->validateUID($route_uids[0]) && $this->model_document_document->isDocumentRoute($document_uid, $route_uids[0])) {
              //такая точка есть
              $daemon_queue_move[$route_uids[0]][] = $document_uid;
            }
          }
          // else {
          //   $daemon_queue_move[$data['params']['document_route_uid']][] = $document_uid;
          // }
        }
      }
      if (count($document_uids)) {
        $result = array(
          'log' => $this->language->get('text_description_documents')
        );
      } else {
        $result = array(
          'log' => $this->language->get('text_description_document')
        );
      }
      //планируем обработку точек перемещенных сторонних доков
      if ($daemon_queue_move) {
        foreach ($daemon_queue_move as $route_uid => $move_document_uids) {
          $queue_data = array(
            'document_uids' => $move_document_uids,
            'document_route_uid' => $route_uid
          );
          if (empty($data['params']['type_move']) && $this->daemon->getStatus()) {
            $this->daemon->addTask('extension/action/move/executeDeferred', $queue_data, 1);
          } else {
            $this->executeDeferred($queue_data);
          }
        }
      }
    } else {
      $result = array(
        'error' => $this->language->get('error_document_not_found'),
        'log' => $this->language->get('error_document_not_found')
      );
    }
    return $result;
  }

  public function executeDeferred($data)
  {
    $this->load->model('document/document');
    foreach ($data['document_uids'] as $document_uid) {
      if ($this->model_document_document->moveRoute($document_uid, $data['document_route_uid'])) {
        $params = array("document_uid" => $document_uid, "context" => 'jump');
        $this->load->controller("document/document/route_cli", $params);
      }
    }
  }
}

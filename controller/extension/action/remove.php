<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */


class ControllerExtensionActionRemove extends ActionController
{
  const ACTION_INFO = array(
    'name'              => 'remove',
    'inFolderButton'    => true,
    'inRouteButton'     => true,
    'inRouteContext'     => true
  );

  public function index()
  {
    $this->load->language('extension/action/remove');

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
    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/remove');
    $this->load->model('doctype/doctype');
    if (empty($params['field_document_uid'])) {
      return $this->language->get('text_description_current_doc');
    } else {
      $field_document_info = $this->model_doctype_doctype->getField($params['field_document_uid']);
      return sprintf($this->language->get('text_description_field_doc'), $field_document_info['name']);
    }
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/remove');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    if (empty($data['folder'])) {
      if (empty($data['action']['field_document_uid'])) {
        $data['action']['field_document_uid'] = 0;
      } else {
        $field_document_info = $this->model_doctype_doctype->getField($data['action']['field_document_uid']);
        $data['field_document_name'] = $this->language->get('text_by_link_in_field') . ' &quot' . $field_document_info['name'] . '&quot';
        $data['field_document_setting'] = $field_document_info['setting'];
      }
    }
    return $this->load->view('action/remove/remove_form', $data);
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionRemove::ACTION_INFO;
  }


  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $this->load->model('document/document');
    $this->load->language('action/remove');
    if (isset($data['document_uids']) && !$data['document_uids']) {
      //запуск из журнала, ни один из документов не выбран
      $result = array(
        'window' => $this->load->view('action/remove/remove_unselect_folder_window', array())
      );
    } elseif (!empty($data['params']['confirm'][$this->config->get('config_language_id')]) && $this->request->server['REQUEST_METHOD'] === 'POST' && empty($this->request->get['save'])) {
      //нужно запрашивать подтверждение
      $data_window = array(
        'button_uid'        => $data['button_uid'],
        'document_uid'      => isset($data['document_uid']) ? $data['document_uid'] : 0,
        'document_uids'     => isset($data['document_uids']) ? implode(",", $data['document_uids']) : 0,
        'text_confirm'      => $data['params']['confirm'][$this->config->get('config_language_id')]
      );
      $result = array(
        'window' => $this->load->view('action/remove/remove_confirm_window', $data_window)
      );
    } else {
      //выполняем удаление
      if (isset($data['document_uid'])) {
        $dd = [
          'uid' => $data['button_uid'],
          'document_uid' => $data['document_uid'],
          'session' => $this->getSession()
        ];
        $result = $this->daemon->exec("ExecuteButtonAction", $dd);

        //запуск из документа
        // if (empty($data['params']['field_document_uid'])) {
        //   //удаляется текущий документ
        //   $result = $this->model_document_document->removeDocument($data['document_uid']);
        //   if (!empty($result['error'])) {
        //     return array(
        //       'reload'    => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $data['document_uid'] . '&_=' . rand(100000000, 999999999))),
        //       'log'       => $result['error']
        //     );
        //   }
        //   if (!$result) {
        //     $result = array(
        //       'reload'    => $this->url->link('info/success', '')
        //     );
        //   }
        // } else {
        //   //удаление документа (или несколько документов) из какого-то поля
        //   $field_value = $this->model_document_document->getFieldValue($data['params']['field_document_uid'], $data['document_uid']);
        //   $result = array(
        //     //                        'reload' => str_replace('&amp;', '&', $this->url->link('document/document','document_uid=' . $data['document_uid'] . '&_=' . rand(100000000, 999999999))),
        //     'log'      => $this->language->get('text_log')
        //   );
        //   if ($field_value) {
        //     foreach (explode(",", $field_value) as $document_uid) {
        //       $result = $this->model_document_document->removeDocument($document_uid);
        //     }
        //   }
        // }
      } elseif (isset($data['document_uids'])) {
        //запуск из журнала
        $dd = [
          'uid' => $data['button_uid'],
          'document_uids' => $data['document_uids'],
          'session' => $this->getSession()
        ];
        $result = $this->daemon->exec("ExecuteButtonAction", $dd);

        // $errors = array();
        // foreach ($data['document_uids'] as $document_uid) {
        //   $result = $this->model_document_document->removeDocument($document_uid);
        //   if (!empty($result['error'])) {
        //     $errors[] = $result['error'];
        //   }
        // }
        // if (empty($errors)) {
        //   $result = array(
        //     'reload'    => 'table',
        //     'log'       => $this->language->get('text_log')
        //   );
        // } else {
        //   $result['error'] = implode(", ", $errors);
        // }
      }
    }
    // print_r($result);
    // exit;

    return $result ?? array();
  }

  //у Редактирования не должно быть этого метода, это просто демонстрация
  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    $this->load->model('document/document');
    if (empty($data['params']['field_document_uid'])) {
      if ($data['context'] == "delete") {
        return; // защита от зацикливания - Удаление на удаление текущего дока в его контексте удаления
      }
      //текущий документ
      $result = $this->model_document_document->removeDocument($data['document_uid']);
      if (!empty($result['error'])) {
        return array(
          'reload'    => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $data['document_uid'] . '&_=' . rand(100000000, 999999999))),
          'log'       => $result['error']
        );
      } else {
        return array('reload'    => $this->url->link('info/success', '')); //при reload в плиточ журнале не показывается страница
      }
    } else {
      //документы из поля
      $field_value = $this->model_document_document->getFieldValue($data['params']['field_document_uid'], $data['document_uid']);
      $result = array(
        'reload' => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $data['document_uid'] . '&_=' . rand(100000000, 999999999))),
        'log'      => $this->language->get('text_log')
      );
      if ($field_value) {
        foreach (explode(",", $field_value) as $document_uid) {
          $error = $this->model_document_document->removeDocument($document_uid);
          if ($error) {
            $result = array('error' => $error);
          } elseif ($document_uid == $data['document_uid']) {
            $result = array(
              'reload'    => $this->url->link('info/success', '')
            );
          }
        }
      }
      return $result;
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

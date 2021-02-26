<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionActionAccess extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'access',
    'inFolderButton' => true,
    'inRouteButton' => true,
    'inRouteContext' => true
  );

  public function index()
  {
    $this->load->language('extension/action/access');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/access', $data));
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
    // echo "P:";
    // print_r($params);
    // exit;

    $this->load->language('action/access');
    $this->load->model('doctype/doctype');
    if (!empty($params['field_subject_id'])) {
      $field_subject_info = $this->model_doctype_doctype->getField($params['field_subject_id'], 0)['name'];
    }
    if (!empty($params['field_object_id'])) {
      $field_object_info = $this->model_doctype_doctype->getField($params['field_object_id'], 0)['name'];
    }

    switch ($params['object_type']) {
      case 'current_document':
        $field_object_info = $this->language->get('text_currentdoc');
        break;
      case 'document':
        $field_object_info = $this->language->get('text_object_type_document') . '"' . $field_object_info . '"';
        break;
      case 'doctype_list':
        $lang_id = (int) $this->config->get('config_language_id');
        if (!empty($params['doctype_uid']) && $params['doctype_uid'] !== '0') {
          $doctypename = $this->model_doctype_doctype->getDoctypeDescriptions($params['doctype_uid'])[$lang_id]['name'];
          $field_object_info = $this->language->get('text_doctype') . ' "' . $doctypename . '"';
        } else {
          $field_object_info = "";
        }

        break;
      case 'doctype':
        $field_object_info = $this->language->get('text_object_type_doctype') . '"' . $field_object_info . '"';
        break;
    }

    return sprintf(
      $this->language->get('text_description'),
      mb_strtolower($this->language->get('text_access_' . $params['access'])),
      !empty($field_subject_info) ? $field_subject_info : "",
      !empty($field_object_info) ? $field_object_info : ""
    );
  }

  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    //#TODO Добавить параметр актуализации доступа (запуск через event opencart)
    //        print_r($data);exit;
    $this->load->model('doctype/doctype');
    if (!empty($data['action']['field_subject_id'])) {
      $data['action']['field_subject_name'] = $this->model_doctype_doctype->getField($data['action']['field_subject_id'], 0)['name'];
    }
    if (!empty($data['action']['field_object_id'])) {
      $data['action']['field_object_name'] = $this->model_doctype_doctype->getField($data['action']['field_object_id'], 0)['name'];
    }
    if (!empty($data['action']['field_author_id'])) {
      $data['action']['field_author_name'] = $this->model_doctype_doctype->getField($data['action']['field_author_id'], 0)['name'];
    }
    if (!empty($data['action']['doctype_uid'])) {
      $doctype_info = $this->model_doctype_doctype->getDoctype($data['action']['doctype_uid']);
      $data['action']['doctype_name'] = $doctype_info['name'];
    }

    $this->load->language('action/access');
    $this->load->language('doctype/doctype');
    //$data['action_general_form'] = $this->load->view($this->config->get('config_theme') . "/template/doctype/route_action_general", $data);
    return $this->load->view('action/access/access_route_context_form', $data);
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionAccess::ACTION_INFO;
  }

  public function setParams($data)
  {
    return $data['params']['action'];
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $result = array();
    if (isset($data['document_uid'])) { //есть document_uid - запуск действия из документ
      $result = $this->executeRoute($data);
    } else {
      if (!empty($data['document_uids'])) {
        foreach ($data['document_uids'] as $document_uid) {
          $data['document_uid'] = $document_uid;
          $result = $this->executeRoute($data);
        }
      } else {
        $data['document_uid'] = 0;
        $result = $this->executeRoute($data); //запуск из журнала без выбранных документов
      }
    }
    return $result;
  }

  //у Редактирования не должно быть этого метода, это просто демонстрация
  /**
   * 
   * @param type $data  = array('document_uid', 'route_uid', 'params');
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

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('extension/action/access');
    $this->load->language('extension/action/access');

    if (!empty($data['params']['field_subject_id'])) {
      //проверяем не настроечное ли поле с указанием субъекта доступа
      $subject_value = $this->model_document_document->getFieldValue($data['params']['field_subject_id'], $data['document_uid']);
      $this->load->model('document/document');
      $subject_ids = array();
      if ($subject_value) { //есть кому делегировать права, работаем дальше
        $subject_ids = explode(",", $subject_value);
        $object_ids = array();
        if ($data['params']['field_author_id']) {
          //получаем всех авторов
          $author_value = $this->model_document_document->getFieldValue($data['params']['field_author_id'], $data['document_uid']);
          $author_ids = array();
          if ($author_value) {
            foreach (explode(",", $author_value) as $author_id) {
              $author_id = $author_id;
              if ($author_id) {
                //проверяем каждого автора на наличии "детей" в структуре; если "дети" есть, значит это подразделение и нужно собрать всех "ребят"
                $author_ids = array_merge($author_ids, $this->model_document_document->getDescendantsDocuments($author_id, $this->config->get('structure_field_parent_id')));
                $author_ids[] = $author_id;
              }
            }
          }
        }

        switch ($data['params']['object_type']) {
          case 'current_document':
            if (isset($data['document_uid'])) {
              $object_ids[] = $data['document_uid'];
            } elseif (isset($data['document_uid'])) {
              $object_ids = $data['document_uids'];
            }
            $data_doc['document_uids'] = $object_ids;
            break;
          case 'document':
            $field_info = $this->model_doctype_doctype->getField($data['params']['field_object_id'], 0);
            if (!empty($field_info['setting'])) {
              $object_value = $this->model_document_document->getFieldValue($data['params']['field_object_id'], 0);
              $object_ids = explode(",", $object_value);
            } elseif (isset($data['document_uid'])) {
              $object_value = $this->model_document_document->getFieldValue($data['params']['field_object_id'], $data['document_uid']);
              $object_ids = explode(",", $object_value);
            } elseif (isset($data['document_uids'])) {
              foreach ($data['document_uids'] as $document_uid) {
                $object_value = $this->model_document_document->getFieldValue($data['params']['field_object_id'], $data['document_uid']);
                $object_ids = array_merge($object_ids, explode(",", $object_value));
              }
            }
            $data_doc['document_uids'] = $object_ids;
            break;
          case 'doctype':
            if (!empty($data['params']['field_object_id'])) {
              $object_value = $this->model_document_document->getFieldValue($data['params']['field_object_id'], $data['document_uid']);
            } else {
              $log = $this->language->get('text_action_setting_error');
              return array(
                'log' => $log
              );
            }
            $object_ids = explode(",", $object_value);
            $data_doc['doctype_uids'] = $object_ids;
            break;
          case 'doctype_list':
            if (!empty($data['params']['doctype_uid']) && $data['params']['doctype_uid'] !== '0') {
              $object_ids[] = $data['params']['doctype_uid'];
              $data_doc['doctype_uids'] = $object_ids;
            } else {
              $log = $this->language->get('text_action_setting_error');
              return array(
                'log' => $log
              );
            }
            break;
        }

        if (!empty($author_ids)) {
          $data_doc['author_ids'] = $author_ids;
        }
        $data_doc['draft_less'] = 4;
        $object_ids = $this->model_document_document->getDocumentIds($data_doc);
        if ($data['params']['access'] == "allow" && $object_ids) {
          $this->model_extension_action_access->addAccess($subject_ids, $object_ids);
        } else {

          $this->model_extension_action_access->removeAccess($subject_ids, $object_ids);
        }

        $log = $this->language->get('text_access_set');
      }
    } else {
      $log = $this->language->get('text_action_setting_error');
    }
    if (!empty($log)) {
      return array(
        'log' => $log
      );
    }
  }
}

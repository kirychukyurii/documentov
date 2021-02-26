<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */


class ControllerExtensionActionEdit extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'edit',
    'inRouteButton' => true,
    'inFolderButton' => true
  );

  public function index()
  {
    $this->load->language('extension/action/edit');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/edit', $data));
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
    $this->load->language('action/edit');
    if (!empty($params['field_document_uid'])) {
      $this->load->model('doctype/doctype');
      return sprintf($this->language->get('text_description_field'), $this->model_doctype_doctype->getFieldName($params['field_document_uid']));
    }
    return $this->language->get('text_description_current');
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/edit');
    $this->load->language('doctype/doctype');

    $data['field_document_name'] = $this->language->get('text_currentdoc');
    if (empty($data['action']['field_document_uid'])) {
      $data['action']['field_document_uid'] = 0;
    } else {
      $field_document_info = $this->model_doctype_doctype->getField($data['action']['field_document_uid']);
      $data['field_document_name'] = $this->language->get('text_by_link_in_field') . ' &quot' . $field_document_info['name'] . '&quot';
      $data['field_document_setting'] = $field_document_info['setting'];
    }
    if (empty($data['context'])) {
      //форма для кнопки
      //$data['action_general_form'] = $this->load->view($this->config->get('config_theme') . "/template/doctype/route_action_general", $data);
      return $this->load->view('action/edit/edit_route_button_form', $data);
    }
  }

  //    /**
  //     * Метод возвращает описание параметров поля
  //     */
  //    public function getDescriptionParams($params) {
  ////        $params = unserialize($params);
  //        $result = array();
  //        if(!empty($params['mask'])) {
  //            $result[] = "Маска ввода: " . $params['mask'];
  //        }
  //        if(!empty($params['default'])) {
  //            $result[] = "Значение по умолчанию: " . $params['default'];
  //        }
  //        
  //        return implode("; ", $result);
  //    }


  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    $data['params']['action']['history'] = empty($data['params']['action']['history']) ? 0 : 1;
    return $data['params']['action'];
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionEdit::ACTION_INFO;
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $this->load->language('document/document');
    $this->load->language('action/edit');
    $this->load->model('document/document');
    if (!empty($data['folder_uid'])) {
      //запуск через журнал
      if (!empty($data['document_uids'])) {
        $document_uid = $data['document_uids'][0]; //редактируем  первый по списку выбранный документ
      }
    } else {
      $document_uid = $data['document_uid'];
    }
    if (empty($document_uid)) {
      return array('error' => $this->language->get('text_error_no_document'));
    }
    $parent_document_uid = $document_uid; //если запуск идет из другого документа, то в parent_document_uid - идентификатор этого другого, а в document_uid будет редактиуемый док
    if (!empty($data['params']['field_document_uid'])) { //редактируем документ по ссылке из поля
      $document_uid = $this->model_document_document->getFieldValue($data['params']['field_document_uid'], $document_uid);
    }

    if (!empty($document_uid)) {
      if ($this->request->server['REQUEST_METHOD'] == 'POST' && (!empty($this->request->get['save']) || !empty($this->request->get['draft']))) {
        //идет сохранение документа
        if (isset($this->request->get['draft'])) {
          //это автосохранение
          $this->model_document_document->saveDraftDocument($document_uid, $this->request->post['field']);
          $result['replace'] = array(); //возвращаем пустой массив замены, чтобы обработчик кнопки остановил обработку (не выполнил перемещение, если оно есть в точке)
        } else {

          //получаем шаблон доктайпа
          $template = $this->model_document_document->getTemplate($document_uid, 'form');
          if ($document_uid) {
            $data_eft = array(
              'fields'         => $this->request->post['field'] ?? [],
              'template'      => $template['template'],
              'document_uid'  => $document_uid
            );

            $result_save_fields = $this->load->controller('document/document/editFieldsTemplate', $data_eft);
            if (!empty($data['params']['history'])) {
              $this->model_document_document->addDocumentHistory($document_uid);
            }
            if (isset($result_save_fields['success'])) {
              $result = array(
                //'redirect' => str_replace('&amp;', '&', $this->url->link('document/document','document_uid=' . $document_uid . '&nocache=' . rand(100000000, 999999999))),
                'log'      => $this->language->get('on_save')
              );
              if (!empty($data['folder_uid'])) {
                $result['reload'] = 'table';
              }
              if (!empty($result_save_fields['append'])) {
                $result['append'] = $result_save_fields['append'];
              }
            } else {
              return $result_save_fields;
            }
          } else {
            $result = array('log' => $this->language->get('error_no_document'));
          }
        }
      } elseif (isset($this->request->get['remove_draft'])) {
        //удаляется черновик
        $this->model_document_document->removeDraftDocument($document_uid);
        $result = array(
          'replace' => array(
            'document_form'         => $this->getDocumentForm($document_uid)
          )
        );
      } else {
        //готовим форму для редактирования
        if (empty($data['folder_uid'])) {
          //запуск через документ
          $result = array(
            'replace' => array(
              //сначала панель, потом форму, чтобы отрабатывали УШ над кнопками
              'document_toolbar'      => $this->getToolbar($data['button_uid'], $parent_document_uid),
              'document_form'         => $this->getDocumentForm($document_uid),

            ),
            // 'log'     => $this->language->get('on_edit')
          );
        } else {
          //запуск через журнал
          $result = array(
            'replace' => array(
              'tcolumn'         => $this->getDocumentForm($document_uid, $data['folder_uid']),
              'folder_toolbar'      => $this->getToolbar($data['button_uid'], $document_uid, $data['folder_uid'])
            ),
            //  'log'     => $this->language->get('on_edit')
          );
        }
      }
    } else {
      $result = array('error' => $this->language->get('error_document_not_found'));
    }
    if (isset($result)) {
      return $result;
    }
  }

  //у Редактирования не должно быть этого метода, это просто демонстрация
  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeRoute($data)
  {
    $log = "ALL OK";
    if (!empty($data['params']['move_route_uid'])) {
      $this->model_document_document->moveRoute($data['document_uid'], $data['params']['move_route_uid']);
      $log .= " move to " . $data['params']['move_route_uid'];
    }
    return array(
      'log'   => $log
    );
  }


  private function getDocumentForm($document_uid, $folder_uid = 0)
  {
    $this->load->model('document/document');

    $document_info = $this->model_document_document->getDocument($document_uid);

    if (empty($document_info['doctype_uid'])) {
      return $this->language->get('text_error_no_document');
    }
    $doctype_uid = $document_info['doctype_uid'];

    $template = $this->model_document_document->getTemplate($document_uid, "form");

    $data = array(
      'document_uid'      => $document_uid,
      'doctype_uid'       => $doctype_uid,
      'draft'             => true,
      'mode'              => 'form',
      'template'          => htmlspecialchars_decode($template['template']),
      'conditions'        => $template['conditions']
    );
    $data_form = array(
      'draft'         => $document_info['draft'],
      'folder_uid'    => $folder_uid
    );
    return $this->load->view('action/edit/edit_header', $data_form) . $this->load->controller('document/document/renderTemplate', $data) . $this->load->view('action/edit/edit_footer', $data_form);
  }

  private function getToolbar($button_uid, $document_uid, $folder_uid = 0)
  {
    $data = array();
    $data['button_uid'] = $button_uid;
    $data['document_uid'] = $document_uid;
    if ($folder_uid) {
      $data['folder_uid'] = $folder_uid; //folder_uid нужен для того, чтобы определить при сохранении (отмене), что работа идет через журнал
    }
    return $this->load->view('action/edit/edit_toolbar', $data);
  }
}

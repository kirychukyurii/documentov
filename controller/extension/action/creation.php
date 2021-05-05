<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

class ControllerExtensionActionCreation extends ActionController
{

  const ACTION_INFO = array(
    'name' => 'creation',
    'inFolderButton' => true,
    'inRouteButton' => true,
    'inRouteContext' => true,
  );

  public function index()
  {
    $this->load->language('extension/action/creation');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/creation', $data));
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
    if (!empty($data['params']['action']['set'])) {
      $data['params']['action']['set'] = array_values($data['params']['action']['set']);
    }
    if (isset($data['params']['action']['doc_debug'])) {
      $data['params']['action']['doc_debug'] = (int)$data['params']['action']['doc_debug'];
    }
    if (isset($data['params']['action']['execute_route'])) {
      $data['params']['action']['execute_route'] = (int)$data['params']['action']['execute_route'];
    }
    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/creation');
    if (!empty($params['doctype_uid'])) {
      $this->load->model('doctype/doctype');
      $doctype_info = $this->model_doctype_doctype->getDoctype($params['doctype_uid']);
      return sprintf($this->language->get('text_description'), $doctype_info['name'] ?? "");
    } else {
      return $this->language->get('text_description_without_doctype');
    }
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    if (empty($data['action'])) {
      $data['action'] = [
        'execute_route' => 1,
      ];
    }

    $this->load->language('action/creation');
    $this->load->language('doctype/doctype');
    $this->load->model('doctype/doctype');
    if (!empty($data['action']['doctype_uid'])) {
      $doctype_info = $this->model_doctype_doctype->getDoctype($data['action']['doctype_uid']);
      $data['action']['doctype_name'] = $doctype_info['name'];
    }
    $data['action']['sets'] = array();
    if (!empty($data['action']['set'])) {
      foreach ($data['action']['set'] as $set) {
        $sourse_field_info = $this->model_doctype_doctype->getField($set['source_field_uid']);
        $target_field_info = $this->model_doctype_doctype->getField($set['target_field_uid']);
        $data['action']['sets'][] = array(
          'source_field_uid'   => $set['source_field_uid'],
          'source_field_name' => isset($sourse_field_info['name']) ? $sourse_field_info['name'] : "",
          'target_field_uid'   => $set['target_field_uid'],
          'target_field_name' => isset($target_field_info['name']) ? $target_field_info['name'] : "",
        );
      }
    }

    if (!empty($data['action']['field_new_document_uid'])) {
      $field_new_document_info = $this->model_doctype_doctype->getField($data['action']['field_new_document_uid']);
      $data['action']['field_new_document_name'] = isset($field_new_document_info['name']) ? $field_new_document_info['name'] : "";
    }
    if (!empty($data['action']['field_new_document_author_id'])) {
      $field_new_document_author_info = $this->model_doctype_doctype->getField($data['action']['field_new_document_author_id']);
      $data['action']['field_new_document_author_name'] = $field_new_document_author_info['name'] ?? "";
      $data['action']['field_new_document_author_setting'] = $field_new_document_author_info['setting'] ?? 0;
    }
    if (!empty($data['context'])) {
      $data['in_route'] = true; //действие добавляется через маршут; в шаблоне показываем возвомжность изменения автора создаваемого документа (в кнопка нельзя, т.к. пользотваль не получит доступ к созданному документу
    }
    return $this->load->view('action/creation/creation_form', $data);
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return ControllerExtensionActionCreation::ACTION_INFO;
  }


  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $this->load->model('tool/utils');
    $this->load->language('document/document');
    $this->load->language('action/creation');
    if (!empty($data['params']['doctype_uid'])) {
      $append = array();
      $result = array();
      $document_uid = 0;

      if (!empty($data['folder_uid'])) {
        //запуск из журнала
        if (!empty($data['document_uids'])) {
          $document_uid = $data['document_uids'][0]; // используем  первый по списку выбранный документ
        }
      } else {
        $document_uid = $data['document_uid'];
      }
      if (
        $this->request->server['REQUEST_METHOD'] == "POST" &&
        (!empty($this->request->get['save']) || !empty($this->request->get['draft']))
      ) {
        //сохранение документа
        if (isset($this->request->get['draft'])) {
          //это автосохранение
          if (!empty($this->request->get['new_document_uid'])) {
            if (isset($this->request->post['field'])) {
              $this->model_document_document->saveDraftDocument($this->request->get['new_document_uid'], $this->request->post['field']);
            }
            $result['stop'] = 1; // чтобы обработчик кнопки остановил обработку (не выполнил перемещение, если оно есть в точке)
          }
        } else {
          // сохранение дока
          $new_document_uid = $this->request->get['new_document_uid'] ?? "";
          if ($new_document_uid) {
            $values = [];
            if (isset($this->request->post['field'])) {
              foreach ($this->request->post['field'] as $field_uid => $field_value) {
                if (is_array($field_value)) {
                  $field_value = implode(",", $field_value);
                }
                $values[$field_uid] = $field_value;
              }
              //   $this->model_document_document->saveDraftDocument($this->request->get['new_document_uid'], $this->request->post['field']);
            }
            $actionData = ['newDocumentUID' => $new_document_uid];
            if ($values) {
              $actionData['values'] = $values;
            }
            $dd = [
              'uid' => $data['button_uid'],
              'document_uid' => $data['document_uid'] ?? "",
              'session' => $this->getSession(),
              'actionData' => json_encode(
                $actionData,
                JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE
              ),
            ];

            $execResult = $this->daemon->exec("ExecuteButtonAction", $dd);
            if (!empty($execResult['error'])) {
              if (is_array($execResult['error'])) {
                foreach ($execResult['error'] as $err) {
                  if (strpos($err, "document was delete")) {
                    return ['error' => "Ошибка при сохранении документа. Возможно, Вы пытались создать документ на новой вкладке браузера. Вам нужно создать документ снова"];
                  }
                }
              }

              return ['error' => $execResult['error']];
            }

            if (empty($execResult['window']) && empty($execResult['redirect']) && empty($execResult['replace'])) {
              $result = array(
                'redirect' => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $new_document_uid . (!empty($data['folder_uid']) ? '&folder_uid=' . $data['folder_uid'] : ""))),
                'document_uid' => $new_document_uid,
                'log'      => sprintf($this->language->get('text_document_create'), str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $new_document_uid, true, true)))
              );
            }
            if (!empty($execResult['append'])) {
              $result['append'] = $execResult['append'];
            }
            return $result;
          }
        }
      }
      //GET-запрос
      elseif (isset($this->request->get['remove_draft'])) {
        //удаляется черновик
        if (!empty($this->request->get['new_document_uid'])) {
          $this->model_document_document->removeDraftDocument($this->request->get['new_document_uid']);
        }
        $result['stop'] = 1; //возвращаем пустой массив замены, чтобы обработчик кнопки остановил обработку (не выполнил перемещение, если оно есть в точке)

      } else {
        //подготовка формы
        if (!empty($data['params']['field_new_document_author_id'])) {
          $value_author_field = $this->model_document_document->getFieldValue($data['params']['field_new_document_author_id'], $document_uid);
          $author_ids = explode(',', $value_author_field);
          $author_id = !empty($author_ids[0]) ? $author_ids[0] : 0;
        } else {
          $author_id = $this->customer->getStructureId();
        }
        if (!$author_id) {
          return array(
            'log' => $this->language->get('error_author_not_found'),
          );
        }


        $values = [];
        if (!empty($data['params']['set'])) {
          //устанавливаем значения полей в созданном документе на основании сопоставления полей в действий
          foreach ($data['params']['set'] as $set) {
            // $value = $this->model_document_document->getFieldValue($set['source_field_uid'], $document_uid);
            $values[$set['target_field_uid']] = $this->model_document_document->getFieldValue($set['source_field_uid'], $document_uid);
            // $result_set_field = $this->model_document_document->editFieldValue($set['target_field_uid'], $new_document_uid, $value);
            // if (!empty($result_set_field['append'])) {
            //   $append = array_merge($append, $result_set_field['append']);
            // }
          }
        }
        $is_debug = 0;

        if (isset($data['params']['doc_debug'])) {
          switch ($data['params']['doc_debug']) {
            case 1:
              if ($this->customer->isAdmin()) {
                $is_debug = 1;
              }
              break;
            case 2:
              $is_debug = 1;
          }
        } elseif (!empty($data['document_uid'])) { // возможно создание из дебажного дока, тогда включаем дебаг
          $parent_document_info = $this->model_document_document->getDocument($data['document_uid']);
          if (!empty($parent_document_info['debug'])) {
            $is_debug = 1;
          }
        }

        $new_document_info = $this->model_document_document->addDocument($data['params']['doctype_uid'], $author_id, 3, true, $values, $is_debug, 1);

        if (!empty($new_document_info['action_result']['redirect'])) {
          $result['redirect'] = str_replace("&amp;", "&", $new_document_info['action_result']['redirect']);
          //поскольку редирект произошел через контекст создания, просто возвращаем его и ничего не делаем
        } else {
          //готовим форму создания / редактирования документа
          $draft_params = [];
          if ($new_document_info['draft_params']) {
            $draft_params = json_decode($new_document_info['draft_params'], true);
          }
          $data_form = array(
            'draft'         => $draft_params ? 1 : 3,
            'folder_uid'    => ""
          );
          $data_toolbar = array(
            'button_uid'        => $data['button_uid'],
            'document_uid'      => $document_uid,
            'new_document_uid'  => $new_document_info['document_uid']
          );

          if (!empty($data['folder_uid'])) {
            //запуск через журнал
            $data_form['folder_uid'] = $data['folder_uid'];
            $data_toolbar['folder_uid'] = $data['folder_uid'];
            $result = array(
              'replace' => array(
                'folder_toolbar'  => $this->load->view('action/creation/creation_toolbar', $data_toolbar),
                'tcolumn'         => $this->load->view('action/creation/creation_header', $data_form) . $this->load->controller('document/document/getForm', $new_document_info['document_uid']) . $this->load->view('action/creation/creation_footer', $data_form)
              ),
            );
          } else {
            //запуск через документ
            $result = array(
              'replace' => array(
                //сначала панель, потом форму, чтобы отрабатывали УШ над кнопками
                'document_toolbar'      => $this->load->view('action/creation/creation_toolbar', $data_toolbar),
                'document_form'         => $this->load->view('action/creation/creation_header', $data_form) . $this->load->controller('document/document/getForm', $new_document_info['document_uid']) . $this->load->view('action/creation/creation_footer', $data_form),
              ),
            );
          }
          if (!empty($new_document_info['action_result']['append'])) {
            $result['append'] = $new_document_info['action_result']['append'];
          }
        }
      }
      if ($append) {
        $result['append'] = $append;
      }


      return $result;
    } else {
      return array(
        'error' => $this->language->get('error_doctype_not_found'),
        'log'   => $this->language->get('error_doctype_not_found')
      );
    }
  }

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

    $this->load->language('action/creation');
    $this->load->model('tool/utils');
    $this->load->model('document/document');
    if (!empty($data['params']['doctype_uid'])) {
      $document_uid = $data['document_uid'];
      if (!empty($data['params']['field_new_document_author_id'])) {
        $value_author_field = $this->model_document_document->getFieldValue($data['params']['field_new_document_author_id'], $document_uid);
        if ($value_author_field) {
          //если в поле несколько авторов, то создаем соответствующее количество документов  
          foreach (explode(',', $value_author_field) as $author_uid) {
            if ($this->model_tool_utils->validateUID($author_uid)) {
            }
            $author_ids[] = $author_uid;
          }
          if (!$author_ids) {
            return array(
              'log'        => sprintf($this->language->get('error_no_author'))
            );
          }
        } else {
          //если поле с автором пустое, то ничего не создаем
          return array(
            'log'        => sprintf($this->language->get('error_no_author'))
          );
        }
      } else {
        $author_ids = array($this->customer->getStructureId());
      }
      $new_document_uids = array();
      $new_document_links = array();
      $values = array();
      if (!empty($data['params']['set']) && $document_uid) {
        foreach ($data['params']['set'] as $set) { //получаем значения одним циклом
          $values[$set['source_field_uid']] = $this->model_document_document->getFieldValue($set['source_field_uid'], $document_uid);
        }
      }
      foreach ($author_ids as $author_id) {
        if ($author_id && !$this->model_tool_utils->validateUID($author_id)) {
          //идентификатор не равен нулю (текущий пользователь) и является некорректным юидом
          continue;
        }
        if (!$author_id) {
          return array(
            'log' => $this->language->get('error_author_not_found'),
          );
        }
        $new_doc = $this->model_document_document->addDocument($data['params']['doctype_uid'], $author_id, 3, false);
        // инициализируем поля
        if (!empty($values)) {
          foreach ($data['params']['set'] as $set) {
            $this->model_document_document->editFieldValue($set['target_field_uid'], $new_doc, $values[$set['source_field_uid']]);
          }
        }
        $data_route = array(
          'document_uid' => $new_doc,
          'context'      => 'create'
        );
        //запускаем "создание" с созданном доке; то, что возвращает маршрут не анализируем, то есть не выполняем
        //редиректы, апенды и пр, поскольку работаем в рамках родительского документа
        $this->load->controller('document/document/route_cli', $data_route);
        $this->model_document_document->editDocument($new_doc);
        $document_info = $this->model_document_document->getDocument($new_doc, false);
        if ($document_info['field_log_uid']) {
          $this->model_document_document->appendLogFieldValue(
            $document_info['field_log_uid'],
            $new_doc,
            array(
              'date' => $this->model_tool_utils->getDateTime(),
              'name' => $this->customer->getName(),
              'button' => '',
              'action_log' => $this->language->get('text_create_document')
            )
          );
        }
        $data_route['context'] = 'jump';
        //запускаем "создание" с созданном доке; то, что возвращает маршрут не анализируем, то есть не выполняем
        //редиректы, апенды и пр, поскольку работаем в рамках родительского документа                
        $this->load->controller('document/document/route_cli', $data_route);
        $new_document_uids[] = $new_doc;
        $new_document_links[] = $this->url->link('document/document', 'document_uid=' . $new_document_uids[count($new_document_uids) - 1], true, true);
      }

      if (!empty($data['params']['field_new_document_uid'])) {
        $this->model_document_document->editFieldValue($data['params']['field_new_document_uid'], $document_uid, implode(",", $new_document_uids));
      }
      return array(
        'log'        => sprintf($this->language->get('text_document_create'), implode(", ", $new_document_links)),

      );
    } else {
      return array(
        'log'   => $this->language->get('error_doctype_not_found')
      );
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

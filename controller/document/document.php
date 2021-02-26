<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerDocumentDocument extends Controller
{

  private $step = 0;

  public function index()
  {
    $this->load->language('document/document');

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (!empty($this->request->get['button_uid'])) {
      $this->session->data['current_button_uid'] = $this->request->get['button_uid'];
    }
    if (!empty($this->request->get['document_uid'])) {
      if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $result = $this->model_document_document->saveDocument($this->request->get['document_uid'], $this->request->post['field'] ?? []);

        if (!empty($result['error'])) {
          //создавать могут только админы, а текущий пользователь не админ
          $this->document->setTitle($doctype_info['name'] ?? "");
          $footer = $this->load->controller('common/footer');
          $header = $this->load->controller('common/header');
          $this->response->setOutput($header . $this->load->view('error/access_denied', $result) . $footer);
        }

        $append = array();
        if (!empty($result['append'])) {
          $append = $result['append'];
        }
        if (empty($result['error'])) {
          // $this->model_document_document->editDocument($this->request->get['document_uid']);
          // $this->addButtonDocumentLog($document_info, "", $this->language->get('text_create_document'));
          // $route_result = $this->route($this->request->get['document_uid'], "jump"); //маршрут может переместить документ на какой-то другой адрес
          if (!empty($result['redirect']) || !empty($result['reload']) || !empty($result['window'])) {
            $json = $result;
          } else {
            $json = array(
              'redirect' => str_replace('&amp;', '&', $this->url->link("document/document", "document_uid=" . $this->request->get['document_uid'])),
              'document_uid' => $this->request->get['document_uid']
            );
            // if (!empty($result['append'])) {
            //   //действие вернуло добавляемый к странице контент
            //   $append = array_merge($append, $result['append']);
            // }
          }
        } else {
          $json = $result;
        }
        if ($append) {
          $json['append'] = $append;
        }
        $this->response->addHeader("Content-type: application/json");
        $this->response->setOutput(json_encode($json));
      } elseif (isset($this->request->get['history_id'])) { //запрос истории
        $this->get_document_history();
      } elseif (!empty($this->request->get['folder_uid'])) { ////просмотр документа, определяем откуда создается документ - из журнала или из отдельного открытого документа
        //из журнала
        $this->get_document();
      } else {
        $this->load->model('account/customer');
        $this->model_account_customer->setLastPage($this->url->link('document/document', 'document_uid=' . $this->request->get['document_uid'], true, true));
        $this->load->model('doctype/doctype');
        $document_info = $this->model_document_document->getDocument($this->request->get['document_uid'], true, true);

        if ($document_info && isset($document_info['doctype_uid'])) {
          $route_result = $document_info['action_result'];
          if (!empty($route_result['redirect'])) {
            $this->response->redirect($route_result['redirect']);
            return;
          }

          $doctype_info = $this->model_doctype_doctype->getDoctype($document_info['doctype_uid']);
          $this->document->setTitle($json['title'] = html_entity_decode($this->model_document_document->getDocumentTitle($this->request->get['document_uid'])));
          $header = $this->load->controller('common/header');
          $header_doc = $this->load->view('document/view_header', array());
          $footer = $this->load->controller('common/footer');
          $footer_doc = $this->load->view('document/view_footer', array());
          $button_toolbar = $this->getButtons($this->request->get['document_uid']);

          $this->response->setOutput($header . $button_toolbar . $header_doc .
            $this->getView($this->request->get['document_uid']) .
            (!empty($route_result['append']) ?
              (is_array($route_result['append']) ? implode("\r\n ", $route_result['append']) : $route_result['append']) :
              "") .
            $footer_doc . $footer);
        } else {
          if (!empty($this->request->get['folder_uid'])) {
            $url = $this->url->link('error/access_denied', 'folder_uid=' . $this->request->get['folder_uid']);
          } else {
            $url = $this->url->link('error/access_denied');
          }
          $this->response->redirect($url);
        }
        // }
      }
    } elseif (!empty($this->request->get['doctype_uid'])) {
      //создается новый документ

      $doctype_info = $this->model_doctype_doctype->getDoctype($this->request->get['doctype_uid']);
      // if (empty($doctype_info['delegate_create']) && !$this->customer->isAdmin()) {
      //   //создавать могут только админы, а текущий пользователь не админ
      //   $this->document->setTitle($doctype_info['name'] ?? "");
      //   $footer = $this->load->controller('common/footer');
      //   $header = $this->load->controller('common/header');
      //   $this->response->setOutput($header . $this->load->view('error/access_denied', array()) . $footer);
      //   return;
      // }
      $this->load->model('account/customer');
      $this->model_account_customer->setLastPage($this->url->link('document/document', 'doctype_uid=' . $this->request->get['doctype_uid'], true, true));

      // $data = [
      //   'doctype_uid' => $this->request->get['doctype_uid'],
      //   'author_uid'  => $this->customer->getStructureId(),
      //   'session' => $this->getSession(),
      //   'debug' => empty($this->request->get['debug']) ? 0 : 1,
      // ];

      $document_info = $this->model_document_document->addDocument($this->request->get['doctype_uid']);

      // $document_info = $this->daemon->exec("AddDocument", $data);


      if ($document_info === null) {
        $this->response->redirect($this->url->link('error/daemon_not_started', true));
      }


      if (!empty($document_info['action_result']['error'])) {
        $this->document->setTitle($doctype_info['name'] ?? "");
        $footer = $this->load->controller('common/footer');
        $header = $this->load->controller('common/header');

        if (is_array($document_info['action_result']['error'])) {
          $document_info['action_result']['error'] = implode(". ", $document_info['action_result']['error']);
        }
        $this->response->setOutput($header . $this->load->view('error/access_denied', $document_info['action_result']) . $footer);
        return;
      }


      // $document_uid = $this->model_document_document->addDocument($this->request->get['doctype_uid']);
      // $this->document->setTitle($doctype_info['name'] ?? "");
      $this->document->setTitle($document_info['titles'][$this->config->get('config_language_id')] ?? "");
      $append = $document_info['action_result']['append'] ?? [];

      // $append = array();
      // $route_result = $this->route($document_uid, 'create');

      // if (isset($route_result['append'])) {
      //   $append = $route_result['append'];
      // }
      if (empty($this->request->get['folder_uid'])) {
        //создание документа в отдельном окне
        // if (!empty($route_result['redirect'])) {
        if (!empty($document_info['action_result']['redirect'])) {
          $this->response->addHeader("Content-type: application/json");
          $this->response->redirect($document_info['action_result']['redirect']);
        }
        // $this->document->setTitle($doctype_info['name'] ?? "");
        $footer = $this->load->controller('common/footer');
        $header = $this->load->controller('common/header');
        $header_doc = $this->load->view('document/form_header', array());
        $footer_doc = $this->load->view('document/form_footer', array('document_uid' => $document_info['document_uid'] ?? ""));
        $data_button = array(
          'document_uid' => $document_info['document_uid'] ?? "",
        );
        $toolbar = $this->load->view('document/form_button', $data_button);
        $form = $this->getForm($document_info['document_uid'] ?? "");
        $this->response->setOutput($header . $toolbar . $header_doc . $form . implode('\r\n', $append) . $footer_doc . $footer);
      } else {
        //документ создается из журнала
        if (!empty($document_info['action_result']['redirect'])) {
          $this->response->addHeader("Content-type: application/json");
          $this->response->setOutput(json_encode($document_info['action_result']));
        } else {
          $header = $this->load->view('document/form_header', array());
          $footer = $this->load->view('document/form_footer', array('document_uid' => $document_info['document_uid']));
          $this->response->addHeader('Content-type: application/json');
          $json = array();
          $data_button = array(
            'document_uid' => $document_info['document_uid'],
            'folder_uid' => $this->request->get['folder_uid'] //запуск идет из журнала, пробрасываем folder_uid, чтобы при сохранении знать об этом
          );
          $json['toolbar'] = $this->load->view('document/form_button', $data_button);
          $json['form'] = $header . $this->getForm($document_info['document_uid']) . $footer;
          $this->response->setOutput(json_encode($json));
        }
      }
    }
  }

  /**
   * Обработка значений полей шаблона
   * @param type $data - содержит fields - именованный массив field_uid=>field_value, template, document_uid
   * @return type
   */
  public function editFieldsTemplate($data)
  {
    $this->load->language('document/document');
    $this->load->model('doctype/doctype');
    $error_validation = array();
    $field_values = array();
    foreach ($data['fields'] as $field_uid => $field_value) {
      //проверяем наличие поля в шаблоне редактирования; это нужно для исключения возможности ручной передачи значения поля злоумышленником, которое отсутствует в шаблоне
      if (strpos($data['template'], 'f_' . str_replace("-", "", $field_uid)) !== false) {
        $field_info = $this->model_doctype_doctype->getField($field_uid);
        $value_access = "";
        $access = true;
        if (!empty(($field_info['params']['access_form']))) {
          $access = false;
          //есть ограничение на доступ к просмотру
          foreach ($field_info['params']['access_form'] as $access_field_uid) {
            $value_access .= "," . $this->model_document_document->getFieldValue($access_field_uid, $data['document_uid']);
          }
        }
        if (!$access && $value_access) {
          foreach ($this->customer->getStructureIds() as $customer_uid) {
            if (strpos($value_access, $customer_uid) !== false) {
              $access = true;
              break;
            }
          }
        }
        if ($access) {
          //проверим уникальность
          if ($field_info['unique'] && $field_value) { //проверяем на уникальность только НЕ пустые значения
            //значение должно быть уникально
            $uniques = $this->model_document_document->getFieldByValue($field_uid, $field_value);
            if ($uniques) {
              if (count($uniques) > 1 || $uniques[0]['document_uid'] != $data['document_uid']) {
                //уникальности нет
                $error_validation['unique'][] = $field_info['name'];
                continue;
              }
            }
          }
          $field_values[$field_uid] = $field_value;
        }
      }

      $empty_array = false;
      if (is_array($field_value)) {
        $empty_array = true;
        foreach ($field_value as $value) {
          if ($value && $value != "null") {
            $empty_array = false;
            break;
          }
        }
      }

      if ($field_value === "" || $field_value === "null" || $empty_array) {
        //значения нет, проверим - не обязательно ли это поле для заполнения?
        $field_info = $this->model_doctype_doctype->getField($field_uid);
        if ($field_info['required']) {
          //поле, обязательное для заполнения, не заполнено
          $error_validation['required'][] = $field_info['name'];
        }
      }
    }
    $append = array(); //при изменении поля может сработать кон. Изменение, который вернет добавляемый блок, который нужно вернуть
    if (!$error_validation) {
      foreach ($field_values as $field_uid => $field_value) {
        $result = $this->model_document_document->editFieldValue($field_uid, $data['document_uid'], $field_value);
        if (!empty($result['append'])) {
          $append = array_merge($append, $result['append']);
        }
      }
      //удаляем из документа признак черновика
      $this->model_document_document->removeDraftDocument($data['document_uid'], false);
      if ($append) {
        return array(
          'success' => true,
          'append' => $append
        );
      } else {
        return array('success' => true);
      }
    } else {
      //валидация не пройдена
      $result = array();
      if (!empty($error_validation['required'])) {
        $result[] = $this->language->get('error_validation_required') . implode(", ", $error_validation['required']);
      }
      if (!empty($error_validation['unique'])) {
        $result[] = $this->language->get('error_validation_unique') . implode(", ", $error_validation['unique']);
      }
      return array(
        'error' => implode("<br>", $result)
      );
    }
  }

  public function remove()
  {
    $this->load->model('document/document');
    $document_info = $this->model_document_document->getDocument($this->request->get['document_uid']);
    if ($document_info && $document_info['draft'] && $document_info['author_uid'] == $this->customer->getStructureId()) {
      //автор удаляет свой черновик
      $this->model_document_document->removeDocument($this->request->get['document_uid']);
      //удаление уведомлений
      $structure_uid = $this->customer->getStructureId();
      if ($structure_uid) {
        $this->model_document_document->removeNotifications($this->request->get['document_uid'], $structure_uid);
      }
    }
    $this->response->addHeader('Content-type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  public function getView($document_uid)
  {

    $document_info = $this->model_document_document->getDocument($document_uid, true);
    if (empty($document_info['doctype_uid'])) {
      return $this->load->view('error/access_denied', array());
    }
    $template = $this->model_document_document->getTemplate($document_uid, "view");
    $data = array(
      'document_uid' => $document_uid,
      'doctype_uid' => $document_info['doctype_uid'],
      'template' => htmlspecialchars_decode($template['template']),
      'conditions' => $template['conditions'],
      'mode' => 'view'
    );
    return $this->renderTemplate($data);
  }

  public function getForm($document_uid)
  {
    $this->load->model('document/document');
    $document_info = $this->model_document_document->getDocument($document_uid);
    if (!$document_info) {
      //документ не найден или нет доступа
      return $this->load->view('error/access_denied', array());
    }
    $template = $this->model_document_document->getTemplate($document_uid, "form");
    $data = array(
      'document_uid' => $document_uid,
      'doctype_uid' => $document_info['doctype_uid'],
      'mode'        => 'form',
      'draft'       => true,
      'template'    => htmlspecialchars_decode($template['template']),
      'conditions'  => $template['conditions']
    );
    //проверяме наличие черновика и, если он есть, используем его данные
    if (!empty($document_info['draft_params'])) {
      $draft_params = json_decode($document_info['draft_params'], true);
      if ($draft_params) {
        foreach ($draft_params as $field_uid => $field_value) {
          if (is_array($field_value)) {
            $data['values'][$field_uid] = $field_value;
          } else {
            $data['values'][$field_uid] = html_entity_decode($field_value); //н-р, Текст в режиме редактора не исполняет теги без декодирования
          }
        }
      }
    }
    return $this->renderTemplate($data);
  }

  public function button()
  {
    $this->load->model('document/document');
    $document_uid = $this->request->get['document_uid'];
    if ($this->validateButton()) {
      $this->session->data['current_button_uid'] = $this->request->get['button_uid']; //идентификатор нажатой кнопки

      $button_info = $this->model_document_document->getButton($this->request->get['button_uid']);

      $document_info = $this->model_document_document->getDocument($document_uid);
      if (!$document_info) {
        return "";
      }
      $route_uid = $document_info['route_uid'];
      $append = array();

      if ($button_info['action']) {
        $data = $this->request->post;
        $data['document_uid'] = $document_uid;
        $data['button_uid'] = $this->request->get['button_uid'];
        $data['params'] = $button_info['action_params'];

        $this->load->model('tool/utils');
        $this->model_tool_utils->addLog($document_uid, 'docbutton_action', $button_info['action'], $data['button_uid'], array_merge($data['params'], array('button_name' => $this->db->escape($button_info['name']))));
        $result = $this->load->controller("extension/action/" . $button_info['action'] . "/executeButton", $data);

        if (!empty($result['append'])) {

          if (is_array($result['append'])) {
            foreach ($result['append'] as $a) {
              $append[] = $a;
            }
          } else {
            $append[] = $result['append'];
          }
        }

        if (empty($result['window']) && empty($result['replace']) && empty($result['error']) && empty($this->request->get['cancel']) && empty($this->request->get['remove_draft']) && empty($this->request->get['draft'])) {
          //действие отработало    
          //ДЕЙСТВИЕ В КНОПКЕ НЕ МОЖЕТ ПЕРЕМЕЩАТЬ ДОКУМЕНТ, но если вдруг стороннее действие это сделало, все же проверим перемещение
          // $this->model_document_document->removeDocumentUIDFromCache($document_uid);
          // echo "Exec1";
          // exit;
          $document_info = $this->model_document_document->getDocument($document_uid, true, false, true);
          // print_r($document_info);
          // exit;
          if ($document_info) {
            // логируем нажатие кнопки в ход работы
            if ($button_info['action'] != "creation") {
              $this->addButtonDocumentLog($document_info, $button_info['name'], $this->request->get['button_uid'], $result['log'] ?? "");
            }
            //проверяем наличие документа; если он был удален - $document_info пуста
            if ($route_uid !== $document_info['route_uid']) {
              //документ перемещен
              $route_result = $this->route($document_uid, 'jump');
            } else {
              //действие не перемещало документ
              //запускаем контекст активности
              $route_result = $this->route($document_uid, 'view');
              if ($button_info['action_move_route_uid'] && empty($result['stop'])) {
                //если действие вернуло replace, оно что-то меняет на текущей странице, перемещать док нельзя
                if ($route_uid == $document_info['route_uid']) {
                  $route_result = $this->model_document_document->moveRoute($document_uid, $button_info['action_move_route_uid']);
                }
              }
            }
            if (!empty($route_result['append'])) {
              if (is_array($route_result['append'])) {
                foreach ($route_result['append'] as $a) {
                  $append[] = $a;
                }
              } else {
                $append[] = $route_result['append'];
              }
            }
            // если действие вернуло редирект, мы вне зависимости от того, что вернул обарботчик перемещения через кнопку
            // должны выполнить этот первый редирект
            if (!empty($result['redirect'])) {
              $route_result['redirect'] = $result['redirect'];
            }
          } else {
            // док удален
            $result['reload'] = $this->url->link('info/success', '');
          }
        }
      } else {
        //в кнопке нет действия
        // пишем нажатие кнопки в ходы работы
        $this->addButtonDocumentLog($document_info,  $button_info['name'], $this->request->get['button_uid']);
        if ($button_info['action_move_route_uid']) {
          $document_info = $this->model_document_document->getDocument($document_uid);
          if ($route_uid == $document_info['route_uid']) {
            $route_result = $this->model_document_document->moveRoute($document_uid, $button_info['action_move_route_uid']);
            // print_r($route_result);
            // exit;
          }
          if (!empty($route_result['append'])) {
            if (is_array($route_result['append'])) {
              foreach ($route_result['append'] as $a) {
                $append[] = $a;
              }
            } else {
              $append[] = $route_result['append'];
            }
          }
        }
        //Записываем название кнопки в ход работы
        if (empty($route_result['redirect']) && empty($route_result['replace']) && empty($route_result['window']) && empty($route_result['reload'])) {
          $document_info = $this->model_document_document->getDocument($document_uid);
          if ($document_info) {
            $route_result = $this->route($document_uid, 'view');
            if (!empty($route_result['append'])) {
              if (is_array($route_result['append'])) {
                foreach ($route_result['append'] as $a) {
                  $append[] = $a;
                }
              } else {
                $append[] = $route_result['append'];
              }
            }
          }
        }
      }

      if (!empty($this->request->get['button_uid'])) {
        if (!empty($route_result['redirect'])) {
          $route_result['redirect'] .= "&button_uid=" . $this->request->get['button_uid'];
        }
        if (!empty($result['redirect'])) {
          $result['redirect'] .= "&button_uid=" . $this->request->get['button_uid'];
        }
      }

      // unset($this->session->data['current_button_uid']); //сброс последней нажатой кнопки // 201127 включил для 5a02ddf1-f454-11e8-8868-fdeeb1ac4867
      if (!empty($route_result['redirect']) || !empty($route_result['reload']) || !empty($route_result['replace']) || !empty($route_result['window'])) {
        $this->response->addHeader("Content-type: application/json");
        $this->response->setOutput(json_encode($route_result));
      } elseif (!empty($result['redirect'])) {
        $route_result = $this->route($document_uid, 'view');
        if (!empty($result['redirect'])) {
          $this->response->addHeader("Content-type: application/json");
          $this->response->setOutput(json_encode($result));
        } elseif (!empty($route_result['redirect'])) {
          $this->response->addHeader("Content-type: application/json");
          $this->response->setOutput(json_encode($route_result));
        } elseif (!empty($result)) {
          $this->response->addHeader('Content-type: application/json');
          $this->response->setOutput(json_encode($result));
        } else {
          $this->response->addHeader('Content-type: application/json');
          $url =  str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $document_uid . '&_=' . rand(100000000, 999999999)));
          if (!empty($this->request->get['button_uid'])) {
            $url .= "&button_uid=" . $this->request->get['button_uid'];
          }
          $result = array(
            'reload' => $url
          );
          $this->response->setOutput(json_encode($result));
        }
      } elseif (!empty($result['replace']) || !empty($result['window']) || !empty($result['reload'])) {
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($result));
      } elseif (!empty($result['error'])) {
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($result));
      } else { //if(isset($result) || isset($route_result))
        $result = array();
        if ($button_info['show_after_execute']) {
          $result['reload'] = str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $document_uid . '&nocache=' . rand(100000000, 999999999)));
        } else {
          $result['redirect'] = str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $document_uid . '&nocache=' . rand(100000000, 999999999)));
        }
        if ($append) {
          $result['append'] = $append;
        }
        if (!empty($this->request->get['button_uid'])) {
          if (!empty($result['reload'])) {
            $result['reload'] .= "&button_uid=" . $this->request->get['button_uid'];
          }
          if (!empty($result['redirect'])) {
            $result['redirect'] .= "&button_uid=" . $this->request->get['button_uid'];
          }
        }
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($result));
      }
    } else {
      $this->load->language('doctype/doctype');
      $json = array(
        'redirect' => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $document_uid . '&nocache=' . rand(100000000, 999999999)))
      );
      if (!empty($this->request->get['button_uid'])) {
        $json['redirect'] .= "&button_uid=" . $this->request->get['button_uid'];
      }
      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function route_cli($data)
  {
    return $this->route($data['document_uid'], $data['context']);
  }

  // Добавление записи в ходы работы документа
  private function addButtonDocumentLog($document_info, $button_name, $button_uid, $action_log = "")
  {
    $data = [
      'document_uid' => $document_info['document_uid'],
      'user_uid' => $this->customer->getStructureId(),
      'action' => $button_name,
      'log' => $action_log,
      'parent_uid' => $button_uid,
    ];
    $this->daemon->exec("AddDocumentLog", $data);

    // // AddDocumentLog
    // if ($document_info['field_log_uid']) {
    //   $data_log = array(
    //     'date' => $this->getCurrentDateTime("d.m.Y H:i:s"),
    //     'name' => $this->customer->getName(),
    //     'button' => $button_name,
    //     'action_log' =>  $action_log
    //   );
    //   $this->model_document_document->appendLogFieldValue(
    //     $document_info['field_log_uid'],
    //     $document_info['document_uid'],
    //     $data_log
    //   );
    // }
  }

  public function route($document_uid, $context)
  {
    if ($context === "view") {
      return; // запуск активности в демоне
    }
    $data = [
      'document_uid' => $document_uid,
      'context' => $context,
      'session' => $this->getSession()
    ];
    $result = $this->daemon->exec("ExecuteDocument", $data);

    return $result;

    if (!$result) {
      return;
    }

    $recursion_depth = $this->config->get('recursion_depth');
    if (++$this->step > $recursion_depth) {
      return array('redirect' => $this->url->link('error/cycle'));
    }
    $this->load->model('document/document');
    // if ($context === 'view') {
    //   //удаляем уведомления к документу определенного пользователя, когда пользователь открывает этот документ
    //   $structure_uid = $this->customer->getStructureId();
    //   if ($structure_uid) {
    //     $this->model_document_document->removeNotifications($document_uid, $structure_uid);
    //   }
    // }

    $document_info = $this->model_document_document->getDocument($document_uid, false);

    if ($document_info) {
      $route_uid = $document_info['route_uid'];
      if ($context == "setting") {
        $first_route_uid = $this->model_document_document->getFirstRoute($document_info['doctype_uid']);
        $actions = $this->model_document_document->getRouteActions($first_route_uid);
      } else {
        if (!$route_uid || !$this->model_document_document->getRoute($route_uid)) {
          $route_uid = $this->model_document_document->getFirstRoute($document_info['doctype_uid']);
        }
        $actions = $this->model_document_document->getRouteActions($route_uid);
      }

      $this->load->model('tool/utils');
      $append = array();
      if (!empty($actions[$context])) {
        foreach ($actions[$context] as $action) {
          $action['document_uid'] = $document_uid;
          $data = array(
            'document_uid'  => $document_uid,
            'route_uid'     => $route_uid,
            'context'       => $context,
            'params'        => $action['params'],
          );

          $this->model_tool_utils->addLog($document_uid, 'action', $action['action'], "", array_merge($data['params'], array('context' => $context)));
          try {
            $result = $this->load->controller("extension/action/" . $action['action'] . "/executeRoute", $data);
          } catch (\Throwable $th) {
            $mess = sprintf(
              "Unable to execute action %s. DATA: %s Error %s in %s:%s ",
              $action['action'],
              print_r($data, true),
              $th->getMessage(),
              $th->getFile(),
              $th->getLine()
            );
            $this->log->write($mess);
          }

          $document_info = $this->model_document_document->getDocument($document_uid, false);
          if ($document_info) {
            //если $document_info есть, значит документ не был удален
            if (isset($result['log']) && $action['action_log'] && $document_info['field_log_uid']) {
              $data_log = array(
                'date' => $this->getCurrentDateTime("d.m.Y H:i:s"),
                'name' => $this->config->get('config_name'),
                'button' => $this->load->controller("extension/action/" . $action['action'] . "/getTitle"),
                'action_log' => $result['log']
              );
              $this->model_document_document->appendLogFieldValue($document_info['field_log_uid'], $data['document_uid'], $data_log);
            }
            if (!empty($result['append'])) {
              if (is_array($result['append'])) {
                $append = array_merge($append, $result['append']);
              } else {
                $append[] = $result['append'];
              }
            }

            if (!empty($result['redirect']) || !empty($result['replace']) || !empty($result['stop'])) {
              //происходит редирект, это может быть действие Перенаправление или остановка работы маршрута - stop из Условия,  н-р
              if ($append) {
                $result['append'] = $append;
              }
              return $result;
            }

            if ($route_uid != $document_info['route_uid']) {
              //документ был перемещен
              break;
            }
          } else {
            //текущий документ был удален
            if ($append) {
              $result['append'] = $append;
            }
            return $result;
          }
        }
        $route_result = array();
        if ($route_uid != $document_info['route_uid']) {
          //изменена точка, запускаем jump
          if ($context != "change" || (isset($this->request->get['document_uid']) && $context == "change" && $document_uid != $this->request->get['document_uid']) || ($this->step == 1 && empty($this->request->get['button_uid']))) {
            // контекст изменения вызывается в результате выполнения действия, запускаемого через маршрут 
            // или от кнопки, поэтому перемещения будут обрабованы на более высоких уровнях в контекстах (route) 
            // или кнопках (view)
            // возможен только вариант - если изменение произошло при создании документа, в этом случае $this->step == 1 и
            // запуск не должен быть от кнопки    
            // ($context == "change" && $document_uid != $this->request->get['document_uid']) - поле было изменено в другом документе, в 
            // этом случае новая точка не будет обработана, поэтому приходится запускать ее таким образом

            $route_result = $this->route($document_uid, 'jump');
            if (!empty($route_result['append'])) {
              $append = array_merge($append, $route_result['append']);
            }
          }
        }
        if ($append) {
          $route_result['append'] = $append;
        }
        $this->step--;
        return $route_result;
      }
    } else {
      if ($document_uid) {
        if (!empty($this->request->get['folder_uid'])) {
          $url = $this->url->link('error/access_denied', 'folder_uid=' . $this->request->get['folder_uid']);
        } else {
          $url = $this->url->link('error/access_denied');
        }
      } else {
        //возможно, не передали document_uid
        if (!empty($this->request->get['folder_uid'])) {
          $url = $this->url->link('error/not_found', 'folder_uid=' . $this->request->get['folder_uid']);
        } else {
          $url = $this->url->link('error/not_found');
        }
      }
      //документ не найден или к нему нет доступа

      return array('redirect' => $url);
    }
  }

  /**
   * 
   * @param type $data: 
   *      'doctype_uid' => обязательный параметр, 
   *      'document_uid' => передается, если нужно установить значения в полях шаблона, 
   *      'draft'         => TRUE или FALSE - черновики брать или нет
   *      'template'  => обязательный параметр
   *      'mode' => view | view_clear (без добавления блоков в виджетах полей) | edit, 
   *      'values' => значения, которые нужно установить в поля шаблона, document_uid в этом случае будет проигнорирован
   *      'params' => доп. параметры, которые будут переданы в TWIG,
   * @return type
   */
  public function renderTemplate($data)
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $data['get'] = $this->request->get; //передаем в TWIG get-параметры открытия документа
    $data['render_id'] = uniqid();
    $data['condition_fields'] = array(); //массив с полями, размещенными в шаблоне, и их значениями; дополняется и используется в условиях шаблона
    if (!empty($data['conditions'])) {
      // $data['conditions'] = json_decode($data['conditions'], true);
      // $data['conditions'] = $data['conditions'];
      if ($data['conditions']) {
        //формируем массив полей, которые будут необходимы для условий шаблона
        $keys = ['action', 'condition'];
        foreach ($data['conditions'] as &$condition) {
          if (!empty($condition['action'])) {
            foreach ($keys as $key) {
              foreach ($condition[$key] as &$category) {
                foreach ($category as &$param_value) {
                  preg_match_all("/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|f_[0-9a-f]{32})/", $param_value, $matches, PREG_SET_ORDER);
                  if ($matches) {
                    foreach ($matches as $match_group) {
                      foreach ($match_group as $match) {
                        $match = str_replace("f_", "", $match, $count); //в некоторых действиях формат f_UID-без-тире
                        if ($count) {
                          $match = substr($match, 0, 8) . "-" . substr($match, 8, 4) . "-" . substr($match, 12, 4)  . "-" . substr($match, 16, 4) . "-" . substr($match, 20, 32);
                          $data['condition_fields'][$match] = "";
                        } else {
                          $data['condition_fields'][$match] = "";
                        }
                      }
                    }
                  }
                  $param_value = preg_replace("/{\{ *render_id *\}\}/i", $data['render_id'], $param_value);
                }
              }
            }
          }
        }
      }
    }

    foreach ($this->model_document_document->getFields($data['doctype_uid']) as $field) {
      if (!$field['license']) {
        continue;
      }
      $fid = 'f_' . str_replace("-", "", $field['field_uid']);
      if (strpos($data['template'], $fid) !== FALSE) {
        //Поле есть в шаблоне, подготавливаем данные для передачи для контроллера поля
        $d = $field['params'];
        $d['document_uid'] = $data['document_uid'] ?? "";
        $d['field_uid'] = $field['field_uid'];
        if (!empty($data['values'])) {
          if (isset($data['values'][$field['field_uid']])) {
            $d['field_value'] = $data['values'][$field['field_uid']];
          }
        }
        if (!isset($d['field_value']) && !empty($data['document_uid'])) {
          //поля не было в черновике и есть document_uid, поэтому пробуем получить значение поля из базы
          $d['field_value'] = $this->model_document_document->getFieldValue($field['field_uid'], $data['document_uid'], $data['draft'] ?? false);
        }
        $data['condition_fields'][$field['field_uid']] = ""; //массив для условий шаблона; факт. значение будет записано, если для поля есть доступ
        $access_view = true;
        $access_form = true;
        if (!empty($field['access_view'])) {
          //есть ограничение на доступ к просмотру поля
          $access_view = false;
          foreach ($field['access_view'] as $access_field_uid) {
            $value = $this->model_document_document->getFieldValue($access_field_uid, $data['document_uid']);
            foreach ($this->customer->getStructureIds() as $structure_uid) {
              if (strpos($value, $structure_uid) !== false) {
                $access_view = true;
                break;
              }
            }
            if ($access_view) {
              break;
            }
          }
        }
        //проверим ограничение на доступ к виджету формы
        if (!$access_view) {
          //если нет доступа на просмотр, то нет и на форму
          $access_form = false;
        } else if (!empty($field['access_form'])) {
          //есть ограничение на доступ к просмотру поля
          $access_form = false;
          foreach ($field['access_form'] as $access_field_uid) {
            $value = $this->model_document_document->getFieldValue($access_field_uid, $data['document_uid']);
            foreach ($this->customer->getStructureIds() as $structure_uid) {
              if (strpos($value, $structure_uid) !== false) {
                $access_form = true;
                break;
              }
            }
            if ($access_form) {
              break;
            }
          }
        }

        $data[$fid] = "";
        if ($data['mode'] == 'form' && $access_form) {
          $data[$fid] = $this->load->controller('extension/field/' . $field['type'] . '/getForm', $d);
        } elseif ($access_view) {
          if ($data['mode'] == 'view_clear') {
            $d['no_block'] = true; //убрать блоки виджетов
          }
          $data[$fid] = trim($this->load->controller('extension/field/' . $field['type'] . '/getView', $d));
          $data['condition_fields'][$field['field_uid']] = htmlentities(strip_tags(html_entity_decode($d['field_value'] ?? ""))); //для условий шаблона, только для просмотра, т.к. в форме значения есть и без этого
        }
      } elseif ($data['condition_fields'] && isset($data['condition_fields'][$field['field_uid']])) { //проверим нет ли поля в условиях шаблона
        $access_view = true;
        if (!empty($field['access_view'])) {
          $access_view = false;
          foreach ($field['access_view'] as $access_field_uid) {
            $value = $this->model_document_document->getFieldValue($access_field_uid, $data['document_uid']);
            foreach ($this->customer->getStructureIds() as $structure_uid) {
              if (strpos($value, $structure_uid) !== false) {
                $access_view = true;
                break;
              }
            }
            if ($access_view) {
              break;
            }
          }
        }

        if ($access_view) {
          //доступ на просмотр поля у пользователя есть, добавляем его значение
          if (isset($data['values'][$field['field_uid']])) { //может быть, значение поля передано в data['values']?
            $fv = $data['values'][$field['field_uid']];
            $data['condition_fields'][$field['field_uid']] = htmlentities(strip_tags(html_entity_decode($fv)));
          } else { //значения нет, получаем его из базы данных
            $val = $this->model_document_document->getFieldValue($field['field_uid'], $data['document_uid'], $data['draft'] ?? false);
            $data['condition_fields'][$field['field_uid']] = htmlentities(strip_tags(html_entity_decode($val)));
          }
        }
      }
    }

    //получаем значения переменных, если есть document_uid
    if (!empty($data['document_uid'])) {
      foreach ($this->model_doctype_doctype->getTemplateVariables() as $var_id => $var_name) {
        $data[$var_id] = $this->model_document_document->getVariable($var_id, $data['document_uid']);
      }
      foreach ($this->model_doctype_doctype->getHiddenTemplateVariables() as $var_id => $var_name) {
        $data[$var_id] = $this->model_document_document->getVariable($var_id, $data['document_uid']);
      }
    }
    if (isset($data['params'])) {
      $data = $data['params'];
    }
    include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');

    Twig_Autoloader::register();

    $loader = new \Twig_Loader_Filesystem(DIR_TEMPLATE);

    $config = array('autoescape' => false);


    $twig = new \Twig_Environment($loader, $config);
    try {
      $template = $data['template'];
      if (!empty($data['document_uid'])) {
        $template = "<div id='document_block_id-" . ($data['document_uid'] ?? "") . $data['render_id'] . "'>" . $template . "</div>";
      }
      $result = $twig->createTemplate($template)->render($data);
      //добавляем условия шаблона
      $result_conditions = "";
      if (!empty($data['conditions'])) {
        $result_conditions = $this->load->view('document/template_conditions', $data);
      }
      return $result . $result_conditions;
    } catch (Exception $ex) {
      $this->load->language('document/document');
      return $this->language->get('template_error') . ": " . $ex->getMessage();
    }
  }

  public function folder_route($data)
  {
    return $this->route($data['document_uid'], $data['context']);
  }

  public function autocomplete()
  {
    $json = array();
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');
    $this->load->language('document/document');

    if (!empty($this->request->get['field_uid'])) {
      $field_uid = $this->request->get['field_uid'];
    } else {
      //field_uid для автокомплита не указан, проверим наличие заголовка
      $document_info = $this->model_doctype_doctype->getDoctype($this->request->get['doctype_uid']);
      // $doctype_descriptions = $this->model_doctype_doctype->getDoctypeDescriptions($this->request->get['doctype_uid']);
      $doctype_descriptions = $document_info['description'];
      if (!empty($doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'])) {
        $field_uid = $doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'];
      } else {
        //будем использовать первое поле доктайпа
        $data = array(
          'doctype_uid' => $this->request->get['doctype_uid'],
          'setting' => 0,
          'limit' => 1
        );
        $fields = $this->model_doctype_doctype->getFields($data);
        $field_uid = $fields[0]['field_uid'] ?? "";
      }
    }
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if ($field_info['access_view']) {
      //если для поля есть ограниченные настройки доступа выдаем ошибку: проверять имеет ли текущий доступ к полю каждого документа слишком накладно по ресурсам
      $json[] = array(
        'document_uid' => 0,
        'name' => $this->language->get('text_error_autocomplete_access')
      );
    } else {
      if (empty($this->request->get['doctype_uid']) && ($field_uid && !empty($this->request->get['document_uid']))) {
        // доктайпа нет, но есть ИД документа - нужно вернуть дисплей для поля указанного дока
        // В качестве ИД документа могут быть переданы несколько ИДшников через запятую, поэтому работаем сразу через массив
        $document_uids = [];
        foreach (explode(",", $this->request->get['document_uid']) as $doc_uid) {
          $doc_uid = trim($doc_uid);
          if ($this->model_tool_utils->validateUID($doc_uid)) {
            $document_uids[] = $doc_uid;
          }
        }
        if (!$document_uids) {
          return $json;
        }
        foreach ($this->model_document_document->getFieldDisplay($field_uid, $document_uids) as $doc_uid => $display) {
          $json[] = [
            'document_uid' => $doc_uid,
            'name' =>  strip_tags(html_entity_decode($display, ENT_QUOTES, 'UTF-8')),

          ];
        }
      } else if ((!empty($this->request->get['doctype_uid']) || !empty($this->request->get['source_field_uid'])) && $field_uid) {
        $filter_data = array();
        if (!empty($this->request->get['filters'])) {
          $filter_data['filter_names'] = array();
          $filter_data['field_uids'][] = ["display" => $field_uid];
          foreach ($this->request->get['filters'] as $filter) {
            // в $filter['value'] передает UID поля документа, в котором находится значение для фильтра                        
            $filter_value = $this->model_document_document->getFieldValue($filter['value'], $this->request->get['document_uid'] ?? 0, true) ?? "";
            if (!isset($filter_data['filter_names'][$filter['field_uid']])) {
              $filter_data['filter_names'][$filter['field_uid']] = array(array(
                'value' => $filter_value, //$filter['value'],
                'condition' => $filter['condition'],
                'concat' => (!empty($filter['concat']) && $filter['concat'] == "OR") ? "OR" : "AND" //как конкатенировать условия, по умолчанию, AND
              ));
            } else {
              $filter_data['filter_names'][$filter['field_uid']][] = array(
                'value' => $filter_value, //$filter['value'],
                'condition' => $filter['condition'],
                'concat' => (!empty($filter['concat']) && $filter['concat'] == "OR") ? "OR" : "AND" //как конкатенировать условия, по умолчанию, AND
              );
            }
          }
        } else {
          $filter_data['field_uids'][] = ["display" => $field_uid];
        }
        if (!empty($this->request->get['filter_name'])) {
          //добавляем фильтр по введенному пользователем тексту в поле
          if (!empty($filter_data)) {
            if (!isset($filter_data['filter_names'][$field_uid])) {
              $filter_data['filter_names'][$field_uid] = array(array(
                'display' => $this->request->get['filter_name'], //display вместо value из-за, например, ссылочного поля - пользователь в автокомллит вводит не value ссылочного поля (идентификатор), а дисплей отображаемого поля. Если отображаемое поле - ссылка, это имеет значение
                'condition' => "contains"
              ));
            } else {
              $filter_data['filter_names'][$field_uid][] = array(
                'display' => $this->request->get['filter_name'],
                'condition' => "contains"
              );
            }
          } else {
            $filter_data['filter_name'] = !empty($this->request->get['filter_name']) ? $this->request->get['filter_name'] : "";
          }
        }
        if (!empty($this->request->get['doctype_uid'])) {
          $filter_data['doctype_uid'] = $this->request->get['doctype_uid'];
        }
        if (!empty($this->request->get['source_field_uid'])) {
          $source_field_value = $this->model_document_document->getFieldValue($this->request->get['source_field_uid'], $this->request->get['document_uid'] ?? 0, TRUE);
          if (is_array($source_field_value)) {
            $filter_data['document_uids'] = $source_field_value;
          } else {
            $filter_data['document_uids'] = explode(",", $source_field_value);
          }
        }
        $filter_data['start'] = 0;
        $filter_data['limit'] = 100;
        $filter_data['draft_less'] = 2;
        $filter_data['sort'] = $field_uid;
        $filter_data['order'] = "asc";

        $results = $this->model_document_document->getDocuments($filter_data);
        foreach ($results as $result) {
          $json[] = array(
            'document_uid' => $result['document_uid'],
            'name' => strip_tags(html_entity_decode($result['display_value'] ?? $result['v' . str_replace("-", "", $field_uid)], ENT_QUOTES, 'UTF-8')),
          );
        }
      } else {
        $json[] = array(
          'document_uid' => 0,
          'name' => $this->language->get('text_error_autocomplete_setting')
        );
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function get_document_history()
  {
    if (!empty($this->request->get['document_uid']) && isset($this->request->get['history_id'])) {
      $this->load->model('document/document');
      $json = array();

      $document_info = $this->model_document_document->getDocument($this->request->get['document_uid'], true);
      if (!$document_info) {
        return $this->load->view('error/access_denied', array());
      }
      $history_info = $this->model_document_document->getDocumentHistory($this->request->get['document_uid'], $this->request->get['history_id']);
      $template = $this->model_document_document->getTemplate($this->request->get['document_uid'], "view");
      $data = array(
        'document_uid' => $this->request->get['document_uid'],
        'doctype_uid' => $document_info['doctype_uid'],
        'template' => htmlspecialchars_decode($template['template']),
        'conditions' => $template['conditions'],
        'mode' => 'view',
        'values' => json_decode($history_info['version'] ?? "", TRUE)
      );
      $json['form'] = $this->renderTemplate($data);
      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  public function get_document()
  {
    if (!empty($this->request->get['document_uid'])) {
      $this->load->model('document/document');
      $document_info = $this->model_document_document->getDocument($this->request->get['document_uid'], true, true);
      $route_result = $document_info['action_result'] ?? [];
      // print_r($route_result);
      // exit;
      if (!empty($route_result['redirect']) || !empty($route_result['reload']) || !empty($route_result['replace'])) { //запускаем сначала контекст активности маршрута
        $this->response->addHeader("Content-type: application/json");
        $this->response->setOutput(json_encode($route_result));
      } else {
        $header = $this->load->view('document/view_header', array());
        $footer = $this->load->view('document/view_footer', array());
        $json = array();
        $json['toolbar'] = $this->getButtons($this->request->get['document_uid']);
        $json['form'] = $header . $this->getView($this->request->get['document_uid']) . $footer;
        $json['document_uid'] = $this->request->get['document_uid'];
        $json['title'] = html_entity_decode($this->model_document_document->getDocumentTitle($this->request->get['document_uid']));
        if (!empty($route_result['append'])) {
          //какое-то действие маршрута добавляет что-то для отображения, например, Сообщение - модальное окно
          $json['append'] = $route_result['append'];
        }
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
      }
    }
    // $this->response->addHeader('Content-Type: application/json');
    // $this->response->setOutput(json_encode([]));
  }

  /**
   * Возвращает view поля; используется в js-объекте documentov
   * _GET:
   *    document_uid - документ, из поля которого будет получено значения для виджета // может быть несколько юидов через запятую (от мн ссылки, например)
   *    field_uid - поле, из которого будет получено значения для виджета
   *    mode - view | form, по умолчанию, view
   *    widget_field_uid - поле, виджет которого будет возвращен (если пусто, то используется field_uid)
   */
  public function get_field_widget()
  {
    if (!empty($this->request->get['document_uid']) && !empty($this->request->get['field_uid'])) {
      $this->load->model('document/document');
      $document_uid = $this->request->get['document_uid'];
      $field_uid = $this->request->get['field_uid'];
      $widget_field_uid = $this->request->get['widget_field_uid'] ?? $field_uid;
      $widget_document_uid = $this->request->get['widget_document_uid'] ?? $document_uid;
      if (strpos($widget_document_uid, ",")) {
        $widget_document_uid = explode(",", $widget_document_uid)[0];
      }
      $widget_doctype_uid = $this->request->get['widget_doctype_uid'] ?? $this->model_document_document->getDocument($widget_document_uid)['doctype_uid'] ?? "";
      if (!$widget_doctype_uid) {
        exit("Document not found");
      }
      if (empty($this->request->get['mode']) || $this->request->get['mode'] !== "form") {
        $mode = "view";
      } else {
        $mode = "form";
      }

      $values = [];
      foreach (explode(",", $document_uid) as $docuid) {
        $values[] = $this->model_document_document->getFieldValue($field_uid, $docuid, false, true);
      }

      $data = array(
        'document_uid' => $widget_document_uid,
        'doctype_uid' => $widget_doctype_uid,
        'mode' => $mode,
        'template' =>  "{{ " . "f_" . str_replace("-", "", $widget_field_uid) . " }}",
        'values' => [$widget_field_uid => implode(",", $values)]
      );
      $this->response->addHeader("Content-type: text/html");
      $this->response->setOutput($this->renderTemplate($data));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode(['error' => "Params are wrong"]));
      // exit("Params are wrong");
    }
  }

  public function getButtons($document_uid)
  {
    $buttons = array();
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/image');
    $button_groups = array();
    $i = 0;
    foreach ($this->model_document_document->getButtons($document_uid) as $button) {
      if ($button['picture']) {
        if ($button['name']) {
          $picture25 = $this->model_tool_image->resize($button['picture'], 28, 28);
        } else {
          $picture25 = $this->model_tool_image->resize($button['picture'], 28, 28);
        }
      } else {
        $picture25 = "";
      }
      $tmp_button = array(
        'button_uid' => $button['route_button_uid'],
        'name' => $button['name'],
        'title' => $button['description'],
        'picture' => $picture25,
        'hide_button_name' => $button['hide_button_name'],
        'color' => $button['color'],
        'background' => $button['background']
      );
      if ($button['button_group_uid']) {
        if (!array_key_exists($button['button_group_uid'], $button_groups)) {
          $button_group = $this->model_doctype_doctype->getButtonGroup($button['button_group_uid']);
          $button_group_name = $button_group['descriptions'][$this->config->get('config_language_id')]['name'] ?? "";
          $button_group['name'] = $button_group_name;
          if ($button_group['picture']) {
            $button_group_picture25 = $this->model_tool_image->resize($button_group['picture'], 28, 28);
          } else {
            $button_group_picture25 = "";
          }
          $button_group['picture'] = $button_group_picture25;
          $button_groups[$button['button_group_uid']] = $i;
          $buttons['buttons'][] = array('button_group' => $button_group, 'buttons' => array());
          $i++;
        }
        $group_index = $button_groups[$button['button_group_uid']];
        $buttons['buttons'][$group_index]['buttons'][] = $tmp_button;
      } else {
        $buttons['buttons'][] = $tmp_button;
        $i++;
      }
    }
    $buttons['document_uid'] = $document_uid;
    //print_r($buttons);
    return $this->load->view('document/view_button', $buttons);
  }

  public function save_draft()
  {
    // $data = [
    //   'document_uid' => $this->request->get['document_uid'],
    //   'values'  => $this->request->post['field'] ?? [],
    //   'session' => $this->getSession()
    // ];
    // foreach ($data['values'] as &$v) {
    //   if (is_array($v)) {
    //     $v = implode(",", $v);
    //   }
    //   $v = html_entity_decode($v);
    // }
    // $this->daemon->exec("SaveDocumentDraft", $data);
    // return

    ////////////////////////////////////////
    $this->load->model('document/document');
    $this->model_document_document->saveDraftDocument($this->request->get['document_uid'], $this->request->post['field']);
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
    return
      //////////////////////
      $document_info = $this->model_document_document->getDocument($this->request->get['document_uid']);
    if (
      isset($document_info['route_uid']) && $document_info['route_uid'] == $this->model_document_document->getFirstRoute($document_info['doctype_uid']) && //документ находится на нулевой точке
      $document_info['author_uid'] == $this->customer->getStructureId()
    ) { //и с ним работает его автор
      $this->model_document_document->saveDraftDocument($this->request->get['document_uid'], $this->request->post['field']);
    }
  }

  public function validateButton()
  {
    if (empty($this->request->get['button_uid']) || empty($this->request->get['document_uid'])) {
      return false;
    }
    return $this->model_document_document->hasAccessButton($this->request->get['button_uid'], $this->request->get['document_uid']);
  }

  private function getCurrentDateTime($format)
  {
    $date = new DateTime("now");
    return $date->format($format);
  }

  public function getUsageDisk()
  {
    $this->load->model("tool/utils");
    echo $this->model_tool_utils->getUseFieldDisk();
  }

  /**
   * Метод загрузки файлов
   * @param type $data
   *      [file_extes] - массив с разрешенными расширениями
   *      [file_mimes] - массив с разрешенными MIME
   *      [size_file]  - макс размер файла
   *      [dir_file_upload] - куда загружаем файлы
   */
  public function uploadFile($data)
  {
    $result = array();
    $this->load->model('tool/utils');
    $this->load->language('document/document');

    if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name']) && !empty($this->request->get['field_uid'])) {

      $filename = rawurldecode(basename(rawurlencode(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'))));

      if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 250)) {
        $result['error'] = $this->language->get('error_upload_filename');
      }
      $filename  = $this->model_tool_utils->clearstr($filename);

      $checkDisk = $this->daemon->exec("CheckFileSizeRestrict", "");
      if (!$checkDisk) {
        $result['error'] = $this->language->get('error_upload_diskspace');
      }
      if (!$result) {
        $allowed = array();

        if (!empty($data['file_extes'])) {
          foreach ($data['file_extes'] as $filetype) {
            $allowed[] = trim($filetype);
          }
        }

        if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
          $result['error'] = sprintf($this->language->get('error_upload_filetype'), strtolower(substr(strrchr($filename, '.'), 1)));
        }
      }
      if (!$result && !empty($data['file_mimes'])) {
        $allowed = array();
        foreach ($data['file_mimes'] as $filemime) {
          $allowed[] = trim($filemime);
        }

        if (!in_array($this->request->files['file']['type'], $allowed)) {
          $result['error'] = sprintf($this->language->get('error_upload_filetype'), $this->request->files['file']['type']);
        }
      }
      if (!$result && !empty($data['size_file']) && filesize($this->request->files['file']['tmp_name']) > $data['size_file'] * 1024) {
        $result['error'] = $this->language->get('error_upload_filesize');
      }

      if (!$result) {
        $token = token(32);
        $time = new DateTime('now');
        $now = $time->format('/Y/m');
        if (!file_exists($data['dir_file_upload'] . $this->request->get['field_uid'] . $now)) {
          mkdir($data['dir_file_upload'] . $this->request->get['field_uid'] . $now, 0750, true);
        }
        if (isset($data['clean_filename']) && $data['clean_filename'] == true) {
          $filepath = $data['dir_file_upload'] . $this->request->get['field_uid'] . $now . "/" . $filename;
        } else {
          $filepath = $data['dir_file_upload'] . $this->request->get['field_uid'] . $now . "/" . $token . $filename;
        }

        move_uploaded_file($this->request->files['file']['tmp_name'], $filepath);
        $result['token'] = $token;
        $result['filepath'] = $filepath;
      }
    } else {
      $result['error'] = $this->language->get('error_upload');
    }

    return $result;
  }

  /**
   * Метод запускает обработку контекстов, может выполняться демоном
   * @param type $data
   *  doctype_uid => все документа данного типа
   *  field_uid   => измененное поле
   *  context     => контекст для запуска
   */
  public function executeDeferred($data)
  {
    $this->load->model('document/document');
    //получаем все документы данного типа
    if (!empty($data['doctype_uid'])) {
      $document_uids = $this->model_document_document->getDocumentIds(array('doctype_uids' => array($data['doctype_uid'])));
      //устанавливаем измененное поле
      $this->request->get['field_uid'] = $data['field_uid'];
      foreach ($document_uids as $document_uid) {
        //запускаем маршрут
        $this->route($document_uid, $data['context']);
      }
    }
  }

  private function returnNotFound()
  {
    if (!isset($this->request->get['json'])) {
      $footer = $this->load->controller('common/footer');
      $header = $this->load->controller('common/header');
      $this->response->setOutput($header . $this->load->view('error/not_found', array()) . $footer);
      return;
    } else {
      $json = array(
        'redirect' => str_replace('&amp;', '&', $this->url->link("error/not_found"))
      );
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode($json));
      return;
    }
  }
  private function utf8_str_split($str)
  {
    // place each character of the string into and array 
    $split = 1;
    $array = array();
    for ($i = 0; $i < strlen($str);) {
      $value = ord($str[$i]);
      if ($value > 127) {
        if ($value >= 192 && $value <= 223)
          $split = 2;
        elseif ($value >= 224 && $value <= 239)
          $split = 3;
        elseif ($value >= 240 && $value <= 247)
          $split = 4;
      } else {
        $split = 1;
      }
      $key = NULL;
      for ($j = 0; $j < $split; $j++, $i++) {
        $key .= $str[$i];
      }
      array_push($array, $key);
    }
    return $array;
  }

  private function clearstr($str)
  {
    $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
    $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'), range('a', 'z'), range('0', '9'), array('&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\'));
    $codes = array();
    for ($i = 0; $i < count($s1); $i++) {
      $codes[] = ord($s1[$i]);
    }
    $str_s = $this->utf8_str_split($str);
    for ($i = 0; $i < count($str_s); $i++) {
      if (!in_array(ord($str_s[$i]), $codes)) {
        $str = str_replace($str_s[$i], '', $str);
      }
    }
    return $str;
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

  private function jsonEncode($v)
  {
    return json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
  }
}

<?php

/**
 * @package		ConditionAction
 * @author		Roman V Zhukov, Andrey V Surov
 * @copyright Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  http://www.documentov.com
 */
class ControllerExtensionActionCondition extends ActionController
{
  const version = "1.0+";
  // исправлена ошибка, из-за которой некорректно работало действие Автонажатие, размещенное внутри Условия

  const ACTION_INFO = array(
    'name' => 'condition',
    'inRouteContext' => true,
    'isCompound' => 'true'
  );

  public function index()
  {
    $this->load->language('extension/action/condition');
    $data['version'] = $this::version;
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/condition', $data));
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
    $this->load->language('doctype/doctype');
    $this->load->language('action/condition');
    $this->load->model('doctype/doctype');
    $condition_description = "";
    if (!empty($params['condition'])) {

      foreach ($params['condition'] as $condition) {
        if (!empty($condition['join'])) {
          $condition_description .= " " . mb_strtoupper($this->language->get('text_' . $condition['join'])) . " ";
        }
        switch ($condition['first_type_value']) {
          case 0:
            //первый аргумент - поле
            if (!empty($condition['first_value_field_uid'])) {
              $first_value_field_info = $this->model_doctype_doctype->getField($condition['first_value_field_uid'], 0);
              if (empty($first_value_field_info['type'])) {
                continue 2;
              }
              $condition_description .= $first_value_field_info['name'] ?? "";
            }
            break;
          case 1:
            //первый аргумент - переменная
            $condition_description .= $this->language->get('text_' . $condition['first_value_var']);
            break;
        }
        $condition_description .= " " . $this->language->get('text_condition_' . $condition['comparison_method']) . " ";
        switch ($condition['second_type_value']) {
          case 0:
            //второй аргумент - поле        
            if (!empty($condition['second_value_field_uid'])) {
              $second_value_field_info = $this->model_doctype_doctype->getField($condition['second_value_field_uid'], 0);
              if (empty($second_value_field_info['type'])) {
                continue 2;
              }
              $condition_description .= $second_value_field_info['name'] ?? "";
            }
            break;
          case 1:
            //второй аргумент - значение из виджета
            if (!empty($condition['second_value_widget'])) {
              if (!empty($first_value_field_info['type']) && $condition['first_type_value'] == "0") {
                //первый аргумент - поле, view которого получаем для текстового описания условия
                $data_f = $first_value_field_info['params'];
                $data_f['field_value'] = $condition['second_value_widget'];
                $data_f['document_uid'] = 0;
                $data_f['field_uid'] = $first_value_field_info['field_uid'];
                $field_view = $this->load->controller("extension/field/" . $first_value_field_info['type'] . "/getView", $data_f);
                $condition_description .= strip_tags(html_entity_decode($field_view));
              } else {
                //первый аргумент - переменная
                $condition_description .= strip_tags(html_entity_decode($condition['second_value_widget']));
              }
            }
            break;
          case 2:
            //второй аргумент - переменная
            $condition_description .= $this->language->get('text_' . $condition['second_value_var']);
            break;
        }
      }
    }

    $actions_true = '';
    $actions_false = '';
    $actions_true_arr = array();
    if (is_array($params['inner_actions_true'])) {
      $actions_true_arr = $params['inner_actions_true'];
    } else {
      //если в параметрах вложенные действия пререданы в виде строки через post
      $actions_true_arr = json_decode(htmlspecialchars_decode($params['inner_actions_true']), true);
    }
    if ($actions_true_arr) {
      foreach ($actions_true_arr as $action) {
        if (($actions_true) !== '') {
          $actions_true .= ', ';
        }
        $actions_true .= '"' . $this->load->controller('extension/action/' . $action['action'] . "/getTitle") . '"';
      }
    }
    $actions_false_arr = array();
    if (is_array($params['inner_actions_false'])) {
      $actions_false_arr = $params['inner_actions_false'];
    } else {
      //если в параметрах вложенные действия пререданы в виде строки через post
      $actions_false_arr = json_decode(htmlspecialchars_decode($params['inner_actions_false']), true);
    }
    if ($actions_false_arr) {
      foreach ($actions_false_arr as $action) {
        if (($actions_false) !== '') {
          $actions_false .= ', ';
        }
        $actions_false .= '"' . $this->load->controller('extension/action/' . $action['action'] . "/getTitle") . '"';
      }
    }

    $description = sprintf($this->language->get('text_condition'), $condition_description) . ' (' . $actions_true . ') ' . $this->language->get('text_condition_else') . ' (' . $actions_false . ')';
    return $description;
  }

  public function getInnerActionDescription()
  {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $action_name = $this->request->post['action'];
      $action_params = $this->request->post['params'];
      $ia_description = array(
        'name' => $this->load->controller('extension/action/' . $action_name . "/getTitle"),
        'description' => $this->clearString($this->load->controller('extension/action/' . $action_name . "/getDescription", $action_params))
      );
      $this->response->setOutput($this->jsonEncode($ia_description));
    }
  }

  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->model('doctype/doctype');
    if (isset($data['action_id'])) {
      $action_description = $this->model_doctype_doctype->getRouteAction($data['action_id']);
      $is_draft = $action_description['draft'] === '1' ? true : false;
    } else {
      $is_draft = true;
    }

    $this->load->language('action/condition');
    $this->load->language('doctype/doctype');

    if (!empty($data['action']['condition'])) {
      foreach ($data['action']['condition'] as &$condition) {
        $condition['description'] = "";
        $first_value_field_info = array();
        switch ($condition['first_type_value']) {
          case 0:
            //первый аргумент - поле
            if (!empty($condition['first_value_field_uid'])) {
              $first_value_field_info = $this->model_doctype_doctype->getField($condition['first_value_field_uid'], 0);
              if (empty($first_value_field_info['type'])) {
                continue 2;
              }
              $condition['description'] .= $first_value_field_info['name'] ?? "";
              $condition['first_value_field_name'] = $first_value_field_info['name'] ?? "";
              $methods_data = array(
                'method_type'   => 'getter',
                'field_uid'     => $condition['first_value_field_uid']
              );
              $condition['first_value_field_avaliable_getters'] = $this->load->controller('extension/field/' . $first_value_field_info['type'] . '/getFieldMethods', $methods_data);
              if (!empty($condition['first_value_field_getter'])) {
                $method_data = array(
                  'doctype_uid' => $data['doctype_uid'],
                  'field_uid' => $condition['first_value_field_uid'],
                );
                if (isset($condition['first_value_field_getter'])) {
                  $method_data['method_name'] = $condition['first_value_field_getter'];
                }

                if (isset($condition['first_value_method_params'])) {
                  $first_value_method_params = [];
                  foreach ($condition['first_value_method_params'] as $param_name => $param_value) {
                    $first_value_method_params[$param_name] = json_encode($param_value);
                  }
                  $condition['first_value_method_params'] = $first_value_method_params;
                }
              }
            }

            break;
          case 1:
            //первый аргумент - переменная
            $condition['description'] .= $this->language->get('text_' . $condition['first_value_var']);
            break;

          default:
            break;
        }

        $condition['description'] .= " " . $this->language->get('text_condition_' . $condition['comparison_method']) . " ";

        //Второе поле
        switch ($condition['second_type_value']) {
          case 0:
            //второй аргумент - поле
            if (!empty($condition['second_value_field_uid'])) {
              $second_value_field_info = $this->model_doctype_doctype->getField($condition['second_value_field_uid'], 0);
              if (empty($second_value_field_info['type'])) {
                continue 2;
              }
              $condition['description'] .= $second_value_field_info['name'] ?? "";
              $condition['second_value_field_name'] = $second_value_field_info['name'] ?? "";
              $methods_data = array(
                'method_type'   => 'getter',
                'field_uid'     => $condition['second_value_field_uid']
              );
              $condition['second_value_field_avaliable_getters'] = $this->load->controller('extension/field/' . $second_value_field_info['type'] . '/getFieldMethods', $methods_data);
              if (!empty($condition['second_value_field_getter'])) {
                $method_data = array(
                  'doctype_uid' => $data['doctype_uid'],
                  'field_uid' => $condition['second_value_field_uid'],
                );
                if (isset($condition['second_value_field_getter'])) {
                  $method_data['method_name'] = $condition['second_value_field_getter'];
                }

                if (isset($condition['second_value_method_params'])) {
                  $second_value_method_params = [];
                  foreach ($condition['second_value_method_params'] as $param_name => $param_value) {
                    $second_value_method_params[$param_name] = json_encode($param_value);
                  }
                  $condition['second_value_method_params'] = $second_value_method_params;
                }
              }
            }
            break;
          case 1:
            //второй аргумент - значение из виджета
            if (!empty($condition['second_value_widget'])) {
              if (!empty($first_value_field_info['type'])) {
                //первый аргумент - поле, view которого получаем для текстового описания условия
                $data_f = $first_value_field_info['params'];
                $data_f['field_value'] = $condition['second_value_widget'];
                $data_f['document_uid'] = 0;
                $data_f['field_uid'] = $first_value_field_info['field_uid'];
                $field_view = $this->load->controller("extension/field/" . $first_value_field_info['type'] . "/getView", $data_f);
                $condition['description'] .= strip_tags(html_entity_decode($field_view));
              } else {
                //первый аргумент - переменная
                $condition['description'] .= strip_tags(html_entity_decode($condition['second_value_widget']));
              }
            }
            break;
          case 2:
            //второй аргумент - переменная
            $condition['description'] .= $this->language->get('text_' . $condition['second_value_var']);
            break;
        }
      }
    }

    //действия при выполнении условия
    $ia_descriptions_true = array();
    $inner_actions_true = array();

    if (!empty($data['action']) && !empty($data['action']['inner_actions_true'])) {
      //если не драфтовое действие, то вырезаем все флаги 'new' вложенных действий
      if ($is_draft) {
        if (!empty($data['action']['inner_actions_true_deleted'])) {
          $inner_actions_true = ($data['action']['inner_actions_true'] + $data['action']['inner_actions_true_deleted']);
          ksort($inner_actions_true);
        } else {
          $inner_actions_true = $data['action']['inner_actions_true'];
        }
      } else {
        $inner_actions_true = $data['action']['inner_actions_true'];
        foreach ($inner_actions_true as &$iatnew) {
          unset($iatnew['new']);
        }
      }
      $inner_actions_true = array_values($inner_actions_true);
      foreach ($inner_actions_true as &$iat) {
        $iat['params'] = $iat['action_type'] ?? $iat['params'] ?? [];
        $ia_descriptions_true[] = array(
          'name' => $this->load->controller('extension/action/' . $iat['action'] . "/getTitle"),
          'description' => $this->clearString($this->load->controller('extension/action/' . $iat['action'] . "/getDescription", $iat['params']))
        );
      }
    }
    $data['action']['inner_actions_true'] = $this->jsonEncode($inner_actions_true);
    $data['inner_actions_description_true'] = $this->jsonEncode($ia_descriptions_true);
    //действия при не выполненни условия
    $ia_descriptions_false = array();
    $inner_actions_false = array();
    if (!empty($data['action']) && !empty($data['action']['inner_actions_false'])) {
      //если не драфтовое действие, то вырезаем все флаги 'new' вложенных действий
      if ($is_draft) {
        if (!empty($data['action']['inner_actions_false_deleted'])) {
          $inner_actions_false = array_values($data['action']['inner_actions_false'] + $data['action']['inner_actions_false_deleted']);
          ksort($inner_actions_false);
        } else {
          $inner_actions_false = array_values($data['action']['inner_actions_false']);
        }
      } else {
        $inner_actions_false = $data['action']['inner_actions_false'];
        foreach ($inner_actions_false as &$iatnew) {
          unset($iatnew['new']);
        }
      }
      $inner_actions_false = array_values($inner_actions_false);
      foreach ($inner_actions_false as &$iaf) {
        $iaf['params'] = $iaf['action_type'] ?? $iaf['params'] ?? [];
        $ia_descriptions_false[] = array(
          'name' => $this->load->controller('extension/action/' . $iaf['action'] . "/getTitle"),
          'description' => $this->clearString($this->load->controller('extension/action/' . $iaf['action'] . "/getDescription", $iaf['params']))
        );
      }
    }
    $data['action']['inner_actions_false'] = $this->jsonEncode($inner_actions_false);
    $data['inner_actions_description_false'] = $this->jsonEncode($ia_descriptions_false);

    $data['second_type_value'] = $data['action']['second_type_value'] ?? 0;
    if (isset($data['action']['second_value_widget'])) {
      if (is_array($data['action']['second_value_widget'])) {
        $value = $this->jsonEncode($data['action']['second_value_widget']);
      } else {
        $value = $data['action']['second_value_widget'];
      }
    } else {
      $value = "";
    }
    $data['second_value_widget'] = urlencode($value);

    //список переменных
    $data['vars'] = $this->model_doctype_doctype->getVariables();
    return $this->load->view('action/condition/condition_context_form', $data);
  }

  private function jsonEncode($str)
  {
    return json_encode($str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  }


  public function getInnerActionForm()
  {
    $context = $this->request->get['context'] ?? "";
    $route_uid = $this->request->get['route_uid'] ?? "";
    $this->load->model('doctype/doctype');
    $this->load->language('action/condition');
    $this->load->language('doctype/doctype');
    $data = array();
    $actions = $this->load->controller('doctype/doctype/getActions', 'inRouteContext');
    $data['actions'] = array();
    $data['context'] = $context;
    $data['route_uid'] = $route_uid;
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $i = 0;
    foreach ($actions as $action) {
      if (!isset($action['isCompound']) || $action['isCompound'] === false) {
        $data['actions'][$i] = $action;
        $i++;
      }
    }
    $doctype_uid = $this->request->get['doctype_uid'];
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $params = $this->request->post['params'];
      $action_name = $this->request->post['action'];
      $action_data = array();
      $action_data['action'] = $params;
      $action_data['doctype_uid'] = $doctype_uid;
      $action_data['context'] = $context;
      $action_data['route_uid'] = $route_uid;
      $data['inner_action_form'] = $this->load->controller('extension/action/' . $action_name . '/getForm', $action_data);
      $data['action_name'] = $action_name;
      $this->response->setOutput($this->load->view('action/condition/condition_inner_action_form', $data));
      return;
      // редактирование вложенного действия`
    } else {
      // добавление вложенного действия
      if (!empty($this->request->get['action_name'])) {
        $action_name = $this->request->get['action_name'];
        $this->response->setOutput($this->load->controller('extension/action/' . $action_name . '/getForm', $data));
        return;
      }
    }

    $this->response->setOutput($this->load->view('action/condition/condition_inner_action_form', $data));
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
   * Контекстное ли действие или нет, то есть может запускаться через контексты маршрута или нет.
   * @return boolean
   */
  public function inRouteContext()
  {
    return true;
  }

  /**
   * Может ли действие использоваться в кнопках
   * @return boolean
   */
  public function inRouteButton()
  {
    return false;
  }

  /**
   * Может ли действие использоваться в кнопках в журналах
   * @return boolean
   */
  public function inFolderButton()
  {
    return false;
  }

  /**
   * является ли составным
   * @return boolean
   */
  public function isCompound()
  {
    return true;
  }
  /**
   * Вызывает setMethodParams метода поля при его наличии и передает ему сохраняемые параметры метода
   */
  private function getFieldMethodParams($field_uid, $method_name, $method_params)
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (empty($field_info['type'])) {
      return NULL;
    }
    $method_data = array(
      'method_name'   => $method_name,
      'method_params' => $method_params,
      'field_uid' => $field_uid
    );

    // проверяем вложенные параметры на наличие методов
    foreach ($method_data['method_params'] as &$mp) {
      if (!empty($mp['field_uid'])  && !empty($mp['method_name']) && !empty($mp['method_params'])) {
        // метод есть, уходим в рекурсию
        $mp['method_params'] = $this->getFieldMethodParams($mp['field_uid'], $mp['method_name'], $mp['method_params']);
      }
    }

    $method_params = $this->load->controller('extension/field/' . $field_info['type'] . "/setMethodParams", $method_data);

    if ($method_params !== NULL) {
      return $method_params;
    }
  }

  public function setParams($data)
  {
    if (!empty($data['params']['action']['condition'])) {
      $args = ["first", "second"];

      foreach ($data['params']['action']['condition'] as &$condition) {
        $condition['first_type_value'] = (int) $condition['first_type_value'];
        $condition['second_type_value'] = (int) $condition['second_type_value'];
        foreach ($args as $arg) {
          if (empty($condition[$arg . '_value_field_getter']) || $condition[$arg . '_value_field_getter'] === "" || $condition[$arg . '_value_field_getter'] === "0") {
            continue;
          }
          if (!empty($condition[$arg . '_value_method_params'])) {
            foreach ($condition[$arg . '_value_method_params'] as &$method_params) {
              if (!is_array($method_params)) {
                $method_params = json_decode(html_entity_decode($method_params), true);
              }
            }
            $mp = $this->getFieldMethodParams($condition[$arg . '_value_field_uid'], $condition[$arg . '_value_field_getter'], $condition[$arg . '_value_method_params']);
            if ($mp !== NULL) {
              $condition[$arg . '_value_method_params'] = $mp;
            }
          }
        }
      }
      $data['params']['action']['condition'] = array_values($data['params']['action']['condition']);
    }

    if (!empty($data['params']['action']['inner_actions_true'])) {
      if (is_array($data['params']['action']['inner_actions_true'])) {
        $inner_actions_true = $data['params']['action']['inner_actions_true'];
      } else {
        $inner_actions_true = json_decode(htmlspecialchars_decode($data['params']['action']['inner_actions_true']), true);
      }

      $inner_actions_true_deleted = array();

      foreach ($inner_actions_true as $key => $action) {
        if (isset($action['deleted'])) {
          $inner_actions_true_deleted[$key] = $action;
          unset($inner_actions_true[$key]);
        }
      }
      $data['params']['action']['inner_actions_true'] = $inner_actions_true;
      if (!empty($inner_actions_true_deleted)) {
        $data['params']['action']['inner_actions_true_deleted'] = $inner_actions_true_deleted;
      }
      $data['params']['action']['inner_actions_true'] = array_values($data['params']['action']['inner_actions_true']);
    }
    if (!empty($data['params']['action']['inner_actions_false'])) {
      if (is_array($data['params']['action']['inner_actions_false'])) {
        $inner_actions_false = $data['params']['action']['inner_actions_false'];
      } else {
        $inner_actions_false = json_decode(htmlspecialchars_decode($data['params']['action']['inner_actions_false']), true);
      }


      $inner_actions_false_deleted = array();

      foreach ($inner_actions_false as $key => $action) {
        if (isset($action['deleted'])) {
          $inner_actions_false_deleted[$key] = $action;
          unset($inner_actions_false[$key]);
        }
      }
      $data['params']['action']['inner_actions_false'] = $inner_actions_false;
      if (!empty($inner_actions_false_deleted)) {
        $data['params']['action']['inner_actions_false_deleted'] = $inner_actions_false_deleted;
      }
      $data['params']['action']['inner_actions_false'] = array_values($data['params']['action']['inner_actions_false']);
    }

    if ($data['params']['action']['inner_actions_true']) {

      foreach ($data['params']['action']['inner_actions_true'] as &$action) {
        $data_action = array(
          'params' => array(
            'action' => $action['params']
          )
        );
        if (isset($data['route_action_uid'])) {
          $data_action['route_action_uid'] = $data['route_action_uid'];
        }
        if (isset($data['route_uid'])) {
          $data_action['route_uid'] = $data['route_uid'];
        }
        $action['params'] = $this->load->controller('extension/action/' . $action['action'] . '/setParams', $data_action);
      }
    }
    if ($data['params']['action']['inner_actions_false']) {
      foreach ($data['params']['action']['inner_actions_false'] as &$action) {
        $data_action = array(
          'params' => array(
            'action' => $action['params']
          )
        );
        if (isset($data['route_action_uid'])) {
          $data_action['route_action_uid'] = $data['route_action_uid'];
        }
        if (isset($data['route_uid'])) {
          $data_action['route_uid'] = $data['route_uid'];
        }
        $action['params'] = $this->load->controller('extension/action/' . $action['action'] . '/setParams', $data_action);
      }
    }
    // var_dump($data['params']['action']['stop']);
    if (!empty($data['params']['action']['stop'])) {
      $data['params']['action']['stop']['true'] = !empty($data['params']['action']['stop']['true']) ? 1 : 0;
      $data['params']['action']['stop']['false'] = !empty($data['params']['action']['stop']['false']) ? 1 : 0;
    } else {
      $data['params']['action']['stop'] = ['true' => 0, 'false' => 0];
    }

    return $data['params']['action'];
  }

  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
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

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->language('action/condition');
    $logs = array();
    $results = array();
    //обрабатываем условия

    if (!empty($data['params']['condition'])) {
      foreach ($data['params']['condition'] as $condition) {
        $first_value = '';
        $second_value = '';
        $first_value_field_info = array();
        switch ($condition['first_type_value']) {
          case 0:
            //первый аргумент -  поле
            if (!empty($condition['first_value_field_uid'])) {
              $first_value_field_info = $this->model_doctype_doctype->getField($condition['first_value_field_uid']);
              if (empty($first_value_field_info['type'])) {
                continue 2;
              }
              $method_params = array(
                'type' => 'document',
                'document_uid' => $data['document_uid'],
                'doclink_field_uid' => '0',
                'field_uid' => $condition['first_value_field_uid'],
                'method_name' => $condition['first_value_field_getter'],
                'current_document_uid' => $data['document_uid'],
              );
              if (isset($condition['first_value_method_params'])) {
                $method_params['method_params'] = $condition['first_value_method_params'];
              }
              $first_value = $this->load->controller('extension/field/' . $first_value_field_info['type'] . '/executeMethod', $method_params);
            }
            break;
          case 1:
            //первый аргумент - переменная
            if (!empty($condition['first_value_var'])) {
              $first_value = $this->model_document_document->getVariable($condition['first_value_var'], $data['document_uid']);
            }
            break;
        }
        switch ($condition['second_type_value']) {
          case 0:
            //второй аргумент - поле
            if (!empty($condition['second_value_field_uid'])) {
              $second_value_field_info = $this->model_doctype_doctype->getField($condition['second_value_field_uid']);
              if (empty($second_value_field_info['type'])) {
                continue 2;
              }
              $method_params = array(
                'type' => 'document',
                'document_uid' => $data['document_uid'],
                'doclink_field_uid' => '0',
                'field_uid' => $condition['second_value_field_uid'],
                'method_name' => $condition['second_value_field_getter'],
                'current_document_uid' => $data['document_uid'],
              );
              if (isset($condition['second_value_method_params'])) {
                $method_params['method_params'] = $condition['second_value_method_params'];
              }
              $second_value = $this->load->controller('extension/field/' . $second_value_field_info['type'] . '/executeMethod', $method_params);
            }
            break;
          case 1:
            //второй аргумент - значение из виджета
            if (isset($condition['second_value_widget'])) {
              if (!empty($first_value_field_info['type'])) {
                //первый аргумент - поле
                //преобразуем введенное значение во внутреннее значение поля (например, 01.05.2018 в 2018-05-01
                $model = "model_extension_field_" . $first_value_field_info['type'];
                $this->load->model('extension/field/' . $first_value_field_info['type']);
                $second_value = $this->$model->getValue($condition['first_value_field_uid'], 0, $condition['second_value_widget'], $first_value_field_info);
                if ($second_value === NULL && isset($condition['second_value_widget']) && !empty($condition['first_value_field_getter'])) {
                  $second_value = $condition['second_value_widget']; // есть значение виджета, а getValue вернул NULL, и в то же время есть геттер для первого аргумента - это ситуация, когда геттер возвращает не тот тип данных, который имеет первый аргумент (н-р, разница в днях для поля время)
                }
              } else {
                //первый аргумент - переменная
                $second_value = trim($condition['second_value_widget']);
              }
            }
            break;
          case 2:
            //второй аргумент - переменная
            if (!empty($condition['second_value_var'])) {
              $second_value = $this->model_document_document->getVariable($condition['second_value_var'], $data['document_uid']);
            }
            break;
        }
        $result = false;
        switch ($condition['comparison_method']) {
          case 'equal':
            if ($first_value == $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_equal'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_notequal'), $second_value);
            }
            break;
          case 'notequal':
            if ($first_value != $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_notequal'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_equal'), $second_value);
            }
            break;
          case 'more':
            if ($first_value > $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_more'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_less'), $second_value);
            }
            break;
          case 'moreequal':
            if ($first_value >= $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_moreequal'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_lessequal'), $second_value);
            }
            break;
          case 'less':
            if ($first_value < $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_less'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_more'), $second_value);
            }
            break;
          case 'lessequal':
            if ($first_value <= $second_value) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_lessequal'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_moreequal'), $second_value);
            }
            break;
          case 'contains':
            if ($first_value && $second_value && mb_stripos($first_value, $second_value) !== FALSE) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_contains'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_notcontains'), $second_value);
            }
            break;
          case 'notcontains':
            if ($first_value && $second_value && mb_stripos($first_value, $second_value) === FALSE) {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_notcontains'), $second_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($first_value_field_info['name'] ?? ""), $this->language->get('text_condition_contains'), $second_value);
            }
            break;
          case 'include':
            if ($first_value && $second_value && mb_stripos($second_value, $first_value) !== FALSE) {
              $log = sprintf($this->language->get('text_log'), ($second_value_field_info['name'] ?? ""), $this->language->get('text_condition_include'), $first_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($second_value_field_info['name'] ?? ""), $this->language->get('text_condition_notinclude'), $first_value);
            }
            break;
          case 'notinclude':
            if ($first_value && $second_value && mb_stripos($second_value, $first_value) === FALSE) {
              $log = sprintf($this->language->get('text_log'), ($second_value_field_info['name'] ?? ""), $this->language->get('text_condition_notinclude'), $first_value);
              $result = true;
            } else {
              $log = sprintf($this->language->get('text_log'), ($second_value_field_info['name'] ?? ""), $this->language->get('text_condition_include'), $first_value);
            }
            break;
        }
        $logs[] = (!empty($condition['join']) ? $this->language->get('text_' . $condition['join']) : "") . " " . $log;
        $results[] = array(
          'result'    => $result,
          'join'      => $condition['join'] ?? ""
        );
      }
    }
    if (count($results) > 1) {
      $result = false;
      foreach ($results as $i => $res) {
        if (empty($res['join']) || !$i) {
          $result = $res['result'];
        } elseif ($res['join'] === "and") {
          $result = ($result && $res['result']);
        } else {
          $result = ($result || $res['result']);
        }
      }
    } else {
      $result = $results[0]['result'] ?? false;
    }
    $result_action = [];

    if ($result) {
      if (!empty($data['params']['inner_actions_true'])) {
        $result_action = $this->executeInnerActions($data, "true");
      }
      $block = "true";
    } else {
      if (!empty($data['params']['inner_actions_false'])) {
        $result_action = $this->executeInnerActions($data, "false");
      }
      $block = "false";
    }

    if (isset($data['params']['stop'][$block]) && $data['params']['stop'][$block] == 1) {
      $result_action['stop'] = 1;
    }

    $result_action['log'] = implode(" ", $logs);

    return $result_action;
  }

  private function executeInnerActions($data, $block = "true")
  {
    $document_uid = $data['document_uid'];
    $route_uid = $data['route_uid'];
    $params = $data['params'];

    $inner_actions = $params['inner_actions_' . $block];
    // $stop = $params['stop'][$block] ?? "";

    $append = array();
    foreach ($inner_actions as $action) {
      $action['params'] = $action['action_type'] ?? $action['params'] ?? [];
      $action['document_uid'] = $document_uid;
      $document_info = $this->model_document_document->getDocument($document_uid, false);
      if ($route_uid != $document_info['route_uid']) {
        //документ был перемещен
        break;
      }
      $action['route_uid'] = $route_uid;
      $action['context'] = $data['context'];
      $result = $this->load->controller("extension/action/" . $action['action'] . "/executeRoute", $action);
      if (!empty($result['append'])) {
        $append[] = $result['append'];
      }
      if (!empty($result['redirect'])) {
        if ($append) {
          $result['append'] = $append;
        }
        return $result;
      }
    }

    $result = [];
    if ($append) {
      $result['append'] = $append;
    }
    // if ($stop) {
    //   $result['stop'] = 1;
    // }
    return $result;
  }

  private function clearString($string)
  {
    return str_replace(array('"', "'", "&quot;"), "\"", $string);
  }

  public function onUndraft($params)
  {
    unset($params['inner_actions_true_deleted']);
    unset($params['inner_actions_false_deleted']);
    foreach (['true', 'false'] as $type) {
      if (!empty($params['inner_actions_' . $type])) {
        foreach ($params['inner_actions_' . $type] as &$action) {
          unset($action['new']);
        }
      }
    }
    return $params;
  }
}

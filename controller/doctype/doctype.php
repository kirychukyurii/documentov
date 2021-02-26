<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */
class ControllerDoctypeDoctype extends Controller
{
  public $contextes = array(
      'jump'          => 'sign-in',
      'view'          => 'flash',
      'change'        => 'pencil',
      'delete'        => 'trash-o'
    ),
    $context0 = array( //дополнительный контекст для 0 точки
      'create'        => 'power-off',
      'setting'       => 'gear'
    );
  private $attr = array(
      'change_field'  => ['pic' => 'pencil', 'default' => 0],
      'required'      => ['pic' => 'exclamation-triangle', 'default' => 0],
      'unique'        => ['pic' => 'exclamation', 'default' => 0],
      'ft_index'      => ['pic' => 'search', 'default' => 0],
      'history'       => ['pic' => 'history', 'default' => 0],
      'access_form'   => ['pic' => 'edit', 'default' => []],
      'access_view'   => ['pic' => 'eye', 'default' => []],
    ),
    $error = array();

  public function index()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('doctype/doctype', '', true, true));
    $this->load->language('doctype/doctype');
    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('doctype/doctype');

    $this->getList();
  }

  public function add()
  {
    $this->load->model('doctype/doctype');
    $doctype_uid = $this->model_doctype_doctype->addDoctype();
    $this->response->redirect($this->url->link('doctype/doctype/edit', 'doctype_uid=' . $doctype_uid, true));
  }

  public function getList()
  {
    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];
      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }
    if (isset($this->session->data['error_warning'])) {
      $data['error_warning'] = $this->session->data['error_warning'];
      unset($this->session->data['error_warning']);
    } else {
      $data['error_warning'] = '';
    }

    //фильтр
    if (isset($this->request->get['filter_name'])) {
      $filter_name = $this->request->get['filter_name'];
    } else {
      $filter_name = '';
    }
    //сортировка по полю
    if (isset($this->request->get['sort'])) {
      $sort = $this->request->get['sort'];
    } else {
      $sort = 'dd.name';
    }
    //порядок сортировка
    if (isset($this->request->get['order'])) {
      $order = $this->request->get['order'];
    } else {
      $order = 'ASC';
    }

    $url = '';
    if (isset($this->request->get['filter_name'])) {
      $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
    }
    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    //кнопки
    $data['add'] = $this->url->link('doctype/doctype/add', $url, true);
    $data['copy'] = $this->url->link('doctype/doctype/copy', true);
    $data['delete'] = $this->url->link('doctype/doctype/delete', $url, true);

    //получаем типы документов
    $data['doctypes'] = array();

    $filter_data = array(
      'filter_name' => $filter_name,
      'sort' => $sort,
      'order' => $order,
    );

    $results = $this->model_doctype_doctype->getDoctypes($filter_data);

    foreach ($results as $result) {
      $data['doctypes'][] = array(
        'doctype_uid' => $result['doctype_uid'],
        'name' => $result['name'],
        'description' => $result['short_description'],
        'date_added' => $result['date_added'],
        'date_edited' => $result['date_edited'],
        //                'user_uid' => $result['user_uid'],
        'edit' => $this->url->link('doctype/doctype/edit', 'doctype_uid=' . $result['doctype_uid'] . $url, true)
      );
    }


    if (isset($this->request->post['doctype_selected'])) {
      $data['doctype_selected'] = (array) $this->request->post['doctype_selected'];
    } else {
      $data['doctype_selected'] = array();
    }

    $url = '';

    if (isset($this->request->get['filter_name'])) {
      $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
    }

    if ($order == 'ASC') {
      $url .= '&order=DESC';
    } else {
      $url .= '&order=ASC';
    }

    $data['sort_name'] = $this->url->link('doctype/doctype', 'sort=dd.name' . $url, true);
    $data['sort_short_description'] = $this->url->link('doctype/doctype', 'sort=dd.short_description' . $url, true);
    $data['sort_date_added'] = $this->url->link('doctype/doctype', 'sort=d.date_added' . $url, true);
    $data['sort_date_edited'] = $this->url->link('doctype/doctype', 'sort=d.date_edited' . $url, true);

    $url = '';

    if (isset($this->request->get['filter_name'])) {
      $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
    }

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }


    $data['filter_name'] = $filter_name;

    $data['sort'] = $sort;
    $data['order'] = $order;

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('doctype/doctype_list', $data));
  }

  public function edit()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('doctype/doctype/edit' . '&doctype_uid=' . ($this->request->get['doctype_uid'] ?? ""), '', true, true));

    $this->load->language('doctype/doctype');

    $this->load->model('doctype/doctype');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

      $this->model_doctype_doctype->editDoctype($this->request->get['doctype_uid'], $this->request->post);
      $this->response->redirect($this->url->link('doctype/doctype/edit', 'doctype_uid=' . $this->request->get['doctype_uid'] . $this->request->post['active_tab'] ?? "", true));
    }
    $this->document->addStyle('view/javascript/colorpicker/css/colorpicker.css');
    $this->document->addScript('view/javascript/colorpicker/js/colorpicker.js');
    $this->getForm();
  }

  protected function getForm()
  {
    $this->load->model('setting/extension');
    $data['text_form'] = !isset($this->request->get['doctype_uid']) ? $this->language->get('text_add') : $this->language->get('text_edit');
    $doctype_uid = !empty($this->request->get['doctype_uid']) ? $this->request->get['doctype_uid'] : 0;

    $doctype_info = $this->model_doctype_doctype->getDoctype($doctype_uid, true);
    if (!$doctype_info) {
      $this->response->redirect($this->url->link('doctype/doctype/add', "", true));
    }
    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = array();
    }

    $url = '';

    if (isset($this->request->get['filter_name'])) {
      $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
    }

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    if (isset($this->request->get['page'])) {
      $url .= '&page=' . $this->request->get['page'];
    }

    if (!isset($this->request->get['doctype_uid'])) {
      $data['action'] = $this->url->link('doctype/doctype/add', $url, true);
    } else {
      $data['action'] = $this->url->link('doctype/doctype/edit', 'doctype_uid=' . $doctype_uid . $url, true);
    }

    $data['cancel'] = $this->url->link('doctype/doctype', $url, true);
    $data['create_document'] = $this->url->link('document/document', 'doctype_uid=' . $doctype_uid, true);
    $data['text_delegate_create'] = sprintf($this->language->get('text_delegate_create'), $data['create_document']);

    if ($doctype_uid && $this->config->get('show_button_export_doctype') && $this->model_setting_extension->getExtensionId('service', 'export')) {
      $data['export_doctype'] = $this->url->link('extension/service/export/export', 'doctype_uid=' . $doctype_uid, true);
    }


    $this->document->setTitle($doctype_info['name'] ?? $this->language->get('text_doctype'));
    $data['heading_title'] = $this->language->get('text_doctype') . " " . ($doctype_info['name'] ?? "");
    $data['doctype_uid'] = $this->request->get['doctype_uid'];

    $delegates = [];
    if (!empty($doctype_info['accesses'])) {
      foreach ($doctype_info['accesses'] as $access) {
        $delegates[$access['access_id']] = $access;
        $delegates[$access['access_id']]['subject'] = [];
        for ($i = 0; $i < count($access['subject_uids']); $i++) {
          $delegates[$access['access_id']]['subject'][] = [
            'subject_name'      => $access['subject_names'][$i] ?? "",
            'subject_uid'       => $access['subject_uids'][$i] ?? ""
          ];
        }
      }
    }
    $data['delegates'] = $delegates;

    if (!empty($doctype_info)) {
      // if ($doctype_info['draft'] == 1 || ($doctype_info['draft'] == 3 && $doctype_info['draft_params'])) {
      if ($doctype_info['draft'] > 0) {
        $data['draft_message'] = sprintf($this->language->get('text_draft_message'), $this->url->link('doctype/doctype/remove_draft', 'doctype_uid=' . $doctype_uid));
      }
      $data['field_log_uid'] = $doctype_info['field_log_uid'];
      $data['field_log'] = $this->model_doctype_doctype->getFieldName($doctype_info['field_log_uid']);
    }
    $data['delegate_create'] = $doctype_info['delegate_create'] ?? 0;
    $this->load->model('localisation/language');

    $data['languages'] = $this->model_localisation_language->getLanguages();
    $data['language_id'] = $this->config->get('config_language_id');

    // $data['doctype_description'] = $this->model_doctype_doctype->getDoctypeDescriptions($this->request->get['doctype_uid'], true);
    $data['doctype_description'] = $doctype_info['description'];
    //получаем названия полей для заголовка для всех языков
    if (!empty($data['doctype_description'])) {
      $data['title_field_name'] = array();
      foreach ($data['doctype_description'] as $lang_id => $doctype_description) {
        if (isset($data['doctype_description'][$lang_id]['title_field_uid'])) {
          $data['title_field_name'][$lang_id] = $this->model_doctype_doctype->getFieldName($data['doctype_description'][$lang_id]['title_field_uid']);
        }
      }
    }

    $doctype_templates = $this->model_doctype_doctype->getDoctypeTemplates($this->request->get['doctype_uid']);
    $data['doctype_template_conditions'] = $doctype_templates['doctype_template_conditions'];

    //разбираем условия шаблонов, чтобы обновить названия полей в условиях
    foreach ($data['doctype_template_conditions'] as $type => &$conditions_lang) {
      foreach ($conditions_lang as &$conditions) {
        foreach ($conditions as &$condition) {
          if (!$condition) {
            $condition = "";
            continue;
          }
          // $jconditions = json_decode($condition, TRUE);
          $jconditions = $condition;
          if (!$jconditions) {
            $conditions = ""; //переписываем массив строкой
            continue;
          }

          //актуализируем названия полей в услових шаблона (УШ)
          foreach ($jconditions as &$jcondition) {
            foreach ($jcondition['condition'] as &$c) {
              if (!empty($c['field_uid'])) {
                $c['field_name'] = $this->model_doctype_doctype->getFieldName($c['field_uid']);
              }
              if (strpos($c['comparison'], 'field') !== false) {
                //значение для сравнения получается из поля
                if ($c['value_id']) {
                  $c['value_value'] = $this->model_doctype_doctype->getFieldName($c['value_id']);
                }
              } else {
                $c['value_id'] = '';
              }
            }
            // актуализируем названия действий УШ
            foreach ($jcondition['condition'] as &$cond) {
              if (!empty($cond['field_uid'])) {
                $cond['field_name'] = $this->model_doctype_doctype->getFieldName($cond['field_uid']);
              }
            }
            // в шаблонах д. Запись УШ могут находиться кавычки, ломающие УШ админки
            foreach ($jcondition['action'] as &$action) {
              if (!empty($action['current_doctype_field_uid'])) {
                $action['current_doctype_field_name'] = $this->model_doctype_doctype->getFieldName($action['current_doctype_field_uid']);
              }
              if (!empty($action['selected_doctype_field_uid'])) {
                $action['selected_doctype_field_name'] = $this->model_doctype_doctype->getFieldName($action['selected_doctype_field_uid']);
              }
              if (empty($action['template'])) {
                continue;
              }
              $action['template'] = html_entity_decode($action['template']);
            }
          }
          $condition = json_encode($jconditions, JSON_HEX_QUOT  | JSON_HEX_APOS | JSON_HEX_AMP);
        }
      }
    }

    $data['doctype_template'] = $doctype_templates['doctype_templates'];

    foreach ($data['doctype_template'] as $type => $tmpls) {
      foreach ($tmpls as $index => $templates) {
        foreach ($templates as $name => $params) {
          $params = $doctype_templates['doctype_template_params'][$type][$index]['params'] ?? [];
          // if ($name == 'params') {
          if ($params) {
            $name = "params";
            $data['doctype_template'][$type][$index][$name] = $params;
            $condition_field_name = $this->model_doctype_doctype->getFieldName($params['condition_field_uid']);
            $data['doctype_template'][$type][$index][$name]['condition_field_name'] = $condition_field_name;
            if (!empty($params['condition_value_uid'])) {
              $condition_value_name = $this->model_doctype_doctype->getFieldName($params['condition_value_uid']);
              $data['doctype_template'][$type][$index][$name]['condition_value_name'] = $condition_value_name;
            } else {
              $data['doctype_template'][$type][$index][$name]['condition_value_name'] = '""';
            }
          }
        }
      }
    }
    // exit;
    $data['templates'] = array(
      'view' => array($this->language->get('text_main_template')),
      'form' => array($this->language->get('text_main_template')),
    );
    // print_r($doctype_templates['doctype_template_params']);
    // exit;
    if (!empty($doctype_templates['doctype_template_params'])) {
      foreach ($doctype_templates['doctype_template_params'] as $template_type => $templates) {
        foreach ($templates as $template) {
          $data['templates'][$template_type][] = $template['params']['template_name'];
        }
      }
    }
    // print_r($data['doctype_template']);
    // exit;
    $data['fields'] = array();

    if (isset($this->request->get['doctype_uid'])) {
      $data['fields'] = $this->model_doctype_doctype->getFields(array('doctype_uid' => $doctype_uid, 'setting' => 0, 'system' => 1));
      foreach ($data['fields'] as &$field_0) {
        $field_0['attributes'] = [];
        foreach ($this->attr as $attr_name => $attr_info) {
          if (!empty($field_0[$attr_name])) {
            $field_0['attributes'][] = [
              'text' => $this->language->get("text_attr_" . $attr_name),
              'pic'  => $attr_info['pic']
            ];
          }
        }
      }
      $data['setting_fields'] = $this->model_doctype_doctype->getFields(array('doctype_uid' => $doctype_uid, 'setting' => 1, 'system' => 1));
      $this->load->model('document/document');
      foreach ($data['setting_fields'] as &$field_1) {
        //Подготавливаем данные для передачи в метод getView
        $setting_field_info = $this->model_doctype_doctype->getField($field_1['field_uid'], 1);
        $data_field = $setting_field_info['params'];
        /* //поле настроек, документ-айди = 0 */
        $data_field['field_value'] = $this->model_document_document->getFieldValue($field_1['field_uid'], 0);
        $data_field['field_uid'] = $field_1['field_uid'];
        $data_field['document_uid'] = 0;
        $field_1['value'] = $this->load->controller('extension/field/' . $setting_field_info['type'] . "/getView", $data_field);
        foreach ($this->attr as $attr_name => $attr_info) {
          if (!empty($field_1[$attr_name])) {
            $field_1['attributes'][] = [
              'text' => $this->language->get("text_attr_" . $attr_name),
              'pic'  => $attr_info['pic']
            ];
          }
        }
      }
    } else {
      $data['fields'] = array();
    }

    //получаем список переменных

    $data['contextes'] = array();
    foreach ($this->contextes as $context => $value) {
      $data['contextes'][$context]['name'] = $this->language->get('text_route_' . $context . '_name');
      $data['contextes'][$context]['description'] = $this->language->get('text_route_' . $context . '_description');
      $data['contextes'][$context]['icon'] = $value;
    }
    $data['context0'] = array();
    foreach ($this->context0 as $context => $value) {
      $data['context0'][$context]['name'] = $this->language->get('text_route_' . $context . '_name');
      $data['context0'][$context]['description'] = $this->language->get('text_route_' . $context . '_description');
      $data['context0'][$context]['icon'] = $value;
    }
    $data['count_contextes'] = count($this->contextes);

    $routes = $this->model_doctype_doctype->getRoutes(array('doctype_uid' => $doctype_uid));
    $data['routes'] = array();
    $first = TRUE;
    foreach ($routes as $route) {
      $route_descriptions = $this->model_doctype_doctype->getRouteDescriptions($route['route_uid']);
      if ($first) {
        //нулевая точка, добавляем контексты нулевой точки
        $actions = array_merge($this->context0, $this->contextes);
        $first = FALSE;
      } else {
        $actions = $this->contextes;
      }

      foreach ($route['actions'] as $context => $value) {
        $actions[$context] = array();
        foreach ($value as $action) {
          $actions[$context][] = array(
            'route_action_uid' => $action['route_action_uid'],
            'action' => $action['action'],
            'name' => $this->load->controller('extension/action/' . $action['action'] . "/getTitle"),
            'params' => $action['params'],
            'draft' => $action['draft'] == 12 ? 2 : $action['draft'],
            'status' => $action['status'],
            'description' => $action['description'] ? $action['description'] : $this->load->controller('extension/action/' . $action['action'] . "/getDescription", $action['params']),
          );
        }
      }
      $buttons = $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $route['route_uid']));

      foreach ($buttons as &$button) {
        if ($button['button_group_uid']) {
          $button['button_group'] = $this->model_doctype_doctype->getButtonGroup($button['button_group_uid']);
        }
        unset($button['button_group_uid']);
      }
      $data['routes'][] = array(
        'route_uid' => $route['route_uid'],
        'name' => isset($route_descriptions[(int) $this->config->get('config_language_id')]['name']) ? $route_descriptions[(int) $this->config->get('config_language_id')]['name'] : "",
        'description' => isset($route_descriptions[(int) $this->config->get('config_language_id')]['description']) ? $route_descriptions[(int) $this->config->get('config_language_id')]['description'] : "",
        'descriptions' => $route_descriptions,
        'draft' => $route['draft'],
        //'buttons' => $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $route['route_uid'])),
        'buttons' => $buttons,
        'actions' => $actions,
      );
    }
    //print_r($data);
    $data['structure_uid'] = $this->config->get('structure_id');
    $data['structure_name_uid'] = $this->config->get('structure_field_name_id');

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('doctype/doctype_form', $data));
  }

  public function autocomplete()
  {
    $json = array();

    if (isset($this->request->get['filter_name'])) {
      $this->load->model('doctype/doctype');
      $filter_name = $this->request->get['filter_name'];

      if (isset($this->request->get['limit'])) {
        $limit = $this->request->get['limit'];
      } else {
        $limit = 100;
      }

      $filter_data = array(
        'filter_name' => $filter_name,
        'start' => 0,
        'limit' => $limit
      );

      $results = $this->model_doctype_doctype->getDoctypes($filter_data);

      foreach ($results as $result) {

        $json[] = array(
          'doctype_uid' => $result['doctype_uid'],
          'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
        );
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Возвращает список названий и идентификаторов полей доктайпа через Аякс (кроме удаленных)
   * 
   */
  public function get_fields()
  {
    if (!empty($this->request->get['doctype_uid'])) {
      $this->load->model('doctype/doctype');
      $fields = $this->model_doctype_doctype->getFields(array('doctype_uid' => $this->request->get['doctype_uid']));
      foreach ($fields as $field) {
        if ($field['draft'] == 2)
          continue;
        $result[] = $field['name'];
      }
      $this->response->setOutput(json_encode($result));
    }
  }

  public function add_route_button()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $data['fields'] = ""; //выбранные поля кнопки (кому делегируется кнопка
    $data['thumb'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['actions'] = $this->getActions('inRouteButton');
    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['route_uid'] = $this->request->get['route_uid'];
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['action_log'] = "1";

    $data['button_groups'] = $this->model_doctype_doctype->getButtonGroups(array('container_uid' => $this->request->get['route_uid'] ?? 0));
    //        $data['action_general_form'] = $this->load->view($this->config->get('config_theme') . "/template/doctype/route_button_action_general", $data);
    $data['language_id'] = $this->config->get('config_language_id');
    $data['btn_group_uid'] = '0';
    $this->response->setOutput($this->load->view('doctype/route_button_form', $data));
  }

  public function add_route_action()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $data['actions'] = $this->getActions('inRouteContext');
    $data['action_status'] = 1;
    $data['context'] = $this->request->get['context'];
    $data['route_uid'] = $this->request->get['route_uid'];
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['action_general_form'] = $this->load->view($this->config->get('config_theme') . "/template/doctype/route_action_general", $data);
    $this->response->setOutput($this->load->view('doctype/route_action_form', $data));
  }

  public function delete()
  {
    $this->load->language('doctype/doctype');
    $this->load->language('doctype/doctype');
    if (isset($this->request->post['doctype_selected'])) {
      $this->load->model('doctype/doctype');
      foreach ($this->request->post['doctype_selected'] as $doctype_uid) {
        if (!$this->model_doctype_doctype->deleteDoctype($doctype_uid)) {
          $this->session->data['error_warning'] = $this->language->get('text_error_document_exists');
        } else {
          $this->session->data['success'] = $this->language->get('text_success');
        }
      }
      $this->response->redirect($this->url->link('doctype/doctype', "", true));
    }

    $this->getList();
  }

  public function copy()
  {
    $this->load->language('doctype/doctype');
    if (!empty($this->request->post['doctype_selected'])) {
      $this->load->model('doctype/doctype');
      foreach ($this->request->post['doctype_selected'] as $doctype_uid) {
        $this->model_doctype_doctype->copyDoctype($doctype_uid);
      }
    }
    $this->getList();
  }

  public function copy_route_action()
  {
    $this->load->model('doctype/doctype');
    if ($this->request->server['REQUEST_METHOD'] == "POST" && isset($this->request->post['selected-copy_action']) && count($this->request->post['selected-copy_action']) && !empty($this->request->get['route_uid']) && !empty($this->request->get['context'])) {
      //копируем действия
      $result = array();
      foreach ($this->request->post['selected-copy_action'] as $route_action_uid) {
        $copy_action_id = $this->model_doctype_doctype->copyRouteAction($route_action_uid, $this->request->get['route_uid'], $this->request->get['context']);
        if ($copy_action_id) {
          $action_info = $this->model_doctype_doctype->getRouteAction($copy_action_id);
          $result[] = array(
            'name' => $this->load->controller('extension/action/' . $action_info['action'] . "/getTitle"),
            'description' => $action_info['description'] ? $action_info['description'] : $this->load->controller('extension/action/' . $action_info['action'] . "/getDescription", $action_info['action_params']),
            'route_action_uid' => $copy_action_id
          );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
      }
    } elseif ($this->request->server['REQUEST_METHOD'] == "GET" && !empty($this->request->get['doctype_uid'])) {
      $data = array();
      $this->load->language('doctype/doctype');
      $data['actions'] = $this->model_doctype_doctype->getListRouteActions($this->request->get['doctype_uid']);
      $data['context'] = $this->request->get['context'];
      $data['route_uid'] = $this->request->get['route_uid'];
      $data['doctype_uid'] = $this->request->get['doctype_uid'];
      $this->response->setOutput($this->load->view('doctype/route_action_form_copy', $data));
    }
  }

  public function copy_route_button()
  {
    $this->load->model('doctype/doctype');
    if ($this->request->server['REQUEST_METHOD'] == "POST" && isset($this->request->post['selected-copy_button']) && count($this->request->post['selected-copy_button']) && !empty($this->request->get['route_uid'])) {
      //копируем кнопки
      $result = array();
      foreach ($this->request->post['selected-copy_button'] as $route_button_uid) {
        $copy_button = $this->model_doctype_doctype->copyRouteButton($route_button_uid, $this->request->get['route_uid']);

        if (isset($copy_button['uid'])) {
          $button_info = $this->model_doctype_doctype->getRouteButton($copy_button['uid']);

          $name = $button_info['descriptions'][$this->config->get('config_language_id')]['name'] ?? current($button_info['descriptions'])['name'] ?? "";
          $result[] = array(
            'name' => $name,
            'route_button_uid' => $copy_button['uid'],
            'color' => $button_info['color'],
            'background' => $button_info['background'],
            'picture25' => $button_info['picture25'],
            'fields' => $button_info['fields']
          );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
      }
    } elseif ($this->request->server['REQUEST_METHOD'] == "GET" && !empty($this->request->get['doctype_uid'])) {
      $data = array();
      $this->load->language('doctype/doctype');
      $data['buttons'] = $this->model_doctype_doctype->getListRouteButtons($this->request->get['doctype_uid']);
      $data['route_uid'] = $this->request->get['route_uid'];
      $data['doctype_uid'] = $this->request->get['doctype_uid'];
      $this->response->setOutput($this->load->view('doctype/route_button_form_copy', $data));
    }
  }

  public function edit_route_button()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $this->load->model('tool/image');
    $this->load->model('doctype/doctype');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $button_info = $this->model_doctype_doctype->getRouteButton($this->request->get['route_button_uid']);
    $data['fields'] = $button_info['fields'];
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['description'] = $button_info['description'];
    $data['descriptions'] = $button_info['descriptions'];
    $data['picture'] = $button_info['picture'];
    $data['color'] = $button_info['color'];
    $data['background'] = $button_info['background'];
    $data['show_after_execute'] = $button_info['show_after_execute'];
    $data['hide_button_name'] = $button_info['hide_button_name'];
    $data['thumb'] = $this->model_tool_image->resize($button_info['picture'] ? $button_info['picture'] : 'no_image.png', 25, 25);
    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['actions'] = $this->getActions('inRouteButton');
    $data['action'] = array();
    $data['route_button_action'] = "";
    $data['action_move_route'] = $button_info['action_move_route_uid'] ? ($this->model_doctype_doctype->getRoute($button_info['action_move_route_uid'])['name'] ?? "") : "";
    $data['action_move_route_uid'] = $button_info['action_move_route_uid'];

    if ($button_info['action'] && $button_info['action_params']) {
      foreach ($button_info['action_params'] as $name => $value) {
        $data['action'][$name] = $value;
      }
      $this->language->load('action/' . $button_info['action']);
      $data['action_log'] = $button_info['action_log'];
      $data['action_form'] = $this->load->controller('extension/action/' . $button_info['action'] . '/getForm', $data);
      $data['action_name'] = $button_info['action'];
    } else {
      // у кнопки нет действия
      $data['action_log'] = $button_info['action_log'];
    }

    $data['button_uid'] = $this->request->get['route_button_uid'];
    $data['route_uid'] = $button_info['route_uid'];
    $data['button_groups'] = $this->model_doctype_doctype->getButtonGroups(array('container_uid' => $data['route_uid'] ?? 0));
    $data['btn_group_uid'] = $button_info['button_group_uid'];
    $data['language_id'] = $this->config->get('config_language_id');
    $this->response->setOutput($this->load->view('doctype/route_button_form', $data));
  }

  public function edit_route_action()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $this->load->model('doctype/doctype');
    $action_info = $this->model_doctype_doctype->getRouteAction($this->request->get['route_action_uid']);
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['actions'] = $this->getActions('inRouteContext');
    $data['action_name'] = $action_info['action'];
    $data['action_status'] = $action_info['status'];
    $data['action_description'] = $action_info['description'];
    $data['action_log'] = $action_info['action_log'];
    $data['action'] = $action_info['action_params'];
    $this->language->load('action/' . $action_info['action']);
    $data['action_id'] = $this->request->get['route_action_uid'];
    $data['route_uid'] = $action_info['route_uid'];
    $data['context'] = $action_info['context'];
    $data['action_general_form'] = $this->load->view($this->config->get('config_theme') . "/template/doctype/route_action_general", $data);
    $data['action_form'] = $this->load->controller('extension/action/' . $action_info['action'] . '/getForm', $data);
    $this->response->setOutput($this->load->view('doctype/route_action_form', $data));
  }

  public function add_route()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['context0'] = array();
    foreach ($this->context0 as $context => $value) {
      $data['context0'][$context]['name'] = $this->language->get('text_route_' . $context . '_name');
      $data['context0'][$context]['description'] = $this->language->get('text_route_' . $context . '_description');
      $data['context0'][$context]['icon'] = $value;
    }
    $data['contextes'] = array();
    foreach ($this->contextes as $context => $value) {
      $data['contextes'][$context]['name'] = $this->language->get('text_route_' . $context . '_name');
      $data['contextes'][$context]['description'] = $this->language->get('text_route_' . $context . '_description');
      $data['contextes'][$context]['icon'] = $value;
    }
    $this->response->setOutput($this->load->view('doctype/route_form', $data));
  }

  public function edit_route()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $this->load->model('document/document');
    $this->load->model('localisation/language');
    $this->load->model('doctype/doctype');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $data['descriptions'] = $this->model_doctype_doctype->getRouteDescriptions($this->request->get['route_uid']);
    $data_docs = array(
      'doctype_uid' => $this->request->get['doctype_uid'],
      'route_uid' => $this->request->get['route_uid'],
      'start' => 0,
      'limit' => 1
    );
    $data['has_docs'] = $this->model_document_document->getDocumentIds($data_docs) ? true : false;
    $data['doctype_uid'] = $this->request->get['doctype_uid'];
    $data['route_uid'] = $this->request->get['route_uid'];
    $data['language_id'] = $this->config->get('config_language_id');
    $this->response->setOutput($this->load->view('doctype/route_form', $data));
  }

  /**
   * Метод обработки сохранения кнопки в маршрут через Ajax
   */
  public function save_route_button()
  {
    //сохранение группы кнопок

    $button_group_uid = isset($this->request->post['btn_group_uid']) ? $this->request->post['btn_group_uid'] : '';
    $button_group_picture = isset($this->request->post['btn_group_picture']) ? $this->request->post['btn_group_picture'] : '';
    $hide_button_group_name = $this->request->post['btn_group_hide_name'];
    $button_group_color = $this->request->post['btn_group_color'];
    $button_group_background = $this->request->post['btn_group_background'];
    $button_group_descriptions = isset($this->request->post['btn_group_descriptions']) ? $this->request->post['btn_group_descriptions'] : array();

    $has_group = false;
    foreach ($button_group_descriptions as $lang_description) {
      if (!empty($lang_description) && $lang_description['name'] !== '') {
        $has_group = true;
        break;
      }
    }
    if ($button_group_picture) {
      $has_group = true;
    }
    //группа будет сохранена (обновлена) только в случае, если было введено название на каком-либо языке, либо выбрана картинка

    $this->load->model('doctype/doctype');

    if ($has_group && !empty($this->request->get['route_uid']) || $button_group_uid === '0') {
      $button_group_data = array(
        'descriptions' => $button_group_descriptions,
        'picture' => $button_group_picture,
        'hide_group_name' => isset($this->request->post['btn_group_hide_name']) ? $this->request->post['btn_group_hide_name'] : '',
        'color' => isset($this->request->post['btn_group_color']) ? $this->request->post['btn_group_color'] : '',
        'background' => isset($this->request->post['btn_group_background']) ? $this->request->post['btn_group_background'] : ''
      );


      if ($button_group_uid === '1') {
        //новая группа
        $button_group_uid = $this->model_doctype_doctype->addButtonGroup($this->request->get['route_uid'], $button_group_data);
      } elseif ($button_group_uid === '0') {
        //нет группы
        $button_group_uid = '';
      } elseif ($button_group_uid !== '') {
        //существующая группа
        $this->model_doctype_doctype->editButtonGroup($button_group_uid, $button_group_data);
      }
    } else {
      // группа не редактировалась, получаем описание группы
      $button_group = $this->model_doctype_doctype->getButtonGroup($button_group_uid);
      //print_r($button_group);
      $button_group_descriptions = $button_group['descriptions'] ?? [];
    }

    $data = $this->request->post;
    $data['btn_group_uid'] = $button_group_uid;
    if (!empty($this->request->get['button_uid'])) {
      //сохраняется существующая кнопка
      $button = $this->model_doctype_doctype->editRouteButton($this->request->get['button_uid'], $data);
      // $button_uid = $this->request->get['button_uid'];
    } elseif (!empty($this->request->get['route_uid'])) {
      //создается новая кнопка            
      $button = $this->model_doctype_doctype->addRouteButton($this->request->get['route_uid'], $data, 3);
    }

    //если есть не привязанные к кнопкам группы - удаляем
    $route_button_groups = $this->model_doctype_doctype->getButtonGroups(array('container_uid' => $this->request->get['route_uid']));
    $route_buttons = $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $this->request->get['route_uid']));
    $unbounded_groups = array();
    foreach ($route_button_groups as $route_button_group) {
      $bounded_group = false;
      foreach ($route_buttons as $route_button) {
        if ($route_button_group['button_group_uid'] === $route_button['button_group_uid']) {
          $bounded_group = true;
          break;
        }
      }
      if (!$bounded_group) {
        $unbounded_groups[] = $route_button_group['button_group_uid'];
        $this->model_doctype_doctype->removeButtonGroup($route_button_group['button_group_uid']);
      }
    }

    $this->load->model('tool/image');
    $fields = "";
    if (!empty($button['fields'])) {
      $field_names = array();
      foreach ($button['fields'] as $field) {
        $field_names[] = $field['name'];
      }
      $fields = implode(",", $field_names);
    }

    if ($this->request->post['route_button_picture']) {
      if (!empty($this->request->post['route_button_descriptions'][(int) $this->config->get('config_language_id')]['name'])) {
        $picture25 = $this->model_tool_image->resize($this->request->post['route_button_picture'], 25, 25);
      } else {
        $picture25 = $this->model_tool_image->resize($this->request->post['route_button_picture'], 36, 36);
      }
    } else {
      $picture25 = "";
    }

    $json = array(
      'route_button_uid' => $button['uid'],
      'name' => $this->request->post['route_button_descriptions'][(int) $this->config->get('config_language_id')]['name'],
      'color' => $this->request->post['route_button_color'],
      'background' => $this->request->post['route_button_background'],
      'picture25' => $picture25,
      'fields' => $fields
    );
    if ($button_group_uid) {
      $json['button_group_name'] = $button_group_descriptions[(int) $this->config->get('config_language_id')]['name'] ?? "...";
      $json['button_group_uid'] = $button_group_uid;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Метод обработки сохранения точки маршрута через Ajax
   */
  public function save_route()
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    if (!empty($this->request->get['route_uid'])) {
      //сохраняется существующая точка
      $data = $this->request->post;
      $data['doctype_uid'] = $this->request->get['doctype_uid'];
      $this->model_doctype_doctype->editRoute($this->request->get['route_uid'], $data);
      $route_uid = $this->request->get['route_uid'];
    } else {
      //создается новая точка            
      $route_uid = $this->model_doctype_doctype->addRoute($this->request->get['doctype_uid'], $this->request->post, 3);
    }

    $json = array(
      'route_uid' => $route_uid,
      'name' => $this->request->post['route_descriptions'][(int) $this->config->get('config_language_id')]['name'],
      'first' => ($this->model_document_document->getFirstRoute($this->request->get['doctype_uid']) == $route_uid)
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Метод обработки отмены удаления точки маршрута через Ajax
   */
  public function undo_remove_route()
  {
    $this->load->model('doctype/doctype');
    $this->model_doctype_doctype->undoRemoveRoute($this->request->get['route_uid']);
    $route_info = $this->model_doctype_doctype->getRoute($this->request->get['route_uid']);
    foreach (array_keys($this->contextes) as $context) {
      $actions[$context] = [];
    }
    foreach ($route_info['actions'] as $context => $value) {
      foreach ($value as $action) {
        $actions[$context][] = array(
          'route_action_uid' => $action['route_action_uid'],
          'action' => $action['action'],
          'name' => $this->load->controller('extension/action/' . $action['action'] . "/getTitle"),
          'params' => $action['params'],
          'draft' => $action['draft'],
          'description' => $action['description'] ? $action['description'] : $this->load->controller('extension/action/' . $action['action'] . "/getDescription", $action['params']),
        );
      }
    }
    $json = array(
      'route_uid' => $this->request->get['route_uid'],
      'name' => $route_info['name'],
      'descriptions' => $route_info['description'],
      'draft' => $route_info['draft'],
      'buttons' => $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $this->request->get['route_uid'])),
      'actions' => $actions
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Метод обработки сохранения действия в маршрут через Ajax
   */
  public function save_route_action()
  {
    $this->load->model('doctype/doctype');
    if (!empty($this->request->get['action_id'])) {
      //сохраняется существующее действие
      $this->model_doctype_doctype->editRouteAction($this->request->get['action_id'], $this->request->post, 1);
      $action_id = $this->request->get['action_id'];
    } elseif (!empty($this->request->get['route_uid'])) {
      //создается новая кнопка            
      $action_id = $this->model_doctype_doctype->addRouteAction($this->request->get['route_uid'], $this->request->get['context'], $this->request->post);
    }
    $description = $this->request->post['action_description'] ? $this->request->post['action_description'] : $this->load->controller('extension/action/' . $this->request->post['route_action'] . '/getDescription', $this->request->post['action']);

    $json = array(
      'route_action_uid' => $action_id,
      'name' => $this->load->controller('extension/action/' . $this->request->post['route_action'] . '/getTitle'),
      'description' => $description,
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function autocomplete_var()
  {
    $this->load->model('doctype/doctype');
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($this->model_doctype_doctype->getTemplateVariables()));
  }

  public function autocomplete_field()
  {
    $json = array();

    if (isset($this->request->get['filter_name'])) {
      $this->load->model('doctype/doctype');
      $filter_name = $this->request->get['filter_name'];


      $filter_data = array(
        'filter_name' => $filter_name,
        'doctype_uid' => $this->request->get['doctype_uid'],
        'limit' => $this->request->get['limit'] ?? 0
      );
      if (isset($this->request->get['setting'])) {
        $filter_data['setting'] = (int) $this->request->get['setting'];
      }

      if (isset($this->request->get['sort'])) {
        $filter_data['sort'] = $this->request->get['sort'];
      }
      if (isset($this->request->get['order'])) {
        $filter_data['order'] = $this->request->get['order'];
      }
      if (isset($this->request->get['access_view'])) {
        $filter_data['access_view'] = $this->request->get['access_view'];
      }
      $standard_params = array('route' => '', 'doctype_uid' => '', 'filter_name' => '', 'setting' => '', 'sort' => '', 'order' => '', 'limit' => '', '_' => '');
      $additional_params = array_diff_key($this->request->get, $standard_params);
      $results = $this->model_doctype_doctype->getFields($filter_data);

      foreach ($results as $result) {
        $additional_param_flg = true;
        if (!empty($additional_params)) {
          $field_info = $this->load->controller('extension/field/' . $result['type'] . '/getFieldInfo');

          foreach ($additional_params as $key => $param) {
            if ($param) {
              if (!(isset($field_info[$key]) && $field_info[$key] == $param)) {
                $additional_param_flg = false;
                break;
              }
            } else {
              if (isset($field_info[$key]) && $field_info[$key] != $param) {
                $additional_param_flg = false;
                break;
              }
            }
          }
        }

        if ($additional_param_flg) {

          $json[] = array(
            'field_uid' => $result['field_uid'],
            'setting' => $result['setting'],
            'name' => str_replace(array('<', '>'), "", html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
          );
        }
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  //Возвращает список методов поля
  public function get_field_methods()
  {
    $json = array();

    $this->load->model('doctype/doctype');
    $field_uid = $this->request->get['field_uid'];

    $field = $this->model_doctype_doctype->getField($field_uid, 1);
    $field_type = $field['type'];
    $methods_data = array(
      'method_type' => $this->request->get['method_type'],
      'field_uid' => $field_uid
    );
    $results = $this->load->controller('extension/field/' . $field_type . '/getFieldMethods', $methods_data);
    $json = $results;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  //Возвращает форму для установки параметров метода поля
  public function get_field_method_form()
  {
    $this->load->model('doctype/doctype');
    $field_uid = $this->request->get['field_uid'];
    if (isset($this->request->get['method'])) {
      $method = $this->request->get['method'];
    } else {
      $method = 'standard_getter';
    }
    if (isset($this->request->get['method_params_name_hierarchy'])) {
      $method_params_name_hierarchy = $this->request->get['method_params_name_hierarchy'];
    } else {
      $method_params_name_hierarchy = '';
    }
    if (isset($this->request->get['doctype_uid'])) {
      $doctype_uid = $this->request->get['doctype_uid'];
    } else {
      $doctype_uid = '';
    }
    $field = $this->model_doctype_doctype->getField($field_uid, 1);
    $field_type = $field['type'];
    $data = array(
      'method_name' => $method,
      'method_params_name_hierarchy' => $method_params_name_hierarchy,
      'field_uid' => $field_uid,
      'doctype_uid' => $doctype_uid
    );
    if (!empty($this->request->post['method_params'])) {
      //передаем значения параметров метода
      $data['method_params'] = $this->request->post['method_params'];
    }
    //$this->load->controller('extension/field/' . $field_type . '/getMethodForm', $data);
    $this->response->setOutput(json_encode($this->load->controller('extension/field/' . $field_type . '/getMethodForm', $data)));
    //$this->response->setOutput(json_encode($this->load->controller('extension/field/' . $field_type . '/getFieldMethodForm', $data)));
  }

  //Возвращает виджет для ввода значения поля
  public function get_field_widget()
  {
    $this->load->model('doctype/doctype');
    $field_uid = $this->request->get['field_uid'];
    $field = $this->model_doctype_doctype->getField($field_uid, 1);
    $field_type = $field['type'];
    $widget_data = $field['params'];
    $widget_data['field_uid'] = $field_uid;
    $widget_data['widget_name'] = $this->request->get['widget_name'] ?? "action[field_widget_value]";
    if (isset($this->request->get['field_value'])) {
      $value = html_entity_decode(htmlspecialchars_decode(urldecode($this->request->get['field_value']))); //json передается при использовании поля в Записи    
      //если значение поля начинается с кавычки, то считаем значение строкой
      if (mb_strpos(trim($value), "\"") !== 0) {
        $field_value = json_decode(html_entity_decode($value), true);
        if (json_last_error() !== 0) {
          $field_value = $value;
        }
      } else {
        $field_value = $value;
      }
      $widget_data['field_value'] = $field_value;
    }
    $this->response->setOutput($this->load->controller('extension/field/' . $field_type . '/getForm', $widget_data));
    //$test_widget = "<input name='test'/>";
    //$this->response->setOutput($test_widget);
  }

  public function autocomplete_document()
  {
    $json = array();

    if (isset($this->request->get['filter_name'])) {
      $this->load->model('doctype/doctype');
      $filter_name = $this->request->get['filter_name'];


      $filter_data = array(
        'filter_name' => $filter_name,
        'doctype_uid' => $this->request->get['doctype_uid'],
        'field_uid' => $this->request->get['field_uid']
      );

      $results = $this->model_doctype_doctype->getDocuments($filter_data);

      foreach ($results as $result) {

        $json[] = array(
          'document_uid' => $result['document_uid'],
          'name' => strip_tags(html_entity_decode($result['display_value'], ENT_QUOTES, 'UTF-8')),
        );
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function autocomplete_route()
  {
    $json = array();
    if (isset($this->request->get['filter_name'])) {


      $this->load->model('doctype/doctype');
      $filter_name = $this->request->get['filter_name'];

      // если указан field_uid, то получаем doctype_uid из настроек поля

      if (isset($this->request->get['doctype_uid'])) {
        $filter_data = array(
          'filter_name' => $filter_name,
          'doctype_uid' => $this->request->get['doctype_uid']
        );
      } else {
        $filter_data = array(
          'filter_name' => $filter_name,
        );
      }

      $results = $this->model_doctype_doctype->getRoutes($filter_data);
      if (!empty($this->request->get['doctype_uid'])) {
        foreach ($results as $result) {
          $json[] = array(
            'route_uid' => $result['route_uid'],
            'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
          );
        }
      } else {
        foreach ($results as $result) {
          $json[] = array(
            'route_uid' => $result['route_uid'],
            'name' => strip_tags(html_entity_decode($result['doctype_name'] . " - " . $result['name'], ENT_QUOTES, 'UTF-8')),
          );
        }
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function autocomplete_button()
  {
    $this->load->model('doctype/doctype');
    $this->load->language('doctype/doctype');
    $json = array();
    $filter_data = array(
      'filter_name' => $this->request->get['filter_name'] ?? "",
      'doctype_uid' => $this->request->get['doctype_uid'] ?? 0,
      'route_uid' => $this->request->get['route_uid'] ?? 0
    );
    $routes = array();
    foreach ($this->model_doctype_doctype->getRouteButtons($filter_data) as $button) {
      if ($button['name']) {
        $name = $button['name'];
      } else {
        $name = $this->language->get('button_no_name');
      }
      if (empty($this->request->get['route_uid'])) {
        if (!isset($routes[$button['route_uid']])) {
          $route_info = $this->model_doctype_doctype->getRoute($button['route_uid']);
          $routes[$button['route_uid']] = [
            'name' => $route_info['name'],
            'sort' => $route_info['sort']
          ];
        }
        $json[] = array(
          'button_uid' => $button['route_button_uid'],
          'name' => strip_tags(html_entity_decode($routes[$button['route_uid']]['name'] . " - " . $name, ENT_QUOTES, 'UTF-8')),
          'sort' => $routes[$button['route_uid']]['sort'] ?? 0
        );
      } else {
        $json[] = array(
          'button_uid' => $button['route_button_uid'],
          'name' => strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8')),
          'sort' => 0
        );
      }
    }
    usort($json, function ($a, $b) {
      return $a['sort'] <=> $b['sort'];
    });
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Метод, возвращающий массив с установленными в системе полями
   * @return type
   */
  public function getFieldtypes()
  {
    $this->load->model('tool/utils');
    $this->load->model('setting/extension');
    $extensions = $this->model_setting_extension->getInstalled('field');
    $fields = array();
    foreach ($extensions as $field) {
      $fields[] = array(
        'name' => $field,
        'title' => $this->load->controller('extension/field/' . $field . '/getTitle')
      );
    }
    usort($fields, function ($a, $b) {
      return $this->model_tool_utils->sortCyrLat($a['title'], $b['title']);
    });
    return $fields;
  }

  /**
   * Метод, возвращающий массив с установленными в системе действиями
   * $context - inRouteContextButton или inRouteContext
   * @return type
   */
  public function getActions($context)
  {
    $this->load->model('tool/utils');
    $this->load->model('setting/extension');
    $extensions = $this->model_setting_extension->getInstalled('action');
    $actions = array();
    foreach ($extensions as $action) {
      $action_info = $this->load->controller('extension/action/' . $action . '/getActionInfo');
      if (isset($action_info[$context]) && $action_info[$context] === true) {
        $action_info['title'] = $this->load->controller('extension/action/' . $action . '/getTitle');
        $actions[] = $action_info;
      }
    }
    usort($actions, function ($a, $b) {
      return $this->model_tool_utils->sortCyrLat($a['title'], $b['title']);
    });
    return $actions;
  }

  public function window_field()
  {
    $data = array();
    $this->load->language('doctype/doctype');
    $data['fields'] = $this->getFieldtypes();
    $data['doctype_uid'] = !empty($this->request->get['doctype_uid']) ? $this->request->get['doctype_uid'] : 0;
    //поле настроек или нет
    if (!empty($this->request->get['setting'])) {
      $data['setting'] = 1;
    } else {
      $data['setting'] = 0;
    }
    if (empty($this->request->get['field_uid'])) {
      //инициализация атрибутов нового поля
      $data['history'] = '0';
    }
    $data['title'] = $this->language->get('text_title_field_list');
    $this->response->setOutput($this->load->view('doctype/field_form', $data));
  }

  /**
   * Метод для получения поля типа документа для редактирования
   */
  public function edit_field()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->language('doctype/doctype');
      $this->load->model('doctype/doctype');
      $field = $this->model_doctype_doctype->getField($this->request->get['field_uid'], 1);

      $data['params'] = $field['params'];
      $data['doctype_uid'] = $field['doctype_uid'];
      $data['field_name'] = $field['name'];
      $data['field_description'] = $field['description'];
      $data['field_uid'] = $field['field_uid'];
      $data['field_type'] = $field['type'];
      $data['field_type_name'] = $this->load->controller("extension/field/" . $field['type'] . "/getTitle");
      $data['form_twig'] = $this->load->controller('/extension/field/' . $field['type'] . '/getAdminForm', $data);
      $data['title'] = $field['name'];
      $data['setting'] = $this->request->get['setting'];
      $data['change_field'] = $field['change_field'];
      $data['required'] = $field['required'];
      $data['unique'] = $field['unique'];
      $data['ft_index'] = $field['ft_index'];
      $data['history'] = $field['history'];
      foreach (array('form', 'view') as $mode) {
        if (!empty($field['access_' . $mode])) {
          $access = $field['access_' . $mode];
          $data['access_' . $mode] = array();
          foreach ($access as $field_uid) {
            $field_info = $this->model_doctype_doctype->getField($field_uid);
            // $name = "";
            // if ($field_info['setting']) {
            //   $doctype_info = $this->model_doctype_doctype->getDoctype($field_info['doctype_uid']);
            //   $name = $doctype_info['name'] . " - ";
            // }
            // $name .= $field_info['name'];
            if (isset($field_info['name'])) {
              $data['access_' . $mode][] = array(
                'field_uid' => $field_uid,
                'name' => $field_info['name']
              );
            }
          }
        }
      }

      $this->load->language('doctype/doctype');
      $this->response->setOutput($this->load->view('doctype/field_form', $data));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  public function get_usage()
  {
    if (empty($this->request->get['field_uid'])) {
      return;
    }
    $data = [
      'usage' => []
    ];
    $this->load->model('doctype/folder');
    $this->load->language('doctype/doctype');
    foreach ($this->model_doctype_doctype->getUsageField($this->request->get['field_uid']) as $usage_name => $usage_value) {
      switch ($usage_name) {
        case 'action':
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['doctype'],
              'value'   => $value['route'] . "." . $this->language->get('text_route_' . $value['context'] . '_name') . "->" . $this->load->controller('extension/action/' . $value['action'] . "/getTitle")
            ];
          }
          break;
        case 'field':
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['doctype'],
              'value'   => $value['field']
            ];
          }
          break;
        case 'button':
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['doctype'],
              'value'   => $value['route'] . "." . $value['button']
            ];
          }
          break;
        case 'template':
          foreach ($usage_value as $value) {
            $template_name = $this->language->get('template_name')[$value['type']] . " " . ($value['template_name'] ?? "");
            $data['usage'][$usage_name][] = [
              'module' => $value['doctype'],
              'value'   => $template_name
            ];
          }
          break;
        case 'f_field':
          foreach ($usage_value as $value) {
            if ($value['grouping']) {
              $data['usage'][$usage_name][] = [
                'module' => $value['folder'],
                'value'   => $value['grouping_name'] ? $value['grouping_name'] : $this->language->get('text_noname')
              ];
            }
            if ($value['tcolumn']) {
              $data['usage'][$usage_name][] = [
                'module' => $value['folder'],
                'value'   => $value['tcolumn_name'] ? $value['tcolumn_name'] : $this->language->get('text_noname')
              ];
            }
          }
          break;
        case 'f_button':
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['folder'],
              'value'   => $value['button']
            ];
          }
          break;
        case 'f_filter':
          $actions = [];
          foreach ($this->model_doctype_folder->getFilterActions() as $action) {
            $actions[$action['value']] = $action['title'];
          }
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['folder'],
              'value'   => $actions[$value['action']] ?? ""
            ];
          }
          break;
        case 'f_template':
          foreach ($usage_value as $value) {
            $data['usage'][$usage_name][] = [
              'module' => $value['folder'],
              'value'   => ""
            ];
            break;
          }
      }
    }
    $this->response->setOutput($this->load->view('doctype/field_usage', $data));
  }

  /**
   * Метод для изменения значения поля (настройки) типа документа 
   */
  public function edit_field_value()
  {
    if (!empty($this->request->get['field_uid'])) {
      $data = array();
      $this->load->model('doctype/doctype');
      $this->load->model('document/document');
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid'], 1);
      $data['title'] = $field_info['name'];
      $data['field_uid'] = $this->request->get['field_uid'];
      $params = $field_info['params'];
      $params['field_uid'] = $this->request->get['field_uid'];
      $params['document_uid'] = 0; //у setting-поле document_uid=0            
      $params['compact_form'] = TRUE;
      //            $widget_data['value'] = $data['value'];
      $params['field_value'] = $this->model_document_document->getFieldValue($params['field_uid'], 0, TRUE);
      $data['form'] = $this->load->controller('extension/field/' . $field_info['type'] . '/getForm', $params);

      $this->load->language('doctype/doctype');
      $this->response->setOutput($this->load->view('doctype/field_value_form', $data));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Метод возвращает форму для настройки поля
   */
  public function get_form_field()
  {
    if (!empty($this->request->get['field_type'])) {
      $data = array(
        'doctype_uid' => $this->request->get['doctype_uid'] ?? 0
      );
      $field_type = $this->request->get['field_type'];
      $this->response->addHeader("Content-type: application/json");

      $this->response->setOutput(json_encode($this->load->controller('extension/field/' . $field_type . '/getAdminForm', $data)));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Метод возвращает форму для настройки действия через Ajax
   */
  public function get_form_action()
  {
    if (!empty($this->request->get['action'])) {
      $this->load->language('doctype/doctype');
      $data = array(
        'context' => !empty($this->request->get['context']) ? $this->request->get['context'] : "",
        'folder' => !empty($this->request->get['folder']) ? $this->request->get['folder'] : "",
        'action_name' => $this->request->get['action'],
        'action_status' => 1,
        'route_uid' => isset($this->request->get['route_uid']) ? $this->request->get['route_uid'] : 0,
        'doctype_uid' => $this->request->get['doctype_uid'],
      );
      $this->response->setOutput(json_encode($this->load->controller('extension/action/' . $this->request->get['action'] . '/getForm', $data)));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Добавление поля в тип документа,
   * тип поля и его настройки передаются через POST
   */
  public function add_field()
  {
    //проверяем данные и передаем контроллеру соответствующего поля
    if (!empty($this->request->post['field_type'])) {
      $this->load->language('doctype/doctype');
      $this->load->model('doctype/doctype');
      if (isset($this->request->post['field'])) { //если поле без настроек, то массива field не будет
        $data = $this->request->post['field'];
      } else {
        $data = array();
      }
      $data['field_type'] = $this->request->post['field_type'];
      $data['field_name'] = $this->request->post['field_name'];
      $data['description'] = $this->request->post['field_description'];
      if (!empty($this->request->get['setting'])) {
        $data['setting'] = 1;
      } else {
        $data['setting'] = '0';
      }

      $attr = [];
      foreach ($this->attr as $attr_name => $attr_info) {
        $post_attr = $this->request->post[$attr_name] ?? $this->request->post['field_' . $attr_name] ?? "";
        if (!empty($post_attr)) {
          $data[$attr_name] = $post_attr;
          $attr[] = [
            'text' => $this->language->get("text_attr_" . $attr_name),
            'pic'  => $attr_info['pic']
          ];
        } else {
          $data[$attr_name] = $attr_info['default'];
        }
      }

      $field_uid = $this->model_doctype_doctype->addField($this->request->get['doctype_uid'] ?? 0, $data);
      //возвращаем массив с данными, достаточными для добавления новой строки в таблице полей
      $params = array();
      foreach ($data as $key => $value) {
        if ($key != "field_type" && $key != "field_name" && $key != "sort") {
          $params[$key] = $value;
        }
      }
      $json = array(
        'doctype_field_uid' => $field_uid,
        'field_name' => $this->request->post['field_name'],
        'field_type' => $this->request->post['field_type'],
        'field_setting' => $data['setting'],
        'field_type_title' => $this->load->controller('extension/field/' . $this->request->post['field_type'] . '/getTitle'),
        'params_description' => $this->request->post['field_description'] ? $this->request->post['field_description'] : $this->load->controller('extension/field/' . $this->request->post['field_type'] . '/getDescriptionParams', $params),
        'sort' => 0,
        'attributes' => $attr
      );
      $this->load->language('doctype/doctype');

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Сохранение параметров типа документа
   */
  public function save_doctype()
  {
    $result = [];
    if (!empty($this->request->get['doctype_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->saveDoctype($this->request->get['doctype_uid'], $this->request->post);
      $result['success'] = "OK";
    } else {
      $result['error'] = "doctype_uid not found";
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($result));
  }

  /**
   * Сохранение изменного поля типа документа
   */
  public function save_field()
  {
    //проверяем данные и передаем контроллеру соответствующего поля

    if (empty($this->request->get['field_uid'])) {
      return;
    }
    $this->load->language('doctype/doctype');
    $post_data = $this->request->post;
    $data = array();
    if (!empty($post_data['field'])) {
      $data = $post_data['field'];
    }
    $data['field_name'] = $post_data['field_name'];
    $data['description'] = $post_data['field_description'];
    $data['field_type'] = $post_data['field_type'];
    if (!empty($post_data['change_field'])) {
      $data['change_field'] = 1;
    } else {
      $data['change_field'] = 0;
    }
    $data['access_form'] = $post_data['field_access_form'] ?? array();
    $data['access_view'] = $post_data['field_access_view'] ?? array();
    if (!empty($post_data['required'])) {
      $data['required'] = 1;
    } else {
      $data['required'] = 0;
    }
    if (!empty($post_data['unique'])) {
      $data['unique'] = 1;
    } else {
      $data['unique'] = 0;
    }
    if (!empty($post_data['ft_index'])) {
      $data['ft_index'] = 1;
    } else {
      $data['ft_index'] = 0;
    }
    if (!empty($post_data['history'])) {
      $data['history'] = 1;
    } else {
      $data['history'] = 0;
    }
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->editField($this->request->get['field_uid'], $data);

    //возвращаем массив с данными, достаточными для добавления новой строки в таблице полей
    $params = array();
    foreach ($data as $key => $value) {
      if ($key != "field_type" && $key != "field_name" && $key != "sort") {
        $params[$key] = $value;
      }
    }

    $attr = [];
    foreach ($this->attr as $attr_name => $attr_info) {
      if (!empty($data[$attr_name])) {
        $attr[] = [
          'text' => $this->language->get("text_attr_" . $attr_name),
          'pic'  => $attr_info['pic']
        ];
      }
    }


    $json = array(
      'doctype_field_uid' => $this->request->get['field_uid'],
      'field_name' => $post_data['field_name'],
      'field_type' => $post_data['field_type'],
      'field_type_title' => $field_info['type_title'],
      'params_description' => $field_info['params_description'],
      'attributes' => $attr,
      'sort' => $field_info['sort']
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }


  /**
   * Сохранение значения сеттинг-поля типа документа
   */
  public function save_field_value()
  {
    if (isset($this->request->get['field_uid'])) {
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid'], 1);
      if (isset($this->request->post['field'][$this->request->get['field_uid']])) {
        $this->model_document_document->editFieldValue($this->request->get['field_uid'], 0, $this->request->post['field'][$this->request->get['field_uid']]);
      } else {
        //значение не передано, обнуляем в базе
        $this->model_document_document->editFieldValue($this->request->get['field_uid'], 0, '');
      }
      //Подготавливаем данные для передачи в метод getView
      $data = $field_info['params'];
      /* //поле настроек, документ-айди = 0 */
      $data['field_value'] = $this->model_document_document->getFieldValue($this->request->get['field_uid'], 0);
      $data['document_uid'] = 0;
      $data['field_uid'] = $this->request->get['field_uid'];
      $json = $this->load->controller('extension/field/' . $field_info['type'] . "/getView", $data);
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  /**
   * Возвращает отображаемое значение поля на основании value
   */
  public function get_field_view()
  {
    if (isset($this->request->get['field_uid']) && isset($this->request->get['field_value'])) {
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid'], 1);
      //Подготавливаем данные для передачи в метод getView
      $data = $field_info['params'];
      $data['field_value'] = $this->request->get['field_value'];
      $data['document_uid'] = 0;
      $data['field_uid'] = $this->request->get['field_uid'];
      $json = $this->load->controller('extension/field/' . $field_info['type'] . "/getView", $data);
    } else {
      $json = array();
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  /**
   * Удаление черновика доктайпа
   */
  public function remove_draft()
  {
    if (!empty($this->request->get['doctype_uid'])) {
      $this->load->model('doctype/doctype');
      if ($this->model_doctype_doctype->removeDraft($this->request->get['doctype_uid'])) {
        $this->response->redirect($this->url->link('doctype/doctype/edit', 'doctype_uid=' . $this->request->get['doctype_uid'], true));
      } else {
        $this->getList();
      }
    } else {
      $this->getList();
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Пометка поля на удаление
   */
  public function remove_field()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->removeField($this->request->get['field_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Снятие с поля пометки на удаление
   */
  public function undo_remove_field()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->undoRemoveField($this->request->get['field_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Пометка точки маршрута на удаление
   */
  public function remove_route()
  {
    if (!empty($this->request->get['route_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->removeRoute($this->request->get['route_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Пометка кнопки маршрута на удаление
   */
  public function remove_route_button()
  {
    if (!empty($this->request->get['route_button_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->removeRouteButton($this->request->get['route_button_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Снятие пометки на удаление с кнопки маршрута
   */
  public function undo_remove_route_button()
  {
    if (!empty($this->request->get['route_button_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->undoRemoveRouteButton($this->request->get['route_button_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Пометка действия маршрута на удаление
   */
  public function remove_route_action()
  {
    if (!empty($this->request->get['route_action_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->removeRouteAction($this->request->get['route_action_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  /**
   * Снятие пометки на удаление с действия маршрута
   */
  public function undo_remove_route_action()
  {
    if (!empty($this->request->get['route_action_uid'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->undoRemoveRouteAction($this->request->get['route_action_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode([]));
    }
  }

  public function disable_route_action()
  {
    $json = ['success' => 0];
    if (!empty($this->request->get['route_action_uid']) && isset($this->request->get['status'])) {
      $this->load->model('doctype/doctype');
      $this->model_doctype_doctype->editStatusRouteAction($this->request->get['route_action_uid'], $this->request->get['status']);
      $json['success'] = 1;
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function move_field()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid']);
      $fields = $this->model_doctype_doctype->getFields(array(
        'doctype_uid' => $field_info['doctype_uid'],
        'setting' => $field_info['setting']
      ));
      if ($this->request->get['direction'] == "up") { //сортируем массив по sort по убыванию, если перемещение вниз
        $fields = array_reverse($fields);
      }
      $next = FALSE;
      $field_target = array(); //поле, с которым нужно поменять местами перемещаемое поле
      $field_source = array(); //перемещаемое поле
      foreach ($fields as $field) {
        if ($next) {
          //меняем местами с этим полем
          $field_target = $field;
          break;
        }
        if ($field['field_uid'] == $this->request->get['field_uid']) {
          $field_source = $field;
          $next = TRUE;
        }
      }
      //обновляем sort поле
      if (isset($field_target['sort'])) { //целевого поля может не быть, если перемещаемое поле первое или последнее
        $this->model_doctype_doctype->editSortField($field_target['field_uid'], $field_source['sort']);
        $this->model_doctype_doctype->editSortField($field_source['field_uid'], $field_target['sort']);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => $field_target['sort'])));
        return;
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Перемещение действия в админке (вверх-вниз)
   */
  public function move_action()
  {
    if (!empty($this->request->get['action_id'])) {
      $this->load->model('doctype/doctype');
      $action_info = $this->model_doctype_doctype->getRouteAction($this->request->get['action_id']);

      $actions = $this->model_doctype_doctype->getRouteActions($action_info['route_uid'], $action_info['context']);
      if ($this->request->get['direction'] == "up") { //сортируем массив по sort по убыванию, если перемещение вниз
        $actions = array_reverse($actions);
      }
      $next = FALSE;
      $action_target = array(); //поле, с которым нужно поменять местами перемещаемое поле
      $action_source = array(); //перемещаемое поле
      foreach ($actions as $action) {
        if ($next) {
          //меняем местами с этим полем
          $action_target = $action;
          break;
        }
        if ($action['route_action_uid'] == $this->request->get['action_id']) {
          $action_source = $action;
          $next = TRUE;
        }
      }
      //обновляем sort действий
      if (isset($action_target['sort'])) { //целевого действия может не быть, если перемещаемое действие первое или последнее
        $this->model_doctype_doctype->editSortRouteAction($action_target['route_action_uid'], $action_source['sort']);
        $this->model_doctype_doctype->editSortRouteAction($action_source['route_action_uid'], $action_target['sort']);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => '1')));
        return;
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Перемещение кнопки в админке (вверх-вниз)
   */
  public function move_button()
  {
    if (!empty($this->request->get['button_uid'])) {
      $this->load->model('doctype/doctype');
      $button_info = $this->model_doctype_doctype->getRouteButton($this->request->get['button_uid']);
      $buttons = $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $button_info['route_uid']));
      if ($this->request->get['direction'] == "up") { //сортируем массив по sort по убыванию, если перемещение вниз
        $buttons = array_reverse($buttons);
      }
      $next = FALSE;
      $button_target = array(); //поле, с которым нужно поменять местами перемещаемое поле
      $button_source = array(); //перемещаемое поле
      foreach ($buttons as $button) {
        if ($next) {
          //меняем местами с этим полем
          $button_target = $button;
          break;
        }
        if ($button['route_button_uid'] == $this->request->get['button_uid']) {
          $button_source = $button;
          $next = TRUE;
        }
      }
      //обновляем sort действий
      if (isset($button_target['sort'])) { //целевого действия может не быть, если перемещаемое действие первое или последнее
        $this->model_doctype_doctype->editSortRouteButton($button_target['route_button_uid'], $button_source['sort']);
        $this->model_doctype_doctype->editSortRouteButton($button_source['route_button_uid'], $button_target['sort']);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => '1')));
        return;
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([]));
  }

  /**
   * Метод для обновления displayValue полей после изменения их параметров через демон
   * @param type $data
   */
  public function executeRefreshDisplay($data)
  {
    if (empty($data['field_type'])) {
      echo " Not found type of field " . ($data['field_uid'] ?? "");
      return;
    }
    $model = "model_extension_field_" . $data['field_type'];
    $this->load->model('extension/field/' . $data['field_type']);
    echo " FUID=" . $data['field_uid'] . " [" . $data['field_type'] . "]";
    $this->$model->refreshDisplayValues($data);
  }
}

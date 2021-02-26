<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerDoctypeFolder extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('doctype/folder', '', true, true));

    $this->load->language('doctype/folder');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('doctype/folder');

    $this->getList();
  }

  public function addFolder()
  {
    $this->load->model('setting/extension');
    $this->load->language('doctype/folder');
    $extensions = $this->model_setting_extension->getInstalled('folder');
    if (($this->request->server['REQUEST_METHOD'] == "POST" && isset($this->request->post['folder_type'])) || !$extensions) {
      $this->load->model('doctype/folder');
      $folder_type = $this->request->post['folder_type'] ?? "";
      $folder_uid = $this->model_doctype_folder->addFolder($folder_type);
      if ($folder_type) {
        $this->response->redirect($this->url->link("extension/folder/" . $folder_type . "/edit", 'folder_uid=' . $folder_uid, true));
      } else {
        $this->response->redirect($this->url->link('doctype/folder/edit', 'folder_uid=' . $folder_uid, true));
      }
    } else {

      $this->document->setTitle($this->language->get('title_select_folder_type'));
      $data = [
        'folder_types' => []
      ];
      foreach ($extensions as $ext) {
        $data['folder_types'][] = array(
          'type'  => $ext == "standard" ? "" : $ext,
          'title' => $ext == "standard" ? $this->language->get('text_standard_type') : $this->load->controller('extension/folder/' . $ext . "/getTitle")
        );
      }
      $data['action'] = $this->url->link('doctype/folder/addFolder');
      $data['header'] = $this->load->controller('common/header');
      $data['footer'] = $this->load->controller('common/footer');
      $this->response->setOutput($this->load->view('doctype/folder_type', $data));
    }
  }

  public function getList()
  {
    //кнопки

    $data['add'] = $this->url->link('doctype/folder/addFolder', '', true);
    $data['copy'] = $this->url->link('doctype/folder/copy', '', true);
    $data['delete'] = $this->url->link('doctype/folder/delete', '', true);
    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];
      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    $sort = $this->request->get['sort'] ?? "fd.name";
    $order = $this->request->get['order'] ?? "ASC";
    $filter_data = array(
      'sort'  => $sort,
      'order' => $order
    );
    $results = $this->model_doctype_folder->getFolders($filter_data);
    $data['folders'] = array();

    foreach ($results as $result) {
      $data['folders'][] = array(
        'folder_uid' => $result['folder_uid'],
        'name' => $result['name'],
        'description' => $result['short_description'],
        'date_added' => $result['date_added'],
        'date_edited' => $result['date_edited'],
        'user_uid' => $result['user_uid'],
        'edit' => $this->url->link(($result['type'] ? 'extension/folder/' . $result['type'] : 'doctype/folder') . '/edit', 'folder_uid=' . $result['folder_uid'], true)
      );
    }

    $url = "";

    if ($order == 'ASC') {
      $url .= '&order=DESC';
    } else {
      $url .= '&order=ASC';
    }

    $data['sort_name'] = $this->url->link('doctype/folder', 'sort=fd.name' . $url, true);
    $data['sort_short_description'] = $this->url->link('doctype/folder', 'sort=fd.short_description' . $url, true);
    $data['sort_date_added'] = $this->url->link('doctype/folder', 'sort=f.date_added' . $url, true);
    $data['sort_date_edited'] = $this->url->link('doctype/folder', 'sort=f.date_edited' . $url, true);
    $data['sort'] = $sort;
    $data['order'] = $order;
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->load->language('doctype/folder');
    $this->response->setOutput($this->load->view('doctype/folder_list', $data));
  }

  public function edit()
  {
    $this->load->language('doctype/folder');

    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('doctype/folder/edit' . '&folder_uid=' . ($this->request->get['folder_uid'] ?? ""), '', true, true));


    $this->load->model('doctype/folder');


    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $this->model_doctype_folder->editFolder($this->request->get['folder_uid']);
      $this->response->redirect($this->url->link('doctype/folder/edit', 'folder_uid=' . $this->request->get['folder_uid'] . $this->request->post['active_tab'] ?? "", true));
    }

    $this->document->addStyle('view/javascript/colorpicker/css/colorpicker.css');
    $this->document->addScript('view/javascript/colorpicker/js/colorpicker.js');
    $this->getForm();
  }

  public function delete()
  {
    $this->load->language('doctype/folder');
    if (isset($this->request->post['folder_selected'])) {
      $this->load->model('doctype/folder');
      foreach ($this->request->post['folder_selected'] as $folder_uid) {
        $this->model_doctype_folder->deleteFolder($folder_uid);
      }
      $this->session->data['success'] = $this->language->get('text_success');
      $this->response->redirect($this->url->link('doctype/folder', "", true));
    }

    $this->getList();
  }

  protected function getForm()
  {
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $data['text_form'] = !isset($this->request->get['folder_uid']) ? $this->language->get('text_add') : $this->language->get('text_edit');
    $folder_uid = !empty($this->request->get['folder_uid']) ? $this->request->get['folder_uid'] : 0;

    $folder_info = $this->model_doctype_folder->getFolder($folder_uid);
    if (!$folder_info) {
      $this->response->redirect($this->url->link('doctype/folder/addFolder', "", true));
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

    if (!isset($this->request->get['folder_uid'])) {
      $data['action'] = $this->url->link('doctype/folder/add', '', true);
    } else {
      $data['action'] = $this->url->link('doctype/folder/edit', 'folder_uid=' . $this->request->get['folder_uid'], true);
      $data['link_folder'] = $this->url->link('document/folder', 'folder_uid=' . $this->request->get['folder_uid'], true);
    }

    $data['cancel'] = $this->url->link('doctype/folder', '', true);

    if (isset($this->request->get['folder_uid'])) {
      $this->document->setTitle($this->language->get('text_folder') . " " . $folder_info['name']);
      $data['folder_uid'] = $this->request->get['folder_uid'];
      $data['heading_title'] = $this->language->get('text_folder') . " " . $folder_info['name'];
    } else {
      $this->document->setTitle($this->language->get($this->language->get('text_folder')));
      $data['heading_title'] = $this->language->get('text_folder');
    }

    if ($folder_info['draft'] == 1 || ($folder_info['draft'] == 3)) {
      $data['draft_message'] = sprintf($this->language->get('text_draft_message'), $this->url->link('doctype/folder/remove_draft', 'folder_uid=' . $folder_uid));
    }
    $data['disable_change_doctype'] = ($folder_info['doctype_uid'] && $folder_info['draft'] != 3);
    $this->load->model('localisation/language');

    $data['languages'] = $this->model_localisation_language->getLanguages();


    if (!empty($folder_info)) {
      $data['date_added'] = ($folder_info['date_added'] != '0000-00-00') ? $folder_info['date_added'] : '';
      $data['date_edited'] = ($folder_info['date_edited'] != '0000-00-00') ? $folder_info['date_edited'] : '';
      $data['folder_description'] = $this->model_doctype_folder->getFolderDescriptions($this->request->get['folder_uid']);
      $data['additional_params'] = $folder_info['additional_params'];
      if ($folder_info['doctype_uid']) {
        $descriptions =  $this->model_doctype_doctype->getDoctypeDescriptions($folder_info['doctype_uid']);
        if ($descriptions) {
          $data['doctype_name'] = $descriptions[$this->config->get('config_language_id')]['name'];
          $data['doctype_uid'] = $folder_info['doctype_uid'];
        } else {
          $data['doctype_name'] = "";
          $data['doctype_uid'] = "";
        }
      } else {
        $data['doctype_name'] = "";
        $data['doctype_uid'] = 0;
      }
      $data['folder_name'] = $folder_info['name'];
      $data_tf = array(
        'folder_uid' => $folder_uid,
        'tcolumn'   => 1,
        'sort'      => 'tcolumn'
      );
      $data['table_fields'] = $this->model_doctype_folder->getFields($data_tf);

      $data_gr = array(
        'folder_uid' => $folder_uid,
        'grouping'  => 1,
        'sort'      => "grouping"
      );
      $group_fields = $this->model_doctype_folder->getFields($data_gr);
      $gf = array();
      $data['group_fields'] = array();
      //добавляем уровни дерева
      $this->load->model('localisation/language');
      foreach ($this->model_localisation_language->getLanguages() as $language) {
        if (!empty($group_fields[$language['language_id']])) {
          foreach ($group_fields[$language['language_id']] as $field) {
            $gf[$language['language_id']][$field['folder_field_uid']] = $field;
          }
          foreach ($group_fields[$language['language_id']] as $field) {
            if ($field['grouping_parent_uid']) {
              $parent_id = $field['grouping_parent_uid'];
              while ($parent_id) {
                if ($field['folder_field_uid'] == $parent_id) {
                  break;
                }
                if (!isset($gf[$language['language_id']][$field['folder_field_uid']]['level'])) {
                  $gf[$language['language_id']][$field['folder_field_uid']]['level'] = 0;
                }
                $gf[$language['language_id']][$field['folder_field_uid']]['level']++;
                if (isset($gf[$language['language_id']][$parent_id])) {
                  $parent_id = $gf[$language['language_id']][$parent_id]['grouping_parent_uid'];
                } else {
                  $parent_id = 0;
                }
              }
            }
          }
        }
      }
      $data['group_fields'] = $gf;
      $data['buttons'] = $this->model_doctype_folder->getButtons($folder_uid);
      $filters = $this->model_doctype_folder->getFilters($folder_uid);
      $data['filters'] = array();
      foreach ($filters as $filter) {
        $field_info = $this->model_doctype_doctype->getField($filter['field_uid']);
        if ($filter['type_value'] == 'input') {
          //готовим виджет просмотра
          $d = $field_info['params'];
          $d['field_value'] = $filter['value'];
          $d['filter_view'] = 1; //разработчик поля может изменить view на основании наличия этого параметра
          $d['document_uid'] = 0;
          $d['field_uid'] = $filter['field_uid'];
          $value = strip_tags($this->load->controller('extension/field/' . $field_info['type'] . '/getView', $d));
        } else {
          $value = $this->language->get('text_' . $filter['value']);
        }
        $data['filters'][] = array(
          'filter_id'         => $filter['folder_filter_uid'],
          'field_name'        => $field_info['name'],
          'condition'         => $this->language->get('text_condition_' . $filter['condition']),
          'value'             => $value,
          'action'            => $filter['action'],
          'action_title'      => $filter['action'] ? $this->language->get('text_action_' . $filter['action']) : $this->language->get('text_none'),
          'action_params'     => $filter['action_params'],
          'draft'             => $filter['draft']
        );
      }
    } else {
      $time = new DateTime('now');
      $now = $time->format('Y-m-d H:i:s');
      $data['date_added'] = $now;
      $data['folder_description'] = array();
      $data['date_edited'] = $now;
      $data['doctype_uid'] = "0";
      $data['doctype_name'] = "";
      $data['fields'] = array();
      $data['buttons'] = array();
      $data['filters'] = array();
    }
    $data['language_id'] = $this->config->get('config_language_id');
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('doctype/folder_form', $data));
  }

  public function validateForm()
  {
    return true;
  }

  public function window_field()
  {
    $this->load->language('doctype/folder');
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $data = array();

    if (!empty($this->request->get['folder_field_uid'])) {
      //редактирование поля
      $folder_field_info = $this->model_doctype_folder->getField($this->request->get['folder_field_uid']);
      $data['folder_field_uid'] = $this->request->get['folder_field_uid'];
      $data['part'] = $this->request->get['part'] ?? ""; //из какой части вызвано поле для редактирования - табличной или группировки
      $data['field_name'] = $folder_field_info['field_name'];
      $folder_info = $this->model_doctype_folder->getFolder($folder_field_info['folder_uid']);
      $data['doctype_uid'] = $folder_info['doctype_uid'];
      $data['folder_uid'] = $folder_field_info['folder_uid'];
      $data['field_uid'] = $folder_field_info['field_uid'];
      // $data['draft'] = array(0, 1);
      $data['draft'] = 0;
      $data['language_id'] = $folder_field_info['language_id'];
      $data_parents = $data;


      $data['fields'] = $this->model_doctype_doctype->getFields($data);
      $data['add_fields'] = array_merge(array($folder_field_info), $this->model_doctype_folder->getDoctypeFieldsWithoutFolder($folder_field_info['folder_uid'], $folder_field_info['language_id']));
      $data['grouping'] = $folder_field_info['grouping'];
      $data['grouping_name'] = $folder_field_info['grouping_name'];

      $data_parents['grouping']  = 1;
      $data_parents['grouping_tree_uid']  = 0;
      $data_parents['grouping_parent_uid']  = 0;
      $grouping_fields = $this->model_doctype_folder->getFields($data_parents);

      $children_fields = $this->model_doctype_folder->getChildrenIds($this->request->get['folder_field_uid']);
      $data['grouping_parents'] = array();
      foreach ($grouping_fields as $field) {
        if (!in_array($field['folder_field_uid'], $children_fields)) {
          $data['grouping_parents'][] = $field;
        }
      }

      $data['grouping_parent_uid'] = $folder_field_info['grouping_parent_uid'];
      $data['grouping_tree_uid'] = $folder_field_info['grouping_tree_uid'];
      $data['tcolumn'] = $folder_field_info['tcolumn'];
      $data['tcolumn_name'] = $folder_field_info['tcolumn_name'];
      $data['tcolumn_width'] = $folder_field_info['tcolumn_width'] ?? "";
      $data['tcolumn_total'] = $folder_field_info['tcolumn_total'] ?? "";
      $data['tcolumn_hidden'] = $folder_field_info['tcolumn_hidden'] ?? "";
      $this->response->setOutput($this->load->view('doctype/folder_field_window', $data));
    } elseif (!empty($this->request->get['doctype_uid']) && !empty($this->request->get['folder_uid'])) {
      //новое поле
      $data['doctype_uid'] = $this->request->get['doctype_uid'];
      $data['folder_uid'] = $this->request->get['folder_uid'];
      $data['fields'] = $this->model_doctype_doctype->getFields($data);
      $data['add_fields'] = $this->model_doctype_folder->getDoctypeFieldsWithoutFolder($this->request->get['folder_uid'], $this->request->get['language_id']);

      $data['language_id'] = $this->request->get['language_id'];
      $data_parents = $data;
      $data_parents['grouping']  = 1;
      $data_parents['grouping_tree_uid']  = 0;
      $data_parents['grouping_parent_uid']  = 0;
      $data['grouping_parents'] = $this->model_doctype_folder->getFields($data_parents);
      $this->response->setOutput($this->load->view('doctype/folder_field_window', $data));
    } else {
      $this->response->setOutput($this->load->view('error/not_found', $data));
    }
  }

  public function add_field()
  {
    if (!empty($this->request->post['field_field_uid'])) {
      $json = array();
      $this->load->model('doctype/folder');
      $field_uid = $this->model_doctype_folder->addField($this->request->get['folder_uid'], $this->request->get['language_id'], $this->request->post);
      $json['folder_field_uid'] = $field_uid;
      if ($this->request->post['field_grouping']) {
        $json['grouping'] = 1;
        //нужен id предыдущего по сортировке поля
        $data = array(
          'folder_uid'     => $this->request->get['folder_uid'],
          'grouping'      => 1,
          'sort'          => "grouping"
        );
        $fields = $this->model_doctype_folder->getFields($data);

        $prev_folder_field_uid = 0;
        $field_info = array();
        foreach ($fields[$this->request->get['language_id']] as $field) {
          if ($prev_folder_field_uid) {
            $prev_folder_field_uid = $field['folder_field_uid'];
            break;
          }
          if ($field['folder_field_uid'] == $field_uid) {
            $prev_folder_field_uid = 1;
            $field_info = $field;
          }
        }
        $json['grouping_prev_folder_field_uid'] = $prev_folder_field_uid;
        $json['field_name'] = $field_info['field_name'];
        $parent_id = $this->model_doctype_folder->getFieldParent($field_uid);
        $json['grouping_level'] = 0;
        while ($parent_id) {
          if ($parent_id == $field_uid) {
            break;
          }
          $parent_id = $this->model_doctype_folder->getFieldParent($parent_id);
          $json['grouping_level']++;
        }
        $this->load->language('doctype/folder');
        $json['grouping_name'] = $field_info['grouping_name'] ? $field_info['grouping_name'] : $this->language->get('text_name_not_select');
        $json['grouping_content'] = $field_info['grouping_tree_uid'] ? $this->language->get('text_content_grouping_tree2') . " " . $field_info['grouping_tree_name'] : $this->language->get('text_content_grouping_list2');
        $json['tcolumn'] = $field_info['tcolumn'];
        $json['tcolumn_name'] = $field_info['tcolumn_name'];
        $json['tcolumn_width'] = $field_info['tcolumn_width'] ?? "";
        $json['tcolumn_total'] = $field_info['tcolumn_total'] ?? "";
        $json['tcolumn_hidden'] = $field_info['tcolumn_hidden'] ?? "";
      } else {
        $json['grouping'] = 0;
        $field_info = $this->model_doctype_folder->getField($field_uid);
        $json['field_name'] = $field_info['field_name'];
        $json['tcolumn'] = $field_info['tcolumn'];
        $json['tcolumn_name'] = $field_info['tcolumn_name'];
        $json['tcolumn_width'] = $field_info['tcolumn_width'] ?? "";
        $json['tcolumn_total'] = $field_info['tcolumn_total'] ?? "";
        $json['tcolumn_hidden'] = $field_info['tcolumn_hidden'] ?? "";
      }
      $json['draft'] = 1;
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function edit_filter()
  {
    $this->load->language('doctype/folder');
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $data = $this->model_doctype_folder->getFilter($this->request->get['folder_filter_uid']);
    // echo "ctrl: ";
    // print_r($data);
    // exit;

    $data['folder_filter_uid'] = $this->request->get['folder_filter_uid'];
    $folder_info = $this->model_doctype_folder->getFolder($data['folder_uid']);
    $data['doctype_uid'] = $folder_info['doctype_uid'];
    $data['folder_uid'] = $data['folder_uid'];
    $data['field_widget'] = "";
    if ($data['type_value'] == 'input') {
      //готовим виджет со значением
      $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
      $field_type = $field_info['type'];
      $widget_data = $field_info['params'];
      $widget_data['field_uid'] = $data['field_uid'];
      $widget_data['filter_form'] = TRUE;
      $widget_data['widget_name'] = "filter_value";
      $widget_data['field_value'] = $data['value'];
      $data['field_widget'] = $this->load->controller('extension/field/' . $field_type . '/getForm', $widget_data);
    }
    $data['fields'] = $this->model_doctype_doctype->getFields(['doctype_uid' => $data['doctype_uid'], 'setting' => 0]);
    $data['conditions'] = $this->model_doctype_folder->getConditions();
    $data['actions'] =  $this->model_doctype_folder->getFilterActions();
    $data['variables'] = $this->model_doctype_folder->getVariables();
    $this->response->setOutput($this->load->view('doctype/folder_filter_form', $data));
  }

  public function add_filter()
  {
    $this->load->language('doctype/folder');
    $this->load->model('doctype/doctype');
    $this->load->model('doctype/folder');
    $data = array();
    if (empty($this->request->get['filter_id'])) {
      $data['folder_filter_uid'] = 0;
    } else {
      $data['folder_filter_uid'] = $this->request->get['filter_id'];
    }
    $folder_info = $this->model_doctype_folder->getFolder($this->request->get['folder_uid']);
    $data['doctype_uid'] = $folder_info['doctype_uid'];
    $data['folder_uid'] = $this->request->get['folder_uid'];
    $data['fields'] = $this->model_doctype_doctype->getFields($data);
    $data['conditions'] = $this->model_doctype_folder->getConditions();
    $data['actions'] =  $this->model_doctype_folder->getFilterActions();
    $data['variables'] = $this->model_doctype_folder->getVariables();
    $this->response->setOutput($this->load->view('doctype/folder_filter_form', $data));
  }

  public function save_filter()
  {
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $this->load->language('doctype/folder');
    if (!empty($this->request->get['filter_id'])) {
      //сохраняется существующий фильтр
      $this->model_doctype_folder->editFilter($this->request->get['filter_id'], $this->request->post);
      $filter_id = $this->request->get['filter_id'];
    } elseif (!empty($this->request->get['folder_uid'])) {
      //создается новый фильтр            
      $filter_id = $this->model_doctype_folder->addFilter($this->request->get['folder_uid'], $this->request->post);
    }
    $field_info = $this->model_doctype_doctype->getField($this->request->post['filter_field_uid']);
    if ($this->request->post['type_condition_value'] == 'input') {
      //готовим виджет просмотра            
      $d = $field_info['params'];
      $d['field_uid'] = $this->request->post['filter_field_uid'];
      $d['field_value'] =  $this->request->post['filter_value'];
      $d['document_uid'] = 0;
      $value = strip_tags($this->load->controller('extension/field/' . $field_info['type'] . '/getView', $d));
    } else {
      $value = $this->language->get('text_' . $this->request->post['field_value_var']);
    }
    $json = array(
      'folder_filter_uid'  => $filter_id,
      'field_name'        => $this->model_doctype_doctype->getFieldName($this->request->post['filter_field_uid']),
      'condition'         => $this->language->get('text_condition_' . $this->request->post['filter_condition_value']),
      'value'             => $value,
      'action'            => $this->request->post['filter_action_value'],
      'action_title'      => $this->request->post['filter_action_value'] ? $this->language->get('text_action_' . $this->request->post['filter_action_value']) : $this->language->get('text_none'),
      'action_params'     => $this->request->post['filter_action_params']
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function remove_filter()
  {
    if (!empty($this->request->get['filter_id'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->removeFilter($this->request->get['filter_id']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function undo_remove_filter()
  {
    if (!empty($this->request->get['filter_id'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->undoRemoveFilter($this->request->get['filter_id']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function add_button()
  {
    $data = array();
    $this->load->language('doctype/folder');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $data['fields'] = ""; //выбранные поля кнопки (кому делегируется кнопка
    $data['thumb'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['actions'] = $this->getActions('inFolderButton');
    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['folder_uid'] = $this->request->get['folder_uid'];
    $data['action_log'] = 1;
    $this->response->setOutput($this->load->view('doctype/folder_button_form', $data));
  }

  public function save_button()
  {
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $this->load->language('doctype/folder');
    if (!empty($this->request->get['button_uid'])) {
      //сохраняется существующая кнопка
      $this->model_doctype_folder->editButton($this->request->get['button_uid'], $this->request->post);
      $button_uid = $this->request->get['button_uid'];
    } elseif (!empty($this->request->get['folder_uid'])) {
      //создается новая кнопка            
      $button_info = $this->model_doctype_folder->addButton($this->request->get['folder_uid'], $this->request->post);
      $button_uid = $button_info['uid'];
    }
    $this->load->model('tool/image');
    $fields = "";
    if (!empty($this->request->post['button_field'])) {
      $field_names = array();
      foreach ($this->request->post['button_field'] as $field_uid) {
        $field_names[] = $this->model_doctype_doctype->getFieldName($field_uid);
      }
      $fields = implode(", ", $field_names);
    }
    $routes = "";
    if (!empty($this->request->post['button_route'])) {
      $route_names = array();
      foreach ($this->request->post['button_route'] as $route_uid) {
        if ($route_uid) {
          $route_names[] = $this->model_doctype_doctype->getRoute($route_uid)['name'];
        } else {
          $route_names[] = $this->language->get('text_all_routes');
        }
      }
      $routes = implode(",", $route_names);
    }

    if (!empty($this->request->post['button_picture'])) {
      if (!empty($this->request->post['button_description'][$this->config->get('config_language_id')]['name'])) {
        $picture25 = $this->model_tool_image->resize($this->request->post['button_picture'], 28, 28);
      } else {
        $picture25 = $this->model_tool_image->resize($this->request->post['button_picture'], 28, 28);
      }
    } else {
      $picture25 = "";
    }


    $json = array(
      'folder_button_uid' => $button_uid,
      'name'              => $this->request->post['button_descriptions'][(int) $this->config->get('config_language_id')]['name'],
      'picture25'         => $picture25,
      'hide_button_name'  => $this->request->post['hide_button_name'],
      'color'             => $this->request->post['button_color'],
      'background'        => $this->request->post['button_background'],
      'action_name'       => $this->request->post['button_action'] ? $this->load->controller('extension/action/' . $this->request->post['button_action'] . '/getTitle') : $this->language->get('text_none'),
      'fields'            => $fields,
      'routes'            => $routes
    );
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function edit_button()
  {
    $data = array();
    $this->load->model('tool/image');
    $this->load->model('doctype/folder');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $button_info = $this->model_doctype_folder->getButton($this->request->get['button_uid']);
    $data['fields'] = $button_info['fields'];
    $data['routes'] = $button_info['routes'];
    $data['folder_uid'] = $button_info['folder_uid'];
    $data['descriptions'] = $button_info['descriptions'];
    $data['picture'] = $button_info['picture'];
    $data['hide_button_name'] = $button_info['hide_button_name'];
    $data['color'] = $button_info['color'];
    $data['background'] = $button_info['background'];
    $data['thumb'] = $this->model_tool_image->resize($button_info['picture'] ? $button_info['picture'] : 'no_image.png', 25, 25);
    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 25, 25);
    $data['actions'] = $this->getActions('inFolderButton');
    $folder_info = $this->model_doctype_folder->getFolder($button_info['folder_uid']);
    $data['doctype_uid'] = $folder_info['doctype_uid'];
    $data['action'] = array();
    $data['button_action'] = "";
    if ($button_info['action']) {
      if (!empty($button_info['action_params'])) {
        foreach ($button_info['action_params'] as $name => $value) {
          $data['action'][$name] = $value;
        }
      }
      $data['action_log'] = $button_info['action_log'];
      $this->language->load('action/' . $button_info['action']);
      $data['action_form'] = $this->load->controller('extension/action/' . $button_info['action'] . '/getForm', $data);
      $data['action_name'] = $button_info['action'];
    }
    $data['action_move_route'] = $button_info['action_move_route_uid'] ? ($this->model_doctype_doctype->getRoute($button_info['action_move_route_uid'])['name'] ?? "") : "";
    $data['action_move_route_uid'] = $button_info['action_move_route_uid'];
    $data['folder_button_uid'] = $this->request->get['button_uid'];
    $this->load->language('doctype/doctype');
    $this->load->language('doctype/folder');
    $this->response->setOutput($this->load->view('doctype/folder_button_form', $data));
  }

  public function remove_button()
  {
    if (!empty($this->request->get['button_uid'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->removeButton($this->request->get['button_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function undo_remove_button()
  {
    if (!empty($this->request->get['button_uid'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->undoRemoveButton($this->request->get['button_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }


  public function getActions($context)
  {
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
      return $a['title'] <=> $b['title'];
    });
    return $actions;
  }


  public function edit_field()
  {
    if (!empty($this->request->get['folder_field_uid'])) {
      $json = array();
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->editField($this->request->get['folder_field_uid'], $this->request->post);
      $json['folder_field_uid'] = $this->request->get['folder_field_uid'];
      $field_info = $this->model_doctype_folder->getField($this->request->get['folder_field_uid']);
      if ($this->request->post['field_grouping']) {
        $json['grouping'] = 1;
        //нужен id предыдущего по сортировке поля
        $data = array(
          'folder_uid'     => $field_info['folder_uid'],
          'grouping'      => 1,
          'language_id'   => $field_info['language_id'],
          'sort'          => "grouping"
        );
        $fields = $this->model_doctype_folder->getFields($data);
        $fields = array_reverse($fields); //была закомментирована строка, что приводило к неправильной сортировке после изменения вложенности в др группу
        $prev_folder_field_uid = 0;
        //определяем положение поля в списке; если поле всего одно, то положение не нужно
        if (count($fields) > 1) {
          foreach ($fields as $field) {
            if ($prev_folder_field_uid) {
              $prev_folder_field_uid = $field['folder_field_uid'];
              break;
            }
            if ($field['folder_field_uid'] == $this->request->get['folder_field_uid']) {
              $prev_folder_field_uid = 1;
            }
          }
          $json['grouping_prev_folder_field_uid'] = $prev_folder_field_uid;
        } else {
          $json['grouping_prev_folder_field_uid'] = 0;
        }
        $json['field_name'] = $field_info['field_name'];
        $parent_id = $this->model_doctype_folder->getFieldParent($this->request->get['folder_field_uid']);
        $json['grouping_level'] = 0;
        while ($parent_id) {
          if ($parent_id == $this->request->get['folder_field_uid']) {
            break;
          }
          $parent_id = $this->model_doctype_folder->getFieldParent($parent_id);
          $json['grouping_level']++;
        }
        $this->load->language('doctype/folder');
        $json['grouping_name'] = $field_info['grouping_name'] ? $field_info['grouping_name'] : $this->language->get('text_name_not_select');
        $json['grouping_content'] = $field_info['grouping_tree_uid'] ? $this->language->get('text_content_grouping_tree2') . " " . $field_info['grouping_tree_name'] : $this->language->get('text_content_grouping_list2');
        $json['tcolumn'] = $field_info['tcolumn'];
        $json['tcolumn_name'] = $field_info['tcolumn_name'];
        $json['tcolumn_width'] = $field_info['tcolumn_width'] ?? "";
        $json['tcolumn_total'] = $field_info['tcolumn_total'] ?? "";
        $json['tcolumn_hidden'] = $field_info['tcolumn_hidden'] ?? "";
      } elseif ($this->request->post['field_tcolumn']) {
        $json['grouping'] = 0;
        $field_info = $this->model_doctype_folder->getField($this->request->get['folder_field_uid']);
        $json['field_name'] = $field_info['field_name'];
        $json['tcolumn'] = $field_info['tcolumn'];
        $json['tcolumn_name'] = $field_info['tcolumn_name'];
        $json['tcolumn_width'] = $field_info['tcolumn_width'] ?? "";
        $json['tcolumn_total'] = $field_info['tcolumn_total'] ?? "";
        $json['tcolumn_hidden'] = $field_info['tcolumn_hidden'] ?? "";
      }
      $json['draft'] = 1;
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function remove_field()
  {
    if (!empty($this->request->get['folder_field_uid'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->removeField($this->request->get['folder_field_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function undo_remove_field()
  {
    if (!empty($this->request->get['folder_field_uid'])) {
      $this->load->model('doctype/folder');
      $this->model_doctype_folder->undoRemoveField($this->request->get['folder_field_uid']);
      $json = array();
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function save_folder()
  {
    if (!empty($this->request->get['folder_uid'])) {
      $this->load->model('doctype/folder');
      echo $this->model_doctype_folder->saveFolder($this->request->get['folder_uid'], $this->request->post);
    }
  }

  public function remove_draft()
  {
    if (!empty($this->request->get['folder_uid'])) {
      $this->load->model('doctype/folder');

      if ($this->model_doctype_folder->removeDraft($this->request->get['folder_uid'])) { // true - если журнал остался после удаления драфта
        $folder_info = $this->model_doctype_folder->getFolder($this->request->get['folder_uid']);
        //                $this->response->redirect($this->url->link('doctype/folder/edit', 'folder_uid=' . $this->request->get['folder_uid'], true));            
        $this->response->redirect($this->url->link(($folder_info['type'] ? 'extension/folder/' . $folder_info['type'] : 'doctype/folder') . '/edit', 'folder_uid=' . $this->request->get['folder_uid'], true));
      } else {
        $this->getList();
      }
    } else {
      $this->getList();
    }
  }

  public function autocomplete()
  {
    $json = array();

    if (isset($this->request->get['filter_name'])) {
      $this->load->model('doctype/folder');
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

      $results = $this->model_doctype_folder->getFolders($filter_data);

      foreach ($results as $result) {
        $json[] = array(
          'folder_uid' => $result['folder_uid'],
          'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
        );
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }


  /**
   * Возвращаает компактный виджет для фильтра
   */
  public function get_field_widget()
  {
    $this->load->model('doctype/doctype');
    $field_uid = $this->request->get['field_uid'];
    $field = $this->model_doctype_doctype->getField($field_uid);
    $field_type = $field['type'];
    $widget_data = $field['params'];
    $widget_data['field_uid'] = $field_uid;
    $widget_data['filter_form'] = TRUE;
    $widget_data['widget_name'] = "filter_value";
    $this->response->setOutput(json_encode($this->load->controller('extension/field/' . $field_type . '/getForm', $widget_data)));
  }

  public function move_field()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->model('doctype/folder');
      $field_info = $this->model_doctype_folder->getField($this->request->get['field_uid']);
      // print_r($field_info);
      // exit;

      $folder_info = $this->model_doctype_folder->getFolder($field_info['folder_uid']);
      $data = array(
        'doctype_uid'    => $folder_info['doctype_uid'],
        'folder_uid'     => $field_info['folder_uid'],
        'language_id'   => $field_info['language_id']
      );
      if (isset($this->request->get['grouping'])) {
        $data['grouping'] = 1;
        $data['sort'] = "grouping";
        if (!empty($field_info['grouping_parent_uid'])) {
          $data['grouping_parent_uid'] = $field_info['grouping_parent_uid'];
        }
      } elseif (isset($this->request->get['tcolumn'])) {
        $data['tcolumn'] = 1;
        $data['sort'] = "tcolumn";
      }
      $fields = $this->model_doctype_folder->getFields($data);
      // print_r($fields);
      // exit;
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
        if ($field['folder_field_uid'] == $this->request->get['field_uid']) {
          $field_source = $field;
          $next = TRUE;
        }
      }
      //обновляем sort поле
      if (isset($data['grouping']) && isset($field_target['sort_grouping'])) {
        $this->model_doctype_folder->editSortField($field_target['folder_field_uid'], $field_source['sort_grouping'], 0);
        $this->model_doctype_folder->editSortField($field_source['folder_field_uid'], $field_target['sort_grouping'], 0);
        $json = array('success' => 1);
      } elseif (isset($data['tcolumn']) && isset($field_target['sort_tcolumn'])) {
        $this->model_doctype_folder->editSortField($field_target['folder_field_uid'], 0, $field_source['sort_tcolumn']);
        $this->model_doctype_folder->editSortField($field_source['folder_field_uid'], 0, $field_target['sort_tcolumn']);
        $json = array('success' => 1);
      }
      if (isset($json['success'])) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => '1')));
      }
    }
  }

  /**
   * Перемещение кнопки в админке (вверх-вниз)
   */
  public function move_button()
  {
    if (!empty($this->request->get['button_uid'])) {
      $this->load->model('doctype/folder');
      $button_info = $this->model_doctype_folder->getButton($this->request->get['button_uid']);
      $buttons = $this->model_doctype_folder->getButtons($button_info['folder_uid']);
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
        if ($button['folder_button_uid'] == $this->request->get['button_uid']) {
          $button_source = $button;
          $next = TRUE;
        }
      }
      //обновляем sort действий
      if (isset($button_target['sort'])) { //целевого действия может не быть, если перемещаемое действие первое или последнее
        $this->model_doctype_folder->editSortButton($button_target['folder_button_uid'], $button_source['sort']);
        $this->model_doctype_folder->editSortButton($button_source['folder_button_uid'], $button_target['sort']);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => '1')));
      }
    }
  }
}

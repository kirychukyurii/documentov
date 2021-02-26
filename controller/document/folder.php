<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

class ControllerDocumentFolder extends Controller
{
  public function index()
  {
    if (!empty($this->request->get['folder_uid'])) {
      $this->load->model('document/folder');
      $folder_info = $this->model_document_folder->getFolder($this->request->get['folder_uid']);
      if (!$folder_info) {
        $this->response->redirect($this->url->link('error/not_found'));
      } else {
        $this->document->setTitle($folder_info['name']);
        $data = array();
        $data['title'] = $folder_info['name'];
        if ($folder_info['type']) {
          $this->load->controller('extension/folder/' . $folder_info['type'] . '/getFolder');
        } else {
          $this->document->addScript('view/javascript/splitter/splitter.js');

          $this->load->model('document/document');
          $this->load->model('doctype/doctype');
          $this->load->language('document/folder');

          $data['grouping_tree'] = $this->model_document_folder->getGroupingTree($this->request->get['folder_uid']);
          $data['folder_uid'] = $this->request->get['folder_uid'];


          $data['doctype_uid'] = $folder_info['doctype_uid'];
          $data['toolbar'] = $folder_info['additional_params']['toolbar'] ?? "";
          $data['navigation'] = $folder_info['additional_params']['navigation'] ?? "";
          $data['collapse_group'] = $folder_info['additional_params']['collapse_group'] ?? "";
          $data['hide_selectors'] = !empty($folder_info['additional_params']['hide_selectors']) ? "1" : "";
          $data['show_count_group'] = !empty($folder_info['additional_params']['show_count_group']) ? "1" : "";
          //проверяем, есть ли в типе документа поля, которые включены в полнотекстовый индекс
          $doctype_fields = $this->model_doctype_doctype->getFields(array('doctype_uid' => $data['doctype_uid']));
          foreach ($doctype_fields as $doctype_field) {
            if (isset($doctype_field['params']['ft_index']) && $doctype_field['params']['ft_index'] == "1") {
              $data['ftsearch_avaliable'] = true;
              break;
            }
          }

          if (!$data['toolbar'] || $data['toolbar'] != "always_hide") {
            $buttons = $this->model_document_folder->getButtons($this->request->get['folder_uid']);
            if ($data['toolbar'] == "empty_hide" && count($buttons) == 0) {
              $data['toolbar'] = "hide";
            } else {
              $data['buttons'] = array();
              $this->load->model('tool/image');
              foreach ($buttons as $button) {
                if (isset($button['documents'][0]['document_uid']) && !$button['documents'][0]['document_uid']) { //кнопка делегируется на все документы через настроечное поле
                  $documents = 0;
                } else {
                  $documents = array();
                  // if ($button['documents']) {
                  //   foreach ($button['documents'] as $document) {
                  //     $documents[] = "'" . $document['document_uid'] . "'";
                  //   }
                  // }
                }
                if ($button['picture']) {
                  $picture_25 = $this->model_tool_image->resize($button['picture'], 28, 28);
                } else {
                  $picture_25 = "";
                }

                $data['buttons'][] = array(
                  'folder_button_uid'  => $button['route_button_uid'],
                  'name'              => $button['name'],
                  'title'             => $button['description'],
                  'picture'           => $picture_25,
                  'hide_button_name'  => $button['hide_button_name'],
                  'color'             => $button['color'],
                  'background'        => $button['background'],
                  'documents'         => is_array($documents) ? implode(",", $documents) : $documents
                );
              }
              $user_filters = $this->model_document_folder->getUserFilters($this->request->get['folder_uid']);
              $data['user_filters'] = array();
              foreach ($user_filters as $filter) {
                $data['user_filters'][] = array(
                  'filter_id'     => $filter['filter_id'],
                  'filter_name'   => htmlspecialchars($filter['filter_name'])
                );
              }
            }
          } else {
            $data['toolbar'] = "hide";
          }
          $data['folder_name'] = $folder_info['name'];
          //                    if (($data['navigation']  && 
          //                            $data['navigation'] == 'toolbar_hidden_show') || $data['toolbar'] == "hide") {
          //                        $data['folder_name'] = $folder_info['name'];
          //                    } else {
          //                        $data['navigation'] = "hide";
          //                    }
          $data['filter_conditions'] = array(
            array(
              'value'    => 'equal',
              'title'  => $this->language->get('text_condition_equal')
            ),
            array(
              'value'    => 'notequal',
              'title'  => $this->language->get('text_condition_notequal')
            ),
            array(
              'value'    => 'more',
              'title'  => $this->language->get('text_condition_more')
            ),
            array(
              'value'    => 'less',
              'title'  => $this->language->get('text_condition_less')
            ),
            array(
              'value'    => 'contains',
              'title'  => $this->language->get('text_condition_contains')
            ),
            array(
              'value'    => 'notcontains',
              'title'  => $this->language->get('text_condition_notcontains')
            ),
          );

          $filters = $this->model_document_folder->getFilters($this->request->get['folder_uid']);
          $data['filters'] = array();
          foreach ($filters as $filter) {
            if ($filter['action'] != 'hide'  && $filter['field_uid']) { //фильтр НЕ на скрытие документов
              $data['filters'][$filter['folder_filter_uid']] = array(
                'action'    => $filter['action'],
                'params'    => $filter['action_params']
              );
            }
          }
          $data['pagination_limits'] = explode(',', $this->config->get('pagination_limits'));
          $data['pagination_limit'] = $this->config->get('pagination_limit');
          $data['header'] = $this->load->controller('common/header');
          $data['footer'] = $this->load->controller('common/footer');
          $this->response->setOutput($this->load->view('document/document_folder', $data));
        }
      }
    } else {
      $this->response->redirect($this->url->link('error/not_found'));
    }
  }

  // get_avail_buttons получает список документов и журнал и возвращает доступность кнопок относительно выбранных документов
  public function get_avail_buttons()
  {
    $data = [
      'parent_uid' => $this->request->get['folder_uid'],
      'user_uid' => $this->customer->getStructureId(),
      'document_uids' => $this->request->post['documentUIDs'] ?? [],
    ];
    $r = $this->daemon->exec("GetUserButtonsByDocs", $data);
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($r));
  }

  public function get_documents()
  {

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('document/folder');
    $this->load->model('doctype/folder');
    $this->load->language('document/folder');
    if (empty($this->request->get['folder_uid'])) {
      return;
    }
    $folder_info = $this->model_document_folder->getFolder($this->request->get['folder_uid']);
    $json = array();

    $get_value = urldecode($this->request->get['value'] ?? "");
    $get_value2 = urldecode($this->request->get['value2'] ?? "");
    if (!$folder_info || empty($folder_info['doctype_uid'])) {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode(['error' => $this->language->get('text_error_no_doctype')]));
      return;
    }
    if (!empty($folder_info['type'])) {
      $json = $this->load->controller('extension/folder/' . $folder_info['type'] . '/get_documents');
    } else {
      $data_folder = array(
        'folder_uid'     => $this->request->get['folder_uid'],
        'tcolumn'        => 1,
        'language_id'   => $this->config->get('config_language_id')
      );
      $folder_fields = $this->model_document_folder->getFields($data_folder);
      $data = array();

      $data['doctype_uid'] = $folder_info['doctype_uid'];
      if (!empty($this->request->get['fid']) && !empty($this->request->get['folder_uid'])) { //открытие ветви дерева    
        $json['reload_url'] = "fid=" . $this->request->get['fid'] . "&value=" . html_entity_decode($get_value);
        $json['reload_url'] .= !empty($this->request->get['namegroup']) ? "&namegroup=" . $this->request->get['namegroup'] : ""; //открытие группы по ее названию
        if (empty($this->request->get['gtid'])) {
          //группировка через вложенные поля
          $data['filter_names'][$this->request->get['fid']][] = array(
            'condition' => '=',
            'display'     => $get_value
          );
          //получаем дочерние ветви дерева  
          if (!isset($this->request->get['fid2'])) {
            //группировка ограничена 2 уровнями, если находимся на последнем уровне, дети уже не нужны
            $folder_fields_infos = $this->model_doctype_folder->getFieldByGroupingField($this->request->get['folder_uid'], $this->request->get['fid']); //вложенное поле журнала

            if ($folder_fields_infos) {
              $json['children_path'] = array();
              foreach ($folder_fields_infos as $folder_field_info) {
                $data_tree = $data;
                $data_tree['group_by']['display'] = $folder_field_info['field_uid'];
                $data_tree['field_uids'] = [];
                $data_tree['field_uids'][] = ["display" => $folder_field_info['field_uid']];
                $data_tree['field_uids'][] = ["display" => $this->request->get['fid']];
                // $data_tree['field_uids'][] = $this->request->get['fid'];
                if (empty($this->request->get['page']) || $this->request->get['page'] < 2) {
                  if ($folder_field_info['grouping_name'] && empty($this->request->get['namegroup'])) {
                    //выводим название группы
                    $json['children_path'][] = array(
                      'fid'  => $this->request->get['fid'],
                      'title' => $folder_field_info['grouping_name'],
                      'value' => $get_value . "&namegroup=" . $folder_field_info['uid']
                    );
                  } else {
                    if (!empty($this->request->get['namegroup']) && $this->request->get['namegroup'] != $folder_field_info['uid']) {
                      continue;
                    }
                    $children_paths = [];
                    $vf = 'v' . str_replace("-", "", $folder_field_info['field_uid']);
                    foreach ($this->model_document_document->getDocuments($data_tree) as $path) {
                      if (!isset($children_paths[$path[$vf]])) {
                        $children_paths[$path[$vf]] = 0;
                      }
                      $children_paths[$path[$vf]] += 1;
                    }
                    foreach ($children_paths as $title => $t) {
                      $json['children_path'][] = array(
                        'fid2'  => $folder_field_info['field_uid'],
                        'title' => html_entity_decode($title)
                      );
                    }
                  }
                }
              }
            }
          }
        } else {
          //группировка через родительское поле (а-ля Структура)
          //готовим дочерние ветви для текущей
          if (empty($this->request->get['page']) || $this->request->get['page'] < 2) {
            $children_paths = $this->model_document_document->getFieldValueTree($this->request->get['fid'], $this->request->get['gtid'], $get_value, false);
            $json['children_path'] = [];
            foreach ($children_paths as $path) {
              if (empty($this->request->get['value']) || $get_value != $path['name']) { //родитель и ребенок не могут иметь одинаковые названия, чтобы исключить бесконечную матрешку
                $json['children_path'][] = array(
                  'fid'   => $this->request->get['fid'],
                  'gtid'  => $this->request->get['gtid'],
                  'title' => $path['name']
                );
              }
            }
          }

          //готовим документы
          $data['filter_names'][$this->request->get['gtid']][] = array(
            'condition'     => '=',
            'display'       => $get_value
          );
          $data['field_uids'][] = ["display" => $this->request->get['gtid']];
          $json['reload_url'] .= "&gtid=" . $this->request->get['gtid'];
        }
        $data['field_uids'][] = ["display" => $this->request->get['fid']];
        if (!empty($this->request->get['fid2'])) {
          $data['filter_names'][$this->request->get['fid2']][] = array(
            'condition'     => '=',
            'display'       =>  $get_value2 //$this->request->get['value2']
          );
          $data['field_uids'][] = ["display" => $this->request->get['fid2']];
          $json['reload_url'] .= "&fid2=" . $this->request->get['fid2'] . "&value2=" . html_entity_decode($this->request->get['value2']);
        }
        foreach ($folder_fields as $folder_field) {
          if ($folder_field['field_uid'] && !in_array($folder_field['field_uid'], $data['field_uids'])) {
            $data['field_uids'][] = ["display" => $folder_field['field_uid']];
          }
          $json['body'][$folder_field['field_uid']] = "";
        }
      } else { //журнал без дерева либо с деревом, но с названием группы, по которой и кликнули
        foreach ($folder_fields as $folder_field) {
          $data['field_uids'][] = ["display" => $folder_field['field_uid']];
          $json['body'][$folder_field['field_uid']] = "";
        }
        if (!empty($this->request->get['field_uid'])) {
          //это группировка не по полю, верхнего уровня (например, по исполнителям, по статусу и т.д.
          $field_info = $this->model_doctype_folder->getFieldByField($this->request->get['folder_uid'], $this->request->get['field_uid']);
          if ($field_info['grouping_tree_uid']) {
            //группировка по названию дерева
            //получаем документы, у которых поле = $field_info['grouping_tree_uid'] имеет пустые значения
            if (empty($this->request->get['page']) || $this->request->get['page'] < 2) {
              $data_children = array(
                'doctype_uid'   => $folder_info['doctype_uid'],
                'field_uids'    => ["display" => $field_info['field_uid']],
                'filter_names'  => array(
                  $field_info['grouping_tree_uid'] => array(array(
                    'condition' => '=',
                    'value'     => ''
                  ))
                )
              );
              $children_paths = $this->model_document_document->getDocuments($data_children);
              $json['children_path'] = array();
              foreach ($children_paths as $path) {
                $json['children_path'][] = array(
                  'fid'   => $this->request->get['field_uid'],
                  'gtid'  => $field_info['grouping_tree_uid'], //'', 0.9.8 Структура: click D1 - Org
                  'title' => html_entity_decode($path['v' . str_replace("-", "", $field_info['field_uid'])])
                );
              }
            }
          } else {
            if (empty($this->request->get['page']) || $this->request->get['page'] < 2) {
              $children_paths = $this->model_document_document->getUniqueFieldValues($this->request->get['field_uid']);
              $json['children_path'] = array();
              foreach ($children_paths as $path) {
                if (empty($this->request->get['value']) || $get_value != $path['display_value']) { //родитель и ребенок не могут иметь одинаковые названия, чтобы исключить бесконечную матрешку
                  $json['children_path'][] = array(
                    'fid'   => $this->request->get['field_uid'],
                    'gtid'  => '', //$this->request->get['gtid'],
                    'title' => html_entity_decode($path['display_value'])
                  );
                }
              }
            }
          }
          $json['reload_url'] = "field_uid=" . $this->request->get['field_uid'];
        }
      }
      //проверяем поиск
      if (!empty($this->request->get['search'])) {
        $search_type = $this->request->get['search_type'] ?? 'quick';
        $data['filter_search_type'] = $search_type;
        if ($search_type === 'fulltext') {
          $data['filter_search'] = $this->load->controller("document/search/format_ftquery", $this->request->get['search']);
        } else {
          $data['filter_search'] = $this->request->get['search'];
        }
      }
      //загружаем админ-фильтры
      $filters = $this->model_document_folder->getFilters($this->request->get['folder_uid']);
      foreach ($filters as $filter) {
        if ($filter['action'] == 'hide'  && $filter['field_uid']) { //фильтр на скрытие документов, добавляем условие в выборку
          //меняем условия на противоположные, т.к. строки нужно скрыть, а не отобразить
          if ($filter['condition_value'] == 'moreequal') {
            $condition = 'less';
          } elseif ($filter['condition_value'] == 'lessequal') {
            $condition = 'more';
          } elseif ($filter['condition_value'] == 'more') {
            $condition = 'lessequal';
          } elseif ($filter['condition_value'] == 'less') {
            $condition = 'moreequal';
          } elseif (strpos($filter['condition_value'], 'not') === FALSE) {
            $condition = "not" . $filter['condition_value'];
          } else {
            $condition = str_replace('not', '', $filter['condition_value']);
          }
          $data['filter_names'][$filter['field_uid']][] = array(
            'condition' => $condition,
            'value'     => $filter['type_value'] == 'var' ? $this->model_document_document->getVariable($filter['value']) : $filter['value'] //   фильтр работает по полю value       
          );
        }
      }
      //проверям наличие пользовательского фильтра
      if (!empty($this->request->get['filter_field']) && !empty($this->request->get['filter_condition']) && !empty($this->request->get['filter_value'])) {
        //все массивы должны быть одной длины
        if (
          count($this->request->get['filter_field']) == count($this->request->get['filter_condition']) &&
          count($this->request->get['filter_condition']) == count($this->request->get['filter_value'])
        ) {
          $this->load->model('doctype/doctype');
          for ($i = 0; $i < count($this->request->get['filter_field']); $i++) {
            $filter_field_info = $this->model_doctype_doctype->getField($this->request->get['filter_field'][$i]);
            $model = "model_extension_field_" . $filter_field_info['type'];
            $this->load->model('extension/field/' . $filter_field_info['type']);
            $value = $this->$model->getValue($this->request->get['filter_field'][$i], 0, $this->request->get['filter_value'][$i]);
            $data['filter_names'][$this->request->get['filter_field'][$i]][] = array(
              'condition' => $this->request->get['filter_condition'][$i],
              'value'     => $value ?? ""
            );
          }
        }
      }
      //проверяем наличие сортировки
      if (!empty($this->request->get['sort'])) {
        $data['sort'] = $json['sort'] = $this->request->get['sort'];
        $data['order'] = $json['order'] = $this->request->get['order'];
      } else {
        //пользовательской сортировки нет, проверям наличие сортировки по умолчанию, установленную админом
        $folder_field_sort = $this->model_doctype_folder->getDefaultSortField($this->request->get['folder_uid'], $this->config->get('config_language_id'));
        if ($folder_field_sort) {
          $data['sort'] = $folder_field_sort['field_uid'];
          $data['order'] = $folder_field_sort['default_sort'] == 1 ? "ASC" : "DESC";
        }
      }
      // $total_documents = $this->model_document_document->getTotalDocuments($data);

      $json['documents'] = array();
      $json['filter_documents'] = array();
      if (!empty($this->request->get['page'])) {
        $page = (int) $this->request->get['page'];
      } else {
        $page = 1;
      }
      if (!empty($this->request->get['limit'])) {
        $limit = (int) $this->request->get['limit'];
      } else {
        $limit = $this->config->get('pagination_limit');
      }

      $data['start'] = ($page - 1) * $limit;
      $data['limit'] = $limit;
      $data['is_count'] = 1;

      $documents = $this->model_document_document->getDocuments($data);
      // print_r($data);
      // print_r($documents);
      // exit;

      // if (isset($this->request->get['only_count'])) {
      //   print_r($data);
      //   print_r($documents);
      //   exit;
      // }
      $json['documents'] = $documents['documents'] ?? [];
      $total_documents = $documents['total'] ?? 0;
      //в дереве а-ля структура отображаем выбранную группы (подразделение) в таблице документов, если не установлен пользовательский фильтр
      if (!empty($this->request->get['gtid']) && !empty($this->request->get['fid']) && !empty($this->request->get['value']) && empty($this->request->get['filter_field'])) {
        //проверим - нет ли уже нашего подразделения в полученных документах
        $data['filter_names'] = array($this->request->get['fid'] => array(array(
          'condition'     => "=",
          'display'       => $get_value
        )));

        $group_document = $this->model_document_document->getDocuments($data);
        if (isset($group_document['documents'][0]['document_uid'])) {
          // $group_document[0]['root'] = true;
          $json['document_root'] = $group_document['documents'][0]['document_uid']; //это искусственно добавленный док, который необходимо будет выделить в интерфейсе
          array_unshift($json['documents'], $group_document['documents'][0]);
        }
      }
      $json['documents'] = array_map("unserialize", array_unique(array_map("serialize", $json['documents'])));
      if ($total_documents < $limit) {
        // $total_documents = count($json['documents']);
      }

      //итоговая строка
      $calc_total_fields = array();
      foreach ($folder_fields as $folder_field) {
        if (!empty($folder_field['tcolumn_total'])) {
          $calc_total_fields[] = array(
            'field_uid'     => $folder_field['field_uid'],
            'vfield'        => "v" . str_replace("-", "", $folder_field['field_uid']),
            'total'         => 0
          );
        }
      }
      if ($calc_total_fields) {
        //есть поля с выводом итоговых сумм
        foreach ($json['documents'] as $document) {
          foreach ($calc_total_fields as &$field) {
            $field['total'] += (float) str_replace(" ", "", $document[$field['vfield']]);
          }
        }
        $json['total_columns'] = array();
        foreach ($calc_total_fields as &$field) {
          if ($field['total']) {
            $total_field_info = $this->model_doctype_doctype->getField($field['field_uid']);
            $data_field = $total_field_info['params'];
            $data_field['field_uid'] = $field['field_uid'];
            $data_field['field_value'] = $field['total'];
            $json['total_columns'][$field['vfield']] = $this->load->controller("extension/field/" . $total_field_info['type'] . "/getView", $data_field);
          }
        }
      }
      $document_uids = array(); //формируем массив с идентификаторами отображаемых в журнале документов
      foreach ($json['documents'] as $document) {
        $document_uids[] = $document['document_uid'];
      }

      //еще раз перебираем админ-фильтры, чтобы добавить условия и стили на оформление строк в журнале
      foreach ($filters as $filter) {
        if ($filter['action'] != 'hide' && $filter['field_uid']) { //фильтр НЕ на скрытие документов, нужно определить документы, которые ему соответсвуют
          //проверяем наличие настроек оформления - если их не будет, игнорируем фильтр
          $action_param = false;
          if (!isset($filter['action_params']) || !is_array($filter['action_params']) || $filter['action_params'] == null) {
            continue;
          }
          foreach ($filter['action_params'] as $param) {
            if (is_array($param)) {
              foreach ($param as $p) {
                if ($p) {
                  $action_param = true;
                  break;
                }
              }
              if ($action_param) {
                break;
              }
            } elseif ($param) {
              $action_param = true;
              break;
            }
          }
          if (!$action_param) {
            continue;
          }
          $data_filter_documents = array(
            'document_uids'  => $document_uids,
            'filter_names'   => array($filter['field_uid'] => array(array(
              'comparison' => $filter['condition_value'],
              'value'     => $filter['type_value'] == 'input' ? $filter['value'] : $this->model_document_document->getVariable($filter['value'])
            )))
          );
          $filter_documents = $this->model_document_document->getDocumentIds($data_filter_documents); //получаем список соответствующих фильтру документов
          foreach ($filter_documents as $document_uid) {
            $json['filter_documents'][$document_uid][] = $filter['folder_filter_uid'];
          }
        }
      }
      $json['buttons'] = array();
      //проверяем необходимость обновления кнопок журнала, делегированных на некоторых точках документов (точки могли измениться)
      if (empty($folder_info['additional_params']['toolbar']) || $folder_info['additional_params']['toolbar'] != "always_hide") {
        $folder_buttons = $this->model_document_folder->getButtonWithRoute($this->request->get['folder_uid']);
        if ($folder_buttons) { //есть кнопки для обновления
          foreach ($folder_buttons as $button) {
            $button_documents =  $this->model_document_folder->getButtonDocuments($button['folder_button_uid']);
            $documents = array();
            if ($button_documents) {
              foreach ($button_documents as $document) {
                $documents[] = $document['document_uid'];
              }
            }
            $json['buttons'][$button['folder_button_uid']] = $documents;
          }
        }
      }
      // }

      $json['fields'] = $folder_fields;
      $json['documents'] = array_values($json['documents']);
      $pagination = new Pagination();
      $pagination->total = $total_documents;
      $pagination->page = $page;
      $pagination->limit = $limit;

      $json['pagination'] = $pagination->render();
      $json['text_total_documents'] = $this->language->get('text_total_documents');
      $json['text_show_documents'] = $this->language->get('text_show_documents');
      $json['total_documents'] = $total_documents;
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function button()
  {
    $this->load->model('document/folder');
    $this->load->model('document/document');

    $document_uids = array();
    $append = array(); //массив, в котором собирать блоки кода, которые действия хотят добавить в телу журнала
    if (!empty($this->request->get['button_uid'])) {
      $this->session->data['current_button_uid'] = $this->request->get['button_uid'];
      if (!empty($this->request->post['selected'])) {
        $document_uids = $this->request->post['selected']; //выбранные документы в журнале
      } elseif (!empty($this->request->get['document_uids'])) { //передается из окна действия                    
        $document_uids = explode(',', $this->request->get['document_uids']);
      }
    }
    if ($document_uids) {
      $access = false;
      $document_uids = $this->model_document_folder->hasAccessButton($this->request->get['button_uid'], $document_uids);
      if ($document_uids) {
        $access = true;
      }
    } else {
      $access =  $this->model_document_folder->hasAccessButton($this->request->get['button_uid']);
    }

    if ($access) {
      $button_info = $this->model_document_folder->getButton($this->request->get['button_uid']);
      if (!$button_info) {
        $result = [
          'error' => $this->language->get('text_button_not_availabled')
        ];

        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($result));
        return;
      }
      $folder_info = $this->model_document_folder->getFolder($button_info['folder_uid']);
      //проверяем, чтобы все документы были на заданных точках маршрута
      $button_routes = $this->model_document_folder->getButtonRoutes($button_info['folder_button_uid']);
      $routes = array();
      $all_route = FALSE;
      $document_verified_uids = array();
      foreach ($button_routes as $route) {
        if ($route['route_uid']) {
          $routes[] = $route['route_uid'];
        } else {
          $all_route = TRUE;
          break;
        }
      }
      foreach ($document_uids as $document_uid) {
        $document_info = $this->model_document_document->getDocument($document_uid, true);
        if (
          $document_info && $document_info['doctype_uid'] === $folder_info['doctype_uid'] && (in_array($document_info['route_uid'], $routes) || $all_route)
        ) {
          $document_verified_uids[] = $document_uid;
        }
      }
      $data['document_uids'] = $document_verified_uids;
      if ($button_info['action']) {
        $data = $this->request->post;
        $data['document_uids'] = $document_verified_uids;
        $data['button_uid'] = $this->request->get['button_uid'];
        $data['folder_uid'] = $button_info['folder_uid'];
        $data['params'] = $button_info['action_params'];

        // if ($button_info['action'] == "remove" || $button_info['action'] == "move") {
        //   $data['uid'] = $data['button_uid'];
        //   $data['session'] = $this->getSession();
        //   $result = $this->daemon->exec("ExecuteButtonAction", $data);
        // } else {
        // $result = $this->load->controller("extension/action/" . $button_info['action'] . "/executeButton", $data);
        // }

        $result = $this->load->controller("extension/action/" . $button_info['action'] . "/executeButton", $data);
        $this->load->model('tool/utils');
        $this->model_tool_utils->addLog($button_info['folder_uid'], 'folbutton_action', $button_info['action'], $data['button_uid'], array_merge($data['params'], array('button_name' => $this->db->escape($button_info['name']))));
        if (!empty($result['append'])) {
          $append[] = $result['append'];
        }
        if (isset($result['log']) && $button_info['action_log']) {
          //записываем результат выполнения действия
          //действия могут возвращать лог строкой - для всех выбранных документов единый лог, 
          //либо массивом - для каждого документа свой лог (ключ - идентифиатор документа)
          // foreach ($document_verified_uids as $document_uid) {
          //   $document_info = $this->model_document_document->getDocument($document_uid);

          //   if ($document_info && $document_info['field_log_uid']) {
          //     $data_log = array(
          //       'date' => $this->getCurrentDateTime("d.m.Y H:i:s"),
          //       'name' => $this->customer->getName(),
          //       'button' => $button_info['name'],
          //       'action_log' => is_array($result['log']) ? $result['log'][$document_uid] : $result['log']
          //     );
          //     $this->model_document_document->appendLogFieldValue(
          //       $document_info['field_log_uid'],
          //       $document_uid,
          //       $data_log
          //     );
          //   }
          // }
        }
        if (!empty($data['document_uids']) && !isset($result['window']) && !isset($result['replace']) && !isset($result['error'])) {
          //действие отработало
          foreach ($data['document_uids'] as $document_uid) {
            $document_info = $this->model_document_document->getDocument($document_uid);
            //документ мог быть и удален, поэтому проверяем наличие информации о нем
            if ($document_info) {
              //сначала отрабатываем контекст активности, потому будем проверять перемещение из кнопки;
              //причем, если активность выполнит перемещение документа, перемещение кнопки не сработает
              $route_result = $this->load->controller('document/document/proc.GetDocument()', array('document_uid' => $document_uid, 'context' => 'view'));
              if (!empty($route_result['append'])) {
                $append[] = $route_result['append'];
              }
              //если в кнопке есть перемещение
              if ($button_info['action_move_route_uid']) {
                //получаем актуальную информацию документа, чтобы проверить не пересетил ли документ контекст активности
                $document_info_2 = $this->model_document_document->getDocument($document_uid);
                //если документ не был перемещен в контексте активности
                if ($document_info['route_uid'] == $document_info_2['route_uid']) {
                  $route_result = $this->model_document_document->moveRoute($document_uid, $button_info['action_move_route_uid']);
                  if (!empty($route_result['append'])) {
                    $append[] = $route_result['append'];
                  }
                }
              }
            }
          }
        }
      } else {
        //у кнопки нет действия                   
        if ($button_info['action_move_route_uid']) {
          foreach ($data['document_uids'] as $document_uid) {
            $route_result = $this->model_document_document->moveRoute($document_uid, $button_info['action_move_route_uid']);
            if (!empty($route_result['append'])) {
              $append[] = $route_result['append'];
            }
          }
        }
        $result = array('reload'    => 'table');
      }
      if (empty($result['window']) && empty($result['replace']) && empty($result['redirect'])) {
        $result['reload'] = 'table';
      }
      if ($append) {
        $result['append'] = $append;
      }

      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($result));
    } else {
      //нет доступа к кнопке
      $this->load->language('doctype/doctype');
      $json = array(
        'error' => $this->language->get('text_error_access_button')
      );
      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }


  public function validateButton()
  {
    if (empty($this->request->get['button_uid'])) {
      return false;
    }
    return $this->model_document_folder->hasAccessButton($this->request->get['button_uid']);
  }


  /**
   * Возвращаает компактный виджет для фильтра
   */
  public function get_field_widget()
  {
    $this->load->model('doctype/doctype');
    $field_uid = $this->request->get['field_uid'];
    $field = $this->model_doctype_doctype->getField($field_uid, 0);
    $field_type = $field['type'];
    $widget_data = $field['params'];
    $widget_data['field_uid'] = $field_uid;
    $widget_data['filter_form'] = TRUE;
    $widget_data['widget_name'] = "filter_value[]";
    $this->response->setOutput(json_encode($this->load->controller('extension/field/' . $field_type . '/getForm', $widget_data)));
    //$test_widget = "<input name='test'/>";
    //$this->response->setOutput($test_widget);
  }

  public function filter_save()
  {
    //проверям наличие пользовательского фильтра
    if (
      !empty($this->request->post['filter_field']) && !empty($this->request->post['filter_condition']) && !empty($this->request->post['filter_value'])
      && !empty($this->request->get['folder_uid']) && !empty($this->request->get['filter_name'])
    ) {
      //все массивы должны быть одной длины
      if (
        count($this->request->post['filter_field']) == count($this->request->post['filter_condition']) &&
        count($this->request->post['filter_condition']) == count($this->request->post['filter_value'])
      ) {
        $filters = array();
        $this->load->model('doctype/doctype');
        for ($i = 0; $i < count($this->request->post['filter_field']); $i++) {
          $filter_field_info = $this->model_doctype_doctype->getField($this->request->post['filter_field'][$i]);
          $model = "model_extension_field_" . $filter_field_info['type'];
          $this->load->model('extension/field/' . $filter_field_info['type']);
          $value = $this->$model->getValue($this->request->post['filter_field'][$i], 0, $this->request->post['filter_value'][$i]);

          $filters[] = array(
            'filter_field'      => $this->request->post['filter_field'][$i],
            'filter_condition'  => $this->request->post['filter_condition'][$i],
            'filter_value'      => $value,
          );
        }
        $this->load->model('document/folder');
        if ($this->request->get['filter_id']) {
          $this->model_document_folder->editFilter($this->request->get['filter_id'], $this->request->get['filter_name'], $filters);
          $filter_id = $this->request->get['filter_id'];
        } else {
          $filter_id = $this->model_document_folder->addFilter($this->request->get['folder_uid'], $this->request->get['filter_name'], $filters);
        }

        $json = array('filter_id' => $filter_id);
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($json));
      }
    }
  }

  /**
   * Пользователь выбрал для загрузки сохраненный ранее фильтр в журнале
   */
  public function filter_load()
  {
    //проверям наличие пользовательского фильтра в запросе
    if (!empty($this->request->get['filter_id'])) {
      $this->load->model('document/folder');
      $this->load->model('doctype/doctype');
      $filters = $this->model_document_folder->getUserFilter($this->request->get['filter_id']);
      if ($filters) {
        foreach ($filters['filter'] as &$filter) {
          $field_info = $this->model_doctype_doctype->getField($filter['filter_field'], 0);
          $field_type = $field_info['type'];
          $widget_data = $field_info['params'];
          $widget_data['field_uid'] = $filter['filter_field'];
          $widget_data['field_value'] = $filter['filter_value'];
          $widget_data['filter_form'] = TRUE;
          $widget_data['widget_name'] = "filter_value[]";
          $filter['filter_form'] = $this->load->controller('extension/field/' . $field_type . '/getForm', $widget_data);
        }
        $this->response->addHeader('Content-type: application/json');
        $this->response->setOutput(json_encode($filters));
      }
    }
  }

  public function filter_remove()
  {
    if (!empty($this->request->get['filter_id'])) {
      $this->load->model('document/folder');
      $this->model_document_folder->removeFilter($this->request->get['filter_id']);
      $this->response->addHeader('Content-type: application/json');
      $json = array();
      $this->response->setOutput(json_encode($json));
    }
  }

  private function getCurrentDateTime($format)
  {
    $date = new DateTime("now");
    return $date->format($format);
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

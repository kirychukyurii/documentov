<?php

class ModelDoctypeFolder extends Model
{

  public function addFolder($type = "")
  {
    $params = [
      'AuthorSUID' => $this->customer->getStructureId(),
      'Type' => $type
    ];
    $folder = $this->daemon->exec("NewFolder", $params);

    if ($folder !== null) {
      return $folder['uid'];
    }
    $this->redirect();
  }

  public function deleteFolder($folder_uid)
  {
    $result = $this->daemon->exec("DeleteFolder", $folder_uid);
    if ($result === null) {
      $this->redirect();
      return;
    }
    return $result;
  }

  public function editFolder($folder_uid)
  {
    $this->daemon->exec("SaveFolder", $folder_uid);
  }

  public function removeDraft($folder_uid)
  {
    return $this->daemon->exec("DeleteFolderDraft", $folder_uid);
  }

  public function getFolders($data)
  {
    $data["draft"] = "0";
    foreach ($data as &$d) {
      $d = (string) $d;
    }

    $folders = $this->daemon->exec("GetFolders", $data);
    if ($folders === null) {
      $this->redirect();
      exit;
    }
    $language_id = $this->config->get('config_language_id');

    foreach ($folders as &$folder) {
      $folder['folder_uid'] = $folder['uid'];
      $folder['name'] = $folder['description'][$language_id]['name'] ?? "";
      $folder['short_description'] = $folder['description'][$language_id]['short_description'] ?? "";
      $folder['full_description'] = $folder['description'][$language_id]['full_description'] ?? "";
    }

    return $folders;


    // $sql = "SELECT f.*, fd.name, fd.short_description, fd.full_description FROM " . DB_PREFIX . "folder f "
    //   . "LEFT JOIN " . DB_PREFIX . "folder_description fd ON (f.folder_uid = fd.folder_uid AND fd.language_id = '" . (int) $this->config->get('config_language_id') . "') "
    //   . "WHERE f.draft < 3 ";
    // if (!empty($data['filter_name'])) {
    //   $filter_name = explode(" ", $data['filter_name']);
    //   $filter_names = array();
    //   foreach ($filter_name as $word) {
    //     $filter_names[] = " fd.name LIKE '%" . $this->db->escape($word) . "%' ";
    //   }
    //   if ($filter_names) {
    //     $sql .= " AND " . implode(" AND ", array_unique($filter_names));
    //   }
    // }
    // $sql .= " ORDER BY " . $this->db->escape(($data['sort'] ?? " fd.name"));
    // $sql .= " " . $this->db->escape(($data['order'] ?? "ASC"));
    // if (!empty($data['start']) && !empty($data['limit'])) {
    //   $sql .= " LIMIT " . $data['start'] . "," . $data['limit'];
    // }
    // $query = $this->db->query($sql);
    // return $query->rows;
  }

  public function getFolder($folder_uid)
  {
    $data = [
      'uid' => $folder_uid,
      'draft'       => 1,
    ];

    $folder_info = $this->daemon->exec("GetFolder", $data);

    if ($folder_info === null) {
      $this->redirect();
      exit;
    }

    if (!$folder_info) {
      return [];
    }

    $language_id = $this->config->get('config_language_id');
    $folder_info['folder_uid'] = $folder_info['uid'];
    $folder_info['name'] = $folder_info['description'][$language_id]['name'] ?? "";
    $folder_info['short_description'] = $folder_info['description'][$language_id]['short_description'] ?? "";
    $folder_info['full_description'] = $folder_info['description'][$language_id]['full_description'] ?? "";

    $additional_params = [
      'toolbar' => $folder_info['show_toolbar'],
      'navigation' => $folder_info['show_title'],
      'collapse_group' => $folder_info['collapse_group'],
      'hide_selectors' => $folder_info['hide_selectors'],
      'show_count_group' => $folder_info['show_group_total'],
    ];
    if ($folder_info['additional_params']) {
      $add_params = json_decode($folder_info['additional_params'], true);
      if ($add_params && is_array($add_params)) {
        $additional_params = array_merge($additional_params, $add_params);
      }
    }

    $folder_info['additional_params'] = $additional_params;
    return $folder_info;


    // $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "folder f "
    //   . "LEFT JOIN folder_description fd ON (f.folder_uid = fd.folder_uid AND fd.language_id = '" . (int) $this->config->get('config_language_id') . "') "
    //   . "WHERE f.folder_uid='" . $this->db->escape($folder_uid) . "'");
    // $result = array();
    // if ($query->num_rows) {
    //   if ($query->row['draft'] && $query->row['draft_params']) {
    //     $draft_params = unserialize($query->row['draft_params']);
    //   }
    //   $additional_params = unserialize($query->row['additional_params']);
    //   if (!empty($draft_params['additional_params'])) {
    //     $additional_params = array_merge(is_array($additional_params) ? $additional_params : array(), $draft_params['additional_params']);
    //   }
    //   $result = array(
    //     'doctype_uid'       => $draft_params['doctype_uid'] ?? $query->row['doctype_uid'],
    //     'type'              => $query->row['type'],
    //     'date_added'        => $query->row['date_added'],
    //     'date_edited'       => $query->row['date_edited'],
    //     'user_uid'          => $query->row['user_uid'],
    //     'additional_params' => $additional_params,
    //     'draft'             => $query->row['draft'],
    //     'draft_params'      => $draft_params ?? array(),
    //     'name'              => $draft_params['folder_description'][$this->config->get('config_language_id')]['name'] ?? $query->row['name'],
    //     'short_description' => $draft_params['folder_description'][$this->config->get('config_language_id')]['short_description'] ?? $query->row['short_description'],
    //     'full_description'  => $draft_params['folder_description'][$this->config->get('config_language_id')]['full_description'] ?? $query->row['full_description']
    //   );
    // }
    // return $result;
  }

  public function getFolderDescriptions($folder_uid)
  {
    $folder_info = $this->getFolder($folder_uid);
    return $folder_info['description'];
  }


  /**
   * Возвращает поля доктайпа, которые еще не относятся к журналу (и могут быть добавлены в админке - Поля)
   * @param type $folder_uid
   */
  public function getDoctypeFieldsWithoutFolder($folder_uid, $language_id)
  {
    $query = $this->db->query("SELECT field_uid, name as field_name, type  FROM `field` WHERE `doctype_uid`=(SELECT DISTINCT `doctype_uid` FROM `folder` WHERE `folder_uid`='" . $this->db->escape($folder_uid) . "') AND field_uid NOT IN (SELECT field_uid FROM " . DB_PREFIX . "folder_field WHERE folder_uid='" . $this->db->escape($folder_uid) . "' AND language_id='" . (int) $language_id . "') AND setting=0 AND draft<2");
    return $query->rows;
  }

  public function getChildrenIds($parent_id)
  {
    $query = $this->db->query("SELECT folder_field_uid FROM " . DB_PREFIX . "folder_field WHERE grouping_parent_uid = '" . $this->db->escape($parent_id) . "' ");
    $result = array();
    foreach ($query->rows as $field) {
      $result[] = $field['folder_field_uid'];
    }
    foreach ($result as $child_id) {
      $result = array_merge($result, $this->getChildrenIds($child_id));
    }
    return $result;
  }

  public function saveFolder($folder_uid, $data)
  {
    $data['uid'] = $folder_uid;
    if (isset($data['additional_params']['toolbar'])) {
      $data['toolbar'] = $data['additional_params']['toolbar'];
      unset($data['additional_params']['toolbar']);
    }
    if (isset($data['additional_params']['navigation'])) {
      $data['navigation'] = $data['additional_params']['navigation'];
      unset($data['additional_params']['navigation']);
    }
    if (isset($data['additional_params']['collapse_group'])) {
      $data['collapse_group'] = $data['additional_params']['collapse_group'];
      unset($data['additional_params']['collapse_group']);
    }
    if (isset($data['additional_params']['hide_selectors'])) {
      $data['hide_selectors'] = $data['additional_params']['hide_selectors'];
      unset($data['additional_params']['hide_selectors']);
    }
    if (isset($data['additional_params']['show_count_group'])) {
      $data['show_count_group'] = $data['additional_params']['show_count_group'];
      unset($data['additional_params']['show_count_group']);
    }
    if (empty($data['additional_params'])) {
      unset($data['additional_params']);
    }
    // print_r($data);
    // exit;

    $this->daemon->exec("SaveFolderDraft", $data);
  }

  public function setDraft($folder_uid)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft=CASE WHEN draft=3 THEN 3 ELSE 1 END WHERE folder_uid='" . $this->db->escape($folder_uid) . "'");
  }


  // ПОЛЯ

  public function getFieldParent($field_uid)
  {
    $field_info = $this->getField($field_uid);
    return $field_info['grouping_parent_uid'] ?? "";
  }

  /**
   * Возвращает поле журнала, которое является дочерним по отшение к переданному field_uid 
   * @param type $folder_uid
   * @param type $field_uid
   */
  public function getFieldByGroupingField($folder_uid, $field_uid)
  {
    $language_id = $this->config->get('config_language_id');
    $data = [
      'folder_uid' => $folder_uid,
      'doctype_fuid' => $field_uid,
      'language_id' => $language_id,
    ];
    foreach ($data as &$d) {
      $d = (string) $d;
    }
    $grouping_parent_folder_field = $this->daemon->exec("GetFolderFields", $data);

    if (empty($grouping_parent_folder_field[$language_id])) {
      return [];
    }

    $data = [
      'folder_uid' => $folder_uid,
      'grouping_parent_ffuid' => $grouping_parent_folder_field[$language_id][0]['uid'],
      'language_id' => $language_id,
    ];
    $result = $this->daemon->exec("GetFolderFields", $data);
    return $result[$language_id] ?? [];
  }

  /**
   * Возвращает поле журнала по полю доктайпа
   * @param type $folder_uid
   * @param type $field_uid
   */
  public function getFieldByField($folder_uid, $field_uid)
  {
    $language_id = $this->config->get('config_language_id');
    $data = [
      'folder_uid' => $folder_uid,
      'doctype_fuid' => $field_uid,
      'language_id' => $language_id,
    ];
    $result = $this->daemon->exec("GetFolderFields", $data);
    return $result[$language_id][0] ?? [];
  }

  public function getDefaultSortField($folder_uid, $language_id)
  {
    $language_id = $this->config->get('config_language_id');
    $data = [
      'folder_uid' => $folder_uid,
      'tabling_sorting' => "1",
      'language_id' => $language_id,
    ];
    $result = $this->daemon->exec("GetFolderFields", $data);

    return $result[$language_id][0] ?? [];

    // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_field WHERE folder_uid = '" . $this->db->escape($folder_uid) . "' AND language_id='" . (int) $language_id . "' AND default_sort > 0 ");
    // return $query->row;
  }

  public function getField($folder_field_uid)
  {
    $data = [
      'uid'   => $folder_field_uid,
      'draft' => 1,
    ];
    $result = $this->daemon->exec("GetFolderField", $data);

    if (!$result) {
      return [];
    }
    $result['folder_field_uid'] = $result['uid'];
    $result['grouping'] = $result['is_grouping'];
    return $result;
  }

  public function getFields($data)
  {

    $daemon_data = [];

    foreach ($data as $k => $v) {
      if (!is_array($v)) {
        $daemon_data[$k] = (string) $v;
      }
    }
    // print_r($daemon_data);
    $folder_fields = $this->daemon->exec("GetFolderFields", $daemon_data);
    // print_r($folder_fields);
    // exit;

    if (!$folder_fields) {
      return [];
    }

    $result = [];
    $langID = $data['language_id'] ?? 0;

    foreach ($folder_fields as $lID => $fields) {
      if ($langID && $langID != $lID) {
        continue;
      }
      $result[$lID] = [];
      foreach ($fields as $field) {
        $field['grouping'] = $field['is_grouping'];
        if (
          (isset($data['grouping']) && $data['grouping'] != $field['grouping'])
          || (isset($data['grouping_tree_uid']) && $data['grouping_tree_uid'] != $field['grouping_tree_uid'])
          || (isset($data['tcolumn']) && $data['tcolumn'] != $field['tcolumn'])
          || (isset($data['grouping_parent_uid']) && $data['grouping_parent_uid'] != $field['grouping_parent_uid'])
          || (isset($data['not_grouping_parent_uid']) && $data['not_grouping_parent_uid'] == $field['grouping_parent_uid'])
        ) {
          continue;
        }
        $field['folder_field_uid'] = $field['uid'];
        $result[$lID][] = $field;
      }
    }

    if (!empty($data['language_id'])) {
      return $result[$langID] ?? [];
    }

    return $result;
  }

  private function prepareField($data, $draft, $folder_uid = "", $language_id = 0, $folder_field_uid = "")
  {
    $data_daemon = [];
    foreach ($data as $k => $v) {
      $data_daemon[str_replace("field_", "", $k)] = $v;
    }

    $data_daemon['draft'] = (int) $draft;
    $data_daemon['tcolumn'] = (int) $data_daemon['tcolumn'];
    $data_daemon['tcolumn_hidden'] = (int) $data_daemon['tcolumn_hidden'];
    $data_daemon['tcolumn_total'] = (int) $data_daemon['tcolumn_total'];
    $data_daemon['is_grouping'] = (int) $data_daemon['grouping'];
    $data_daemon['field_uid'] = $data_daemon['uid'];
    unset($data_daemon['uid']);
    if ($folder_uid) {
      $data_daemon['folder_uid'] = $folder_uid;
    }
    if ($language_id) {
      $data_daemon['language_id'] = (int) $language_id;
    }
    if ($folder_field_uid) {
      $data_daemon['uid'] = $folder_field_uid;
    }
    return $data_daemon;
  }

  public function addField($folder_uid, $language_id, $data, $draft = 1)
  {
    $data_daemon = $this->prepareField($data, $draft, $folder_uid, $language_id, "");
    $field_info = $this->daemon->exec("AddFolderField", $data_daemon);
    return $field_info['uid'] ?? 0;
  }

  public function editField($folder_field_uid, $data, $draft = 1, $sort_grouping = 0, $sort_tcolumn = 0)
  {
    $data_daemon = $this->prepareField($data, $draft, "", 0, $folder_field_uid);
    if ($sort_grouping || isset($data_daemon['sort_grouping'])) {
      $data_daemon['sort_grouping'] = $sort_grouping ? $sort_grouping : $data_daemon['sort_grouping'];
    }
    if ($sort_tcolumn || isset($data_daemon['sort_tcolumn'])) {
      $data_daemon['sort_tcolumn'] = $sort_tcolumn ? $sort_tcolumn : $data_daemon['sort_tcolumn'];
    }

    $field_info = $this->daemon->exec("EditFolderField", $data_daemon);

    if (!$field_info) {
      return [];
    }
    return $field_info;
  }

  public function editSortField($folder_field_uid, $sort_grouping, $sort_tcolumn)
  {
    $field_info = $this->getField($folder_field_uid);

    $this->editField($folder_field_uid, $field_info, 1, $sort_grouping, $sort_tcolumn);
  }

  public function removeField($folder_field_uid)
  {
    $field_info = $this->getField($folder_field_uid, 1);
    $this->editField($folder_field_uid, $field_info, 2);

    // $this->db->query("UPDATE " . DB_PREFIX . "folder_field SET draft=2 WHERE folder_field_uid='" . $this->db->escape($folder_field_uid) . "'");
    // $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft=CASE WHEN draft=3 THEN 3 ELSE 1 END WHERE folder_uid = (SELECT folder_uid FROM " . DB_PREFIX . "folder_field WHERE folder_field_uid = '" . $this->db->escape($folder_field_uid) . "')");
  }

  public function undoRemoveField($folder_field_uid)
  {
    $field_info = $this->getField($folder_field_uid, 1);
    $this->editField($folder_field_uid, $field_info, -1);
    // $this->db->query("UPDATE " . DB_PREFIX . "folder_field SET draft=(CASE WHEN draft_params='' THEN 0 ELSE 1 END) WHERE folder_field_uid='" . $this->db->escape($folder_field_uid) . "'");
  }

  // ФИЛЬТРЫ 
  public function getFilter($filter_uid)
  {
    $data = [
      'uid' => $filter_uid,
      'draft'      => 1,
    ];
    // print_r($data);
    // exit;

    $filter = $this->daemon->exec("GetFolderFilter", $data);
    // echo "mdl:";
    // print_r($filter);
    // exit;

    if (!$filter) {
      return [];
    };


    $filter['folder_filter_uid'] = $filter['uid'];
    $filter['condition'] = $filter['condition_value'];
    $filter['action_params'] = json_decode($filter['action_params'], true);

    return $filter;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_filter WHERE folder_filter_uid = '" . $this->db->escape($filter_uid) . "'");
    $result = array();
    if ($query->num_rows) {
      $filter = $query->row;
      if ($filter['draft'] && $filter['draft_params']) {
        $params = unserialize($filter['draft_params']);
      } else {
        $params = $filter;
      }
      $result['folder_uid']  = $filter['folder_uid'];
      $result['field_uid']          = $params['field_uid'];
      $result['condition']         = $params['condition_value'];
      $result['type_value']        = $params['type_value'];
      $result['value']             = $params['value'];
      $result['action']            = $params['action'];
      $result['action_params']     = $filter['draft'] && $filter['draft_params'] ? $params['action_params'] : unserialize($filter['action_params']);
      $result['draft']             = $filter['draft'];
    }
    return $result;
  }

  public function getFilters($folder_uid)
  {
    $data = [
      'folder_uid' => $folder_uid,
      'draft'      => 1,
    ];
    $filters = $this->daemon->exec("GetFolderFilters", $data);

    if (!$filters) {
      return [];
    };

    foreach ($filters as &$filter) {
      $filter['folder_filter_uid'] = $filter['uid'];
      $filter['condition'] = $filter['condition_value'];
      $filter['action_params'] = json_decode($filter['action_params'], true);
    }

    return $filters;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_filter WHERE folder_uid = '" . $this->db->escape($folder_uid) . "'");
    $result = array();
    foreach ($query->rows as $filter) {
      if ($filter['draft'] && $filter['draft_params']) {
        $params = unserialize($filter['draft_params']);
      } else {
        $params = $filter;
      }
      $result[] = array(
        'folder_filter_uid'  => $filter['folder_filter_uid'],
        'field_uid'          => $params['field_uid'],
        'condition'         => $params['condition_value'],
        'type_value'        => $params['type_value'],
        'value'             => $params['value'],
        'action'            => $params['action'],
        'action_params'     => $filter['draft'] && $filter['draft_params'] ? $params['action_params'] : unserialize($filter['action_params']),
        'draft'             => $filter['draft']
      );
    }
    return $result;
  }

  private function prepareFilter($data, $draft, $folder_uid, $filter_uid = "")
  {
    $data['field_uid'] = $data['filter_field_uid'] ?? $data['field_uid'];
    $data['condition_value'] = $data['filter_condition_value'] ?? $data['condition_value'];
    $data['type_value'] = $data['type_condition_value'] ?? $data['type_value'];
    if ($data['type_value'] == "var") {
      $data['value'] = $data['field_value_var'] ?? $data['value'];
    } else {
      $data['value'] = $data['filter_value'] ?? $data['value'];
    }
    $data['action'] = $data['filter_action_value'] ?? $data['action'];
    $data['action_params'] = $data['filter_action_params'] ?? $data['action_params'];
    if (is_array($data['action_params'])) {
      $data['action_params'] = $this->jsonEncode($data['action_params']);
    }
    $result = [];
    foreach ($data as $k => $v) {
      if (strpos($k, "filter_") === 0 || $k == "type_condition_value" || $k == "field_value_var") {
        continue;
      }
      $result[$k] = $v;
    }
    $result['draft'] = $draft;
    if ($folder_uid) {
      $result['folder_uid'] = $folder_uid;
    }
    if ($filter_uid) {
      $result['uid'] = $filter_uid;
    }
    return $result;
  }

  public function addFilter($folder_uid, $data, $draft = 3)
  {
    // print_r($data);
    // exit;

    // $data_daemon = $this->prepareField($data, $draft, $folder_uid, $language_id, "");
    $data_daemon = $this->prepareFilter($data, $draft, $folder_uid);
    // print_r($data_daemon);
    // exit;
    $field_info = $this->daemon->exec("AddFolderFilter", $data_daemon);
    return $field_info['uid'] ?? 0;




    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($data['filter_field_uid']);
    $this->load->model('extension/field/' . $field_info['type']);
    $model = "model_extension_field_" . $field_info['type'];
    $draft_filter = serialize(array(
      'field_uid'                      => $data['filter_field_uid'],
      'condition_value'               => $data['filter_condition_value'],
      'type_value'                    => $data['type_condition_value'],
      'value'                         => $data['type_condition_value'] == 'var' ? $data['field_value_var'] : $this->$model->getValue($data['filter_field_uid'], 0, $data['filter_value']),
      'action'                        => $data['filter_action_value'],
      'action_params'                 => $data['filter_action_params']
    ));
    $query = $this->db->query("SELECT UUID() AS uid");
    $folder_filter_uid = $query->row['uid'];
    $sql = "INSERT INTO " . DB_PREFIX . "folder_filter SET "
      . "folder_filter_uid = '" . $folder_filter_uid . "', "
      . "folder_uid='" . $this->db->escape($folder_uid) . "', "
      . "draft='" . (int) $draft . "', "
      . "draft_params = '" . $this->db->escape($draft_filter) . "' ";
    $this->db->query($sql);
    $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft = (CASE WHEN draft='3' THEN 3 ELSE 1 END) WHERE folder_uid = '" . $this->db->escape($folder_uid) . "'");
    return $folder_filter_uid;
  }

  public function editFilter($filter_uid, $data, $draft = 1)
  {
    $data_daemon = $this->prepareFilter($data, $draft, "", $filter_uid);

    $field_info = $this->daemon->exec("EditFolderFilter", $data_daemon);

    if (!$field_info) {
      return [];
    }
    return $field_info;

    // $this->load->model('doctype/doctype');
    // $field_info = $this->model_doctype_doctype->getField($data['filter_field_uid']);
    // $this->load->model('extension/field/' . $field_info['type']);
    // $model = "model_extension_field_" . $field_info['type'];
    // $draft_filter = serialize(array(
    //   'field_uid'                      => $data['filter_field_uid'],
    //   'condition_value'               => $data['filter_condition_value'],
    //   'type_value'                    => $data['type_condition_value'],
    //   'value'                         => $data['type_condition_value'] == 'var' ? $data['field_value_var'] : $this->$model->getValue($data['filter_field_uid'], 0, $data['filter_value']),
    //   'action'                        => $data['filter_action_value'],
    //   'action_params'                 => $data['filter_action_params']
    // ));
    // $sql = "UPDATE " . DB_PREFIX . "folder_filter SET "
    //   . "draft=CASE WHEN draft='3' THEN 3 ELSE " . (int) $draft . " END, "
    //   . "draft_params = '" . $this->db->escape($draft_filter) . "' "
    //   . "WHERE folder_filter_uid = '" . $this->db->escape($filter_uid) . "'";
    // $this->db->query($sql);
    // $filter_info = $this->getFilter($filter_uid);
    // $this->setDraft($filter_info['folder_uid']);
  }

  public function removeFilter($filter_uid)
  {
    $filter_info = $this->getFilter($filter_uid, 1);
    $this->editFilter($filter_uid, $filter_info, 2);
    // $this->db->query("UPDATE " . DB_PREFIX . "folder_filter SET draft=2 WHERE folder_filter_uid='" . $this->db->escape($filter_uid) . "'");
    // $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft=CASE WHEN draft=3 THEN 3 ELSE 1 END WHERE folder_uid = (SELECT folder_uid FROM " . DB_PREFIX . "folder_filter WHERE folder_filter_uid = '" . $this->db->escape($filter_uid) . "')");
  }

  public function undoRemoveFilter($filter_uid)
  {
    $filter_info = $this->getFilter($filter_uid, 1);
    $this->editFilter($filter_uid, $filter_info, -1);
    // $this->db->query("UPDATE " . DB_PREFIX . "folder_filter SET draft=(CASE WHEN draft_params='' THEN 0 ELSE 1 END) WHERE folder_filter_uid='" . $this->db->escape($filter_uid) . "'");
  }

  public function getConditions()
  {
    return array(
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
        'value'    => 'moreequal',
        'title'  => $this->language->get('text_condition_moreequal')
      ),
      array(
        'value'    => 'less',
        'title'  => $this->language->get('text_condition_less')
      ),
      array(
        'value'    => 'lessequal',
        'title'  => $this->language->get('text_condition_lessequal')
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
  }

  public function getFilterActions()
  {
    $this->load->language('doctype/folder');
    return array(
      array(
        'value'     => 'hide',
        'title'     => $this->language->get('text_action_hide')
      ),
      array(
        'value'     => 'style',
        'title'     => $this->language->get('text_action_style')
      ),
      array(
        'value'     => 'font',
        'title'     => $this->language->get('text_action_font')
      )
    );
  }

  public function getVariables()
  {
    return array(
      'var_customer_id'                   => $this->language->get('text_var_customer_uid'),
      'var_customer_name'                 => $this->language->get('text_var_customer_name'),
      'var_current_time'                  => $this->language->get('text_var_current_datetime'),
      'var_current_date'                  => $this->language->get('text_var_current_date'),

    );
  }

  // КНОПКИ

  public function getButton($button_uid)
  {
    $data = [
      'uid' => $button_uid,
      'draft' => 1,
    ];
    $button = $this->daemon->exec("GetButton", $data);

    if (!$button) {
      return [];
    }

    $button['folder_button_uid'] = $button['uid'];
    $button['folder_uid'] = $button['parent_uid'];
    $button['name'] = !empty($button['descriptions'][(int) $this->config->get('config_language_id')]['name']) ? $button['descriptions'][(int) $this->config->get('config_language_id')]['name'] : "";
    $button['picture25'] = "";
    if ($button['picture']) {
      $this->load->model('tool/image');
      $button['picture25'] = $this->model_tool_image->resize($button['picture'], 28, 28);
    }
    $button['route_uids'] = $button['routes'];
    $button['routes'] = $button['route_names'];
    $button['action_params'] = $button['action_type'];

    return $button;

    // $this->load->model('doctype/doctype');
    // $this->load->language('doctype/folder');
    // $query = $this->db->query(
    //   "SELECT DISTINCT * FROM " . DB_PREFIX . "folder_button "
    //     . "WHERE folder_button_uid = '" . $this->db->escape($button_uid) . "'"
    // );
    // if (!$query->num_rows) {
    //   return [];
    // }
    // $button = $query->row;
    // if ($button['draft'] && $button['draft_params']) {
    //   $draft_button = unserialize($button['draft_params']);
    //   $descriptions = $draft_button['description'];
    //   $fields = array();
    //   if ($draft_button['field']) {
    //     foreach ($draft_button['field'] as $field_uid) {
    //       $field_info = $this->model_doctype_doctype->getField($field_uid);
    //       $name = "";
    //       if ($field_info['setting']) {
    //         $doctype_info = $this->model_doctype_doctype->getDoctype($field_info['doctype_uid']);
    //         $name = $doctype_info['name'] . " - ";
    //       }
    //       $name .= $field_info['name'];
    //       $fields[] = array(
    //         'field_uid'      => $field_uid,
    //         'name'          => $name
    //       );
    //     }
    //   }
    //   $routes = array();
    //   if ($draft_button['route']) {
    //     foreach ($draft_button['route'] as $route_uid) {
    //       $routes[] = array(
    //         'route_uid'      => $route_uid,
    //         'name'          => $route_uid ? $this->model_doctype_doctype->getRoute($route_uid)['name'] : $this->language->get('text_all_routes')
    //       );
    //     }
    //   } else {
    //     $routes[] = array(
    //       'route_uid'      => 0,
    //       'name'          => $this->language->get('text_all_routes')
    //     );
    //   }
    //   $picture = $draft_button['picture'];
    //   $hide_button_name = $picture ? $draft_button['hide_button_name'] : 0;
    //   $color = $draft_button['color'];
    //   $background = $draft_button['background'];
    //   $action = $draft_button['action'];
    //   $action_params = unserialize($draft_button['action_params']);
    //   $action_log = $draft_button['action_log'];
    //   $action_move_route_uid = $draft_button['action_move_route_uid'];
    //   if (!empty($draft_button['sort'])) {
    //     $sort = $draft_button['sort'];
    //   } else {
    //     $sort = $button['sort'];
    //   }
    // } else {
    //   $descriptions = $this->getButtonDescriptions($button['folder_button_uid']);
    //   $fields = array();
    //   foreach ($this->getButtonFields($button['folder_button_uid']) as $field) {
    //     $fields[] = array(
    //       'field_uid'     => $field['field_uid'],
    //       'name'          => $this->model_doctype_doctype->getFieldName($field['field_uid'])
    //     );
    //   }

    //   $routes = $this->getButtonRoutes($button['folder_button_uid']);
    //   $picture = $button['picture'];
    //   $hide_button_name = $picture ? $button['hide_button_name'] : 0;
    //   $color = $button['color'];
    //   $background = $button['background'];
    //   $action = $button['action'];
    //   $action_params = unserialize($button['action_params']);
    //   $action_log = $button['action_log'];
    //   $action_move_route_uid = $button['action_move_route_uid'];
    //   $sort = $button['sort'];
    // }
    // $this->load->model('tool/image');
    // if ($picture) {
    //   if (empty($descriptions[(int) $this->config->get('config_language_id')]['name'])) {
    //     $picture_25 = $this->model_tool_image->resize($picture, 28, 28);
    //   } else {
    //     $picture_25 = $this->model_tool_image->resize($picture, 28, 28);
    //   }
    // } else {
    //   $picture_25 = "";
    // }
    // $result = array(
    //   'folder_button_uid'         => $button['folder_button_uid'],
    //   'folder_uid'                => $button['folder_uid'],
    //   'name'                      => !empty($descriptions[(int) $this->config->get('config_language_id')]['name']) ? $descriptions[(int) $this->config->get('config_language_id')]['name'] : "",
    //   'picture'                   => $picture,
    //   'hide_button_name'          => $hide_button_name,
    //   'color'                     => $color,
    //   'background'                => $background,
    //   'picture25'                 => $picture_25,
    //   'draft'                     => $button['draft'],
    //   'draft_params'              => $button['draft_params'] ? unserialize($button['draft_params']) : "",
    //   'descriptions'              => $descriptions,
    //   'fields'                    => $fields,
    //   'routes'                    => $routes,
    //   'action'                    => $action,
    //   'action_params'             => $action_params,
    //   'action_log'                => $action_log,
    //   'action_move_route_uid'     => $action_move_route_uid,
    //   'sort'                      => $sort
    // );

    // return $result;
  }

  public function getButtons($folder_uid)
  {
    $data = [
      'draft' => "1",
      'parent_uid' => $folder_uid,
    ];
    $buttons = $this->daemon->exec("GetButtons", $data);
    // print_r($buttons);
    // exit;

    if (empty($buttons[$folder_uid])) {
      return [];
    }

    // $result = [];
    $this->load->model('tool/image');
    foreach ($buttons[$folder_uid] as &$button) {
      $button['folder_button_uid'] = $button['uid'];
      $button['folder_uid'] = $button['parent_uid'];
      $button['name'] = isset($button['descriptions'][(int) $this->config->get('config_language_id')]['name']) ? $button['descriptions'][(int) $this->config->get('config_language_id')]['name'] : "";
      if ($button['picture']) {
        $button['picture25'] = $this->model_tool_image->resize($button['picture'], 28, 28);
      } else {
        $button['picture25'] = "";
      }
      $button['routes'] = $button['route_names'];
      $button['action_params'] = $button['action_type'];
      // 'routes'            => $routes,
      // 'action_name'       => $this->load->controller('extension/action/' . $action . "/getTitle"),
      // 'sort'              => $sort


    }
    // print_r($buttons[$folder_uid]);
    // exit;

    return $buttons[$folder_uid];

    // $this->load->model('doctype/doctype');
    // $this->load->language('doctype/folder');
    // $query = $this->db->query(
    //   "SELECT * FROM " . DB_PREFIX . "folder_button "
    //     . "WHERE folder_uid = '" . $this->db->escape($folder_uid) . "'"
    // );
    // $result = array();
    // foreach ($query->rows as $button) {
    //   if ($button['draft'] && $button['draft_params']) {
    //     $draft_button = unserialize($button['draft_params']);
    //     $descriptions = $draft_button['description'];
    //     $fields = array();
    //     if ($draft_button['field']) {
    //       foreach ($draft_button['field'] as $field_uid) {
    //         $fields[] = array(
    //           'field_uid'      => $field_uid,
    //           'name'          => $this->model_doctype_doctype->getFieldName($field_uid)
    //         );
    //       }
    //     }
    //     $routes = array();
    //     if ($draft_button['route']) {
    //       foreach ($draft_button['route'] as $route_uid) {
    //         $routes[] = array(
    //           'route_uid'      => $route_uid,
    //           'name'          => $route_uid ? $this->model_doctype_doctype->getRoute($route_uid)['name'] : $this->language->get('text_all_routes')
    //         );
    //       }
    //     } else {
    //       $routes[] = array(
    //         'route_uid'      => 0,
    //         'name'          => $this->language->get('text_all_routes')
    //       );
    //     }
    //     $picture = $draft_button['picture'];
    //     $hide_button_name = $picture ? $draft_button['hide_button_name'] : 0;
    //     $color = $draft_button['color'];
    //     $background = $draft_button['background'];
    //     $action = $draft_button['action'];
    //     if (!empty($draft_button['sort'])) {
    //       $sort = $draft_button['sort'];
    //     } else {
    //       $sort = $button['sort'];
    //     }
    //   } else {
    //     $descriptions = $this->getButtonDescriptions($button['folder_button_uid']);
    //     $fields = $this->getButtonFields($button['folder_button_uid']);
    //     $routes = $this->getButtonRoutes($button['folder_button_uid']);
    //     $picture = $button['picture'];
    //     $hide_button_name = $picture ? $button['hide_button_name'] : 0;
    //     $color = $button['color'];
    //     $background = $button['background'];
    //     $action = $button['action'];
    //     $sort = $button['sort'];
    //   }

    //   $this->load->model('tool/image');
    //   if ($picture) {
    //     if (empty($descriptions[(int) $this->config->get('config_language_id')]['name'])) {
    //       $picture_25 = $this->model_tool_image->resize($picture, 28, 28);
    //     } else {
    //       $picture_25 = $this->model_tool_image->resize($picture, 28, 28);
    //     }
    //   } else {
    //     $picture_25 = "";
    //   }
    //   $result[] = array(
    //     'folder_button_uid' => $button['folder_button_uid'],
    //     'name'              => isset($descriptions[(int) $this->config->get('config_language_id')]['name']) ? $descriptions[(int) $this->config->get('config_language_id')]['name'] : "",
    //     'picture'           => $picture,
    //     'hide_button_name'  => $hide_button_name,
    //     'color'             => $color,
    //     'background'        => $background,
    //     'picture25'         => $picture_25,
    //     'draft'             => $button['draft'],
    //     'descriptions'      => $descriptions,
    //     'fields'            => $fields,
    //     'routes'            => $routes,
    //     'action_name'       => $this->load->controller('extension/action/' . $action . "/getTitle"),
    //     'sort'              => $sort
    //   );
    // }
    // usort($result, function ($a, $b) {
    //   if ($a['sort'] == $b['sort']) {
    //     return 0;
    //   }
    //   return $a['sort'] > $b['sort'] ? 1 : 0;
    // });
    // return $result;
  }

  public function addButton($folder_uid, $data, $draft = 1)
  {
    if (!$folder_uid) {
      return;
    }
    $data['parent_uid'] = $folder_uid;
    $data['draft'] = $draft > 1 ? 1 : $draft;

    $data_button = $data;
    $data_button['hide_button_name'] = (int) $data['hide_button_name'];
    $data_button['action_log'] = (int) $data['action_log'];
    $data_button['action_params'] = [];
    $data_params = array(
      'folder_button_uid' => "",
      'params'    => $data
    );
    $data_button['action_params'] = $this->jsonEncode($this->load->controller('extension/action/' . $data['button_action'] . '/setParams', $data_params));
    $data_button['action'] = $data['button_action'];
    unset($data_button['button_action']);
    $data_button['picture'] = $data['button_picture'];
    unset($data_button['button_picture']);
    $data_button['color'] = $data['button_color'];
    unset($data_button['button_color']);
    $data_button['descriptions'] = $data['button_descriptions'];
    unset($data_button['button_descriptions']);
    $data_button['background'] = $data['button_background'];
    unset($data_button['button_background']);
    $data_button['field_delegates'] = $data['button_field'] ?? [];
    unset($data_button['button_field']);
    $data_button['routes'] = $data['button_route'] ?? [];
    if (!is_array($data_button['routes'])) {
      $data_button['routes'] = ["0"];
    }

    $button = $this->daemon->exec("AddButton", $data_button);

    if ($button === null) {
      $this->redirect();
    }
    return $button;

    if ($data['button_action']) {
      $data_params = array(
        'folder_uid' => $folder_uid,
        'params'    => $data
      );
      $action_params = $this->load->controller('extension/action/' . $data['button_action'] . '/setParams', $data_params);
    } else {
      $action_params = array();
    }
    $query = $this->db->query("SELECT MAX(sort) AS sort FROM " . DB_PREFIX . "folder_button WHERE folder_uid = '" . $this->db->escape($folder_uid) . "' ");
    $sort = (int) $query->row['sort'];
    $draft_button = serialize(array(
      'description'                   => $data['button_descriptions'],
      'picture'                       => $data['button_picture'],
      'hide_button_name'              => $data['hide_button_name'],
      'color'                         => $data['button_color'],
      'background'                    => $data['button_background'],
      'field'                         => !empty($data['button_field']) ? $data['button_field'] : "",
      'route'                         => !empty($data['button_route']) ? $data['button_route'] : array(0),
      'action'                        => !empty($data['button_action']) ? $data['button_action'] : "",
      'action_log'                    => !empty($data['action_log']) ? (int) $data['action_log'] : "0",
      'action_move_route_uid'         => !empty($data['action_move_route_uid']) ? $this->db->escape($data['action_move_route_uid']) : "0",
      'action_params'                 => serialize($action_params),
      'sort'                          => ++$sort
    ));
    $query_uid = $this->db->query("SELECT UUID() AS uid");
    $folder_button_uid = $query_uid->row['uid'];
    $sql = "INSERT INTO " . DB_PREFIX . "folder_button SET "
      . "folder_button_uid = '" . $folder_button_uid . "', "
      . "folder_uid='" . $this->db->escape($folder_uid) . "', "
      . "draft='" . (int) $draft . "', "
      . "draft_params = '" . $this->db->escape($draft_button) . "', "
      . "sort = '" . $sort . "'";
    $this->db->query($sql);
    $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft = (CASE WHEN draft='3' THEN 3 ELSE 1 END) WHERE folder_uid = '" . $this->db->escape($folder_uid) . "'");
    return $folder_button_uid;
  }

  public function editButton($button_uid, $data, $draft = 1, $sort = 0)
  {
    if (!$button_uid) {
      return;
    }

    $button_info = $this->getButton($button_uid);

    $data_button = $data;
    $data_button['sort'] = $sort ? $sort : $button_info['sort'];

    $data_button['uid'] = $button_uid;
    $data_button['parent_uid'] = $button_info['parent_uid'];
    $data_button['draft'] = (int) $draft;

    $data_button['hide_button_name'] = (int) $data['hide_button_name'];
    $data_button['action_log'] = (int) $data['action_log'];
    $data_button['action_params'] = [];
    if (isset($data['button_action'])) { // сохранение формы
      $data_params = array(
        'folder_button_uid' => $button_uid,
        'params'    => $data
      );
      $data_button['action_params'] = $this->jsonEncode($this->load->controller('extension/action/' . $data['button_action'] . '/setParams', $data_params));
      $data_button['action'] = $data['button_action'];
      unset($data_button['button_action']);
      $data_button['picture'] = $data['button_picture'];
      unset($data_button['button_picture']);
      $data_button['color'] = $data['button_color'];
      unset($data_button['button_color']);
      $data_button['descriptions'] = $data['button_descriptions'];
      unset($data_button['button_descriptions']);
      $data_button['background'] = $data['button_background'];
      unset($data_button['button_background']);
      $data_button['field_delegates'] = $data['button_field'] ?? [];
      unset($data_button['button_field']);
      $data_button['routes'] = $data['button_route'] ?? [];
    } else { // удаление/сортировка
      $data_button['action_params'] = $this->jsonEncode($data_button['action_type']);
      unset($data_button['action_type']);
      $data_button['routes'] = $data_button['route_uids'];
    }
    if (!is_array($data_button['routes'])) {
      $data_button['routes'] = ["0"];
    }
    $button = $this->daemon->exec("EditButton", $data_button);
    if ($button === null) {
      $this->redirect();
    }
    return $button;

    // if ($data['button_action']) {
    //   $data_params = array(
    //     'folder_button_uid' => $button_uid,
    //     'params'    => $data
    //   );
    //   $action_params = $this->load->controller('extension/action/' . $data['button_action'] . '/setParams', $data_params);
    // } else {
    //   $action_params = array();
    // }

    // $draft_button = array(
    //   'description'                   => $data['button_descriptions'],
    //   'picture'                       => $data['button_picture'],
    //   'hide_button_name'              => $data['hide_button_name'],
    //   'color'                         => $data['button_color'],
    //   'background'                    => $data['button_background'],
    //   'field'                         => !empty($data['button_field']) ? $data['button_field'] : "",
    //   'route'                         => !empty($data['button_route']) ? $data['button_route'] : array(0),
    //   'action'                        => !empty($data['button_action']) ? $data['button_action'] : "",
    //   'action_log'                    => !empty($data['action_log']) ? (int) $data['action_log'] : "0",
    //   'action_move_route_uid'          => $this->db->escape($data['action_move_route_uid'] ?? 0),
    //   'action_params'                 => serialize($action_params),
    // );

    // $button_info = $this->getButton($button_uid);
    // if (!empty($button_info['draft_params']['sort'])) {
    //   $draft_button['sort'] = $button_info['draft_params']['sort'];
    // }

    // $this->db->query("UPDATE " . DB_PREFIX . "folder_button SET "
    //   . "draft= (CASE WHEN draft='3' THEN 3 ELSE " . (int) $draft . " END), "
    //   . "draft_params = '" . $this->db->escape(serialize($draft_button)) . "' "
    //   . "WHERE folder_button_uid = '" . $this->db->escape($button_uid) . "' ");
    // $this->setDraft($button_info['folder_uid']);
  }

  public function editSortButton($folder_button_uid, $sort)
  {
    $button_info = $this->getButton($folder_button_uid);
    $this->editButton($folder_button_uid, $button_info, 0, $sort);
    // $button_info = $this->getButton($folder_button_uid);
    // $fields = array();
    // if ($button_info['fields']) {
    //   foreach ($button_info['fields'] as $field) {
    //     $fields[] = $field['field_uid'];
    //   }
    // }
    // $routes = array();
    // if ($button_info['routes']) {
    //   foreach ($button_info['routes'] as $route) {
    //     $routes[] = $route['route_uid'];
    //   }
    // }
    // $draft_params = array(
    //   'description'                   => $button_info['descriptions'],
    //   'picture'                       => $button_info['picture'],
    //   'hide_button_name'              => $button_info['hide_button_name'],
    //   'color'                         => $button_info['color'],
    //   'background'                    => $button_info['background'],
    //   'field'                         => $fields,
    //   'route'                         => $routes,
    //   'action'                        => $button_info['action'],
    //   'action_log'                    => $button_info['action_log'],
    //   'action_move_route_uid'          => $button_info['action_move_route_uid'],
    //   'action_params'                 => serialize($button_info['action_params']),
    //   'sort'                          => $sort
    // );


    // $this->db->query(
    //   "UPDATE " . DB_PREFIX . "folder_button SET "
    //     . "draft = CASE WHEN draft=3 THEN 3 ELSE 1 END, "
    //     . "draft_params = '" . $this->db->escape(serialize($draft_params)) . "' "
    //     . "WHERE folder_button_uid = '" . $this->db->escape($folder_button_uid) . "' "
    // );
    // $this->setDraft($button_info['folder_uid']);
  }

  public function removeButton($button_uid)
  {
    $button_info = $this->getButton($button_uid);
    $this->editButton($button_uid, $button_info, 2);
    // $this->db->query("UPDATE " . DB_PREFIX . "folder_button SET draft=2 WHERE folder_button_uid='" . $this->db->escape($button_uid) . "'");
    // $this->db->query("UPDATE " . DB_PREFIX . "folder SET draft=CASE WHEN draft=3 THEN 3 ELSE 1 END WHERE folder_uid = (SELECT folder_uid FROM " . DB_PREFIX . "folder_button WHERE folder_button_uid = '" . $this->db->escape($button_uid) . "')");
  }

  public function undoRemoveButton($button_uid)
  {
    $button_info = $this->getButton($button_uid);
    $this->editButton($button_uid, $button_info, -1);
    // $this->db->query("UPDATE " . DB_PREFIX . "folder_button SET draft=(CASE WHEN draft_params='' THEN 0 ELSE 1 END) WHERE folder_button_uid='" . $this->db->escape($button_uid) . "'");
  }



  private function redirect()
  {
    $this->response->redirect($this->url->link('error/daemon_not_started', true));
  }

  private function jsonEncode($v)
  {
    return json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
  }
}

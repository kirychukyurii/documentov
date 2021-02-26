<?php

class ModelDocumentFolder extends Model
{
  private $step = 0;
  public function getFolder($folder_uid)
  {
    $data = [
      'uid' => $folder_uid,
      'draft'       => 0,
    ];

    $folder_info = $this->daemon->exec("GetFolder", $data);
    if (!$folder_info) {
      return [];
    }

    $folder_info['additional_params'] = [
      'toolbar' => $folder_info['show_toolbar'],
      'navigation' => $folder_info['show_title'],
      'collapse_group' => $folder_info['collapse_group'],
      'hide_selectors' => $folder_info['hide_selectors'],
      'show_count_group' => $folder_info['show_group_total'],
    ];
    $language_id = $this->config->get('config_language_id');

    $folder_info['name'] = $folder_info['description'][$language_id]['name'] ?? "";
    // print_r($folder_info);
    // exit;
    return $folder_info;

    // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder f "
    //   . "LEFT JOIN " . DB_PREFIX . "folder_description fd ON (f.folder_uid = fd.folder_uid AND fd.language_id = '" . (int) $this->config->get('config_language_id') . "') "
    //   . "WHERE f.folder_uid = '" . $this->db->escape($folder_uid) . "' ");
    // $result = $query->row;
    // if (!empty($result['additional_params'])) {
    //   $result['additional_params'] = unserialize($result['additional_params']);
    // }
    // return $result;
  }

  public function getFields($data)
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "folder_field ff "
      . "LEFT JOIN " . DB_PREFIX . "field f ON (f.field_uid = ff.field_uid) "
      . "WHERE ff.folder_uid = '" . $this->db->escape($data['folder_uid']) . "' "
      . "AND language_id='" . (int) $this->config->get('config_language_id') . "' "
      . "AND f.field_uid IS NOT NULL "; //проверка на наличие поля у доктайпа, вдруг было удалено
    if (!empty($data['grouping'])) {
      $sql .= "AND ff.is_grouping = '" . (int) $data['grouping'] . "' ";
      $sort = " ORDER BY ff.sort_grouping ASC ";
    }
    if (!empty($data['grouping_parent_uid'])) {
      $sql .= "AND ff.grouping_parent_uid = '" . (int) $data['grouping_parent_uid'] . "' ";
    }
    if (!empty($data['tcolumn'])) {
      $sql .= "AND ff.tcolumn = '" . (int) $data['tcolumn'] . "' ";
      $sort = " ORDER BY ff.sort_tcolumn ASC ";
    }
    if (!empty($data['language_id'])) {
      $sql .= "AND ff.language_id = '" . (int) $data['language_id'] . "' ";
    }
    if ($sort) {
      $sql .= $sort;
    }

    $query = $this->db->query($sql);
    $result = [];
    foreach ($query->rows as $row) {
      $row['grouping'] = $row['is_grouping'];
      $result[] = $row;
    }
    return $result;
  }

  private function convertFieldValueTree($tree, $separator = false)
  {

    $result = array();
    $i = 1;

    if ($separator) {
      $result[]['separator'] = "<ul>";
    }
    foreach ($tree as $path) {
      $result[] = array(
        'name'          => $path['name'],
        'field_uid'      => $path['field_uid'],
        'document_uid'   => $path['document_uid'],
        'separator'     => ""
      );
      if (++$this->step < 20) {
        $result = array_merge($result, $path['children'] ? $this->convertFieldValueTree($path['children'], true) : array());
      } else {
        $result = array_merge($result, array()); //ограничение на кол-во рекурсий для исключения зацикливания
      }

      $i++;
    }
    if ($separator) {
      $result[]['separator'] = "</ul>";
    }
    return $result;
  }

  public function getGroupingTree($folder_uid, $parent_id = 0)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $folder_info = $this->getFolder($folder_uid);
    $data = array(
      'folder_uid'             => $folder_uid,
      'grouping'              => 1,
      'grouping_parent_uid'    => $parent_id
    );
    $result = array();
    $i = 1;
    foreach ($this->getFields($data) as $field) {
      if (!$field['grouping_parent_uid']) {
        $field_info = $this->model_doctype_doctype->getField($field['field_uid'], 0);
        if ($field['grouping_tree_uid']) {
          $values = $this->convertFieldValueTree($this->model_document_document->getFieldValueTree($field['field_uid'], $field['grouping_tree_uid']));
        } else {
          $doctype_uid = $folder_info['doctype_uid'];
          $query = $this->db->query("SELECT fv.display_value, fv.field_uid, fv.document_uid, d.author_uid, d.department_uid FROM field_value_" . $this->db->escape($field_info['type']) . " fv "
            . " LEFT JOIN document d ON (d.document_uid = fv.document_uid) "
            . " WHERE "
            . "d.draft < 2 " //если убрать, то формируются группы с учетом драфтовых доков (см. folder_uid=4fc2f0bf-d66b-11e8-8160-f1b7c1a1e38f), к тому же в getTotalDocuments стоит такая проверка - получаются разные данные
            // . "d.draft < 2 AND " //если убрать, то формируются группы с учетом драфтовых доков (см. folder_uid=4fc2f0bf-d66b-11e8-8160-f1b7c1a1e38f), к тому же в getTotalDocuments стоит такая проверка - получаются разные данные
            // . "("
            // . " fv.document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "document_access WHERE doctype_uid = '" . $this->db->escape($folder_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
            // . "OR "
            // . " (SELECT doctype_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid='" . $this->db->escape($field_info['doctype_uid']) . "' AND (object_uid=d.author_uid OR object_uid=d.department_uid)  AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 0,1) IS NOT NULL "
            // . ") "
            . "AND fv.field_uid = '" . $this->db->escape($field['field_uid']) . "' GROUP BY fv.display_value");
          // $query_perm = $this->db->query("SELECT `object_uid` FROM matrix_doctype_access WHERE doctype_uid='$doctype_uid' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
          // $accecced_authors = [];
          // foreach ($query_perm->rows as $row) {
          //   $accecced_authors[$row['object_uid']] = 1;
          // }
          $accecced_author_uids = $this->daemon->exec("GetUserAccessAuthors", ["doctypeUID" => $doctype_uid, "userSUID" => $this->customer->getStructureId()]);
          foreach ($accecced_author_uids as $uid) {
            $accecced_authors[$uid] = 1;
          }
          $accecced_authors[$this->customer->getStructureId()] = 1;
          $query_perm = $this->db->query("SELECT document_uid FROM document_access WHERE doctype_uid = '$doctype_uid' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
          $accessed_duids = [];
          foreach ($query_perm->rows as $row) {
            $accessed_duids[$row['document_uid']] = 1;
          }
          $v = [];
          foreach ($query->rows as $row) {
            if (!empty($accessed_duids[$row['document_uid']]) || !empty($accecced_authors[$row['author_uid']]) || !empty($accecced_authors[$row['department_uid']])) {
              $v[] = $row;
            }
          }
          // $v = $query->rows;
          //проверяем на пустые значения, то есть наличие документов, у которых в таблице поля вообще нет записей. Такое возможно, когда
          //например, поле добавили после того как были созданы документы                    
          $empty = FALSE;
          foreach ($query->rows as $row) {
            if (!$row['display_value']) {
              $empty = TRUE; //пустое значение уже вошло в выборку
              break;
            }
          }
          if (!$empty) { //пустого значение в выборке нет, проверяем наличие документов, у которых нет value данного поля
            $query_empty = $this->db->query(
              "SELECT d.document_uid FROM " . DB_PREFIX . "document d "
                . "WHERE "
                . "d.draft < 2 "
                . "AND d.doctype_uid = '" . $this->db->escape($folder_info['doctype_uid']) . "' "
                . "AND d.document_uid NOT IN "
                . " (SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " WHERE field_uid = '" . $this->db->escape($field['field_uid']) . "') "
                . "AND ("
                . " document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "document_access WHERE doctype_uid = '" . $this->db->escape($folder_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') ) "
                . " OR author_uid IN (SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid = '" . $this->db->escape($folder_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
                . " OR department_uid IN (SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid = '" . $this->db->escape($folder_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
                . ")"
            );
            if ($query_empty->num_rows) { //документы без поля найдены, добавляем пустоту в результат
              $v[] = array('display_value' => '', 'field_uid' => $field['field_uid']);
            }
          }

          $values = $v;
          //получаем список детей для данного поля
          $query_children = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_field WHERE grouping_parent_uid = '" . $this->db->escape($field['folder_field_uid']) . "' ");
          if ($query_children->num_rows > 0) {
            //есть вложенная группировка, строим дерево
            foreach ($query_children->rows as $child) {
              $child_field_info = $this->model_doctype_doctype->getField($child['field_uid'], 0);
              foreach ($values as &$value) {
                $query = $this->db->query("SELECT fv2.display_value, fv2.field_uid, fv2.document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($child_field_info['type']) . " fv2 "
                  . "LEFT JOIN field_value_" . $this->db->escape($field_info['type']) . " fv1 ON (fv1.document_uid = fv2.document_uid AND fv1.field_uid = '" . $this->db->escape($value['field_uid']) . "') "
                  . "WHERE "
                  . "fv2.field_uid = '" . $this->db->escape($child['field_uid']) . "' "
                  . "AND fv2.display_value <> '' "
                  . "AND fv1.display_value = '" . $this->db->escape($value['display_value']) . "' "
                  . "GROUP BY fv2.display_value");
                if ($query->num_rows > 0) {
                  $value['children'][] = array(
                    'folder_field_uid' => $child['folder_field_uid'],
                    'grouping_name' => $child['grouping_name'],
                    'values'        => $query->rows
                  );
                }
              }
            }
          }
        }
        $result[$i] = array(
          'folder_field_uid'  => $field['folder_field_uid'],
          'grouping_name'     => $field['grouping_name'],
          'grouping_tree_uid' => $field['grouping_tree_uid'],
          'field_uid'         => $field['field_uid'],
          'field_values'      => $values,
        );
        $i++;
      }
    }
    return $result;
  }


  // getAvailabilityButtons определяет доступность кнопок пользователю в зависимости от выбранного документа
  public function getAvailabilityButtons($folder_uid, $document_uids)
  {
  }

  public function getButtons($folder_uid)
  {
    $this->load->model("document/document");
    return $this->model_document_document->getButtons($folder_uid);
  }

  public function getButton($button_uid)
  {
    $query = $this->db->query("SELECT fb.uid, fb.parent_uid, fb.picture, fb.hide_button_name, fb.color, fb.background, fb.action, fb.action_log, fb.action_move_route_uid, fb.action_params, "
      . "fbd.name, fbd.description FROM `button` fb "
      . "LEFT JOIN `button_description` fbd ON (fb.uid = fbd.uid AND fbd.language_id = '" . (int) $this->config->get('config_language_id') . "') "
      . "WHERE fb.uid = '" . $this->db->escape($button_uid) . "' ");

    if ($query->num_rows) {
      return array(
        'folder_button_uid'     => $query->row['uid'],
        'folder_uid'            => $query->row['parent_uid'],
        'picture'               => $query->row['picture'],
        'hide_button_name'      => $query->row['hide_button_name'],
        'color'                 => $query->row['color'],
        'background'            => $query->row['background'],
        'action'                => $query->row['action'],
        'action_log'            => $query->row['action_log'],
        'action_move_route_uid' => $query->row['action_move_route_uid'],
        'action_params'         => json_decode($query->row['action_params'], true),
        'name'                  => $query->row['name'],
        'description'           => $query->row['description'],
      );
    } else {
      return array();
    }
  }

  public function getButtonRoutes($button_uid)
  {
    $query = $this->db->query("SELECT * FROM button_route WHERE `uid` = '" . $this->db->escape($button_uid) . "' ");
    return $query->rows;
  }

  /**
   * Возвращает список кнопок журнала, которые делегированы на определенных точках документов
   * @param type $folder_uid
   */
  public function getButtonWithRoute($folder_uid)
  {
    $query = $this->db->query("SELECT `uid` FROM `button_route` WHERE route_uid IN (SELECT DISTINCT route_uid FROM `route` WHERE doctype_uid = (SELECT DISTINCT doctype_uid FROM folder WHERE folder_uid='" . $this->db->escape($folder_uid) . "'))");
    $result = [];
    foreach ($query->rows as $row) {
      $row['folder_button_uid'] = $row['uid'];
      $result[] = $row;
    }
    return $result;
  }

  /**
   * Возвращает массив документов, для которых текущий пользователь может нажать на кнопку в журнале
   * @param type $button_uid
   * @return type
   */
  public function getButtonDocuments($button_uid)
  {
    $query_route0 = $this->db->query("SELECT route_uid FROM " . DB_PREFIX . "button_route WHERE `uid` = '" . $this->db->escape($button_uid) . "' AND (route_uid = '0' OR route_uid = '') ");
    $query_document0 = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "button_delegate WHERE `uid` = '" . $this->db->escape($button_uid) . "' AND document_uid = '0' AND structure_uid IN ('" . implode("','", ($this->customer->getStructureIds())) . "') ");
    if ($query_route0->num_rows) { //кнопка доступна на всех точках документов            
      if ($query_document0->num_rows) { //кнопка доступна для всех документов, т.к. делегируется через настроечное поле, вне зависимости от точки маршрута
        return array(
          '0' => array('document_uid'  => '0')
        );
      } else { //кнопка доступна на всех точках некоторых документов для некоторых пользователей (делегирование через обычное поле)
        $query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "button_delegate WHERE `uid` = '" . $this->db->escape($button_uid) . "' AND structure_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') ");
        $result =  $query->rows;
      }
    } else { //кнопка доступна на некоторых точках маршрута
      if ($query_document0->num_rows) { //кнопка доступна для всех документов, которые находятся на заданных точках маршрута
        $query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "document WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "button_route WHERE `uid` = '" . $this->db->escape($button_uid) . "' )");
        $result =  $query->rows;
      } else { //кнопка доступна для некоторых документов, причем они должны находится на заданных точках маршрута
        $query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "document WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "button_route WHERE `uid` = '" . $this->db->escape($button_uid) . "' ) AND document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "button_delegate WHERE `uid` = '" . $this->db->escape($button_uid) . "' AND structure_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "'))");
        $result = $query->rows;
      }
    }
    return $result;
  }

  public function hasAccessButton($folder_button_uid, $document_uids = array())
  {
    $data = [
      'button_uid' => $folder_button_uid,
      'user_uid' => $this->customer->getStructureId(),
      'document_uids' => $document_uids,
    ];
    return $this->daemon->exec("HasAccessUserButton", $data);

    if ($document_uids) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "button_delegate WHERE "
        . "`uid` = '" . $this->db->escape($folder_button_uid) . "' "
        . "AND (document_uid = '0' OR document_uid IN ('" . implode("','", $document_uids) . "')) "
        . "AND structure_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
      $result = array();
      if ($query->num_rows) {
        foreach ($query->rows as $row) {
          if (!$row['document_uid']) {
            //у пользователя есть доступ на все документы, завершаем проверку
            return $document_uids;
          } else {
            //доступ только на текоторые документы, проверяем наличие каждого из этих документов в запросе
            $result[] = $row['document_uid'];
          }
        }
      }
    } else {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "button_delegate WHERE "
        . "`uid` = '" . $this->db->escape($folder_button_uid) . "' "
        . "AND document_uid = '0' "
        . "AND structure_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
      $result = ($query->num_rows);
    }
    return $result;
  }

  /**
   * Добавляется пользовательский фильтр журнала
   * @param type $folder_uid
   * @param type $filter_name
   * @param type $filters
   * @return type
   */
  public function addFilter($folder_uid, $filter_name, $filters)
  {
    $this->db->query(
      "INSERT INTO " . DB_PREFIX . "folder_user_filter SET "
        . "folder_uid = '" . $this->db->escape($folder_uid) . "', "
        . "user_uid = '" . $this->db->escape($this->customer->getStructureId()) . "', "
        . "filter_name = '" . $this->db->escape($filter_name) . "', "
        . "filter = '" . $this->db->escape(serialize($filters)) . "'"
    );
    return $this->db->getLastId();
  }
  /**
   * Редактирвуется пользовательский фильтр журнала
   * @param type $filter_id
   * @param type $filter_name
   * @param type $filters
   * @return type
   */
  public function editFilter($filter_id, $filter_name, $filters)
  {
    $this->db->query(
      "UPDATE " . DB_PREFIX . "folder_user_filter SET "
        . "filter_name = '" . $this->db->escape($filter_name) . "', "
        . "filter = '" . $this->db->escape(serialize($filters)) . "' "
        . "WHERE filter_id = '" . (int) $filter_id . "' AND user_uid = '" . $this->customer->getStructureId() . "'"
    );
  }

  public function removeFilter($filter_id)
  {
    $this->db->query(
      "DELETE FROM " . DB_PREFIX . "folder_user_filter "
        . "WHERE filter_id = '" . (int) $filter_id . "' AND user_uid = '" . $this->db->escape($this->customer->getStructureId()) . "'"
    );
  }

  /**
   * Возвращает пользовательский фильтр журнала по его идентификатору с учетом текущего пользователя
   * @param type $filter_id
   * @return type
   */
  public function getUserFilter($filter_id)
  {
    $query = $this->db->query(
      "SELECT * FROM " . DB_PREFIX . "folder_user_filter WHERE "
        . "filter_id = '" . (int) $filter_id . "' AND "
        . "user_uid = '" . $this->customer->getStructureId() . "' "
    );
    if ($query->num_rows) {
      return array(
        'filter_name'   => $query->row['filter_name'],
        'filter'        => unserialize($query->row['filter'])
      );
    }
    return 0;
  }

  /**
   * Возвращает фильтры, установленные текущим пользователем в журнале
   * @param type $folder_uid
   * @return type
   */
  public function getUserFilters($folder_uid)
  {
    $query = $this->db->query(
      "SELECT * FROM " . DB_PREFIX . "folder_user_filter WHERE "
        . "folder_uid = '" . $this->db->escape($folder_uid) . "' AND "
        . "user_uid = '" . $this->customer->getStructureId() . "' "
    );
    return $query->rows;
  }

  /**
   * Возвращает админ фильтры журнала
   * @param type $folder_uid
   */
  public function getFilters($folder_uid)
  {
    $query = $this->db->query(
      "SELECT * FROM " . DB_PREFIX . "folder_filter WHERE "
        . "folder_uid = '" . $this->db->escape($folder_uid) . "' "
    );
    $result = array();
    foreach ($query->rows as $filter) {
      $result[] = array(
        'folder_filter_uid' => $filter['folder_filter_uid'],
        'folder_uid' => $filter['folder_uid'],
        'field_uid' => $filter['field_uid'],
        'condition_value' => $filter['condition_value'],
        'type_value' => $filter['type_value'],
        'value' => $filter['value'],
        'action' => $filter['action'],
        'action_params' => json_decode($filter['action_params'], true)
      );
    }
    return $result;
  }
}

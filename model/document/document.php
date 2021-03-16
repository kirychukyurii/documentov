<?php

class ModelDocumentDocument extends Model
{

  private $step = 0;
  private $recursion = array();
  private $step_view = 0;

  private $document_cache = [];

  /**
   * Создание документа
   * draftdoc = true - не создавать новый док, если есть черновик
   */
  public function addDocument($doctype_uid, $author_uid = 0, $draft = 3, $draftdoc = true, $values = [], $is_debug = 0, $no_by_link = 0)
  {
    if (!$author_uid) {
      $author_uid = $this->customer->getStructureId();
    }
    $data = [
      'doctype_uid' => $doctype_uid,
      'author_uid'  => $author_uid,
      'session' => $this->getSession(),
      'debug' => empty($this->request->get['debug']) ? $is_debug : 1,
      'no_by_link' => (int)$no_by_link,
    ];
    if ($values) {
      $data['values'] = $values;
    }
    $document_info = $this->daemon->exec("AddDocument", $data);
    $document_info['document']['action_result'] = $document_info['action_result'];
    return $document_info['document'];

    /////////////////////////////////////////////////////////////////

    if (!$doctype_uid) {
      return;
    }
    $doctype_uid = $this->db->escape($doctype_uid);
    if (!$author_uid) {
      $author_uid = $this->customer->getStructureId();
    }

    $query = $this->db->query("SELECT UUID() AS uid");
    $document_uid = $query->row['uid'];
    $draft_params = "";

    $this->load->model('account/customer');
    $department_uid = $this->model_account_customer->getParentStructure($author_uid);

    if ($draftdoc) {
      // получаем самый свежий черновик
      $query = $this->db->query("SELECT `document_uid`, `draft_params` FROM " . DB_PREFIX . "document WHERE draft = '3' AND author_uid = '" . $this->db->escape($author_uid) . "' AND doctype_uid = '" . $this->db->escape($doctype_uid) . "' ORDER BY `date_added` DESC LIMIT 1");
      $draftdoc = $query->num_rows > 0;
    }

    if ($draftdoc) {
      $draft_params = $query->row['draft_params']; // если есть черновик, копируем его черновые данные в создаваемый документ; использовать тот же черновик нельзя, т.к. он может быть открыть на текущий момент во вкладке браузера пользователя и мы не можем его переписать
      // есть в черновик текстового поля был загружен файл, нужно пофиксить права доступа
      $old_draft_document_uid = $query->row['document_uid'];
      $this->db->query("UPDATE `field_value_text_file` SET `document_uid`='$document_uid' WHERE `document_uid`='$old_draft_document_uid' ");
      // почистим старые черновики, созданные более 7 дней назад
      $this->db->query("DELETE FROM document WHERE draft='3' AND `date_added`<SUBDATE(NOW(), INTERVAL 7 DAY)");
      // проверим количество черновиков
      $query = $this->db->query("SELECT `document_uid` FROM `document` WHERE draft = '3' AND author_uid = '" . $this->db->escape($author_uid) . "' AND doctype_uid = '" . $this->db->escape($doctype_uid) . "' ");
      if ($query->num_rows > 40) {
        // у пользоавтеля более 20 черновиков, возможна атака на переполнение
        $query = $this->db->query("DELETE FROM `document` WHERE draft = '3' AND author_uid = '" . $this->db->escape($author_uid) . "' AND doctype_uid = '" . $this->db->escape($doctype_uid) . "' ORDER BY `date_added` ASC LIMIT 20");
      }
    }
    $this->db->query(
      "INSERT INTO " . DB_PREFIX . "document SET "
        . "document_uid = '" . $document_uid . "', "
        . "doctype_uid = '" . $this->db->escape($doctype_uid) . "', "
        . "author_uid = '" . $this->db->escape($author_uid) . "', "
        . "department_uid = '" . $this->db->escape($department_uid) . "', "
        . "route_uid = '" . $this->getFirstRoute($doctype_uid) . "', "
        . "draft = '" . (int) $draft . "', "
        . "draft_params = '$draft_params', "
        . "date_added=NOW()"
    );
    $this->db->query("REPLACE INTO `" . DB_PREFIX . "document_access` SET subject_uid='" . $this->db->escape($author_uid) . "', document_uid = '" . $this->db->escape($document_uid) . "', doctype_uid = '" . $this->db->escape($doctype_uid) . "' ");
    return $document_uid;
  }

  public function getLastDraftDocument($doctype_uid, $author_uid = "")
  {
    if (!$author_uid) {
      $author_uid = $this->customer->getStructureId();
    }
    $doctype_uid = $this->db->escape($doctype_uid);
    $query = $this->db->query("SELECT `document_uid` FROM `document` WHERE `draft` = '3' AND `author_uid` = '$author_uid' AND doctype_uid = '$doctype_uid' ORDER BY `date_added` DESC LIMIT 1");
    return $query->row['document_uid'] ?? "";
  }
  public function fixTextFile($new_document_uid, $old_document_uid)
  {
    $this->db->query("UPDATE `field_value_text_file` SET `document_uid`='$new_document_uid' WHERE `document_uid`='$old_document_uid' ");
  }
  public function removeDraftDocumentsByDoctype($doctype_uid, $author_uid = "")
  {
    if (!$author_uid) {
      $author_uid = $this->customer->getStructureId();
    }
    $doctype_uid = $this->db->escape($doctype_uid);
    $query = $this->db->query("SELECT `document_uid` FROM `document` WHERE `draft` = '3' AND `author_uid` = '$author_uid' AND doctype_uid = '$doctype_uid' ");
    foreach ($query->rows as $row) {
      $this->removeDocument($row['document_uid']);
    }
  }

  public function saveDocument($document_uid, $values, $no_by_link = 0)
  {
    $data = [
      'document_uid' => $document_uid,
      'session' => $this->getSession(),
      'its_button'  => "1",
      'no_by_link' => (int)$no_by_link,
    ];
    if ($values) {
      $data['values'] = [];
      foreach ($values as $uid => $v) {
        if (is_array($v)) {
          $v = implode(",", $v);
        }
        $data['values'][$uid] = html_entity_decode($v);
      }
    }
    // print_r($data);
    // exit;

    $r = $this->daemon->exec("SaveDocument", $data);
    if ($r == null) {
      $r = ['error' => "Daemon error"];
    } else if (isset($r['error']) && is_array($r['error'])) {
      $r['error'] = implode("<br>", $r['error']);
    }

    return $r;
  }
  /**
   * Сохраняется документ после нажатия пользователем на кнопку Сохранить, поля сохранены уже, сбрасываем драфт, устанавливает дату изменения
   * @param type $document_uid
   */
  public function editDocument($document_uid)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "document SET date_added = (CASE WHEN draft=3 THEN NOW() ELSE date_added END), draft=0, draft_params='', date_edited=NOW() WHERE document_uid='" . $this->db->escape($document_uid) . "' ");
    $this->addDocumentHistory($document_uid);
  }

  /**
   * Сохраняется черновик документа (пользователь правит документ, все изменения записываются через этот метод)
   * @param type $document_uid
   */
  public function saveDraftDocument($document_uid, $field_values)
  {
    if (!$field_values) {
      return;
    }
    $data = [
      'document_uid' => $document_uid,
      'values'  => [],
      'session' => $this->getSession()
    ];
    foreach ($field_values as $uid => $v) {
      if (is_array($v)) {
        $v = implode(",", $v);
      }
      $data['values'][$uid] = html_entity_decode($v);
    }
    $this->daemon->exec("SaveDocumentDraft", $data);
    // $this->db->query("UPDATE " . DB_PREFIX . "document SET draft=(CASE WHEN draft=3 THEN 3 ELSE 1 END), draft_params='" . $this->db->escape($this->jsonEncode($data)) . "' WHERE document_uid='" . $this->db->escape($document_uid) . "' ");
  }

  public function removeDraftDocument($document_uid, $remove_draft_3 = true)
  {
    $data = [
      'document_uid' => $document_uid,
      'session' => $this->getSession()
    ];
    $this->daemon->exec("DeleteDocumentDraft", $data);
    return;
    $this->db->query("UPDATE " . DB_PREFIX . "document SET draft=(CASE WHEN draft=3 THEN 3 ELSE 0 END), draft_params='' WHERE document_uid='" . $this->db->escape($document_uid) . "' ");
    //если документ имел драфт=3, то вместе с драфотом удаляем и его полностью
    //кейс: док создали чрез Создание и инициализировали поля (они пишутся прямо в базе, поскольку драфт работает только с полями из шаблона, а инициализироваться могут любые
    //при нажатии на отмены при создании дока - нужно все зачистить
    if ($remove_draft_3) {
      $document_info = $this->getDocument($document_uid);
      // автор хочет удалить черновик, черновики должны быть удалены все
      $query = $this->db->query("SELECT `document_uid` FROM `document` WHERE `doctype_uid`='" . $document_info['doctype_uid'] . "' AND `draft`='3' AND `author_uid`='" .  $document_info['author_uid']  . "' ");
      foreach ($query->rows as $row) {
        $this->removeDocument($row['document_uid']);
      }
      // if (isset($document_info['draft']) && $document_info['draft'] == 3) {
      //   $this->removeDocument($document_uid);
      // }
    }
  }

  /**
   * Возвращает информацию о документы
   * @param type $document_uid
   * @param type $check_access - проверяется доступ к документы для текущего пользователя
   * @return type
   */
  public function getDocument($document_uid, $check_access = true, $ctxActivity = false, $noCache = false)
  {
    if (!$document_uid) {
      return [];
    }
    if (is_array($document_uid)) {
      echo "Wrong data type document:";
      debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      exit;
    }
    if (!empty($this->document_cache[$document_uid]) && !$ctxActivity && !$noCache) {
      return $this->document_cache[$document_uid];
    }
    $data = [
      'document_uid' => $document_uid,
      'check_access' => (bool)$check_access,
      'session' => $this->getSession(),
      'ctx_activity' => $ctxActivity,
    ];

    $result = $this->daemon->exec("GetDocument", $data);

    if ($result === null) {
      $this->redirect();
    }

    if (!$result) {
      return [];
    }
    $result['document']['action_result'] = $result['action_result'];
    $this->document_cache[$document_uid] = $result['document'];
    return $result['document'];

    ///////////////////////////

    if ($check_access) {
      $query = $this->db->query(
        "SELECT DISTINCT d.document_uid, d.doctype_uid, d.author_uid, d.department_uid, d.route_uid, d.draft, d.draft_params, d.date_added, d.date_edited, "
          . "dt.field_log_uid FROM " . DB_PREFIX . "document d "
          . "LEFT JOIN doctype dt ON (dt.doctype_uid = d.doctype_uid) "
          . "WHERE d.document_uid = '" . $this->db->escape($document_uid) . "' "
          . "AND ("
          . " ((SELECT doctype_uid FROM " . DB_PREFIX . "document_access WHERE document_uid='" . $this->db->escape($document_uid) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 0,1) IS NOT NULL) "
          . " OR "
          . " ((SELECT doctype_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid=d.doctype_uid AND (object_uid=d.author_uid OR object_uid=d.department_uid)  AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 0,1) IS NOT NULL) "
          . ") "
      );
    } else {
      $query = $this->db->query("SELECT DISTINCT d.document_uid, d.doctype_uid, d.author_uid, d.department_uid, d.route_uid, d.draft, d.draft_params, d.date_added, d.date_edited, dt.field_log_uid FROM " . DB_PREFIX . "document d "
        . "LEFT JOIN doctype dt ON (dt.doctype_uid = d.doctype_uid) "
        . "WHERE d.document_uid = '" . $this->db->escape($document_uid) . "' ");
    }
    return $query->row;
  }

  /**
   * 
   */

  /**
   * Метод возвращает идентификаторы документов на основании критерием в $data
   * @param type $data: 
   *      'author_uids'  - массив с идентификаторами авторов документов
   *      'doctype_uids' - массив с идентификаторами типов документов 
   *      'document_uids - выборка из массима с document_uid
   *      'filter_names' - условия по значению полей: filed_id => $condition_value = array($condition,$value) (value может быть заменен на display, чтобы выборка шла по display
   */
  public function getDocumentIds($data)
  {
    $this->load->model('doctype/doctype');
    $joins = array();
    $where = "";
    $sql = "SELECT ";
    if (!empty($data['function'])) {
      $sql .= $this->db->escape($data['function']) . "(";
      if (!empty($data['function_join'])) {
        $field_info = $this->model_doctype_doctype->getField($data['function_join']);
        if (!empty($field_info['type'])) {
          $sql .= "fv" . $this->db->escape(str_replace("-", "", $data['function_join'])) . ".value";
          $joins[] = "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " fv" . $this->db->escape(str_replace("-", "", $data['function_join'])) . " ON (fv" . $this->db->escape(str_replace("-", "", $data['function_join'])) . ".document_uid=d.document_uid AND fv" . $this->db->escape(str_replace("-", "", $data['function_join'])) . ".field_uid = '" . $this->db->escape($data['function_join']) . "') ";
        }
      } else {
        $sql .= "d.document_uid";
      }
      $sql .= ") AS result ";
    } else {
      $sql .= "d.document_uid ";
    }
    $sql .= "FROM " . DB_PREFIX . "document d ";
    if (!empty($data['filter_names'])) {
      foreach ($data['filter_names'] as $field_uid => $condition_value) {
        if (count($condition_value)) {
          $field_info = $this->model_doctype_doctype->getField($field_uid);
          if (empty($field_info['type'])) {
            continue;
          }
          $joins[] = "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " fv" . $this->db->escape(str_replace("-", "", $field_uid)) . " ON (fv" . $this->db->escape(str_replace("-", "", $field_uid)) . ".document_uid=d.document_uid AND fv" . $this->db->escape(str_replace("-", "", $field_uid)) . ".field_uid = '" . $this->db->escape($field_uid) . "') ";
          foreach ($condition_value as $condition) {
            if (isset($condition['value'])) {
              $table_field = "value";
              $value = $this->db->escape($condition['value']);
            } else {
              $table_field = "display_value";
              $value = $this->db->escape($condition['display']);
            }
            //проверям знаки сравнения 
            $sql_field = "fv" . $this->db->escape(str_replace("-", "", $field_uid)) . "." . $table_field;
            if ($field_info['type'] == "datetime" && !$value) {
              $sql_value = "'0000-01-01 00:00:00'";
            } else {
              $sql_value = "'" . $value . "'";
            }
            $add_cond = "";
            $link_add_cond = "OR";
            switch ($condition['comparison']) {
              case '=':
                $comparison = '=';
                if (!$value) {
                  $add_cond = "IS NULL";
                }
                break;
              case '>':
                $comparison = '>';
                break;
              case '<':
                $comparison = '<';
                break;
              case 'equal':
                $comparison = '=';
                if (!$value) {
                  $add_cond = "IS NULL";
                }
                break;
              case 'notequal':
                $comparison = '<>';
                if ($value) {
                  $add_cond = "IS NULL";
                } else {
                  $add_cond = "IS NOT NULL";
                  $link_add_cond = "AND";
                }
                break;
              case 'more':
                $comparison = '>';
                break;
              case 'moreequal':
                $comparison = '>=';
                break;
              case 'less':
                $comparison = '<';
                $add_cond = "IS NULL";
                break;
              case 'lessequal':
                $comparison = '<=';
                break;
              case 'contains':
                $comparison = 'LIKE';
                $sql_value = "'%" . $value . "%'";
                break;
              case 'notcontains':
                $comparison = 'not LIKE';
                if ($value) {
                  $add_cond = "IS NULL";
                } else {
                  $add_cond = "IS NOT NULL";
                  $link_add_cond = "AND";
                }
                $sql_value = "'%" . $value . "%'";
                break;
              case 'include':
                $comparison = 'LIKE';
                $sql_value = "CONCAT('%', TRIM(" . $sql_field . "), '%')";
                $sql_field = "'" . $value . "'";
                break;
              case 'notinclude':
                $comparison = 'not LIKE';
                if ($value) {
                  $add_cond = "IS NULL";
                } else {
                  $add_cond = "IS NOT NULL";
                  $link_add_cond = "AND";
                }
                $sql_value = "CONCAT('%', TRIM(" . $sql_field . "), '%')";
                $sql_field = "'" . $value . "'";
                break;
              default:
                $comparison = '=';
                break;
            }
            // $wh = $this->getSQLCondition($table_field, $field_uid, $condition['comparison'], $value);
            // 

            $wh = "(" . $sql_field . " " . $comparison . " " . $sql_value . " " . ($add_cond ? " " . $link_add_cond . " fv" . $this->db->escape(str_replace("-", "", $field_uid)) . "." . $table_field . " " . $add_cond : "") .
              ") ";

            if ($where) {
              if (isset($condition['concat']) && strtoupper($condition['concat']) == 'OR') {
                $concat = " OR ";
              } else {
                $concat = " AND ";
              }
              $where .= $concat . $wh;
            } else {
              $where = $wh;
            }
          }
        }
      }
    }
    if (!empty($data['sort'])) {
      $field_sort_info = $this->model_doctype_doctype->getField($data['sort']);
      if (!empty($field_sort_info['type'])) {
        $joins[] = "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field_sort_info['type']) . " fv" . $this->db->escape(str_replace("-", "", $data['sort'])) . " ON (fv" . $this->db->escape(str_replace("-", "", $data['sort'])) . ".document_uid=d.document_uid AND fv" . $this->db->escape(str_replace("-", "", $data['sort'])) . ".field_uid = '" . $this->db->escape($data['sort']) . "') ";
      }
    }
    if (!empty($joins)) {
      $joins = array_unique($joins);
      $sql .= implode(" ", $joins);
    }
    if (!empty($data['draft_less'])) {
      $sql .= "WHERE d.draft < '" . (int) $data['draft_less'] . "' ";
    } else {
      $sql .= "WHERE d.draft < 2 ";
    }

    if (!empty($where)) {
      $wh = explode(" AND ", $where);
      $sql .= " AND (" . implode(") AND (", $wh) . ") ";
    }
    if (!empty($data['author_uids'])) {
      $sql .= "AND d.author_uid IN ('" . implode("','", $data['author_uids']) . "') ";
    }
    if (!empty($data['department_uids'])) {
      $sql .= "AND d.department_uid IN ('" . implode("','", $data['department_uids']) . "') ";
    }
    if (!empty($data['doctype_uids'])) {
      $sql .= "AND d.doctype_uid IN ('" . implode("','", $data['doctype_uids']) . "') ";
    }
    if (!empty($data['document_uids'])) {
      $sql .= "AND d.document_uid IN ('" . implode("','", $data['document_uids']) . "') ";
    }
    if (!empty($data['route_uid'])) {
      $sql .= "AND d.route_uid = '" . $this->db->escape($data['route_uid']) . "' ";
    }
    if (!empty($data['sort']) && $field_sort_info) {
      $query = $this->db->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME='field_value_" . $this->db->escape($field_sort_info['type']) . "' AND COLUMN_NAME='value'");
      if ($query->row['DATA_TYPE'] == 'datetime' || $query->row['DATA_TYPE'] == 'date' || $query->row['DATA_TYPE'] == 'time' || $query->row['DATA_TYPE'] == 'int' || $query->row['DATA_TYPE'] == 'tinyint' || $query->row['DATA_TYPE'] == 'smallint' || $query->row['DATA_TYPE'] == 'mediumint' || $query->row['DATA_TYPE'] == 'bigint' || $query->row['DATA_TYPE'] == 'decimal' || $query->row['DATA_TYPE'] == 'float' || $query->row['DATA_TYPE'] == 'double' || $query->row['DATA_TYPE'] == 'real') {
        $sql .= " ORDER BY fv" . $this->db->escape(str_replace("-", "", $data['sort'])) . ".value " . $this->db->escape(strtoupper($data['order'] ?? "ASC")) . " ";
      } else {
        $sql .= " ORDER BY fv" . $this->db->escape(str_replace("-", "", $data['sort'])) . ".display_value " . $this->db->escape(strtoupper($data['order'] ?? "ASC")) . " ";
      }
    }
    if (!empty($data['limit']) && isset($data['start'])) {
      $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
    }

    $query = $this->db->query($sql);
    if (!empty($data['function'])) {
      $result = $query->row['result'];
    } else {
      $result = array();
      foreach ($query->rows as $document) {
        $result[] = $document['document_uid'];
      }
    }
    return $result;
  }

  /**
   * Рекурсивная функция для получения родителя structure_uid в массиве $parents, где ключ - идентфиикатор родителя, 
   * а значение - его родитель
   */
  private function getParent($parents, $structure_uid)
  {
    $result = array();
    if (isset($parents[$structure_uid])) {
      $father_uid = $parents[$structure_uid];
      if ($father_uid && $father_uid != $structure_uid) {
        $result[] = $father_uid;
        $result = array_merge($result, $this->getParent($parents, $father_uid));
      }
    }
    return $result;
  }
  /**
   * Возвращает всех родителей документа (document_uid) дерева по полю с типом parent_field_type и идентификатором parent_field_uid
   */
  public function getParents($document_uid, $parent_field_type, $parent_field_uid)
  {
    $result = array();
    //получаем всю документы с полем  parent_field_uid
    $query = $this->db->query("SELECT document_uid, `value` as parent_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($parent_field_type) . " WHERE field_uid = '" . $this->db->escape($parent_field_uid) . "' ");
    if ($query->num_rows) {
      $parents = array();
      $everything = array();
      foreach ($query->rows as $row) {
        if ($row['parent_uid']) {
          if (!isset($parents[$row['parent_uid']])) {
            $parents[$row['parent_uid']] = array();
          }
        }
        if ($row['document_uid']) {
          $everything[$row['document_uid']] = $row;
        }
      }
      //$parents - одноуровневый массив с ключами - родителями
      //получаем родителя document_uid
      if (!isset($everything[$document_uid]['parent_uid'])) {
        return array(); //а переданного document_uid вообще нет в числе документов
      }
      $father_uid = $everything[$document_uid]['parent_uid'];

      //добавляем для каждого родителя его родителя
      foreach ($parents as $document_uid => &$parent) {
        $parent = $everything[$document_uid]['parent_uid'] ?? "";
      }
      //теперь получаем всех родителей отца
      $result = array_merge(array($father_uid), $this->getParent($parents, $father_uid));
    }
    return $result;
  }

  /**
   * Рекурсивная функция для получения детей structure_uid в массиве $parents, 
   * где ключ - идентификатор родителя, 
   * а значение - его родитель
   */
  private function getChild($parents, $document_uid)
  {
    $result = array();
    foreach ($parents as $child_uid => $parent_uid) {
      if ($document_uid == $parent_uid && $document_uid != $child_uid) {
        //наш ребенок
        $result[] = $child_uid;
        //получаем детей ребенка
        $result = array_merge($result, $this->getChild($parents, $child_uid));
      }
    }
    return $result;
  }

  /**
   * Возвращает всех родителей документа (document_uid) дерева по полю с типом 
   * parent_field_type и идентификатором parent_field_uid (в этом поле хранятся
   * идентификаторы документов)
   */
  public function getChildren($document_uid, $parent_field_type, $parent_field_uid)
  {
    $result = array();
    //получаем всю документы с полем  parent_field_uid
    $query = $this->db->query("SELECT document_uid, `value` as parent_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($parent_field_type) . " WHERE field_uid = '" . $this->db->escape($parent_field_uid) . "' ");
    if ($query->num_rows) {
      $parents = array();
      $everything = array();
      foreach ($query->rows as $row) {
        if ($row['parent_uid']) {
          if (!isset($parents[$row['parent_uid']])) {
            $parents[$row['parent_uid']] = array();
          }
        }
        if ($row['document_uid']) {
          $everything[$row['document_uid']] = $row;
        }
      }
      //$parents - одноуровневый массив с ключами - родителями
      //проверяем, является ли переданный document_uid родителем
      if (!isset($parents[$document_uid])) {
        return array(); //у переданного document_uid нет детей
      }

      //добавляем для каждого родителя его родителя
      foreach ($parents as $doc_uid => &$parent) {
        $parent = $everything[$doc_uid]['parent_uid'] ?? "";
      }

      //теперь получаем всех детей
      $result = $this->getChild($parents, $document_uid);
    }
    return $result;
  }

  /**
   * Метод выстраивает дерево из массива по значению parent
   */
  private function getTreeChild($family, $parent)
  {
    $result = array();
    foreach ($family as $member) {
      if (!isset($member['parent'])) {
        continue;
      }
      if ($member['parent'] == $parent) {
        if ($member['name'] != $parent) {
          $result[] = array_merge($member, array('children' => $this->getTreeChild($family, $member['name'])));
        }
      }
    }
    return $result;
  }

  /**
   * Получает дерево как в Структуре (на основе значения поля)
   * @param type $field_uid
   * @param type $parent_field_uid
   * @param type $parent_field_value - корневой элемент, с которого должно начаться дерево
   * @return type
   */
  public function getFieldValueTree($field_uid, $parent_field_uid, $parent_field_value = "")
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (empty($field_info['doctype_uid'])) {
      return [];
    }

    $parent_field_info = $this->model_doctype_doctype->getField($parent_field_uid);
    $doctype_uid = $this->db->escape($field_info['doctype_uid']);
    $sql = "SELECT fv.display_value AS name, fvp.display_value AS parent, fv.field_uid, fv.document_uid, d.author_uid, d.department_uid   FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " fv "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($parent_field_info['type']) . " fvp ON (fv.document_uid = fvp.document_uid AND fvp.field_uid = '" . $this->db->escape($parent_field_uid) . "') "
      . "LEFT JOIN document d ON (d.document_uid = fv.document_uid) "
      . "WHERE "
      . "fv.field_uid = '" . $this->db->escape($field_uid) . "' "
      //                . "AND d.draft < 2 "
      // . "AND ("
      // . " fv.document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "document_access WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
      // . "OR "
      // . " (SELECT doctype_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid='" . $this->db->escape($field_info['doctype_uid']) . "' AND (object_uid=d.author_uid OR object_uid=d.department_uid)  AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 0,1) IS NOT NULL "
      // . ") ";
    ;

    $query = $this->db->query($sql);

    // $query_perm = $this->db->query("SELECT `object_uid` FROM matrix_doctype_access WHERE doctype_uid='$doctype_uid' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");

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
    // print_r($v);
    $parents = array();
    $parents_tmp = array();
    $everything = array();
    foreach ($v as $row) {
      if ($row['parent']) {
        if (!isset($parents_tmp[$row['parent']])) {
          $parents_tmp[$row['parent']] = array();
        }
      }
      if ($row['name']) {
        $everything[$row['name']] = $row;
      }
    }
    // print_r($parents_tmp);
    // print_r($everything);
    // exit;
    //ищем корни
    foreach ($parents_tmp as $parent_name => &$parent) {
      if (!isset($everything[$parent_name])) {
        continue;
      }
      $parent = $everything[$parent_name];
      if (($parent_field_value && $parent['parent'] == $parent_field_value) || //задан родитель, детей которого нужно получить
        (!$parent_field_value && ($parent['parent'] == $parent['name'] || !$parent['parent']))
      ) { //это корневой родитель - у него нет родителя или он сам себе гермафродит
        if (array_search($parent['name'], $parents) === FALSE) {
          $parents[$parent['name']] = $parent;
        }
      }
    }
    // print_r($parents);
    // exit;
    foreach ($parents as &$parent) {
      //перебираем корневых родителей и выстраиваем дерево
      $parent = array_merge($parent, array('children' => $this->getTreeChild($parents_tmp, $parent['name'])));
    }
    //отсортируем по алфавиту
    ksort($parents);

    return $parents;
  }

  private function getSQLCondition($table_field, $field_uid, $condition, $value)
  {
    //проверям знаки сравнения
    $sql_field = "fv" . $this->db->escape(str_replace("-", "", $field_uid)) . "." . $table_field;
    $sql_value = "'" . $value . "'";
    $add_cond = "";
    $link_add_cond = "OR";
    switch ($condition) {
      case '=':
        $comparison = '=';
        if (!$value) {
          $add_cond = "IS NULL";
        }
        break;
      case '>':
        $comparison = '>';
        break;
      case '<':
        $comparison = '<';
        break;
      case 'equal':
        $comparison = '=';
        if (!$value) {
          $add_cond = "IS NULL";
        }
        break;
      case 'notequal':
        $comparison = '<>';
        if ($value) {
          $add_cond = "IS NULL";
        } else {
          $add_cond = "IS NOT NULL";
          $link_add_cond = "AND";
        }
        break;
      case 'more':
        $comparison = '>';
        break;
      case 'moreequal':
        $comparison = '>=';
        break;
      case 'less':
        $comparison = '<';
        $add_cond = "IS NULL";
        break;
      case 'lessequal':
        $comparison = '<=';
        break;
      case 'contains':
        $comparison = 'LIKE';
        $sql_value = "'%" . $value . "%'";
        break;
      case 'notcontains':
        $comparison = 'not LIKE';
        if ($value) {
          $add_cond = "IS NULL";
        } else {
          $add_cond = "IS NOT NULL";
          $link_add_cond = "AND";
        }
        $sql_value = "'%" . $value . "%'";
        break;
      case 'include':
        $comparison = 'LIKE';
        $sql_value = "CONCAT('%', TRIM(" . $sql_field . "), '%')";
        $sql_field = "'" . $value . "'";
        break;
      case 'notinclude':
        $comparison = ' NOT LIKE';
        if ($value) {
          $add_cond = "IS NULL";
        } else {
          $add_cond = "IS NOT NULL";
          $link_add_cond = "AND";
        }
        $sql_value = "CONCAT('%', TRIM(" . $sql_field . "), '%')";
        $sql_field = "'" . $value . "'";
        break;
      default:
        $comparison = '=';
        break;
    }

    return "(" . $sql_field . " " . $comparison . " " . $sql_value . " " . ($add_cond ? " " . $link_add_cond . " fv" . $this->db->escape(str_replace("-", "", $field_uid)) . "." . $table_field . " " . $add_cond : "") .
      ") ";
  }

  public function getDocuments($data)
  {
    // debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    // exit;
    // print_r($data);
    // exit;
    $result = [];
    if (!empty($data['is_count'])) {
      $result = ['documents' => [], 'total' => 0];
    }
    if (isset($data['limit'])) {
      $data['limit'] = (int)$data['limit'];
    }
    if (isset($data['start'])) {
      $data['start'] = (int)$data['start'];
    }
    if (isset($data['filter_names'])) {
      $data['filters'] = [];
      foreach ($data['filter_names'] as $field_uid => $filters) {
        foreach ($filters as $filter) {
          $f = [
            'field_uid' => $field_uid,
            'condition' => $filter['condition'],
          ];
          if (isset($filter['concat']) && $filter['concat'] == "OR") {
            $f['concat'] = 1;
          } else {
            $f['concat'] = 0;
          }
          if (!isset($filter['value'])) {
            $f['is_display'] = true;
          }
          $f['value'] = html_entity_decode($filter['value'] ?? $filter['display']); // 1click: тоо "ааа" ; 2click: тоо &quoite...
          $data['filters'][] = $f;
        }
      }
      unset($data['filter_names']);
    }
    if (isset($data['filter_search_type'])) {
      if ($data['filter_search_type'] == "quick") {
        $data['display_filter'] = $data['filter_search'] ?? "";
      } else {
        $data['value_filter'] = $data['filter_search'] ?? "";
      }
      unset($data['filter_search_type']);
      unset($data['filter_search']);
    }
    $data['user_suid'] = $this->customer->getStructureId();
    // print_r($data);
    // exit;

    $r = $this->daemon->exec("GetDocuments", $data);

    // print_r($r);
    // exit;

    if (!$r) {
      return $result;
    }
    $fields = [];
    if (!empty($data['field_uids'])) {
      foreach ($data['field_uids'] as $ff) {
        foreach ($ff as  $m => $uid) {
          $fields[] = "v" . str_replace("-", "", $uid);
        }
      }
    }

    foreach ($r as $doc) {
      $res = [];
      foreach ($doc as $uid => $value) {
        if ($uid == "total") {
          $result["total"] = $value;
          break;
        }
        if ($uid == "document_uid" || $uid == "author_uid" || $uid == "department_uid") {
          $res[$uid] = $value;
          continue;
        }
        $res["v" . str_replace("-", "", $uid)] = $value;
      }
      if (count($res) == 0) { // total
        continue;
      }
      foreach ($fields as $f) {
        if (!isset($res[$f])) {
          $res[$f] = "";
        }
      }
      if (!empty($data['is_count'])) {
        $result['documents'][] = $res;
      } else {
        $result[] = $res;
      }
    }
    // print_r($result);
    // exit;
    return $result;
  }

  public function getDoctype($doctype_uid)
  {
    $query = $this->db->query("SELECT DISTINCT * "
      . "FROM " . DB_PREFIX . "doctype d "
      . "LEFT JOIN " . DB_PREFIX . "doctype_description dd ON (d.doctype_uid = dd.doctype_uid) "
      . "WHERE d.doctype_uid = '" . $this->db->escape($doctype_uid) . "' "
      . "AND dd.language_id = '" . $this->db->escape($this->config->get('config_language_id')) . "' ");
    $result = $query->row;
    if (!empty($result['params'])) {
      $result['params'] = json_decode($result['params'], true);
    }
    return $result;
  }

  private function getTemplateComparison($first_value, $second_value, $comparison)
  {
    if ($first_value == "0" && $second_value == "") {
      $first_value = "";
    }
    if ($first_value == "" && $second_value == "0") {
      $second_value = "";
    }
    $result = false;
    switch ($comparison) {
      case 'equal':
        if ($first_value == $second_value) {
          $result = true;
        }
        break;
      case 'notequal':
        if ($first_value != $second_value) {
          $result = true;
        }
        break;
      case 'more':
        if ($first_value > $second_value) {
          $result = true;
        }
        break;
      case 'moreequal':
        if ($first_value >= $second_value) {
          $result = true;
        }
        break;
      case 'less':
        if ($first_value < $second_value) {
          $result = true;
        }
        break;
      case 'lessequal':
        if ($first_value <= $second_value) {
          $result = true;
        }
        break;
      case 'contains':
        if (mb_strpos($first_value, $second_value) !== FALSE) {
          $result = true;
        }
        break;
      case 'notcontains':
        if (mb_strpos($first_value, $second_value) === FALSE) {
          $result = true;
        }
        break;
    }
    return $result;
  }

  /**
   * Метод возвращает шаблон документа (если есть дополнительные шаблоны, то возвращает первый из них, в котором условие отображение будет TRUE
   * @param type $document_uid - идентификатор документа
   * @param type $type_template - тип шаблона: form || view
   * @param type $doctype_uid - если документ еще не создан, то получаем шаблон по доктайпу
   * @param type $values - если документ еще не создан, значит его поля не инициализированы; можно передать значения полей через этот массив
   * @return string
   */
  public function getTemplate($document_uid, $type_template, $doctype_uid = "", $values = array())
  {
    $recursion_depth = $this->config->get('recursion_depth_template') ?? 3;

    if (++$this->step_view > $recursion_depth) {
      $template['template'] = $this->load->view('error/cycle', array('error_cycle' => $this->language->get('error_cycle_template')));
      return $template;
    }

    $data = [
      'document_uid'  => (string) $document_uid,
      'doctype_uid'  => $doctype_uid,
      'draft'         => "0",
      'type'          => $type_template
    ];
    $language_id = $this->config->get('config_language_id');
    $result = ['template' => "", 'conditions' => "", 'sort' => 0];

    $templates = $this->daemon->exec("GetDocumentTemplates", $data);
    // print_r($templates);
    // exit;

    // template, conditions, sort
    if ($templates) {
      $i = 0;
      foreach ($templates as $template) {
        if ($type_template && $type_template != $template['type']) {
          continue;
        }
        if ($i) {
          // это дополнительный шаблон
          $first_value = $this->getFieldValue($template['condition_field_uid'], $document_uid);
          $second_value = $this->getFieldValue($template['condition_value_uid'], $document_uid);
          if (!$this->getTemplateComparison($first_value, $second_value, $template['condition_comparison'])) {
            // условиям не соответствует
            continue;
          }
        }
        // это или первый (основной) шаблон или дополнительный, отвечающий условиям
        $result['template'] = $template['forms'][0][$language_id]['html'] ?? "";
        $result['conditions'] = $template['forms'][0][$language_id]['conditions'] ?? [];
        $result['sort'] = $template['sort'];
        if ($i) break;
        $i++;
      }
    }
    // print_r($result);
    // exit;
    return $result;

    ///////////////////////////////////////

    // $template = array(
    //   'template' => '',
    //   'conditions' => array()
    // );
    // $recursion_depth = $this->config->get('recursion_depth_template') ?? 3;

    // if (++$this->step_view > $recursion_depth) {
    //   $template['template'] = $this->load->view('error/cycle', array('error_cycle' => $this->language->get('error_cycle_template')));
    //   return $template;
    // }
    // if (!$doctype_uid) {
    //   $document_info = $this->getDocument($document_uid);
    //   if (!$document_info) {
    //     return $template;
    //   }
    //   $doctype_uid = $document_info['doctype_uid'];
    // }

    // $query = $this->db->query("SELECT template, conditions, sort FROM " . DB_PREFIX . "doctype_template WHERE "
    //   . "doctype_uid = '" . $this->db->escape($doctype_uid) . "' AND language_id = '" . (int) $this->config->get('config_language_id') . "' "
    //   . "AND type='" . $this->db->escape($type_template) . "' ORDER BY `sort` ASC ");
    // $templates = $query->rows;


    // if ($templates) {
    //   $doctype_info = $this->getDoctype($doctype_uid);
    //   if (!empty($doctype_info['params']['doctype_template'][$type_template])) {
    //     foreach ($doctype_info['params']['doctype_template'][$type_template] as $index => $doctype_template) {

    //       $first_value = $values[$doctype_template['condition_field_uid']] ?? $this->getFieldValue($doctype_template['condition_field_uid'], $document_uid);
    //       $second_value = $values[$doctype_template['condition_value_uid']] ?? $this->getFieldValue($doctype_template['condition_value_uid'], $document_uid);
    //       $result = false;
    //       switch ($doctype_template['condition_comparison']) {
    //         case 'equal':
    //           if ($first_value == $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'notequal':
    //           if ($first_value != $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'more':
    //           if ($first_value > $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'moreequal':
    //           if ($first_value >= $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'less':
    //           if ($first_value < $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'lessequal':
    //           if ($first_value <= $second_value) {
    //             $result = true;
    //           }
    //           break;
    //         case 'contains':
    //           if (mb_strpos($first_value, $second_value) !== FALSE) {
    //             $result = true;
    //           }
    //           break;
    //         case 'notcontains':
    //           if (mb_strpos($first_value, $second_value) === FALSE) {
    //             $result = true;
    //           }
    //           break;
    //       }
    //       if ($result) {
    //         foreach ($templates as $template) {
    //           if ($template['sort'] == $index) {
    //             $conditions = html_entity_decode($template['conditions']);
    //             if (!$conditions) {
    //               $template['conditions'] = "";
    //             }
    //             return $template;
    //           }
    //         }
    //       }
    //     }
    //   }
    // }
    // if ($templates) {
    //   $template = $templates[0];
    //   if (!empty($template['conditions'])) {
    //     $template['conditions'] = html_entity_decode($template['conditions']);
    //   } else {
    //     $template['conditions'] = "";
    //   }
    // }
    // return $template;
  }

  public function getFields($doctype_uid)
  {
    if (!$doctype_uid) {
      return [];
    }
    $data = [
      'language_id' => $this->config->get('config_language_id'),
      'draft' => "0",
      'doctype_uid' => $doctype_uid,
    ];
    $fields = $this->daemon->exec("GetDoctypeFields", $data);
    foreach ($fields as &$f) {
      $f['field_uid'] = $f['uid'];
      $f['params'] = $f['field_type'];
    }
    return $fields;

    // GetDoctypeFields
    $doctype_uid = $this->db->escape($doctype_uid);
    $sql = "SELECT * FROM `field` WHERE doctype_uid='$doctype_uid' AND `draft`=0 ORDER BY sort ASC";
    $query = $this->db->query($sql);
    $result = array();
    foreach ($query->rows as $field) {
      $field['params'] = json_decode($field['params'], true);
      if ($field['access_view']) {
        $field['access_view'] = explode(",", $field['access_view']);
      }
      if ($field['access_form']) {
        $field['access_form'] = explode(",", $field['access_form']);
      }
      $result[] = $field;
    }
    return $result;
  }


  /**
   * Метод возвращает уникальные значения field_uid без привязки к document_uid. 
   * @param type $field_uid
   */
  public function getUniqueFieldValues($field_uid)
  {
    $this->load->model('doctype/doctype');
    $result = array();
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    $query = $this->db->query(
      "SELECT DISTINCT(fv.display_value), fv.value  FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " fv "
        . "LEFT JOIN document d ON (d.document_uid = fv.document_uid) "
        . "WHERE fv.field_uid = '" . $this->db->escape($field_uid) . "' "
        . "AND d.draft < 2 "
        . "AND "
        . "("
        . " fv.document_uid IN (SELECT document_uid FROM document_access WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
        . "OR "
        . " (SELECT doctype_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid='" . $this->db->escape($field_info['doctype_uid']) . "' AND (object_uid=d.author_uid OR object_uid=d.department_uid)  AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 1) IS NOT NULL "
        . ")"
    );
    $check_empty = true;
    foreach ($query->rows as $field_value) {
      $result[] = array(
        'value' => $field_value['value'],
        'display_value' => $field_value['display_value'],
      );
      if (!$field_value['display_value']) {
        $check_empty = false;
      }
    }
    //проверяем наличие пустых значений, чтобы тоже вернуть при их наличии     
    if ($check_empty) {
      $query_empty = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "document d "
        . "WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' "
        . "AND draft < 2 "
        . "AND ("
        .   " document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "document_access WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') ) "
        .   " OR author_uid IN (SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
        .   " OR department_uid IN (SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid = '" . $this->db->escape($field_info['doctype_uid']) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')) "
        . ")"
        . "AND document_uid NOT IN "
        . "(SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " WHERE field_uid = '" . $this->db->escape($field_uid) . "')");
      if ($query_empty->num_rows) { //документы без поля найдены, добавляем пустоту в результат
        $result[] = array(
          'display_value' => '',
          'value' => ''
        );
      }
    }

    return $result;
  }

  /**
   * Возвращает документ, в которых поле field_uid содержит value
   * @param type $field_uid
   * @param  $value
   * @return type
   */
  public function getFieldByValue($field_uid, $value)
  {
    if (is_array($value)) {
      $value = implode(",", $value);
    }
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    $query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $field_info['type'] . " WHERE field_uid= '" . $this->db->escape($field_uid) . "' AND value = '" . $this->db->escape($value) . "'");
    return $query->rows;
  }

  /**
   * Удаление значения поля
   * @param type $field_uid
   * @param type $document_uid
   */
  public function removeFieldValue($field_uid, $document_uid)
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if ($field_info['change_field']) {
      //установлен атрибут запуска контекста изменения; получаем значение у поля (если оно было до удаления - нужно запустить контекст
      $field_value = $this->getFieldValue($field_uid, $document_uid);
    }
    $this->load->model('extension/field/' . $field_info['type']);
    $model = "model_extension_field_" . $field_info['type'];
    $this->$model->removeValue($field_uid, $document_uid);
    //если была подписка на удаленное значение поля field_uid документа document_uid, нужно ее обновить
    $this->updateSubscription($this->getSubscriptions($field_uid, $document_uid));
    if (!empty($field_value)) {
      return $this->runChangeContext($field_uid, $field_value, $document_uid, $field_info);
    }
  }

  /**
   * Удаление всех значений заданного поля
   * @param type $field_uid
   * @return type
   */
  public function removeFieldValues($field_uid)
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (empty($field_info['type'])) {
      return;
    }
    $this->load->model('extension/field/' . $field_info['type']);
    $model = "model_extension_field_" . $field_info['type'];
    $this->$model->removeValues($field_uid);
    $this->updateSubscription($this->getSubscriptions($field_uid));
    //контекст изменения в отличие от метода removeFieldValue не запускаем, поскольку тут однозначно идет удаление
    //самого поля и смысла в таком запуске нет
  }

  /**
   * Метод для запуска контекста маршрута Изменение при изменении значения поля
   * @param type $field_uid
   * @param type $field_value
   * @param type $document_uid
   * @param type $field_info
   * @return type
   */
  private function runChangeContext($field_uid, $field_value, $document_uid, $field_info = "")
  {
    if (!$field_info) {
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($field_uid);
    }
    if (empty($field_info['change_field'])) {
      return;
    }
    //защита от бесконечного цикла через контекст изменения
    if (empty($this->recursion[$field_uid])) {
      $this->recursion[$field_uid] = 1;
    } else {
      $recursion_depth = $this->config->get('recursion_depth');
      if (++$this->recursion[$field_uid] > $recursion_depth) {
        return array('redirect' => $this->url->link('error/cycle'));
      }
    }
    $this->request->get['field_uid'] = $field_uid; //используется для переменной ИДЕНТИФИКАТОР ИЗМЕННЕННОГО ПОЛЯ (д. Запись, Условие)
    $this->request->get['field_value'] = $field_value; //используется для переменной ПРЕЖНЕЕ ЗНАЧЕНИЕ ИЗМЕННЕННОГО ПОЛЯ (д. Запись, Условие)

    if ($field_info['setting']) {
      //это настроечное поле - запуск контекста "настройки" для всех документов данного типа
      $data = array(
        'doctype_uid' => $field_info['doctype_uid'],
        'field_uid' => $field_uid,
        'context' => 'setting'
      );
      if ($this->daemon->getStatus()) {
        //если демон доступен - отдаем ему задачу обработки
        $this->daemon->addTask('document/document/executeDeferred', $data, 1);
      } else {
        $this->load->controller('document/document/executeDeferred', $data);
      }
    } else {
      $params = array(
        'document_uid' => $document_uid,
        'context' => 'change'
      );
      return $this->load->controller('document/document/route_cli', $params);
    }
  }

  /**
   * Возвращает отображаемо значение поля документа. В качестве document_uid может быть 
   * передан массив документов, в этом случае возвращается массив [document_uid] => display
   */
  public function getFieldDisplay($field_uid, $document_uid, $access = FALSE)
  {
    return $this->getFieldValue($field_uid, $document_uid, false, $access, true);
  }

  // проверка доступа на просмотр (через атрибуты)
  public function checkAccessViewField($field_info, $document_uid)
  {
    $access_view = true;
    if (!empty($field_info['params']['access_view'])) {
      //есть ограничение на доступ к просмотру поля
      $access_view = false;
      foreach ($field_info['params']['access_view'] as $access_field_uid) {
        $value = $this->getFieldValue($access_field_uid, $document_uid);
        if (!$value) {
          continue;
        }
        $values = explode(',', $value);
        foreach ($this->customer->getStructureIds() as $structure_uid) {
          if (array_search($structure_uid, $values) !== false) {
            $access_view = true;
            break;
          }
        }
        if ($access_view) {
          break;
        }
      }
    }
    return $access_view;
  }
  /**
   * Возвращает значения поля field_uid для документов из массива document_uids, 
   * если массив document_uids пуст, возвращаются все значения поля
   */
  public function getFieldValues($field_uid, array $document_uids = [])
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    $result = [];
    if (empty($field_info['type'])) {
      return $result;
    }
    $sql = "SELECT document_uid, value FROM field_value_" . $field_info['type'] . " WHERE field_uid='" . $field_uid . "' ";
    if ($document_uids) {
      $sql .= " AND document_uid IN ('" . implode("','", $document_uids) . "') ";
    }

    $query = $this->db->query($sql);
    if (!$query->num_rows) {
      return $result;
    }

    foreach ($query->rows as $row) {
      $result[$row['document_uid']] = $row['value'];
    }
    return $result;
  }

  /**
   * Возвращает значение поля документа
   * @param type $field_uid
   * @param type $document_uid
   * @param type $draft - если TRUE проверить наличие черновика и, при его наличии, вернуть из него значение данного поля
   * @param access - FALSE - без проверки доступа, TRUE - с проверкой
   * @return type
   */
  public function getFieldValue($field_uid, $document_uid, $draft = FALSE, $access = FALSE, $display = false)
  {
    $this->load->model('doctype/doctype');
    $document_info = $this->getDocument($document_uid);
    $field_info = $this->model_doctype_doctype->getField($field_uid);

    if (!$field_info) {
      return;
    }

    if ($access && $document_uid) {
      // проверяем доступ к полю (кроме документ_июд = 0)
      if (!$document_info || !$this->checkAccessViewField($field_info, $document_uid)) {
        return; //нет доступа
      }
    }

    if (!empty($field_info['setting'])) {
      //имеем дело с настроечным полем, document_uid = 0
      $document_uid = 0;
    }
    if ($document_uid && $draft) { //если запрашивается настроечное поле document_uid может быть равен 0
      $document_info = $this->getDocument($document_uid);
      // print_r($document_info);
      // exit;
      if (!empty($document_info['draft_params'])) {
        $draft_params = json_decode($document_info['draft_params'], true);
        if (isset($draft_params[$field_uid])) {
          if (is_array($draft_params[$field_uid])) {
            $draft_params[$field_uid] = implode(",", $draft_params[$field_uid]);
          }

          return html_entity_decode($draft_params[$field_uid]);
        }
      }
    }
    $this->load->model('doctype/doctype');
    if (!empty($field_info['type'])) {
      $data = [
        'field_uid' => $field_uid,
        'document_uid' => (string)$document_uid
      ];
      $field_value = $this->daemon->exec("GetFieldValue", $data);
      if ($display) {
        return $field_value['display'];
      }
      return $field_value['value'];
    }
  }

  public function editFieldValue($field_uid, $document_uid, $value)
  {
    if (!$field_uid) {
      return;
    }

    if (is_array($value)) {
      $value = implode(",", $value);
    }
    $data = [
      "field_uid" => $field_uid,
      "document_uid" => (string)$document_uid,
      "value" => $value,
      'session' => $this->getSession(),
    ];
    $this->daemon->exec("SaveFieldValue", $data);
    return;
  }

  public function appendLogFieldValue($field_uid, $document_uid, $log)
  {
    if ($field_uid) {
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($field_uid);
      if (!empty($field_info['type'])) {
        $this->load->model('extension/field/' . $field_info['type']);
        $model = "model_extension_field_" . $field_info['type'];
        $this->$model->appendLogValue($field_uid, $document_uid, $log);
      }
    }
  }

  /**
   * Метод возвращает массив со структурными идентификаторами, имеющими доступ к документу
   * В расчет принимаются как доступ через вкладку Доступ доктайпа, так и доступ через действие Доступ
   * @param type $document_uid
   */
  public function getDocumentAccessStructureIds($document_uid)
  {
    $result = array();
    $query_document_access = $this->db->query("SELECT subject_uid FROM " . DB_PREFIX . "document_access WHERE document_uid='" . $this->db->escape($document_uid) . "' ");
    foreach ($query_document_access->rows as $row) {
      $result[] = $row['subject_uid'];
    }
    $document_info = $this->getDocument($document_uid, false);
    //получаем всю цепочку подразделений выше текущего
    $department_uids = array_merge([$document_info['department_uid']], $this->getAncestryDocuments($document_info['department_uid'], $this->config->get('structure_field_parent_id')));

    $query_matrix_doctype_access = $this->db->query(
      "SELECT subject_uid FROM " . DB_PREFIX . "matrix_doctype_access " .
        " WHERE doctype_uid = (SELECT doctype_uid FROM " . DB_PREFIX . "document WHERE document_uid='" . $this->db->escape($document_uid) . "')" .
        " AND (" .
        " object_uid = (SELECT author_uid FROM " . DB_PREFIX . "document WHERE document_uid='" . $this->db->escape($document_uid) . "')" .
        " OR" .
        " object_uid IN ('" . implode("','", $department_uids) . "') )"
    );
    foreach ($query_matrix_doctype_access->rows as $row) {
      $result[] = $row['subject_uid'];
    }
    return array_unique($result);
  }

  public function hasAccessButton($route_button_uid, $document_uid)
  {
    if (!$route_button_uid) {
      return 0;
    }
    $buttons = $this->getButtons($document_uid);
    foreach ($buttons as $button) {
      if ($button['route_button_uid'] == $route_button_uid) {
        return true;
      }
    }
    return false;

    $route_button_uid = $this->db->escape($route_button_uid);
    $document_uid = $this->db->escape($document_uid);
    $suid = implode("','", $this->customer->getStructureIds());
    $query = $this->db->query("SELECT * FROM `button_delegate` WHERE `uid` = '$route_button_uid' 
      AND `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid` = (
                                 SELECT `route_uid` FROM `document` WHERE document_uid = '$document_uid') ) 
      AND (`document_uid` = '$document_uid' OR document_uid = '0') 
      AND structure_uid IN ('$suid') ");
    return ($query->num_rows);
  }

  public function updateButtonDelegate($field_uid, $document_uid, $value)
  {
    // $checks = array('route', 'folder'); //проверяем поле на наличие в делегировании кнопок маршрутов доктайпов и журналов
    // foreach ($checks as $check) {
    $query = $this->db->query("SELECT `uid` FROM `button_field` WHERE `field_uid`='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows) { //изменяем поле используется в делегировании 
      //structure_uid могут быть перечислены через запятую
      if (is_array($value)) {
        $values = $value;
      } else {
        $values = explode(",", $value);
      }
      foreach ($query->rows as $button) {
        $delegate_structure_uids = $values;
        //получаем другие поля, которые используются в делегировании данной кнопки
        $query_delegate = $this->db->query("SELECT field_uid FROM `button_field` WHERE `uid` = '" . $button['uid'] . "' ");
        if ($query_delegate->num_rows) {
          foreach ($query_delegate->rows as $delegate_field) {
            if ($delegate_field['field_uid'] == $field_uid) {
              continue;
            }
            $delegate_field_value = $this->getFieldValue($delegate_field['field_uid'], $document_uid);
            if ($delegate_field_value) {
              if (is_array($delegate_field_value)) {
                $delegate_field_values = $delegate_field_value;
              } else {
                $delegate_field_values = explode(",", $delegate_field_value);
              }
              $delegate_structure_uids = array_merge($delegate_structure_uids, $delegate_field_values);
            }
          }
        }
        //удаляем прежнее значение
        $this->db->query("DELETE FROM `button_delegate` "
          . "WHERE `uid` = '" . $this->db->escape($button['uid']) . "' "
          . "AND document_uid = '" . $this->db->escape($document_uid) . "' ");
        //готовим новые записи
        $sqls = array();
        $delegate_structure_uids = array_unique($delegate_structure_uids);
        foreach ($delegate_structure_uids as $structure_uid) {
          $structure_uid = trim($structure_uid);
          if ($structure_uid) {
            $sqls[] = "('" . $this->db->escape($button['uid']) . "','" . $this->db->escape($document_uid) . "','" . $this->db->escape($structure_uid) . "')";
          }
        }
        $sqls = array_unique($sqls);
        if ($sqls) {
          $this->db->query("INSERT INTO `button_delegate` "
            . "(uid, document_uid, structure_uid) "
            . "VALUES " . implode(",", $sqls));
        }
      }
    }
    // }
  }

  public function getButtons($document_uid)
  {
    $data = [
      'parent_uid' => $document_uid,
      'user_uid' => $this->customer->getStructureId(),
    ];

    $r = $this->daemon->exec("GetUserButtons", $data);
    $result = array();
    foreach ($r as $route_button) {
      $result[] = array(
        'route_button_uid' => $route_button['uid'],
        'route_uid' => $route_button['parent_uid'],
        'picture' => $route_button['picture'],
        'hide_button_name' => $route_button['hide_button_name'],
        'color' => $route_button['color'],
        'background' => $route_button['background'],
        'action' => $route_button['action'],
        'action_log' => $route_button['action_log'],
        'action_move_route_uid' => $route_button['action_move_route_uid'],
        'show_after_execute' => $route_button['show_after_execute'],
        'action_params' => $route_button['action_type'],
        'name' => $route_button['descriptions'][$this->config->get('config_language_id')]['name'] ?? current($route_button['descriptions'])['name'] ?? "",
        'description' => $route_button['description'],
        'button_group_uid' => $route_button['button_group_uid']
      );
    }
    return $result;
    // print_r($result);
    // exit;

    $document_uid = $this->db->escape($document_uid);
    $language_id = (int) $this->config->get('config_language_id');
    $suid = implode("','", $this->customer->getStructureIds());
    //выдает все кнопки на текущей точке маршрута
    $query = $this->db->query("SELECT * FROM `button` rb 
      LEFT JOIN `button_description` rbd ON (rb.`uid` = rbd.`uid` AND rbd.`language_id` = '$language_id' AND rbd.`draft`=0) 
      WHERE 
      rb.`parent_uid` = (SELECT DISTINCT `route_uid` FROM `document` WHERE `document_uid` = '$document_uid') 
      AND rb.`uid` IN (
          SELECT `uid` FROM `button_delegate` WHERE (document_uid = '$document_uid' OR document_uid = '0') 
            AND structure_uid IN ('$suid')) 
      AND rb.`draft` = 0      
      ORDER BY `sort` ASC");
    $result = array();
    foreach ($query->rows as $route_button) {
      $result[] = array(
        'route_button_uid' => $route_button['uid'],
        'route_uid' => $route_button['parent_uid'],
        'picture' => $route_button['picture'],
        'hide_button_name' => $route_button['hide_button_name'],
        'color' => $route_button['color'],
        'background' => $route_button['background'],
        'action' => $route_button['action'],
        'action_log' => $route_button['action_log'],
        'action_move_route_uid' => $route_button['action_move_route_uid'],
        'show_after_execute' => $route_button['show_after_execute'],
        'action_params' => json_decode($route_button['action_params'], true),
        'name' => $route_button['name'],
        'description' => $route_button['description'],
        'button_group_uid' => $route_button['button_group_uid']
      );
    }
    return $result;
  }

  public function getButton($button_uid)
  {
    if (!$button_uid) {
      return [];
    }

    $data = [
      'uid' => $button_uid,
      'draft' => 1,
    ];
    $button = $this->daemon->exec("GetButton", $data);
    // print_r($button);
    return array(
      'route_uid' => $button['parent_uid'],
      'picture' => $button['picture'],
      'hide_button_name' => $button['hide_button_name'],
      'color' => $button['color'],
      'background' => $button['background'],
      'action' => $button['action'],
      'action_log' => $button['action_log'],
      'action_move_route_uid' => $button['action_move_route_uid'],
      'show_after_execute' => $button['show_after_execute'],
      'action_params' => $button['action_type'],
      'name' => $button['descriptions'][$this->config->get('config_language_id')]['name'] ?? current($button['descriptions'])['name'] ?? "",
      'description' => $button['description'],
      'button_group_uid' =>  $button['button_group_uid']
    );
    print_r($button);
    exit;

    $button_uid = $this->db->escape($button_uid);
    $language_id = (int) $this->config->get('config_language_id');

    $query = $this->db->query("SELECT DISTINCT * FROM `button` rb 
      LEFT JOIN `button_description` rbd ON 
        (rb.`uid` = rbd.`uid` AND rbd.`language_id` = '$language_id') AND rbd.`draft`=0 
      WHERE rb.`uid` = '$button_uid' ");
    if ($query->num_rows) {
      return array(
        'route_uid' => $query->row['parent_uid'],
        'picture' => $query->row['picture'],
        'hide_button_name' => $query->row['hide_button_name'],
        'color' => $query->row['color'],
        'background' => $query->row['background'],
        'action' => $query->row['action'],
        'action_log' => $query->row['action_log'],
        'action_move_route_uid' => $query->row['action_move_route_uid'],
        'show_after_execute' => $query->row['show_after_execute'],
        'action_params' => json_decode($query->row['action_params'], true),
        'name' => $query->row['name'],
        'description' => $query->row['description'],
        'button_group_uid' =>  $query->row['button_group_uid']
      );
    } else {
      return array();
    }
  }

  public function getFirstRoute($doctype_uid)
  {
    $query = $this->db->query("SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $this->db->escape($doctype_uid) . "' ORDER BY sort ASC LIMIT 0,1");
    if (isset($query->row['route_uid'])) {
      return $query->row['route_uid'];
    } else {
      return 0;
    }
  }

  public function getRoute($route_uid)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route WHERE route_uid = '" . $this->db->escape($route_uid) . "' ");
    return $query->row;
  }



  public function getRouteActions($route_uid)
  {
    if (!$route_uid) {
      return [];
    }

    $data = [
      'route_uid' => $route_uid,
      'context'   => "",
      'draft'     => "0"
    ];
    $route_actions = $this->daemon->exec("GetRouteActions", $data);
    $result = [];
    foreach ($route_actions as $ctx_name => $ctx_actions) {
      $result[$ctx_name] = [];
      foreach ($ctx_actions as $action) {
        if (!$action['status']) {
          continue;
        }
        $action['params'] = $action['action_type'];
        $result[$ctx_name][] = $action;
      }
    }
    return  $result;
  }

  /**
   * Метод проверяет наличие точки у документа
   * @param type $document_uid
   * @param type $route_uid
   * @return boolean
   */
  public function isDocumentRoute($document_uid, $route_uid)
  {
    if ($route_uid && $document_uid) {
      //проверим наличие точки $route_uid
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route WHERE route_uid = '" . $this->db->escape($route_uid) . "' AND doctype_uid = (SELECT doctype_uid FROM " . DB_PREFIX . "document WHERE document_uid='" . $this->db->escape($document_uid) . "')");
      return ($query->num_rows > 0);
    }
    return FALSE;
  }

  public function moveRoute($document_uid, $route_uid)
  {
    $data = [
      "document_uid" => $document_uid,
      "route_uid" => $route_uid,
      "session" => $this->getSession(),
    ];
    return $this->daemon->exec("MoveDocument", $data);
    ////////////////////////////////////
    if ($this->isDocumentRoute($document_uid, $route_uid)) {
      $this->db->query("UPDATE " . DB_PREFIX . "document SET route_uid = '" . $this->db->escape($route_uid) . "' WHERE document_uid = '" . $this->db->escape($document_uid) . "' ");
      $this->load->model('doctype/doctype');
      $route_info = $this->model_doctype_doctype->getRoute($route_uid);
      $this->load->model('tool/utils');
      $this->model_tool_utils->addLog($document_uid, 'route', "", "", $route_info['name']);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Метод возвращает всех предков document_uid по дереву, выстраиваемомум по $parent_field_uid
   */
  public function getAncestryDocuments($document_uid, $parent_field_uid)
  {
    $result = array();
    $field_value = $this->getFieldValue($parent_field_uid, $document_uid);
    if ($field_value && $field_value !== $document_uid) {
      $result[] = $field_value;
      $result = array_merge($result, $this->getAncestryDocuments($field_value, $parent_field_uid));
    }
    return $result;
  }
  /**
   * Метод возвращает всех потомков document_uid по дереву, выстраиваемомум по $parent_field_uid
   */
  public function getDescendantsDocuments($document_uid, $parent_field_uid)
  {
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($parent_field_uid);
    $query = $this->db->query(
      "SELECT fvp.document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field_info['type']) . " fvp "
        . "WHERE fvp.field_uid = '" . $this->db->escape($parent_field_uid) . "' AND fvp.value = '" . $this->db->escape($document_uid) . "' "
    );
    $result = array();

    foreach ($query->rows as $document) {
      if ($document_uid == $document['document_uid']) {
        continue; //сам себе дите и родитель
      }
      $result[] = $document['document_uid'];
      $result = array_merge($result, $this->getDescendantsDocuments($document['document_uid'], $parent_field_uid));
    }
    return $result;
  }

  /**
   * Метод возвращает всех предков document_uid по дереву, выстраиваемомум по $parent_field_uid
   */
  public function getBrothersDocuments($document_uid, $parent_field_uid)
  {
    $result = array();
    $parent = $this->getFieldValue($parent_field_uid, $document_uid);
    //получаем все документы, у которых значение $parent_field_uid равно $parent
    $data = array(
      'filter_names' => array(
        $parent_field_uid => array(
          array(
            'comparison' => "equal",
            'value'     => $parent
          )
        )
      )
    );
    $result = $this->getDocumentIds($data);
    //удаляем сам документ из массива братьев
    unset($result[array_search($document_uid, $result)]);
    return $result;
  }

  public function addAccess($subject_uid, $document_uid)
  {
  }

  public function removeAccess($subject_uid, $document_uid)
  {
  }

  public function removeDocument($document_uid)
  {
    $document_uid = $this->db->escape($document_uid);
    //получаем все поля типа данного документа
    //проверяем, не удаляет ли пользователь свой структурный ID
    $this->load->language('document/document');
    if ((is_null($this->customer->getStructureIds()) || array_search($document_uid, $this->customer->getStructureIds()) === FALSE) && $this->customer->getId() != $document_uid) {
      //сохраняем информацию, чтобы проверить не было ли перемещения после выполнения контекста удаления
      $document_info = $this->getDocument($document_uid, false);
      if (!$document_info) {
        return "";
      }
      //запускаем контекст удаления
      $params = array(
        'document_uid' => $document_uid,
        'context' => 'delete'
      );
      $result = $this->load->controller('document/document/route_cli', $params);
      //если изменилась точка - отмена удаления
      $document_info_new = $this->getDocument($document_uid, false);
      if (!$document_info_new || $document_info['route_uid'] != $document_info_new['route_uid']) {
        $result['error'] = $this->language->get('error_cancel_remove');
        return $result;
      }
      //проверяем - не удаляется ли документ Структуры?
      if ($document_info['doctype_uid'] == $this->config->get('structure_id')) {
        // ищем документы, где удаляемый документ указан в deparment_uid - имеем дело с удалением подразделения
        $query_dep = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "document WHERE department_uid='" . $this->db->escape($document_uid) . "' ");
        if ($query_dep->num_rows) {
          // документы, созданные в удаляемом подразделении есть, переносим их в корневое подразделение
          $query_structure_root = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->config->get('structure_type') . " WHERE field_uid='" . $this->config->get('structure_field_parent_id') . "' AND (value='' OR value=document_uid) ");
          if (empty($query_structure_root->row['document_uid'])) {
            return $result;
          }
          $this->db->query("UPDATE " . DB_PREFIX . "document SET department_uid='" . $query_structure_root->row['document_uid'] . "' WHERE department_uid='" . $this->db->escape($document_uid) . "' ");
        }
      }

      //проверки завершены, запускаем удаление документа
      $field_query = $this->db->query("SELECT * FROM `field` WHERE 
      `doctype_uid` =  (SELECT `doctype_uid` FROM `document` WHERE `document_uid`='$document_uid') 
      AND `draft` = 0");
      foreach ($field_query->rows as $field) {
        $model = "model_extension_field_" . $field['type'];
        try {
          $this->load->model('extension/field/' . $field['type']);
          $this->$model->removeValue($field['field_uid'], $document_uid);
        } catch (\Throwable $th) {
          //throw $th;
        }
      }
      $this->db->query("DELETE FROM " . DB_PREFIX . "document WHERE document_uid = '" . $this->db->escape($document_uid) . "' ");
      $this->db->query("DELETE FROM " . DB_PREFIX . "document_access WHERE document_uid = '" . $this->db->escape($document_uid) . "' ");

      return $result;
    } else {
      return array('error' => $this->language->get('text_remove_self_structure'));
    }
  }

  public function getVariable($variable, $document_uid = 0)
  {

    if (!$document_uid && !empty($this->request->get['document_uid'])) {
      $document_uid = $this->request->get['document_uid'];
    }
    switch ($variable) {
      case 'var_author_name':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }
          $this->load->model('account/customer');
          return $this->model_account_customer->getCustomerName($document_info['author_uid']);
        } else {
          return "";
        }
      case 'var_department_name':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }

          $this->load->model('account/customer');
          return $this->model_account_customer->getCustomerName($document_info['department_uid']);
        } else {
          return "";
        }
      case 'var_author_uid':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          return $document_info['author_uid'] ?? "";
        } else {
          return "";
        }
      case 'var_department_uid':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          return $document_info['department_uid'] ?? "";
        } else {
          return 0;
        }
      case 'var_customer_id':
      case 'var_customer_uid':
        $structureId = $this->customer->getStructureId();
        if (!$structureId) {
          return "";
        }
        return $structureId;
      case 'var_customer_uids':
        $structureIds = $this->customer->getStructureIds();
        if (!$structureIds) {
          return "";
        }
        return implode(",", $structureIds);
      case 'var_customer_user_uid':
        $id = $this->customer->getId();
        if (!$id) {
          return "";
        }
        return $id;
      case 'var_customer_name':
        return $this->customer->getName();
      case 'var_current_route_uid':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          return $document_info['route_uid'];
        } else {
          return "";
        }
      case 'var_current_route_name':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }
          $this->load->model('doctype/doctype');
          $route_descriptions = $this->model_doctype_doctype->getRouteDescriptions($document_info['route_uid']);
          if (isset($route_descriptions[$this->config->get('config_language_id')]['name'])) {
            return $route_descriptions[$this->config->get('config_language_id')]['name'];
          } else {
            return "";
          }
        } else {
          return "";
        }
      case 'var_current_route_description':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }
          $this->load->model('doctype/doctype');
          $route_descriptions = $this->model_doctype_doctype->getRouteDescriptions($document_info['route_uid']);
          if (isset($route_descriptions[$this->config->get('config_language_id')]['description'])) {
            return $route_descriptions[$this->config->get('config_language_id')]['description'];
          } else {
            return "";
          }
        } else {
          return "";
        }
      case 'var_current_date':
        $dbformat = 'Y-m-d';
        $date = new DateTime('now');
        $value = $date->format($dbformat);
        return $value . " 00:00:00";
      case 'var_current_time':
        $dbformat = 'Y-m-d H:i:s';
        $date = new DateTime('now');
        $value = $date->format($dbformat);
        return $value;
      case 'var_current_locale_datetime':
        $dbformat = $this->language->get('datetime_format');
        $date = new DateTime('now');
        $value = $date->format($dbformat);
        return $value;
      case 'var_current_locale_time':
        $dbformat = $this->language->get('time_format');
        $date = new DateTime('now');
        $value = $date->format($dbformat);
        return $value;
      case 'var_time_added':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }
          $format = $this->language->get('datetime_format');
          $d = explode(".", $document_info['date_added']);

          $datetime = $d[0];
          if (count($d) > 1) {
            $d2 = explode("+", $d[1]);
            if (count($d2) > 1) {
              $datetime .= "+" . $d2[1];
            }
          }

          $date = new DateTime($datetime);
          $value = $date->format($format);
          return $value;
        }
        return "";
      case 'var_document_time_added':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          return $document_info['date_added'] ?? "";
        }
        return "";
      case 'var_date_added':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
          if (!$document_info) {
            return "";
          }
          $format = $this->language->get('date_format_short');
          $d = explode(".", $document_info['date_added']); // echo $document_info['date_added']; // 2020-09-28T15:53:00.623113006+06:00
          $datetime = $d[0];
          if (count($d) > 1) {
            $d2 = explode("+", $d[1]);
            if (count($d2) > 1) {
              $datetime .= "+" . $d2[1];
            }
          }

          $date = new DateTime($datetime);
          $value = $date->format($format);
          return $value;
        }
        return "";
      case 'var_current_locale_date':
        $dbformat = $this->language->get('date_format_short');
        $date = new DateTime('now');
        $value = $date->format($dbformat);
        return $value;
      case 'var_current_document_uid':
        return $document_uid;
      case 'var_current_button_uid':
        return $this->session->data['current_button_uid'] ?? "";
      case 'var_change_field_uid':
        return !empty($this->request->get['field_uid']) ? $this->request->get['field_uid'] : 0;
      case 'var_change_field_value':
        return !empty($this->request->get['field_value']) ? $this->request->get['field_value'] : "";
      case 'var_current_doctype_uid':
        if (!empty($document_uid)) {
          $document_info = $this->getDocument($document_uid, false);
        }
        return $document_info['doctype_uid'] ?? "";
      case 'var_current_folder_uid':
        return $this->request->get['folder_uid'] ?? "";
      case 'var_struid_access_document':
        // $server_info
        return implode(",", $this->getDocumentAccessStructureIds($document_uid));
      case 'QUERY_INFO':
        $result = [
          'HTTP_COOKIE: ' . ($_SERVER['HTTP_COOKIE'] ?? ""),
          'HTTP_USER_AGENT: ' . ($_SERVER['HTTP_USER_AGENT'] ?? ""),
          'HTTP_HOST: ' . ($_SERVER['HTTP_HOST'] ?? ""),
          'SERVER_NAME: ' . ($_SERVER['SERVER_NAME'] ?? ""),
          'SERVER_PORT: ' . ($_SERVER['SERVER_PORT'] ?? ""),
          'SERVER_ADDR: ' . ($_SERVER['SERVER_ADDR'] ?? ""),
          'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? ""),
          'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? ""),
          'REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? ""),
          'QUERY_STRING: ' . ($_SERVER['QUERY_STRING'] ?? ""),
          'REMOTE_PORT: ' . ($_SERVER['REMOTE_PORT'] ?? ""),
          'REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR'] ?? ""),
          'HTTP_CLIENT_IP: ' . ($_SERVER['HTTP_CLIENT_IP'] ?? ""),
          'HTTP_X_FORWARDED_FOR: ' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ""),
          'HTTP_REFERER: ' . ($_SERVER['HTTP_REFERER'] ?? "")
        ];
        return implode(PHP_EOL . "<br>", $result);
        break;
      default:
        break;
    }
  }

  /**
   * Метод проверки наличия подписки на изменение поля. При наличии
   * подписки запускает подписанный модуль. Метод запускается при отключенном 
   * демоне
   * @param type $field_uid
   */
  public function executeFieldSubscription($field_uid, $document_uid, $field_type)
  {
    $subscriptions = $this->getSubscriptions($field_uid, $document_uid);
    if ($this->variable->get('subscription_time_' . $field_type)) {
      //демон работал, а потом был остановлен; возможно остались неактуализированные подписки
      $this->load->model('daemon/queue');
      $time = new DateTime('now');
      $now = $time->format('Y-m-d H:i:s');
      $subscriptions = array_merge($subscriptions, $this->model_daemon_queue->getFieldChangeSubscriptions($field_type, $this->variable->get('subscription_time_' . $field_type), $now));
      $this->load->model('setting/variable');
      $this->model_setting_variable->delVar("subscription_time_" . $field_type);
      $this->variable->set('subscription_time_' . $field_type, "");
    }
    if ($subscriptions) {
      foreach ($subscriptions as $subscription) {
        $field_info = $this->model_doctype_doctype->getField($subscription['subscription_field_uid']);
        if (!$field_info['type']) {
          //поле подписки не существует, удаляем подписку
          $this->model_doctype_doctype->delSubscription($subscription['subscription_field_uid']);
          continue;
        }
        $model = "model_extension_field_" . $field_info['type'];
        $this->load->model('extension/field/' . $field_info['type']);
        $this->$model->subscription($subscription['subscription_field_uid'], array($subscription['subscription_document_uid']));
      }
    }
  }

  /**
   * Метод возвращает подписки на изменение для field_uid с возможным указанием document_uid
   * @param type $field_uid
   * @param type $document_uid
   */
  public function getSubscriptions($field_uid, $document_uid = "")
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "field_change_subscription WHERE field_uid = '" . $this->db->escape($field_uid) . "' ";
    if ($document_uid) {
      $sql .= "AND document_uid = '" . $this->db->escape($document_uid) . "' ";
    }
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      return $query->rows;
    } else {
      return array();
    }
  }

  /**
   * Обновление подписок полей на изменения. 
   * @param type $subscriptions - массив ('subscription_field_uid','subscription_document_uid')
   */
  public function updateSubscription($subscriptions)
  {
    if ($subscriptions) {
      $this->load->model('doctype/doctype');

      $fields = array();
      $load_models = array();
      foreach ($subscriptions as $subscription) {
        $fields[$subscription['subscription_field_uid']][] = $subscription['subscription_document_uid'];
      }
      foreach ($fields as $field_uid => $documents) {
        $field_info = $this->model_doctype_doctype->getField($field_uid);
        if (empty($field_info['type'])) {
          $this->model_doctype_doctype->delSubscription($field_uid);
          continue;
        }
        $documents = array_unique($documents);
        $model = "model_extension_field_" . $field_info['type'];
        if (empty($load_models[$field_info['type']])) {
          $this->load->model('extension/field/' . $field_info['type']);
          $load_models[$field_info['type']] = 1;
        }
        $this->$model->subscription($field_uid, $documents);
      }
    }
  }

  /**
   * Метод для получения заголовка докуменрта. Сначала проверяется наличие поля в заголовке, и наличие его значения для 
   * текущего языка; если дисплея нет - возвращается название типа документа
   * @param type $document_uid
   */
  public function getDocumentTitle($document_uid)
  {
    $query_title_field = $this->db->query("SELECT title_field_uid, name FROM " . DB_PREFIX . "doctype_description WHERE "
      . "language_id = '" . (int) $this->config->get('config_language_id') . "' AND doctype_uid = "
      . "(SELECT DISTINCT doctype_uid FROM " . DB_PREFIX . "document WHERE document_uid='" . $this->db->escape($document_uid) . "')");
    if ($query_title_field->num_rows) {
      $display_value = $this->getFieldDisplay($query_title_field->row['title_field_uid'], $document_uid);
      if ($display_value) {
        return $display_value;
      }
      return $query_title_field->row['name'];
    }
    return "";
  }

  /**
   * Проверка:
   * 1. $type == setting_field_uid: на наличие настроечного поля в настройка Доступа доктайпа и обновление матрицы доступа, 
   * если поле используется
   * 2. $type = document_uid: передан изменненный документ структуры; проверяем его на наличе в матрице, и пересчитываем
   * все доктайпы, в которых он есть
   * @param type $field_uid
   */
  private function updateDoctypeAccess($uid, $type = "setting_field_uid")
  {
    if ($type == "setting_field_uid") {
      // настроечное поле; проверим нет ли его в настройках доступа доктайпа
      $sql = "SELECT DISTINCT doctype_uid FROM " . DB_PREFIX . "doctype_access WHERE subject_uids LIKE '%" . $this->db->escape($uid) . "%' ";
    } else {
      // получен док Структуры, получаем все доктайпы, где он задействован,
      // а также все доктайпы, где задействованы его родители (например, было К - Д - О1, есть доктайп с доступом к докам по авторству
      // К, что означает наличие в матрице и К, и Д, и О1. Добавляется О2, его в матрице нет, обновление не срабатывает и к докам, 
      // созданным в О2 доступа нет)
      $structure_uids = $this->getParents($uid, $this->config->get('structure_type'), $this->config->get('structure_field_parent_id'));
      $structure_uids[] = $this->db->escape($uid);
      $sql = "SELECT DISTINCT doctype_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE object_uid IN ('" . implode("','", $structure_uids) . "') ";
    }
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      $this->load->model('doctype/doctype');
      foreach ($query->rows as $row) {
        $this->model_doctype_doctype->updateDoctypeAccess($row['doctype_uid']);
      }
    }
  }


  /**
   * Метод возвращает версию документа по ее  номеру. Если номера нет, возвращается последняя версия
   * @param type $document_uid
   * @param type $history_id
   * @return type
   */
  public function getDocumentHistory($document_uid, $history_id = 0)
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "document_history WHERE document_uid = '" . $this->db->escape($document_uid) . "' ";
    if ($history_id) {
      $sql .= " AND history_id='" . (int) $history_id . "' ";
    } else {
      $sql .= " ORDER BY history_id DESC LIMIT 1";
    }
    $query = $this->db->query($sql);
    return $query->row;
  }
  /**
   * Метод возвращает все версии из истории документа
   * @param type $document_uid
   * @return type
   */
  public function getDocumentHistories($document_uid)
  {
    $query = $this->db->query("SELECT history_id, author_uid, date_added FROM " . DB_PREFIX . "document_history WHERE document_uid = '" . $this->db->escape($document_uid) . "' ORDER BY history_id DESC ");
    return $query->rows;
  }

  public function addDocumentHistory($document_uid)
  {
    $document_info = $this->getDocument($document_uid, false);
    if (!$document_info) {
      return;
    }
    // поскольку добавляется история, явно произошло изменение / добавление документа. Между тем, если документ создан с пустыми полями, кэш не сбрасывается, сбрасываем его тут

    $query_field = $this->db->query("SELECT * FROM `field` WHERE `doctype_uid`='" . $this->db->escape($document_info['doctype_uid']) . "' 
        AND `draft`=0 AND `history`=1 ORDER BY `field_uid` ASC");
    if (!$query_field->num_rows) {
      //нет полей для создания версии
      return;
    }
    $field_values = array();
    foreach ($query_field->rows as $field) {
      $field_values[$field['field_uid']] = $this->getFieldValue($field['field_uid'], $document_uid) ?? ""; //если NULL, то пишем пустоту
    }
    $new_version = json_encode($field_values);
    $last_version = $this->getDocumentHistory($document_uid);
    if ($new_version != ($last_version['version'] ?? "")) {
      //сохраняем версию
      $this->db->query("INSERT INTO " . DB_PREFIX . "document_history SET document_uid='" . $this->db->escape($document_uid) . "', "
        . "version='" . $this->db->escape($new_version) . "', author_uid='" . $this->customer->getStructureId() . "' ");
    }
  }

  public function setNotification($document_uid, $recipient_uid, $message)
  {
    $this->db->query(
      "INSERT INTO " . DB_PREFIX . "document_notification SET "
        . "document_uid='" . $this->db->escape($document_uid) . "', "
        . "recipient_uid='" . $this->db->escape($recipient_uid) . "', "
        . "message='" . $this->db->escape($message) . "' "
    );
  }

  public function removeNotifications($document_uid, $recipient_uid = "", $notification_id = "")
  {
    // если не указан полчатель уведомления и ид уведомления, удаляем все уведомления для данного документа
    if (!$recipient_uid) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "document_notification WHERE document_uid = '" . $this->db->escape($document_uid) . "' ");
      return;
    }
    // if ($notification_id) {
    //   $this->db->query("DELETE FROM " . DB_PREFIX . "document_notification WHERE document_uid = '" . $this->db->escape($document_uid) . "' AND  recipient_uid = '" . $this->db->escape($recipient_uid) . "'"); // AND notification_id = '" .  $this->db->escape($notification_id) . "'");
    // } else {
    $this->db->query("DELETE FROM " . DB_PREFIX . "document_notification WHERE document_uid = '" . $this->db->escape($document_uid) . "' AND  recipient_uid = '" . $this->db->escape($recipient_uid) . "'");
    // }
  }

  public function getNotifications($structure_uid)
  {

    $result = [];
    $query = $this->db->query("SELECT document_uid, message, created FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' ORDER BY created DESC");
    if ($query->num_rows) {

      foreach ($query->rows as $row) {
        $result[] = [
          "notification_id" => $row['document_uid'],
          "document_uid" => $row['document_uid'],
          "message" => $row['message'],
          "created" => $row['created'],
        ];
      }
      return $result;
    }
  }

  public function getNewNotifications($structure_uid, $index)
  {
    $cnt = intval($this->getNotificationCount($structure_uid));
    $query = $this->db->query("SELECT document_uid, message, created FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' ORDER BY created DESC LIMIT " . ($cnt - $index));
    $result = [];
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        $result[] = [
          "notification_id" => $row['document_uid'],
          "document_uid" => $row['document_uid'],
          "message" => $row['message'],
          "created" => $row['created'],
        ];
      }
    }
    return $result;
  }

  public function getNotificationCount($structure_uid)
  {

    $query = $this->db->query("SELECT COUNT(document_uid) as notification_count FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "'");
    return $query->row['notification_count'];
  }

  public function getNotificationCountDatePoint($structure_uid, $date_point)
  {
    if (!($date_point instanceof DateTime)) {
      $date_point = new DateTime();
    }
    $date = $date_point->format('Y-m-d H:i:s');
    $sql = "(SELECT COUNT(document_uid) as count FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' AND created > '" . $date . "') UNION (SELECT COUNT(document_uid) as count FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' AND created <= '" . $date . "')";
    $query = $this->db->query($sql);
    $result = array("after" => $query->rows[0]["count"], "before" =>  $query->rows[1]["count"]);
    return $result;
  }

  public function getAllNotifications($structure_uid)
  {
    $cnt = intval($this->getNotificationCount($structure_uid));
    $query = $this->db->query("SELECT document_uid, message, created FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' ORDER BY created DESC");
    $result = [];
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        $result[] = [
          "notification_id" => $row['document_uid'],
          "document_uid" => $row['document_uid'],
          "message" => $row['message'],
          "created" => $row['created'],
        ];
      }
    }
    return $result;
  }

  public function getNotificationsAfterDatePoint($structure_uid, $date_point)
  {
    if (!($date_point instanceof DateTime)) {
      $date_point = new DateTime();
    }
    $date = $date_point->format('Y-m-d H:i:s');
    $result = [];
    $query = $this->db->query("SELECT document_uid, message, created FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' AND created > '" . $date . "' ORDER BY created DESC");
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        $result[] = [
          "notification_id" => $row['document_uid'],
          "document_uid" => $row['document_uid'],
          "message" => $row['message'],
          "created" => $row['created'],
        ];
      }
    }
    return $result;
  }

  public function getNotificationsBeforeDatePoint($structure_uid, $date_point)
  {
    if (!($date_point instanceof DateTime)) {
      $date_point = new DateTime();
    }
    $date = $date_point->format('Y-m-d H:i:s');
    $result = [];
    $query = $this->db->query("SELECT document_uid, message, created FROM " . DB_PREFIX . "document_notification WHERE recipient_uid = '" . $this->db->escape($structure_uid) . "' AND created <= '" . $date . "' ORDER BY created DESC");
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        $result[] = [
          "notification_id" => $row['document_uid'],
          "document_uid" => $row['document_uid'],
          "message" => $row['message'],
          "created" => $row['created'],
        ];
      }
    }
    return $result;
  }

  public function getAccesses($doctype_uid, $document_uids = [])
  {
    $sql = "SELECT document_uid FROM document_access WHERE ";
    if ($doctype_uid) {
      $sql .= "doctype_uid='" . $this->db->escape($doctype_uid) . "' AND ";
    }
    if ($document_uids) {
      $sql .= "document_uid IN ('" . implode("','", $document_uids) . "') AND ";
    }

    $sql .= " subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') ";
    $query_document = $this->db->query($sql);
    $documents = [];
    foreach ($query_document->rows as $row) {
      $documents[$row['document_uid']] = '';
    }
    $query_doctype = $this->db->query("SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE doctype_uid='" . $this->db->escape($doctype_uid) . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
    $doctypes = [];
    foreach ($query_doctype->rows as $row) {
      $doctypes[$row['object_uid']] = '';
    }
    return [
      'documents' => $documents,
      'doctypes'  => $doctypes
    ];
  }
  private function jsonEncode($v)
  {
    return json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
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

  private function redirect()
  {
    $this->response->redirect($this->url->link('error/daemon_not_started', true));
  }
}

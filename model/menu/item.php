<?php

class ModelMenuItem extends Model
{

  public function getMenuItems($item_id = "")
  {
    $query = $this->db->query("SELECT DISTINCT(mi.menu_item_id), mid.name, mi.type, mi.image, mi.hide_name, mi.action, mi.action_value FROM " . DB_PREFIX . "menu_item mi "
      . "LEFT JOIN menu_item_description mid ON (mid.menu_item_id = mi.menu_item_id AND mid.language_id = '" . (int) $this->config->get('config_language_id') . "') "
      . "LEFT JOIN menu_item_delegate midlg ON (midlg.menu_item_id = mi.menu_item_id) "
      . "WHERE "
      . "mi.parent_id = '" . $this->db->escape($item_id) . "' "
      . "AND mi.status=1 "
      . "AND midlg.structure_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') "
      . "ORDER BY mi.sort_order ASC, mid.name ASC");

    $result = array();
    foreach ($query->rows as $item) {
      $result[] = array(
        'name' => $item['name'],
        'type' => $item['type'],
        'image' => $item['image'],
        'hide_name' => $item['hide_name'],
        'action' => $item['action'],
        'action_value' => $item['action_value'],
        'children' => $this->getMenuItems($item['menu_item_id'])
      );
    }
    return $result;
  }

  public function getItems($data = array())
  {
    $this->load->model('doctype/doctype');
    $sql = "SELECT mip.menu_item_id AS menu_item_id, "
      . "GROUP_CONCAT(mid1.name ORDER BY mip.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, "
      . "mi1.parent_id, mi1.image, mi1.sort_order, mi1.status  "
      . "FROM " . DB_PREFIX . "menu_item_path mip "
      . "LEFT JOIN " . DB_PREFIX . "menu_item mi1 ON (mip.menu_item_id = mi1.menu_item_id) "
      . "LEFT JOIN " . DB_PREFIX . "menu_item mi2 ON (mip.path_id = mi2.menu_item_id) "
      . "LEFT JOIN " . DB_PREFIX . "menu_item_description mid1 ON (mip.path_id = mid1.menu_item_id) "
      . "LEFT JOIN " . DB_PREFIX . "menu_item_description mid2 ON (mip.menu_item_id = mid2.menu_item_id) "
      . "WHERE mid1.language_id = '" . (int) $this->config->get('config_language_id') . "' "
      . "AND mid2.language_id = '" . (int) $this->config->get('config_language_id') . "'";
    if (!empty($data['filter_name'])) {
      $sql .= " AND mid2.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
    }

    $sql .= " GROUP BY mip.menu_item_id";

    $sort_data = array(
      'name',
      'sort_order'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      $sql .= " ORDER BY " . $data['sort'];
    } else {
      $sql .= " ORDER BY sort_order";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
      $sql .= " DESC";
    } else {
      $sql .= " ASC";
    }

    $query = $this->db->query($sql);

    $result = array();
    $item_ids = array();
    foreach ($query->rows as $item) {
      $result[] = array(
        'menu_item_id'  => $item['menu_item_id'],
        'name'          => $this->model_doctype_doctype->getNamesTemplate($item['name'], $this->config->get('structure_id'), $this->model_doctype_doctype->getTemplateVariables()),
        'parent_id'     => $item['parent_id'],
        'image'         => $item['image'],
        'status'        => $item['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
        'sort_order'    => $item['sort_order']
      );
      $item_ids[] = $item['menu_item_id'];
    }
    //проверка на наличие всех существующих пунктов меню в результате
    $sql2 = "SELECT mi.menu_item_id, mid.name, mi.parent_id, mi.image, mi.status, mi.sort_order  FROM " . DB_PREFIX . "menu_item mi "
      . "LEFT JOIN " . DB_PREFIX . "menu_item_description mid ON (mid.menu_item_id = mi.menu_item_id) "
      . "WHERE mid.language_id = '" . (int) $this->config->get('config_language_id') . "' ";
    if (!empty($data['filter_name'])) {
      $sql2 .= " AND mid.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
    }
    $query_items = $this->db->query($sql2);
    if (count($result) != $query_items->num_rows) {
      foreach ($query_items->rows as $item) {
        if (array_search($item['menu_item_id'], $item_ids) === FALSE) {
          $result[] = array(
            'menu_item_id'  => $item['menu_item_id'],
            'name'          => $this->model_doctype_doctype->getNamesTemplate($item['name'], $this->config->get('structure_id'), $this->model_doctype_doctype->getTemplateVariables()),
            'parent_id'     => $item['parent_id'],
            'image'         => $item['image'],
            'status'        => $item['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
            'sort_order'    => $item['sort_order']
          );
        }
      }
    }
    return $result;
  }

  public function addItem($data)
  {
    $query = $this->db->query("SELECT UUID() AS uid");
    $item_id = $query->row['uid'];

    $sql = "INSERT INTO " . DB_PREFIX . "menu_item SET menu_item_id='" . $item_id . "', ";
    if ($data['type'] == "text") {
      $sql .= "type = 'text', "
        . "action = '" . $this->db->escape($data['action']) . "', "
        . "action_value = '" . ($data['action'] == 'folder' ? $this->db->escape($data['action_id']) : $this->db->escape($data['action_value'])) . "', "
        . "image = '" . ($this->db->escape($data['image']) ?? "") . "', "
        . "hide_name = '" . ((int) $data['hide_name'] ?? 0) . "', "
        . "parent_id = '" . $this->db->escape($data['parent_id']) . "', "
        . "sort_order = '" . (int) $data['sort_order'] . "', "
        . "status = '" . (int) $data['status'] . "' ";
    } else {
      $sql .= "type = 'divider', action = '', action_value = '', image = '', parent_id = '" . $this->db->escape($data['parent_id']) . "', sort_order = '" . (int) $data['sort_order'] . "', status = '" . (int) $data['status'] . "' ";
      $this->load->model('localisation/language');
      foreach ($this->model_localisation_language->getLanguages() as $language) {
        $data['item_description'][$language['language_id']] = "_";
      }
    }


    $this->db->query($sql);

    foreach ($data['item_description'] as $language_id => $value) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "menu_item_description SET "
        . "menu_item_id = '" . $item_id . "', "
        . "language_id = '" . (int) $language_id . "', "
        . "name = '" . $this->db->escape($value['name'] ?? "") . "' ");
    }


    if (!empty($data['item_structure'])) {
      $this->delegate($item_id, $data['item_structure']);
      $set = array();
      foreach ($data['item_structure'] as $uid) {
        $set[] = "('" . $item_id . "','" . $uid . "')";
      }
      if ($set) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "menu_item_field (menu_item_id, uid) VALUES " . implode(",", array_unique($set)));
      }
    }

    // MySQL Hierarchical Data Closure Table Pattern
    $level = 0;

    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_item_path` WHERE menu_item_id = '" . $this->db->escape($data['parent_id']) . "' ORDER BY `level` ASC");

    foreach ($query->rows as $result) {
      $this->db->query("INSERT INTO `" . DB_PREFIX . "menu_item_path` SET "
        . "`menu_item_id` = '" . $this->db->escape($item_id) . "', "
        . "`path_id` = '" . $result['path_id'] . "', "
        . "`level` = '" . (int) $level . "'");
      $level++;
    }

    $this->db->query("INSERT INTO `" . DB_PREFIX . "menu_item_path` SET "
      . "`menu_item_id` = '" . $this->db->escape($item_id) . "', "
      . "`path_id` = '" . $this->db->escape($item_id) . "', "
      . "`level` = '" . (int) $level . "'");

    $this->cache->delete('item');
    return $item_id;
  }

  public function editItem($item_id, $data)
  {
    $this->load->model('doctype/doctype');
    $sql = "UPDATE " . DB_PREFIX . "menu_item SET ";
    if ($data['type'] == "text") {
      $sql .= "type = 'text', "
        . "action = '" . $this->db->escape($data['action']) . "', "
        . "action_value = '" . ($data['action'] == 'folder' ? $this->db->escape($data['action_id']) : $this->db->escape($data['action_value'])) . "', "
        . "image = '" . ($this->db->escape($data['image']) ?? "") . "', "
        . "hide_name = '" . ((int) $data['hide_name'] ?? 0) . "', "
        . "parent_id = '" . $this->db->escape($data['parent_id']) . "', "
        . "sort_order = '" . (int) $data['sort_order'] . "', "
        . "status = '" . (int) $data['status'] . "' ";
    } else {
      $sql .= "type = 'divider', "
        . "action = '', "
        . "action_value = '', "
        . "image = '', "
        . "parent_id = '" . $this->db->escape($data['parent_id']) . "', "
        . "sort_order = '" . (int) $data['sort_order'] . "', "
        . "status = '" . (int) $data['status'] . "' ";
      $this->load->model('localisation/language');
      foreach ($this->model_localisation_language->getLanguages() as $language) {
        $data['item_description'][$language['language_id']] = "_";
      }
    }
    $sql .= "WHERE menu_item_id = '" . $this->db->escape($item_id) . "'";
    $this->db->query($sql);

    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_description WHERE menu_item_id = '" . $this->db->escape($item_id) . "'");

    foreach ($data['item_description'] as $language_id => $value) {

      if (!empty($value['name'])) {
        $name = $this->model_doctype_doctype->getIdsTemplate($value['name'], $this->config->get('structure_id'));
      } else {
        $name = "";
      }
      $this->db->query("INSERT INTO " . DB_PREFIX . "menu_item_description SET "
        . "menu_item_id = '" . $this->db->escape($item_id) . "', "
        . "language_id = '" . (int) $language_id . "', "
        . "name = '" . $this->db->escape($name) . "' ");
    }
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_field WHERE menu_item_id = '" . $this->db->escape($item_id) . "'");

    $this->updateDelegate($item_id, $data);

    $this->cache->delete('item');
  }

  public function deleteItem($item_id)
  {
    $item_id = $this->db->escape($item_id);
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_path WHERE menu_item_id = '" . $item_id . "' OR path_id = '" . $item_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item WHERE menu_item_id = '" . $item_id . "'");
    $this->db->query("UPDATE " . DB_PREFIX . "menu_item SET parent_id='' WHERE parent_id = '" . $item_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_description WHERE menu_item_id = '" . $item_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_delegate WHERE menu_item_id = '" . $item_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_field WHERE menu_item_id = '" . $item_id . "'");
    //        $this->cache->delete('item');
  }

  public function getItem($item_id)
  {
    $item_id = $this->db->escape($item_id);
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "menu_item mi WHERE mi.menu_item_id = '" . $item_id . "' ");
    if ($query->num_rows) {
      return array(
        'type' => $query->row['type'],
        'action' => $query->row['action'],
        'action_value' => $query->row['action_value'],
        'parent_id' => $query->row['parent_id'],
        'image' => $query->row['image'],
        'hide_name' => $query->row['hide_name'],
        'sort_order' => $query->row['sort_order'],
        'status' => $query->row['status'],
        'description' => $this->getItemDescriptions($item_id),
        'delegate' => $this->getItemDelegate($item_id)
      );
    } else {
      return array();
    }
  }

  public function getItemDescriptions($item_id)
  {
    $item_id = $this->db->escape($item_id);
    $this->load->model('doctype/doctype');
    $item_description_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "menu_item_description WHERE menu_item_id = '" . $item_id . "'");

    foreach ($query->rows as $result) {
      $item_description_data[$result['language_id']] = array(
        'name' => $this->model_doctype_doctype->getNamesTemplate($result['name'], $this->config->get('structure_id'), $this->model_doctype_doctype->getTemplateVariables())
      );
    }

    return $item_description_data;
  }

  public function getItemDelegate($item_id)
  {
    $this->load->model('doctype/doctype');
    $item_delegate_data = array();
    $item_id = $this->db->escape($item_id);
    $query = $this->db->query("SELECT mid.uid as uid, fvn.value as name FROM " . DB_PREFIX . "menu_item_field mid "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_name_type') . " fvn ON (fvn.field_uid = '" . $this->config->get('structure_field_name_id') . "' AND fvn.document_uid = mid.uid) "
      . "WHERE mid.menu_item_id = '" . $item_id . "'");
    foreach ($query->rows as $result) {
      if ($result['name'] == null) {
        //это поле
        $name = $this->model_doctype_doctype->getFieldName($result['uid']);
      } else {
        $name = $result['name'];
      }
      $item_delegate_data[] = array(
        'structure_uid' => $result['uid'],
        'name' => $name
      );
    }

    return $item_delegate_data;
  }

  public function getTotalItems()
  {
    $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "menu_item");

    return $query->row['total'];
  }

  /**
   * Метод вызывается при изменении значения любого настроечного поля системы
   * @param type $field_uid
   */
  public function updateMenuDelegate($field_uid)
  {
    $query = $this->db->query("SELECT menu_item_id FROM " . DB_PREFIX . "menu_item_field WHERE uid='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows) {
      //измененное поле используется в делегировании одного или нескольких пунктов меню
      foreach ($query->rows as $item) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_delegate WHERE menu_item_id = '" . $item['menu_item_id'] . "'");
        $this->delegate($item['menu_item_id']);
      }
    }
  }

  /**
   * Метод обновления делегирования пункта меню
   * @param type $item_id
   * @param type $uids
   */
  private function delegate($item_id, $uids = array())
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $this->load->model('tool/utils');
    $item_id = $this->db->escape($item_id);

    if (!$uids) {
      //если "кому делегировать" пусто, то это вызов для пересчета делегирования - список делегируемых полей и структур. нужно получить из базы
      $query_uids = $this->db->query("SELECT uid FROM " . DB_PREFIX . "menu_item_field WHERE menu_item_id='" . $this->db->escape($item_id) . "' ");
      foreach ($query_uids->rows as $row) {
        $uids[] = $row['uid'];
      }
    }

    $sets = array();
    if ($uids) {
      foreach ($uids as $uid) {
        //$uid может быть как идентификатором настроечного поля, так и идентификатором структуры
        $field_info = $this->model_doctype_doctype->getField($uid);
        if ($field_info) {
          //$uid - идентификатор поля
          $field_values = $this->model_document_document->getFieldValue($uid, 0);
          foreach (explode(",", $field_values) as $stucture_uid) {
            $sets[] = "('" . $item_id . "','" . $this->db->escape(trim($stucture_uid)) . "')";
          }
        } else {
          //$uid - идентификатор структуры
          if ($this->model_tool_utils->validateUID(trim($uid))) {
            $sets[] = "('" . $item_id . "','" . $this->db->escape(trim($uid)) . "')";
          }
        }
      }
    }

    if ($sets) {
      $this->db->query(
        "INSERT INTO " . DB_PREFIX . "menu_item_delegate (menu_item_id, structure_uid) VALUES " .
          implode(", ", array_unique($sets))
      );
    }
  }

  /**
   * $data['item_structure'] - может быть пустым, - данные будут получены из БД
   * $data['parent_id']
   */
  public function updateDelegate($item_id, $data)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "menu_item_delegate WHERE menu_item_id = '" . $this->db->escape($item_id) . "'");
    $this->delegate($item_id, $data['item_structure'] ?? []);
    if (!empty($data['item_structure'])) {
      // $this->delegate($item_id, $data['item_structure']);
      $set = array();
      foreach ($data['item_structure'] as $uid) {
        $set[] = "('" . $item_id . "','" . $uid . "')";
      }
      $this->db->query("INSERT INTO " . DB_PREFIX . "menu_item_field (menu_item_id, uid) VALUES " . implode(",", array_unique($set)));
    }

    // MySQL Hierarchical Data Closure Table Pattern
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_item_path` WHERE path_id = '" . $this->db->escape($item_id) . "' ORDER BY level ASC");

    if ($query->rows) {
      foreach ($query->rows as $item_path) {
        // Delete the path below the current one
        $this->db->query("DELETE FROM `" . DB_PREFIX . "menu_item_path` WHERE "
          . "menu_item_id = '" . $this->db->escape($item_path['menu_item_id']) . "' AND "
          . "level < '" . (int) $item_path['level'] . "' ");

        $path = array();

        // Get the nodes new parents
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_item_path` WHERE "
          . "menu_item_id = '" . $this->db->escape($data['parent_id']) . "' ORDER BY level ASC");

        foreach ($query->rows as $result) {
          $path[] = $result['path_id'];
        }

        // Get whats left of the nodes current path
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_item_path` WHERE "
          . "menu_item_id = '" . $this->db->escape($item_path['menu_item_id']) . "' ORDER BY level ASC");

        foreach ($query->rows as $result) {
          $path[] = $result['path_id'];
        }

        // Combine the paths with a new level
        $level = 0;

        foreach ($path as $path_id) {
          $this->db->query("REPLACE INTO `" . DB_PREFIX . "menu_item_path` SET "
            . "menu_item_id = '" . $this->db->escape($item_path['menu_item_id']) . "', "
            . "`path_id` = '" . $this->db->escape($path_id) . "', "
            . "level = '" . (int) $level . "'");

          $level++;
        }
      }
    } else {
      // Delete the path below the current one
      $this->db->query("DELETE FROM `" . DB_PREFIX . "menu_item_path` WHERE menu_item_id = '" . $this->db->escape($item_id) . "'");

      // Fix for records with no paths
      $level = 0;

      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_item_path` WHERE "
        . "menu_item_id = '" . $this->db->escape($data['parent_id']) . "' ORDER BY level ASC");

      foreach ($query->rows as $result) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "menu_item_path` SET "
          . "menu_item_id = '" . $this->db->escape($item_id) . "', "
          . "`path_id` = '" . $this->db->escape($result['path_id']) . "', "
          . "level = '" . (int) $level . "'");

        $level++;
      }

      $this->db->query("REPLACE INTO `" . DB_PREFIX . "menu_item_path` SET "
        . "menu_item_id = '" . $this->db->escape($item_id) . "', "
        . "`path_id` = '" . $this->db->escape($item_id) . "', "
        . "level = '" . (int) $level . "'");
    }
  }
  public function updateDelegateField($field_uid)
  { }
}

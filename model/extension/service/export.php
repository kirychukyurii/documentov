<?php

class ModelExtensionServiceExport extends Model
{

  public function getDoctype($doctype_uid)
  {
    $doctype_uid = $this->db->escape($doctype_uid);

    $result = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doctype WHERE doctype_uid='" . $doctype_uid . "'");
    $result['doctype'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doctype_description WHERE doctype_uid='" . $doctype_uid . "'");
    $result['doctype_description'] = $query->rows;

    $query = $this->db->query("SELECT * FROM `doctype_template` WHERE `doctype_uid`='$doctype_uid'");
    $result['doctype_template'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "template_form WHERE template_uid IN (SELECT `template_uid` FROM `doctype_template` WHERE `doctype_uid`='$doctype_uid') ");
    $result['template_form'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doctype_access WHERE doctype_uid = '" . $doctype_uid . "' ");
    $result['doctype_access'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field WHERE doctype_uid='" . $doctype_uid . "'");
    $result['field'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route WHERE doctype_uid='" . $doctype_uid . "'");
    $result['route'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_description WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "') ");
    $result['route_description'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_action WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "') ");
    $result['route_action'] = $query->rows;

    // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_button WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "') ");
    // $result['route_button'] = $query->rows;
    $query = $this->db->query("SELECT * FROM `button` WHERE `parent_uid` IN (SELECT `route_uid` FROM `route` WHERE `doctype_uid` = '$doctype_uid') ");
    $result['button'] = $query->rows;

    // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_button_description WHERE route_button_uid IN (SELECT route_button_uid FROM " . DB_PREFIX . "route_button WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "')) ");
    // $result['route_button_description'] = $query->rows;
    $query = $this->db->query("SELECT * FROM `button_description` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid` IN (SELECT `route_uid` FROM `route` WHERE `doctype_uid` = '$doctype_uid')) ");
    $result['button_description'] = $query->rows;

    // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_button_field WHERE route_button_uid IN (SELECT route_button_uid FROM " . DB_PREFIX . "route_button WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "')) ");
    // $result['route_button_field'] = $query->rows;
    $query = $this->db->query("SELECT * FROM `button_field` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid` IN (SELECT `route_uid` FROM `route` WHERE `doctype_uid` = '$doctype_uid')) ");
    $result['button_field'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "button_group WHERE container_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '" . $doctype_uid . "') ");
    $result['button_group'] = $query->rows;
    if ($query->num_rows) {
      $button_group_uids = array();
      foreach ($query->rows as $row) {
        $button_group_uids[] = $row['button_group_uid'];
      }
      $query_bgd = $this->db->query("SELECT * FROM " . DB_PREFIX . "button_group_description WHERE 	button_group_uid IN ('" . implode("','", array_unique($button_group_uids)) . "') ");
      $result['button_group_description'] = $query_bgd->rows;
    }


    //добавляем значения настроечных полей
    foreach ($result['field'] as $field) {
      if ($field['setting']) {
        $query_field_value = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_" . $field['type'] . " WHERE field_uid='" . $field['field_uid'] . "' AND document_uid=0");
        $result["field_value_" . $field['type']][] = $query_field_value->row;
      }
    }

    return $result;
  }

  public function getFolder($folder_uid)
  {
    $folder_uid = $this->db->escape($folder_uid);

    $result = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder WHERE folder_uid='" . $folder_uid . "'");
    $result['folder'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_description WHERE folder_uid='" . $folder_uid . "'");
    $result['folder_description'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_field WHERE folder_uid='" . $folder_uid . "'");
    $result['folder_field'] = $query->rows;

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "folder_filter WHERE folder_uid='" . $folder_uid . "'");
    $result['folder_filter'] = $query->rows;

    $query = $this->db->query("SELECT * FROM `button` WHERE `parent_uid`='" . $folder_uid . "'");
    $result['button'] = $query->rows;

    $query = $this->db->query("SELECT * FROM `button_description` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $result['button_description'] = $query->rows;

    $query = $this->db->query("SELECT * FROM `button_field` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $result['button_field'] = $query->rows;

    $query = $this->db->query("SELECT * FROM `button_route` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $result['button_route'] = $query->rows;

    if ($result['folder'][0]['type']) {
      //экспортируется нестандарный журнал
      $query_folder_ext_table = $this->db->query("SELECT DISTINCT TABLE_NAME FROM information_schema.columns WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME LIKE '" . DB_PREFIX . $result['folder'][0]['type'] . "%' ");
      if ($query_folder_ext_table->num_rows) {
        foreach ($query_folder_ext_table->rows as $table) {
          $query = $this->db->query("SELECT * FROM " . DB_PREFIX . $table['TABLE_NAME'] . " WHERE folder_uid='" . $folder_uid . "'");
          $result[$table['TABLE_NAME']] = $query->rows;
        }
      }
    }

    // проверяем наличие журнала в меню

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "menu_item WHERE action='folder' AND action_value='" . $folder_uid . "' ");
    if ($query->num_rows) {
      $result['menu_item'] = $query->rows;
      foreach ($query->rows as $row) {
        $parent_id = $row['parent_id'];
        while ($parent_id) {
          $query_m = $this->db->query("SELECT * FROM " . DB_PREFIX . "menu_item WHERE menu_item_id='" . $parent_id . "' ");
          $result['menu_item'][] = $query_m->row;
          $parent_id = $query_m->row['parent_id'] ?? "";
        }
      }
      $menu_tables = ['menu_item_description', 'menu_item_field'];
      foreach ($result['menu_item'] as $menu) {
        foreach ($menu_tables as $table) {
          if (!isset($result[$table])) {
            $result[$table] = [];
          }
          $query_d = $this->db->query("SELECT * FROM " . DB_PREFIX . $table . " WHERE menu_item_id='" . $menu['menu_item_id'] . "' ");
          if ($query_d->num_rows) {
            foreach ($query_d->rows as $row) {
              $result[$table][] = $row;
            }
          }
        }
      }
    }

    return $result;
  }

  private function updateField17($type, $params)
  {
    $aParams = json_decode($params, true);
    switch ($type) {
      case 'collectiondoc':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'currency':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'file':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'link':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'grafic':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'hidden':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'list':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'piediagram':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'table':
        if (!empty($aParams['inner_fields'])) {
          foreach ($aParams['inner_fields'] as &$inner_field) {
            $inner_field['field_form_required'] = (int) $inner_field['field_form_required'];
            $inner_field['inner_field_uid'] = (int) $inner_field['inner_field_uid'];
            $inner_field['column_title'] = $inner_field['params']['column_title'];
            unset($inner_field['params']['column_title']);
            $inner_field['params'] = json_encode($this->model_tool_update->updateField17($inner_field['field_type'], $inner_field['params']));
          }
          $aParams['inner_fields'] = array_values($aParams['inner_fields']);
        }
        break;
      case 'tabledoc':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'text':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'treedoc':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
      case 'viewdoc':
        $aParams = $this->model_tool_update->updateField17($type, $aParams);
        break;
    }
    return json_encode($aParams);
  }

  private function patchConf1700($configuration)
  {
    $version = "1599";
    if (!empty($configuration['version'])) {
      $version = str_replace(".", "", $configuration['version']);
      while (strlen($version) < 4) {
        $version .= "0";
      }
    }
    if ($version && $version >= 1700) {
      return $configuration;
    }
    $this->load->model("tool/update");
    foreach ($configuration as $idx => &$conf) {
      if (!is_array($conf)) {
        continue;
      }
      if (isset($conf['doctype'])) {
        if (isset($conf['doctype_template'])) {
          $doctype_uid = $conf['doctype'][0]['doctype_uid'];
          $doctype_info = $conf['doctype'][0];
          $doctype_info['draft'] = 0;
          foreach ($conf['doctype_template'] as &$t) {
            $t['draft'] = "0";
          }
          $doctype_templates = $this->model_tool_update->getDoctypeTemplates($doctype_info, $conf['doctype_template']);

          $patched_contents = [];
          $template_forms = [];

          $i = 0;
          foreach ($doctype_templates['templates'] as $type => $templs) {
            foreach ($templs as $sort => $templ) {
              $template_uid = $this->model_tool_update->getUid();
              if (!isset($templ['params'])) {
                // парамсов нет, это основной шаблон
                $patched_contents[] = [
                  'template_uid' => $template_uid,
                  'doctype_uid' => $doctype_uid,
                  'type' => $type,
                  'sort' => $sort,
                  'draft' => 0,
                ];
              }
              foreach ($templ as $language_id => $html) {
                if ($language_id == "conditions") {
                  continue;
                }
                if ($language_id == "params") {
                  $patched_contents[] = [
                    'template_uid' => $template_uid,
                    'doctype_uid' => $doctype_uid,
                    'template_name' => $html['template_name'],
                    'condition_field_uid' => $html['condition_field_uid'],
                    'condition_comparison' => $html['condition_comparison'],
                    'condition_value_uid' => $html['condition_value_uid'],
                    'type' => $type,
                    'sort' => $sort,
                    'draft' => 0,
                  ];
                  continue;
                }
                // ищем УШ по типу (form | view ) и sort в исходных данных
                $conditions = "";

                foreach ($configuration[$idx]['doctype_template'] as $tmpl) {
                  if ($tmpl['type'] == $type && $tmpl['sort'] == $sort) {
                    if (!$tmpl['conditions']) {
                      break;
                    }
                    $cs = json_decode($tmpl['conditions'], true);
                    if (is_array($cs)) {
                      foreach ($cs as &$c) {
                        foreach ($c as &$a) {
                          if (is_array($a)) {
                            $a = array_values($a);
                          } else {
                            $b = [];
                            foreach ($a as $a1) {
                              $b[] = $a1;
                            }
                            $a = $b;
                          }
                        }
                      }
                    }
                    $conditions = json_encode($cs);
                  }
                }

                $template_forms[] = [
                  'template_form_uid' => $this->model_tool_update->getUid(),
                  'template_uid' => $template_uid,
                  'html' => $html,
                  'conditions' => $conditions,
                  'language_id' => $language_id,
                  'draft' => 0,
                ];
              }
            }
          }
          $conf['doctype_template'] = $patched_contents;
          $conf['template_form'] = $template_forms;
        }

        $patched_contents = [];
        foreach ($conf['doctype'] as $content) {
          unset($content['params']);
          unset($content['draft_params']);
          $patched_contents[] = $content;
        }
        $conf['doctype'] = $patched_contents;
      }
      if (isset($conf['doctype_description'])) {
        $patched_contents = [];
        foreach ($conf['doctype_description'] as $cntdd) {
          unset($cntdd['full_description']);
          $patched_contents[] = $cntdd;
        }
        $conf['doctype_description'] = $patched_contents;
      }
      if (isset($conf['doctype_delegate'])) {
        foreach ($conf['doctype_delegate'] as &$cntdd2) {
          $cntdd2['subject_uids'] = $cntdd2['subject_uid'];
          unset($cntdd2['subject_uid']);
          $cntdd2['access_id'] = $cntdd2['delegate_id'];
          unset($cntdd2['delegate_id']);
        }
        $conf['doctype_access'] = $conf['doctype_delegate'];
        unset($conf['doctype_delegate']);
      }
      if (isset($conf['folder'])) {
        foreach ($conf['folder'] as &$cntfld) {
          unset($cntfld['additional_params']);
          unset($cntfld['draft_params']);
        }
      }
      if (isset($conf['route_button'])) {
        foreach ($conf['route_button'] as &$cntrb) {
          $cntrb['parent_uid'] = $cntrb['route_uid'];
          unset($cntrb['route_uid']);
          $cntrb['uid'] = $cntrb['route_button_uid'];
          unset($cntrb['route_button_uid']);
          unset($cntrb['draft_params']);
          $cntrb['draft'] = 0;
          $p = json_decode($cntrb['action_params'], true);
          $p = $this->model_tool_update->updateAction17($cntrb['action'], $p);
          if (isset($p['method_params'])) {
            foreach ($p['method_params'] as &$t) {
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
              if (empty($t['method_name'])) {
                continue;
              }
              if ($t['method_name'] == "get_json") {
                $t['method_params'] = $this->model_tool_update->updateGetJsonXML17($t['method_params']);
              }
              if ($t['method_name'] == "get_key_array") {
                $t['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($t['method_params']);
              }
            }
          }
          if ($cntrb['action'] == "record" && isset($p['target_field_method_name'])) {
            if ($p['target_field_method_name'] == "insert_textes") {
              $temp = $p['method_params'];
              $p['method_params'] = ['textes' => $temp];
            }
            if ($p['target_field_method_name'] == "add_document") {
              $p['method_params'] = $this->model_tool_update->updateAddDocCollDoc17($p['method_params']);
            }
          }
          if ($cntrb['action'] == "condition") {
            foreach ($p['condition'] as &$condition) {
              if (isset($condition['first_value_method_params']['char_count']['value'])) {
                $condition['first_value_method_params']['char_count']['value'] = (int)$condition['first_value_method_params']['char_count']['value'];
              }
              if (isset($condition['second_value_method_params']['char_count']['value'])) {
                $condition['second_value_method_params']['char_count']['value'] = (int)$condition['second_value_method_params']['char_count']['value'];
              }
            }
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($p[$block] as &$action) {
                if (isset($action['params']['method_params'])) {
                  foreach ($temp as &$t) {
                    if (isset($t['method_params']['char_count']['value'])) {
                      $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
                    }
                    if ($t['method_name'] == "get_json") {
                      $t['method_params'] = $this->model_tool_update->updateGetJsonXML17($t['method_params']);
                    }
                    if ($t['method_name'] == "get_key_array") {
                      $t['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($t['method_params']);
                    }
                  }
                }
                if ($action['action'] == "record") {
                  if (!empty($action['params']['target_field_method_name']) && $action['params']['target_field_method_name'] === "insert_textes") {
                    $temp = $action['params']['method_params'];
                    $action['params']['method_params'] = ['textes' => $temp];
                  }
                  if ($action['params']['target_field_method_name'] === "add_document") {
                    $action['params']['method_params'] = $this->model_tool_update->updateAddDocCollDoc17($action['params']['method_params']);
                  }
                }
              }
            }
          }

          $cntrb['action_params'] = json_encode($p);
        }
        $conf['button'] = $conf['route_button'];
        unset($conf['route_button']);
      }
      if (isset($conf['folder_button'])) {
        foreach ($conf['folder_button'] as &$cntfb) {
          $cntfb['parent_uid'] = $cntfb['folder_uid'];
          unset($cntfb['folder_uid']);
          $cntfb['uid'] = $cntfb['folder_button_uid'];
          unset($cntfb['folder_button_uid']);
          unset($cntfb['draft_params']);
          $cntfb['draft'] = 0;
          if ($cntfb['action_params']) {
            $this->ser2json(["action_params"], $cntfb);
          }
          $cntfb['action_params'] = json_encode($this->model_tool_update->updateAction17($cntfb['action'], json_decode($cntfb['action_params'], true)));
        }
        $conf['button'] = array_merge($conf['button'] ?? [], $conf['folder_button']);
        unset($conf['folder_button']);
      }
      if (isset($conf['route_button_description'])) {
        foreach ($conf['route_button_description'] as &$cntrbd) {
          $cntrbd['uid'] = $cntrbd['route_button_uid'];
          unset($cntrbd['route_button_uid']);
        }
        $conf['button_description'] = $conf['route_button_description'];
        unset($conf['route_button_description']);
      }
      if (isset($conf['folder_button_description'])) {
        foreach ($conf['folder_button_description'] as &$cntfbd) {
          $cntfbd['uid'] = $cntfbd['folder_button_uid'];
          unset($cntfbd['folder_button_uid']);
        }
        $conf['button_description'] = array_merge($conf['button_description'] ?? [], $conf['folder_button_description']);
        unset($conf['folder_button_description']);
      }
      if (isset($conf['route_button_field'])) {
        foreach ($conf['route_button_field'] as &$cntrbf) {
          $cntrbf['uid'] = $cntrbf['route_button_uid'];
          unset($cntrbf['route_button_uid']);
        }
        $conf['button_field'] = $conf['route_button_field'];
        unset($conf['route_button_field']);
      }
      if (isset($conf['button_group'])) {
        foreach ($conf['button_group'] as &$cntbg) {
          unset($cntbg['draft_params']);
          $cntbg['draft'] = 0;
        }
      }
      if (isset($conf['folder_button_field'])) {
        foreach ($conf['folder_button_field'] as &$cntfbf) {
          $cntfbf['uid'] = $cntfbf['folder_button_uid'];
          unset($cntfbf['folder_button_uid']);
        }
        $conf['button_field'] = array_merge($conf['button_field'] ?? [], $conf['folder_button_field']);
        unset($conf['folder_button_field']);
      }
      if (isset($conf['folder_button_route'])) {
        foreach ($conf['folder_button_route'] as &$cntfbr) {
          $cntfbr['uid'] = $cntfbr['folder_button_uid'];
          unset($cntfbr['folder_button_uid']);
        }
        $conf['button_route'] = array_merge($conf['button_route'] ?? [], $conf['folder_button_route']);
        unset($conf['folder_button_route']);
      }
      if (isset($conf['folder_field'])) {
        foreach ($conf['folder_field'] as &$cntff) {
          unset($cntff['draft_params']);
        }
      }
      if (isset($conf['folder_filter'])) {
        foreach ($conf['folder_filter'] as &$cntfflt) {
          unset($cntfflt['draft_params']);
        }
      }
      if (isset($conf['field'])) {
        // до 1.6 параметры полей сериализовались, переводим в json
        foreach ($conf['field'] as &$cntf) {
          if (!empty($cntf['params'])) {
            if ($version < 1600) {
              $data_params = @unserialize($cntf['params']);
              if ($data_params !== false && is_array($data_params)) {
                $cntf['params'] = json_encode($data_params);
              }
            }
            unset($cntf['draft_params']);
            unset($cntf['cache_out']);
            $cntf['params'] = $this->updateField17($cntf['type'], $cntf['params']);
          }
        }
      }
      if (isset($conf['route'])) {
        foreach ($conf['route'] as &$cntr) {
          if ($version < 1601 && !empty($cntr['params'])) {
            $this->ser2json(["params"], $cntr);
          }
          unset($cntr['draft_params']);
        }
      }
      if (isset($conf['route_action'])) {
        foreach ($conf['route_action'] as &$cntra) {
          // до 1.6 параметры полей сериализовались, переводим в json
          if ($version < 1601 && !empty($cntra['params'])) {
            $this->ser2json(["params"], $cntra);
          }
          unset($cntra['draft_params']);
          $p = $this->model_tool_update->updateAction17($cntra['action'], json_decode($cntra['params'], true));
          if (isset($p['method_params'])) {
            foreach ($p['method_params'] as &$t) {
              if (isset($t['method_params']) && !is_array($t['method_params'])) {
                continue;
              }
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
              if (empty($t['method_name'])) {
                continue;
              }
              if ($t['method_name'] == "get_json") {
                $t['method_params'] = $this->model_tool_update->updateGetJsonXML17($t['method_params']);
              }
              if ($t['method_name'] == "get_key_array") {
                $t['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($t['method_params']);
              }
            }
          }
          if ($cntra['action'] == "record") {
            if ($p['target_field_method_name'] == "insert_textes") {
              $temp = $p['method_params'];
              $p['method_params'] = ['textes' => $temp];
            }
            if ($p['target_field_method_name'] == "add_document") {
              $p['method_params'] = $this->model_tool_update->updateAddDocCollDoc17($p['method_params']);
            }
          }
          if ($cntra['action'] == "condition") {
            foreach ($p['condition'] as &$condition) {
              if (isset($condition['first_value_method_params']['char_count']['value'])) {
                $condition['first_value_method_params']['char_count']['value'] = (int)$condition['first_value_method_params']['char_count']['value'];
              }
              if (isset($condition['second_value_method_params']['char_count']['value'])) {
                $condition['second_value_method_params']['char_count']['value'] = (int)$condition['second_value_method_params']['char_count']['value'];
              }
            }
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($p[$block] as &$action) {
                if (isset($action['params']['method_params'])) {
                  foreach ($action['params']['method_params'] as &$t) {
                    if (isset($t['method_params']['char_count']['value'])) {
                      $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
                    }
                    if (empty($t['method_name'])) {
                      continue;
                    }
                    if ($t['method_name'] == "get_json") {
                      $t['method_params'] = $this->model_tool_update->updateGetJsonXML17($t['method_params']);
                    }
                    if ($t['method_name'] == "get_key_array") {
                      $t['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($t['method_params']);
                    }
                  }
                }

                if ($action['action'] == "record") {
                  if (!empty($action['params']['target_field_method_name']) && $action['params']['target_field_method_name'] === "insert_textes") {
                    $temp = $action['params']['method_params'];
                    $action['params']['method_params'] = ['textes' => $temp];
                  }
                  if ($action['params']['target_field_method_name'] === "add_document") {
                    $action['params']['method_params'] = $this->model_tool_update->updateAddDocCollDoc17($action['params']['method_params']);
                  }
                }
              }
            }
          }
          $cntra['params'] = json_encode($p);
        }
      }
    }
    return $configuration;
  }

  private function patchConf1800($configuration)
  {

    $version = str_replace(".", "", $configuration['version']);
    while (strlen($version) < 4) {
      $version .= "0";
    }
    if ($version >= 1800) {
      return $configuration;
    }

    foreach ($configuration as $idx => &$conf) {

      if (!is_array($conf)) {
        continue;
      }
      if (isset($conf['field'])) {
        foreach ($conf['field'] as &$field) {
          unset($field['unique']);
        }
      }
      if (isset($conf['field_value_link'])) {
        foreach ($conf['field_value_link'] as &$fv) {
          unset($fv['full_display_value']);
        }
      }
      if (isset($conf['button'])) {
        foreach ($conf['button'] as &$button) {
          if ($button['action'] != "import_f") {
            continue;
          }
          $params = json_decode($button['action_params'], true);
          if (isset($params['matching'])) {
            foreach ($params['matching'] as &$match) {
              $match['column'] = (int)$match['column'];
            }
          }
          if (isset($params['filepath_field'])) {
            $params['filepath_field']['type'] = "document";
            $params['filepath_field']['doclink_field_uid'] = "0";
            unset($params['filepath_field']['method_params']);
          }
          $button['action_params'] = json_encode($params);
        }
      }
    }
    return $configuration;
  }

  private function patchConf1808($configuration)
  {

    $version = str_replace(".", "", $configuration['version']);
    while (strlen($version) < 4) {
      $version .= "0";
    }
    if ($version >= 1808) {
      return $configuration;
    }

    foreach ($configuration as $idx => &$conf) {
      if (!is_array($conf)) {
        continue;
      }
      if (isset($conf['folder_field'])) {
        foreach ($conf['folder_field'] as &$field) {
          if (isset($field['grouping'])) {
            $field['is_grouping'] = $field['grouping'];
            unset($field['grouping']);
          }
        }
      }
    }
    return $configuration;
  }
  private function patchConf1830($configuration)
  {

    $version = str_replace(".", "", $configuration['version']);
    while (strlen($version) < 4) {
      $version .= "0";
    }
    if ($version >= 1830) {
      return $configuration;
    }

    foreach ($configuration as $idx => &$conf) {
      if (!is_array($conf)) {
        continue;
      }
      if (isset($conf['field'])) {
        foreach ($conf['field'] as &$field) {
          if ($field['type'] == "text") {
            $field['params'] = $this->convertIntTextParams($field['params']);
          }
          if ($field['type'] == "table") {
            $params = json_decode($field['params'], true);
            if (isset($params['inner_fields'])) {
              foreach ($params['inner_fields'] as &$iField) {
                if ($iField['field_type'] == 'text') {
                  $iField['params'] = $this->convertIntTextParams($iField['params']);
                }
              }
              $field['params'] = $this->jsonEncode($params);
            }
          }
        }
      }
    }
    return $configuration;
  }

  private function convertIntTextParams($fieldParams)
  {
    $params = json_decode($fieldParams, true);
    if (isset($params['image_height'])) {
      $params['image_height'] = (int)$params['image_height'];
    }
    if (isset($params['image_width'])) {
      $params['image_width'] = (int)$params['image_width'];
    }
    if (isset($params['size_file'])) {
      $params['size_file'] = (int)$params['size_file'];
    }
    return $this->jsonEncode($params);
  }

  private function clearDoctype($doctype_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "doctype_description WHERE doctype_uid='$doctype_uid'");
    $this->db->query("DELETE FROM `template_form` WHERE template_uid IN (SELECT `template_uid` FROM `doctype_template` WHERE `doctype_uid`='$doctype_uid') ");
    $this->db->query("DELETE FROM `doctype_template` WHERE `doctype_uid`='$doctype_uid' ");
    $this->db->query("DELETE FROM " . DB_PREFIX . "doctype_access WHERE doctype_uid='$doctype_uid'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "field WHERE doctype_uid='$doctype_uid'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "route_description WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '$doctype_uid') ");
    $this->db->query("DELETE FROM " . DB_PREFIX . "route_action WHERE route_uid IN (SELECT route_uid FROM " . DB_PREFIX . "route WHERE doctype_uid = '$doctype_uid') ");
    $this->db->query("DELETE FROM `button_description` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid` IN (SELECT `route_uid` FROM `route` WHERE `doctype_uid` = '$doctype_uid')) ");
    $this->db->query("DELETE FROM `button_field` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid` IN (SELECT route_uid FROM `route` WHERE `doctype_uid` = '$doctype_uid')) ");
    $this->db->query("DELETE FROM `button` WHERE `parent_uid` IN (SELECT `route_uid` FROM `route` WHERE `doctype_uid` = '$doctype_uid') ");
    $this->db->query("DELETE FROM " . DB_PREFIX . "route WHERE doctype_uid='$doctype_uid'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "doctype WHERE doctype_uid='$doctype_uid'");
  }

  private function clearFolder($folder_uid)
  {
    $this->db->query("DELETE FROM `folder` WHERE folder_uid='$folder_uid'");
    $this->db->query("DELETE FROM `folder_description` WHERE folder_uid='$folder_uid'");
    $this->db->query("DELETE FROM `folder_field` WHERE folder_uid='$folder_uid'");
    $this->db->query("DELETE FROM `folder_filter` WHERE folder_uid='$folder_uid'");
    $this->db->query("DELETE FROM `button_description` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $this->db->query("DELETE FROM `button_field` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $this->db->query("DELETE FROM `button_route` WHERE `uid` IN (SELECT `uid` FROM `button` WHERE `parent_uid`='$folder_uid') ");
    $this->db->query("DELETE FROM `button` WHERE `parent_uid`='$folder_uid'");
  }

  private function addToResult(&$result, $type, $uid)
  {
    $found = false;
    foreach ($result[$type] as $huid) {
      if ($huid == $uid) {
        $found = true;
      }
    }
    if (!$found) {
      $result[$type][] = $uid;
    }
  }

  public function addConfiguration($configuration)
  {

    $this->load->model('menu/item');
    $result = array(
      'doctype'   => array(),
      'folder'    => array(),
    );
    $exception_menu = [];
    $menu_tables = ['menu_item_description', 'menu_item_field'];

    // патчим 
    $configuration = $this->patchConf1700($configuration);
    $configuration = $this->patchConf1800($configuration);
    $configuration = $this->patchConf1808($configuration);
    $configuration = $this->patchConf1830($configuration);
    $doctype_uid = "";
    $folder_uid = "";
    $doctype_uids = [];
    $folder_uids = [];
    foreach ($configuration as $conf) {
      if (!is_array($conf)) {
        continue;
      }

      if (!empty($conf['doctype'][0]['doctype_uid'])) {
        $doctype_uid = $conf['doctype'][0]['doctype_uid'];
        $this->clearDoctype($doctype_uid);
        // если импортируется с драфтом, может быть дубль, проверяем
        $this->addToResult($result, "doctype", $doctype_uid);
      }

      if (!empty($conf['folder'][0]['folder_uid'])) {
        $folder_uid = $conf['folder'][0]['folder_uid'];
        $this->clearFolder($folder_uid);
        $this->addToResult($result, "folder", $folder_uid);
      }
      foreach ($conf as $table => $contents) {
        foreach ($contents as $content) {
          if ($table == "folder_card_template") {
            $this->db->query("DELETE FROM " . DB_PREFIX . "folder_card_template WHERE folder_uid='" . $this->db->escape($content['folder_uid']) . "'");
          }
          if (strpos($table, "field_value") !== false && $content) {
            //проверяем наличие данных настроечного поля
            if ($content['field_uid'] == "ef4950c1-a90b-11e9-a0c6-7c2a31f58480") {
              //НОМЕР ВЕРСИИ
              $this->db->query("DELETE FROM " . DB_PREFIX . $this->db->escape($table) . " "
                . "WHERE field_uid='" . $this->db->escape($content['field_uid']) . "' AND document_uid='0' ");
            } else {
              $query_fs = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->db->escape($table) . " "
                . "WHERE field_uid='" . $this->db->escape($content['field_uid']) . "' AND (document_uid='0' OR document_uid='')");
              if ($query_fs->num_rows) {
                continue;
              }
            }
          }
          if ($table == "button_group") {
            $this->db->query("DELETE FROM " . DB_PREFIX . $this->db->escape($table) . " "
              . "WHERE button_group_uid='" . $this->db->escape($content['button_group_uid']) . "' AND container_uid='" . $this->db->escape($content['container_uid']) . "'");
          }
          if ($table == "button_group_description") {
            $this->db->query("DELETE FROM " . DB_PREFIX . $this->db->escape($table) . " "
              . "WHERE button_group_uid='" . $this->db->escape($content['button_group_uid']) . "' AND language_id='" . $this->db->escape($content['language_id']) . "'");
          }
          if ($table == "menu_item") {
            // проверяем наличие такого пункта меню
            $query_menu = $this->db->query("SELECT menu_item_id FROM " . DB_PREFIX . $table . " WHERE menu_item_id = '" . $this->db->escape($content['menu_item_id']) . "' ");
            if ($query_menu->num_rows) {
              $exception_menu[] = $content['menu_item_id'];
              continue;
            }
          } else if (array_search($table, $menu_tables) !== FALSE && array_search($content['menu_item_id'], $exception_menu) !== FALSE) {
            continue;
          }

          $sql = "INSERT INTO " . DB_PREFIX . $this->db->escape($table) . " SET ";
          $sets = array();

          foreach ($content as $name => $value) {
            if ($name) {
              $sets[] = "`" . $this->db->escape($name) . "` = '" . $this->db->escape($value) . "' ";
            }
          }
          if ($sets) {
            $sql .= implode(",", $sets);
            try {
              $this->db->query($sql);
            } catch (Exception $e) {
              $this->db->query("DELETE FROM "  . DB_PREFIX . $this->db->escape($table) . " WHERE "
                . "" . implode(" AND ", $sets));
              $this->db->query($sql);
            }
          }
          if ($table == "menu_item_field") {
            $query_menu_item = $this->db->query("SELECT * FROM " . DB_PREFIX . "menu_item WHERE menu_item_id='" . $this->db->escape($content['menu_item_id']) . "' ");
            $this->model_menu_item->updateDelegate($content['menu_item_id'], $query_menu_item->row);
            $this->cache->delete('item');
          }

          if ($table == "doctype_access") {
            // $this->model_doctype_doctype->updateDoctypeAccess($content['doctype_uid']);
          }
        }
      }
      if ($doctype_uid) {
        $doctype_uids[] = $doctype_uid;
      }
      if ($folder_uid) {
        $folder_uids[] = $folder_uid;
      }
    }
    if (count($doctype_uids) > 0) {
      $doctype_uids = array_unique($doctype_uids);
      $this->daemon->exec("LoadDoctypes", ['doctype_uids' => implode(",", $doctype_uids)]);
      foreach ($doctype_uids as $doctype_uid) {
        $this->model_doctype_doctype->updateDoctypeAccess($doctype_uid);
      }
    }
    if (count($folder_uids) > 0) {
      foreach ($folder_uids as $folder_uid) {
        $this->daemon->exec("LoadFolder", ['folder_uid' => $folder_uid]);
      }
    }
    // if (!empty($result['doctype'])) {
    //   // $this->daemon->exec("LoadDoctypes", ['doctype_uids'=>[$result['doctype']]]);
    // }
    // if (!empty($result['folder'])) {
    //   // $this->daemon->exec("LoadFolders", $result['folder']);
    // }
    return $result ?? "";
    //        $this->db->query($sql);
  }

  private function ser2json($params, &$content)
  {
    foreach ($params as $param) {
      if (!empty($content[$param])) {
        $data_params = @unserialize($content[$param]);
        if ($data_params !== false && is_array($data_params)) {
          $content[$param] = json_encode($data_params);
        }
      }
    }
  }

  private function jsonEncode($v)
  {
    return json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
    // return $this->db->escape(json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
  }
}

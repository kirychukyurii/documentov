<?php

class ModelToolUpdate extends Model
{

  public function manualUpdate()
  {
    $var = $this->variable->get('manual_update');
    if ($var) {
      $methods = explode(",", $var);
      foreach ($methods as $method_name) {
        if (method_exists($this, $method_name)) {
          $this->$method_name();
        }
      }
    }
    $this->variable->set('manual_update', '');
    $this->setManualUpdate("");
  }

  public function update()
  {

    $this->load->model('setting/setting');

    $version_db = $this->config->get('version_db'); //версия БД, версия кода в VERSION. Нужно выполнить все обновления, начиная с версии БД
    if (!$version_db) {
      $version_db = '0.5.0';
    }
    $i_version_code = $this->getNumberVersion(VERSION);
    $i_version_db = $this->getNumberVersion($version_db);
    if ($version_db == VERSION) {
      return;
    }
    $this->cache->clear();
    if ($i_version_code >= 1800) {
      if ($i_version_db >= 1702) { // обновлений через php нет, обновления через демон
        if (
          $this->request->get['route'] != 'error/daemon_not_started' &&
          strpos($this->request->get['route'], "extension/service/daemon") === false
        ) {
          $this->response->redirect($this->url->link('error/daemon_not_started', true));
        }
        return;
      }
    }
    // if ($i_version_code >= 1800 && $i_version_db >= 1800) { //обновлений через php нет, но версии не совпадают
    //   return;
    // }
    // if ($i_version_code >= 1800 && $i_version_db >= 1702) { // php отработал, замыкаем на демон все запросы
    //   // обновление через демон
    //   if (
    //     $this->request->get['route'] != 'error/daemon_not_started' &&
    //     strpos($this->request->get['route'], "extension/service/daemon") === false
    //   ) {
    //     $this->response->redirect($this->url->link('error/daemon_not_started', true));
    //   }
    //   return;
    // }

    $new_version_db = VERSION;
    if ($i_version_code >= 1800) {
      $new_version_db = "1.7.9.9";
    }
    // обновления есть
    $this->daemon->runDaemon("stop");

    if (!$this->customer->isLogged() || !$this->customer->isAdmin()) {
      // незалогиненный пользователь или не админ
      // выводим сообщение об обновлении и выходим
      $this->config->set('technical_break', $this->language->get('technical_break_update'));
      $this->response->setOutput($this->load->controller('info/info/getView', array('text_info' => html_entity_decode($this->config->get('technical_break')))));
      $this->response->output();
      exit;
    }

    if ($i_version_db >= $i_version_code) {
      // версия БД больше версии кода, такого быть не должно
      $this->model_setting_setting->editSetting('dv_system', array('version_db' => $new_version_db));
      return;
    }

    while ($i_version_db < $i_version_code) {
      $this->updateTo($i_version_db);
      $i_version_db++;
    }

    $this->model_setting_setting->editSetting('dv_system', array('version_db' => $new_version_db));

    $this->cache->clear();
    $this->daemon->runDaemon("start");
  }

  public function updateTo($version)
  {
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    switch ($version) {
      case 1090: //обновляем 1.0.9 до 1.1.0
        if (!$this->isTableColumn("button_group")) {
          $this->db->query("CREATE TABLE " . DB_PREFIX . "button_group (`button_group_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL, `container_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL, `picture` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `hide_group_name` smallint(6) NOT NULL, `color` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL, `background` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL, `draft` tinyint(4) NOT NULL, `draft_params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
        if (!$this->isTableColumn("button_group_description")) {
          $this->db->query("CREATE TABLE " . DB_PREFIX . "button_group_description (`button_group_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL, `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, `language_id` int(11) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
        if (!$this->isTableColumn("route_button", "button_group_uid")) {
          $this->db->query("ALTER TABLE " . DB_PREFIX . "route_button ADD `button_group_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL AFTER `route_uid`;");
        }
        if (!$this->isTableColumn('doctype_template', 'conditions')) {
          $this->db->query("ALTER TABLE " . DB_PREFIX . "doctype_template ADD `conditions` MEDIUMTEXT NOT NULL AFTER `template`;");
        }
        return;
        if (!$this->isTableColumn("folder_field", "tcolumn_width")) {
          $this->db->query("ALTER TABLE " . DB_PREFIX . "folder_field ADD `tcolumn_width` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL AFTER `tcolumn_name`;");
        }
        return;
      case 1190:
        $this->load->model('setting/extension');

        $type_string = "string";
        if ($this->model_setting_extension->getExtensionId('field', 'string_plus')) {
          $type_string = "string_plus";
        }
        $type_text = "text";
        if ($this->model_setting_extension->getExtensionId('field', 'text_plus')) {
          $type_text = "text_plus";
        }
        $field_string_params = array(
          'mask' => ''
        );
        $field_text_params = array(
          'editor_enabled' => ''
        );

        $field_description = 'График рабочего времени, используемый по умолчанию полем Время. Формат: НОМЕР_ДНЯ_НЕДЕЛИ:РАБ_ЧАС1, РАБ_ЧАС2, РАБ_ЧАС3; Например, для шестидневной рабочей недели (с пн по пт с 9 до 17, с обедом с 13 до 14; в сб - с 9 до 14 без обеда): 1:9,10,11,12,14,15,16; 2:9,10,11,12,14,15,16; 3:9,10,11,12,14,15,16; 4:9,10,11,12,14,15,16; 5:9,10,11,12,14,15,16; 6:9,10,11,12,13';
        $this->addField('b9e08649-8132-11e9-aefb-7c2a31f58480', 'РАБОЧИЙ ГРАФИК', '51f80627-1df9-11e8-a7fb-201a06f86b88', $type_string, serialize($field_string_params), 11, 1, $field_description);

        $field_description = 'Дополнительные выходные и праздничные дни, используемые полем Время. Формат:  ГГГГ-ММ-ДД; ГГГГ-ММ-ДД Например, 2020-01-01; 2020-08-03; 2020-05-09';
        $this->addField('b9e08649-8132-11e9-aefb-7c2a31f58481', 'ПРАЗДНИЧНЫЕ ДНИ', '51f80627-1df9-11e8-a7fb-201a06f86b88', $type_text, serialize($field_text_params), 12, 1, $field_description);

        $field_description = 'Дни с нестандартным рабочим временем или рабочие выходые, используемые полем Время. Формат: ГГГГ-ММ-ДД:РАБ_ЧАС1, РАБ_ЧАС2; Например, 2020-12-31:9,10,11,12;2020-03-07:9,10,11,12,13,14';
        $this->addField('b9e08649-8132-11e9-aefb-7c2a31f58482', 'НЕСТАНДАРТНЫЕ РАБОЧИЕ ДНИ', '51f80627-1df9-11e8-a7fb-201a06f86b88', $type_text, serialize($field_text_params), 13, 1, $field_description);

        $this->model_document_document->editFieldValue('b9e08649-8132-11e9-aefb-7c2a31f58480', 0, '1:9,10,11,12,14,15,16,17; 2:9,10,11,12,14,15,16,17; 3:9,10,11,12,14,15,16,17; 4:9,10,11,12,14,15,16,17; 5:9,10,11,12,14,15,16,17');
        $this->model_document_document->editFieldValue('b9e08649-8132-11e9-aefb-7c2a31f58481', 0, '2020-01-01; 2020-03-08');
        $this->model_document_document->editFieldValue('b9e08649-8132-11e9-aefb-7c2a31f58482', 0, '2019-12-31:9,10,11,12; 2019-03-07:9,10,11,12,14,16');

        $this->addSetting('dv_field_datetime', 'worktime_field_uid', 'b9e08649-8132-11e9-aefb-7c2a31f58480');
        $this->addSetting('dv_field_datetime', 'holidays_field_uid', 'b9e08649-8132-11e9-aefb-7c2a31f58481');
        $this->addSetting('dv_field_datetime', 'irregular_worktime_field_uid', 'b9e08649-8132-11e9-aefb-7c2a31f58482');
        return;
      case 1200:
        $text_tables = ['field_value_text', 'field_value_text_plus'];
        foreach ($text_tables as $table) {
          if ($this->isTableColumn($table)) {
            $this->db->query("UPDATE " . DB_PREFIX . $table . " SET `value` = replace(`value`, '__amprsnd__', '&')");
            $this->db->query("UPDATE " . DB_PREFIX . $table . " SET `display_value` = replace(`display_value`, '__amprsnd__', '&')");
          }
        }
        return;
      case 1300:
        if (!$this->isTableColumn("token")) {
          $this->db->query("CREATE TABLE " . DB_PREFIX . "token (`token` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL, `route` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,`params` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL, `structure_uid` varchar(36) NOT NULL, `validity_date` datetime, PRIMARY KEY (`token`)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
        $this->addSetting('dv_zsystem', 'token_validity_period', '720');
        if ($this->isTableColumn('field_value_file_list')) {
          if (!$this->isTableColumn('field_value_file_list', 'version')) {
            $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_file_list ADD `version` INT(11) AFTER `file_uid`;");
            $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_file_list DROP PRIMARY KEY, ADD PRIMARY KEY(file_uid, version)");
          }
        }
        if (!$this->isTableColumn('field', 'cache_out')) {
          $this->db->query("ALTER TABLE " . DB_PREFIX . "field ADD `cache_out` TINYINT AFTER `ft_index`;");
        }

        // Строка+ -> Строка
        if ($this->isTableColumn("field_value_string_plus")) {
          if (!$this->isTableColumn("field_value_string")) {
            $this->load->controller("extension/extension/field/install", "string");
          }
          $this->db->query("REPLACE INTO field_value_string SELECT * FROM field_value_string_plus");
          $this->db->query("UPDATE setting SET value='string' WHERE value='string_plus'");
          $this->db->query("UPDATE field SET type='string' WHERE type='string_plus'");
          $this->load->controller("extension/extension/field/uninstall", "string_plus");
        }

        // Текст+ -> Текст
        if (is_dir(DIR_DOWNLOAD . "field_text_plus")) {
          rename(DIR_DOWNLOAD . "field_text_plus", DIR_DOWNLOAD . "field_text");
        }

        if (!$this->isTableColumn("field_value_text_file")) {
          $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_text_file ( `file_uid` VARCHAR(36) , `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `file_name` VARCHAR(256) NOT NULL , `size` INT NOT NULL, `token` VARCHAR(32) NOT NULL , `date_added` DATETIME NOT NULL , `user_uid` INT NOT NULL, `status` TINYINT NOT NULL, PRIMARY KEY (file_uid) )  ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
          $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text_file ADD INDEX( `field_uid`);");
          $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text_file ADD INDEX( `document_uid`);");
        }
        if ($this->isTableColumn("field_value_text_plus")) {
          if (!$this->isTableColumn("field_value_text")) {
            $this->load->controller("extension/extension/field/install", "text");
          }
          $this->db->query("REPLACE INTO field_value_text SELECT * FROM field_value_text_plus");
          $this->db->query("REPLACE INTO field_value_text_file SELECT * FROM field_value_text_plus_file");
          $this->db->query("UPDATE setting SET value='text' WHERE value='text_plus'");
          $this->db->query("UPDATE field SET type='text' WHERE type='text_plus'");
          $this->db->query("UPDATE `field_value_text` SET `value` = replace(`value`,'index.php?route=field/text_plus/file','index.php?route=field/text/file')");
          $this->load->controller("extension/extension/field/uninstall", "text_plus");
        }

        // Ссылка+ -> Ссылка
        if ($this->isTableColumn("field_value_link_plus")) {
          if (!$this->isTableColumn("field_value_link")) {
            $this->load->controller("extension/extension/field/install", "link");
          }
          $this->db->query("REPLACE INTO field_value_link SELECT * FROM field_value_link_plus");
          $this->db->query("UPDATE setting SET value='link' WHERE value='link_plus'");
          $this->db->query("UPDATE field SET type='link' WHERE type='link_plus'");
          $this->load->controller("extension/extension/field/uninstall", "link_plus");
        }

        // Условие+ -> Условие
        $this->load->model("setting/extension");
        if (!$this->model_setting_extension->getExtensionId("action", "condition")) {
          $this->load->controller("extension/extension/action/install", "condition");
        }
        $query_conditions = $this->db->query("SELECT * FROM " . DB_PREFIX . "route_action WHERE action='condition' ");
        foreach ($query_conditions->rows as $condition) {
          $this->db->query("UPDATE " . DB_PREFIX . "route_action SET 
              params = '" . $this->transformConditionPlus($condition['params']) . "',
              draft_params = '" . $this->transformConditionPlus($condition['draft_params']) . "'
              WHERE route_action_uid = '" . $condition['route_action_uid'] . "' 
            ");
        }
        $this->db->query("UPDATE " . DB_PREFIX . "route_action SET action='condition' WHERE action='condition_plus' ");
        $this->load->controller("extension/extension/action/uninstall", "condition_plus");

        // Выборка+ -> Выборка
        if (!$this->model_setting_extension->getExtensionId("action", "selection")) {
          $this->load->controller("extension/extension/action/install", "selection");
        }
        $this->db->query("UPDATE " . DB_PREFIX . "route_action SET action='selection' WHERE action='selection_plus' ");
        $this->load->controller("extension/extension/action/uninstall", "selection_plus");
        return true;
      case 1330:
        if ($this->isTableColumn("field_value_table") && !$this->isTableColumn("field_value_table", "view")) {
          $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_table ADD `view` MEDIUMTEXT NOT NULL AFTER `display_value`;");
        }

        $query = $this->db->query("SHOW KEYS FROM " . DB_PREFIX . "button_group WHERE Key_name = 'PRIMARY'");

        if (!$query->num_rows) {


          if (!$this->isTableColumn("button_group_tmp")) {
            $this->db->query("CREATE TABLE " . DB_PREFIX . "button_group_tmp (button_group_uid varchar(36), container_uid varchar(36), picture varchar(255), hide_group_name smallint(6), color varchar(6), background varchar(6), draft tinyint(4), draft_params mediumtext, primary key (button_group_uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
          }

          $this->db->query("INSERT INTO " . DB_PREFIX . "button_group_tmp (button_group_uid, container_uid, picture, hide_group_name, color, background, draft, draft_params) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group");
          if ($this->isTableColumn("button_group_tmp")) {
            $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "button_group");
            $this->db->query("ALTER TABLE " . DB_PREFIX . "button_group ADD PRIMARY KEY(button_group_uid)");
            $this->db->query("INSERT INTO " . DB_PREFIX . "button_group (button_group_uid, container_uid, picture, hide_group_name, color, background, draft, draft_params) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group_tmp");
            $query = $this->db->query("SELECT COUNT(*) cnt FROM " . DB_PREFIX . "button_group_tmp");
            $cnt_tmp = $query->row['cnt'];
            $query = $this->db->query("SELECT COUNT(*) cnt FROM " . DB_PREFIX . "button_group");
            $cnt = $query->row['cnt'];
            if ($cnt_tmp === $cnt) {
              $this->db->query("DROP TABLE " . DB_PREFIX . "button_group_tmp");
            }
          }
        }


        if (!$this->isTableColumn("button_group_description_tmp")) {
          $this->db->query("CREATE TABLE " . DB_PREFIX . "button_group_description_tmp (button_group_uid varchar(36), name varchar(255), language_id int(11)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "button_group_description_tmp (button_group_uid, name, language_id) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group_description");
        if ($this->isTableColumn("button_group_description_tmp")) {
          $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "button_group_description");
          $this->db->query("INSERT INTO " . DB_PREFIX . "button_group_description (button_group_uid, name, language_id) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group_description_tmp");
          $query = $this->db->query("SELECT COUNT(*) cnt FROM " . DB_PREFIX . "button_group_description_tmp");
          $cnt_tmp = $query->row['cnt'];
          $query = $this->db->query("SELECT COUNT(*) cnt FROM " . DB_PREFIX . "button_group_description");
          $cnt = $query->row['cnt'];
          if ($cnt_tmp === $cnt) {
            $this->db->query("DROP TABLE " . DB_PREFIX . "button_group_description_tmp");
          }
        }
        return;



      case 1340:
        $this->load->model('setting/extension');
        $extensions = $this->model_setting_extension->getInstalled('field');
        foreach ($extensions as $field) {
          $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_" . $this->db->escape($field) . " WHERE document_uid != '0' AND document_uid NOT IN (SELECT document_uid FROM document)");
        }
        return true;
      case 1370:
        if (!$this->isTableColumn('setting', 'type')) {
          $this->db->query("ALTER TABLE `setting` ADD `type` VARCHAR(16) NOT NULL AFTER `serialized`;");
        }
        $this->db->query("UPDATE setting SET `type`='password' WHERE `key`='config_mail_smtp_password' ");
        return true;
      case 1390:
        $query = $this->db->query("SHOW KEYS FROM " . DB_PREFIX . "button_group_description WHERE Key_name = 'PRIMARY'");
        if (!$query->num_rows) {
          if (!$this->isTableColumn("button_group_description_tmp")) {
            $this->db->query("CREATE TABLE " . DB_PREFIX . "button_group_description_tmp (button_group_uid varchar(36), name varchar(255), language_id int(11)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            $this->db->query("INSERT INTO " . DB_PREFIX . "button_group_description_tmp (button_group_uid, name, language_id) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group_description");
            $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "button_group_description");
            $this->db->query("ALTER TABLE " . DB_PREFIX . "button_group_description ADD PRIMARY KEY(button_group_uid,language_id)");
            $this->db->query("INSERT INTO " . DB_PREFIX . "button_group_description (button_group_uid, name, language_id) SELECT DISTINCT * FROM " . DB_PREFIX . "button_group_description_tmp");
            $this->db->query("DROP TABLE " . DB_PREFIX . "button_group_description_tmp");
          }
        }

        $items = $this->getMenuUids();

        $menu_tables = ['menu_item', 'menu_item_delegate', 'menu_item_description', 'menu_item_field', 'menu_item_path'];
        foreach ($menu_tables as $table) {
          if ($this->getTableColumn($table, 'menu_item_id')['DATA_TYPE'] != "varchar") {
            $this->db->query("ALTER TABLE " . DB_PREFIX . $table . " CHANGE `menu_item_id` `menu_item_id` VARCHAR(36) NOT NULL;");
            foreach (['parent_id', 'path_id'] as $column) {
              if ($this->isTableColumn($table, $column)) {
                $this->db->query("ALTER TABLE " . DB_PREFIX . $table . " CHANGE " . $column . " " . $column . " VARCHAR(36) NOT NULL;");
              }
            }
            $qvalues = $this->db->query("SELECT * FROM " . DB_PREFIX . $table);
            foreach ($qvalues->rows as $row) {
              $where = "";
              if (!isset($items[$row['menu_item_id']])) {
                $items[$row['menu_item_id']] = $this->getUid();
              }
              $sql = "UPDATE " . DB_PREFIX . $table . " SET menu_item_id = '" . $items[$row['menu_item_id']] . "'";
              foreach (['parent_id', 'path_id'] as $column) {
                if (isset($row[$column])) {
                  if (!isset($items[$row[$column]])) {
                    $items[$row[$column]] = $this->getUid();
                  }
                  $sql .= ", " . $column . "='" . $items[$row[$column]] . "'";
                  if ($column == "path_id") {
                    $where = " AND path_id='" . $row[$column] . "'";
                  }
                }
              }
              $sql .= " WHERE menu_item_id = '" . $row['menu_item_id'] . "' " . $where;
              // echo PHP_EOL . $sql . PHP_EOL;
              $this->db->query($sql);
            }
          }
        }
        return true;

      case 1420:
        $query_route = $this->db->query("SELECT * FROM `" . DB_PREFIX . "route` WHERE `route_uid` = '5fa8b997-1df9-11e8-a7fb-201a06f86b88' AND `doctype_uid` = '51f803b2-1df9-11e8-a7fb-201a06f86b88'");
        if ($query_route->num_rows) {
          $query_docs = $this->db->query("SELECT *  FROM `" . DB_PREFIX . "document` WHERE `doctype_uid` = '51f803b2-1df9-11e8-a7fb-201a06f86b88' AND `route_uid` != '5fa8b997-1df9-11e8-a7fb-201a06f86b88'");
          if ($query_docs->num_rows) {
            foreach ($query_docs->rows as $row) {
              $this->cache->delete("", $row['document_uid']);
              $data_route = array(
                'document_uid' => $row['document_uid'],
                'context'      => 'jump'
              );
              $this->load->controller('document/document/route_cli', $data_route);
            }
          }
        }
        return true;
      case 1510:
        $this->db->query("ALTER TABLE " . DB_PREFIX . "session ADD INDEX( `expire`) ");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_file_list ADD INDEX( `status`) ");
        return;
      case 1570:
        $var_dir = DIR_STORAGE . "/var";
        if (!file_exists($var_dir)) {
          $old = umask();
          mkdir($var_dir, 0770);
          chmod($var_dir, 0770);
          umask($old);
        }
        return;
      case 1580:
        $this->model_tool_update->addSetting("dv_system", "user_doctype_uid", "51f800b5-1df9-11e8-a7fb-201a06f86b88");
        $this->model_tool_update->addSetting("dv_system", "position_doctype_uid", "7ebae35c-b712-11e8-8204-485ab6e1c06f");
        $this->model_tool_update->addSetting("dv_system", "employee_doctype_uid", "1e66f868-d605-11e9-a710-525400d1dee3");
        $this->model_tool_update->addSetting("dv_system", "employee_field_firstname_uid", "3188109e-d605-11e9-a710-525400d1dee3");
        $this->model_tool_update->addSetting("dv_system", "employee_field_surename_uid", "3b1776ed-d605-11e9-a710-525400d1dee3");
        $this->model_tool_update->addSetting("dv_system", "employee_field_secondname_uid", "4145230e-d605-11e9-a710-525400d1dee3");
        $this->model_tool_update->addSetting("dv_system", "structure_field_employee_uid", "7c6de689-d60b-11e9-a710-525400d1dee3");
        $this->model_tool_update->addSetting("dv_system", "structure_field_typeunit_uid", "baf5606f-c55e-11e8-b18e-485ab6e1c06f");
        $this->model_tool_update->addSetting("dv_system", "structure_field_position_uid", "f2502873-f92b-11e8-a2b8-9a384dd95a35");
        $this->model_tool_update->addSetting("dv_system", "structure_field_parent_type", "link");
        $this->model_tool_update->addSetting("dv_system", "position_field_name_uid", "96473124-b712-11e8-8204-485ab6e1c06f");
        $this->model_tool_update->addSetting("dv_system", "position_field_name_type", "string");
        return true;
      case 1590:
        $this->model_tool_update->addSetting("dv_zsystem", "kerberos_auth_enabled", "0");
        if (strtoupper(substr(php_uname("s"), 0, 3)) != 'WIN') {
          $command = "stop";
          $server_name = $_SERVER['HTTP_HOST'];
          $name = htmlentities(explode(".", $server_name)[0], ENT_QUOTES);
          $now = new DateTime('now');
          $logfile = DIR_STORAGE . "logs/daemontask.log";
          $fp = fopen($logfile, 'a');
          fwrite($fp, "\n" . $now->format('Y-m-d H:i:s') . "\tDAEMON: " . $command . " command\r\n");
          fclose($fp);
          exec('nohup php ' . DIR_APPLICATION . 'system/daemon.php ' . $command . ' ' . $name . ' >> ' . DIR_STORAGE . 'logs/daemontask.log &');
          $file_pid = "logs/daemon.lock";
          if (file_exists($file_pid)) {
            @unlink($file_pid);
          }
        }
        $this->db->query("DELETE FROM setting WHERE `key`='daemon_timeout' OR `key`='daemon_pull_size'");

        $json = [];
        $query = $this->db->query("SELECT field_uid, params, draft_params FROM field");
        foreach ($query->rows as $row) {
          $json['params'] = "";
          $json['draft_params'] = "";
          foreach ($json as $name => &$value) {
            if (!empty($row[$name])) {
              $data = @unserialize($row[$name]);
              if ($data !== false) {
                $value = json_encode($data);
              } else {
                if (is_array(json_decode($row[$name], true))) {
                  break; // уже json
                }
              }
            }
          }
          if ($json['params'] || $json['draft_params']) {
            $this->db->query("UPDATE field SET params = '" . $this->db->escape($json['params']) . "', draft_params = '" . $this->db->escape($json['draft_params']) . "' WHERE field_uid= '" . $row['field_uid'] . "' ");
          }
        }
        return;
      case 1600:
        $this->db->query("ALTER TABLE `field` CHANGE `ft_index` `ft_index` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `unique` `unique` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `required` `required` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `sort` `sort` INT NOT NULL DEFAULT '0'");
        $this->db->query("UPDATE `field` SET `draft`=0 WHERE `draft`='NULL' ");
        $this->db->query("ALTER TABLE `field` CHANGE `draft` `draft` INT NULL DEFAULT '1'");
        $this->db->query("ALTER TABLE `field` CHANGE `setting` `setting` INT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `change_field` `change_field` INT NULL DEFAULT '0'");

        $json = [];
        $query = $this->db->query("SELECT route_action_uid, params, draft_params FROM route_action");
        foreach ($query->rows as $row) {
          if (!$row['params']) {
            continue;
          }
          $json = ['params' => "", 'draft_params' => ""];
          foreach ($json as $name => &$value) {
            $data = @unserialize($row[$name]);
            if ($data !== false) {
              $value = $this->jsonEncode($data);
            } else {
              if (is_array(json_decode($row[$name], true))) {
                break; // уже json, преобразование было выполнено
              }
            }
          }
          if ($json['params'] || $json['draft_params']) {
            $this->db->query("UPDATE route_action SET params = '" . $json['params'] . "', draft_params = '" . $json['draft_params'] . "' WHERE route_action_uid = '" . $row['route_action_uid'] . "' ");
          }
        }

        $query = $this->db->query("SELECT route_uid, draft_params FROM `route`");
        $value = "";
        foreach ($query->rows as $row) {
          if (!$row['draft_params']) {
            continue;
          }
          $data = @unserialize($row['draft_params']);
          if ($data !== false) {
            $value = $this->jsonEncode($data);
          } else {
            if (is_array(json_decode($row['draft_params'], true))) {
              continue; // уже json, преобразование было выполнено
            }
          }
          if ($value) {
            $this->db->query("UPDATE `route` SET draft_params = '$value' WHERE route_uid = '" . $row['route_uid'] . "' ");
          }
        }
        return;
      case 1604:
        $this->db->query("ALTER TABLE `field` CHANGE `ft_index` `ft_index` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `unique` `unique` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `required` `required` TINYINT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `sort` `sort` INT NOT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `draft` `draft` INT NULL DEFAULT '1'");
        $this->db->query("ALTER TABLE `field` CHANGE `setting` `setting` INT NULL DEFAULT '0'");
        $this->db->query("ALTER TABLE `field` CHANGE `change_field` `change_field` INT NULL DEFAULT '0'");
        if (!$this->isTableColumn("route_action", "draft_params") && !$this->isTableColumn("field", "draft_params")) {
          return; // драфт-парамсов нет, уже пропатчили
        }

        // десериализация параметров маршрута
        $query = $this->db->query("SELECT route_uid, draft_params FROM `route`");
        $value = "";
        foreach ($query->rows as $row) {
          if (!$row['draft_params']) {
            continue;
          }
          $data = @unserialize($row['draft_params']);
          if ($data !== false) {
            $value = $this->jsonEncode($data);
          } else {
            if (is_array(json_decode($row['draft_params'], true))) {
              continue; // уже json, преобразование было выполнено
            }
          }
          if ($value) {
            $this->db->query("UPDATE `route` SET draft_params = '$value' WHERE route_uid = '" . $row['route_uid'] . "' ");
          }
        }

        // патчим действия маршрута
        $this->db->query("ALTER TABLE `route_action` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_action_uid`, `draft`) USING BTREE");

        $json = [];
        $query = $this->db->query("SELECT route_action_uid, params, draft_params FROM route_action");
        foreach ($query->rows as $row) {
          if (!$row['params']) {
            continue;
          }
          $json = ['params' => "", 'draft_params' => ""];
          foreach ($json as $name => &$value) {
            $data = @unserialize($row[$name]);
            if ($data !== false) {
              $value = $this->jsonEncode($data);
            } else {
              if (is_array(json_decode($row[$name], true))) {
                break; // уже json, преобразование было выполнено
              }
            }
          }
          if ($json['params'] || $json['draft_params']) {
            $this->db->query("UPDATE route_action SET params = '" . $json['params'] . "', draft_params = '" . $json['draft_params'] . "' WHERE route_action_uid = '" . $row['route_action_uid'] . "' ");
          }
        }

        $query = $this->db->query("SELECT * FROM `route_action`");
        foreach ($query->rows as $row) {
          $params = @unserialize($row['params']);
          if ($params === false) {
            continue;
          }
          // $db_params = $row['params'];
          // $db_draft_params = $row['draft_params'];
          $action_uid = $row['route_action_uid'];
          // $params = json_decode($db_params, true);
          if (isset($params['params']['sort'])) {
            unset($params['params']);
          }
          if (isset($params['sort'])) {
            unset($params['sort ']);
          }
          $db_params = $this->jsonEncode($params);
          $this->db->query("UPDATE `route_action` SET `params` = '$db_params', draft=0 WHERE route_action_uid='$action_uid' ");

          if (!$row['draft']) {
            continue;
          }
          $draft_params = @unserialize($row['draft_params']);
          if ($draft_params === false) {
            continue;
          }
          // $draft_params = json_decode($db_draft_params, true);
          $db_draft_action_log = $row['action_log'];
          if (isset($draft_params['action_log'])) {
            $db_draft_action_log = $draft_params['action_log'];
            unset($draft_params['action_log']);
          }
          $db_draft_description = $row['description'];
          if (isset($draft_params['action_description'])) {
            $db_draft_description = $draft_params['action_description'];
            unset($draft_params['action_description']);
          }
          $db_draft_sort = $row['sort'];
          if (isset($draft_params['params']['sort'])) {
            $db_draft_sort = $draft_params['params']['sort'];
            unset($draft_params['params']['sort']);
          }
          unset($draft_params['sort']);
          $db_draft_params = $this->jsonEncode($draft_params);
          $this->db->query("INSERT INTO `route_action` SET 
          `route_action_uid` = '" . $row['route_action_uid'] . "',
          `route_uid` = '" . $row['route_uid'] . "',
          `context` = '" . $row['context'] . "',
          `action` = '" . $row['action'] . "',
          `draft` = '" . $row['draft'] . "',
          `status` = '" . $row['status'] . "',
          `params` = '" . $db_draft_params . "', 
          `action_log`='$db_draft_action_log', 
          `sort`='$db_draft_sort', 
          `description`='" . $this->db->escape($db_draft_description) . "' ");
        }
        if ($this->isTableColumn("route_action", "draft_params")) {
          $this->db->query("ALTER TABLE `route_action` DROP `draft_params`");
        }

        // патчим поля
        $query = $this->db->query("ALTER TABLE `field` DROP PRIMARY KEY, ADD PRIMARY KEY (`field_uid`, `draft`) USING BTREE");
        $query = $this->db->query("SELECT * FROM `field`");
        // устанавливаем на все существующие поля draft=0
        $this->db->query("UPDATE `field` SET `draft`=0 ");
        foreach ($query->rows as $row) {
          if (!$row['draft']) {
            continue;
          }
          $draft_params = json_decode($row['draft_params'], true);
          $this->db->query(
            "INSERT INTO `field` SET 
              `field_uid`='" . $row['field_uid'] . "', 
              `name`='" . $row['name'] . "', 
              `doctype_uid`='" . $row['doctype_uid'] . "', 
              `type`='" . $row['type'] . "', 
              `setting`='" . $row['setting'] . "', 
              `change_field`='" . ($draft_params['change_field'] ?? $row['change_field']) . "', 
              `access_form`='" . ($draft_params['access_form'] ?? $row['access_form']) . "', 
              `access_view`='" . ($draft_params['access_view'] ?? $row['access_view']) . "', 
              `required`='" . ($draft_params['required'] ?? $row['required']) . "', 
              `unique`='" . ($draft_params['unique'] ?? $row['unique']) . "', 
              `ft_index`='" . ($draft_params['ft_index'] ?? $row['ft_index']) . "', 
              `history`='" . ($draft_params['history'] ?? $row['history']) . "', 
              `params`='" . $row['draft_params'] . "', 
              `sort`='" . ($draft_params['sort'] ?? $row['sort']) . "', 
              `draft`='" . $row['draft'] . "', 
              `description`='" . ($draft_params['description'] ?? $row['description']) . "' "
          );
        }
        if ($this->isTableColumn("field", "draft_params")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_params`");
        }
        if ($this->isTableColumn("field", "cache_out")) {
          $this->db->query("ALTER TABLE `field` DROP `cache_out`");
        }

        $this->db->query("ALTER TABLE `doctype` CHANGE `delegate_create` `delegate_create` TINYINT(4) NULL DEFAULT '0'");

        // десериализация доктайпов
        $json = [];
        $query = $this->db->query("SELECT `doctype_uid`, `params`, `draft_params` FROM `doctype`");
        foreach ($query->rows as $row) {
          $json = ['params' => "", 'draft_params' => ""];
          foreach ($json as $name => &$value) {
            $data = @unserialize($row[$name]);
            if ($data !== false) {
              $value = $this->jsonEncode($data);
            } else {
              if (is_array(json_decode($row[$name], true))) {
                break; // уже json, преобразование было выполнено
              }
            }
            if ($json['params'] || $json['draft_params']) {
              $this->db->query("UPDATE `doctype` SET params = '" . $json['params'] . "', draft_params = '" . $json['draft_params'] . "' WHERE doctype_uid = '" . $row['doctype_uid'] . "' ");
            }
          }
        }

        // draft для полей делегирования и описания кнопок
        if (!$this->isTableColumn("route_button_description", "draft")) {
          $this->db->query("ALTER TABLE `route_button_description` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `language_id`");
          $this->db->query("ALTER TABLE `route_button_description` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_button_uid`, `language_id`, `draft`) USING BTREE;");
        }
        if (!$this->isTableColumn("route_button_field", "draft")) {
          $this->db->query("ALTER TABLE `route_button_field` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `field_uid`");
          $this->db->query("ALTER TABLE `route_button_field` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_button_uid`, `field_uid`, `draft`) USING BTREE;");
        }

        // обновление кнопок
        if ($this->isTableColumn("route_button", "draft_params")) {
          $this->db->query("ALTER TABLE `route_button` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_button_uid`, `draft`) USING BTREE");
          $json = [];
          $query = $this->db->query("SELECT * FROM `route_button`");
          foreach ($query->rows as $row) {
            $action_params = "";
            $data = @unserialize($row['action_params']);
            if ($data !== false) {
              $action_params = $this->jsonEncode($data);
            } else {
              if (is_array(json_decode($action_params, true))) {
                continue; // уже json, преобразование было выполнено
              }
            }
            $this->db->query("UPDATE `route_button` SET action_params = '" . $action_params . "', `draft`=0 WHERE route_button_uid = '" . $row['route_button_uid'] . "' ");
            if (!$row['draft']) {
              continue;
            }
            $draft_action_params = "";
            $data = @unserialize($row['draft_params']);
            $data2 = $data['action_params'];
            if (!is_array($data2)) {
              $data2 = @unserialize($data2);
            }
            $draft_action_params = $this->jsonEncode($data2);
            // создаем новые драфтовые кнопки
            $this->db->query("INSERT INTO `route_button` SET 
              `route_button_uid`='" . $row['route_button_uid'] . "',
              `route_uid`='" . $row['route_uid'] . "',
              `draft`='" . $row['draft'] . "',
              `button_group_uid`='" . ($data['button_group_uid'] ?? $row['button_group_uid']) . "',
              `picture`='" . ($data['picture'] ?? $row['picture']) . "',
              `hide_button_name`='" . ($data['hide_button_name'] ?? $row['hide_button_name']) . "',
              `color`='" . ($data['color'] ?? $row['color']) . "',
              `background`='" . ($data['background'] ?? $row['background']) . "',
              `action`='" . ($data['action'] ?? $row['action']) . "',
              `action_log`='" . ($data['action_log'] ?? $row['action_log']) . "',
              `action_move_route_uid`='" . ($data['action_move_route_uid'] ?? $row['action_move_route_uid']) . "',
              `action_params`='" . $draft_action_params . "',
              `description`='" . ($data['action_description'] ?? $row['description']) . "',
              `sort`='" . ($data['sort'] ?? $row['sort']) . "'
            ");
            if (!empty($data['description'])) {
              // локализованное описание
              foreach ($data['description'] as $lang_id => $desc) {
                $this->db->query("REPLACE INTO `route_button_description` SET `route_button_uid`='" . $row['route_button_uid'] . "', `name`='" . $desc['name'] . "', `description`='" . $desc['description'] . "', `language_id`='" . $lang_id . "', `draft`='1' ");
              }
            }
            if (!empty($data['field'])) {
              // делегированные поля
              foreach ($data['field'] as $field_uid) {
                $this->db->query("REPLACE INTO `route_button_field` SET `route_button_uid`='" . $row['route_button_uid'] . "', `field_uid`='" . $field_uid . "', `draft`='1' ");
              }
            }
          }
          $this->db->query("ALTER TABLE `route_button` DROP `draft_params`");
        }

        // ОБНОВЛЕНИЕ ВНУТРЕННИХ БАЗ; в релизе можно убрать
        if (
          !$this->isTableColumn("route_action", "draft_action_log") && !$this->isTableColumn("field", "draft_access_form")
        ) {
          return;
        }
        if ($this->isTableColumn("route_action", "draft_action_log")) {
          $this->db->query("ALTER TABLE `route_action` DROP `draft_action_log`");
        }
        if ($this->isTableColumn("route_action", "draft_sort")) {
          $this->db->query("ALTER TABLE `route_action` DROP `draft_sort`");
        }
        if ($this->isTableColumn("route_action", "draft_description")) {
          $this->db->query("ALTER TABLE `route_action` DROP `draft_description`");
        }
        if ($this->isTableColumn("field", "draft_name")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_name`");
        }
        if ($this->isTableColumn("field", "draft_change_field")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_change_field`");
        }
        if ($this->isTableColumn("field", "draft_access_form")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_access_form`");
        }
        if ($this->isTableColumn("field", "draft_access_view")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_access_view`");
        }
        if ($this->isTableColumn("field", "draft_required")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_required`");
        }
        if ($this->isTableColumn("field", "draft_unique")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_unique`");
        }
        if ($this->isTableColumn("field", "draft_ft_index")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_ft_index`");
        }
        if ($this->isTableColumn("field", "draft_history")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_history`");
        }
        if ($this->isTableColumn("field", "draft_sort")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_sort`");
        }
        if ($this->isTableColumn("field", "draft_description")) {
          $this->db->query("ALTER TABLE `field` DROP `draft_description`");
        }
        if ($this->isTableColumn("route_button", "draft_description")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_description`");
        }
        if ($this->isTableColumn("route_button", "draft_picture")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_picture`");
        }
        if ($this->isTableColumn("route_button", "draft_action_params")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_action_params`");
        }
        if ($this->isTableColumn("route_button", "draft_hide_button_name")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_hide_button_name`");
        }
        if ($this->isTableColumn("route_button", "draft_color")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_color`");
        }
        if ($this->isTableColumn("route_button", "draft_background")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_background`");
        }
        if ($this->isTableColumn("route_button", "draft_action_log")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_action_log`");
        }
        if ($this->isTableColumn("route_button", "draft_action_move_route_uid")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_action_move_route_uid`");
        }
        if ($this->isTableColumn("route_button", "draft_show_after_execute")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_show_after_execute`");
        }
        if ($this->isTableColumn("route_button", "draft_action")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_action`");
        }
        if ($this->isTableColumn("route_button", "draft_sort")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_sort`");
        }
        if ($this->isTableColumn("route_button", "draft_button_group_uid")) {
          $this->db->query("ALTER TABLE `route_button` DROP `draft_button_group_uid`");
        }

        return;
      case 1639:
        $this->db->query("DELETE FROM `field_value_link` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_datetime` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_file` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_hidden` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_int` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_list` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_string` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM  `field_value_table` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM `field_value_text` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        if ($this->isTableColumn("field_value_treedoc")) {
          $this->db->query("DELETE FROM  `field_value_treedoc` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        }
        $this->db->query("DELETE FROM  `field_value_viewdoc` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM `document_access` WHERE `document_uid`!='0' AND `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM `field_change_subscription` WHERE `subscription_document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        $this->db->query("DELETE FROM `field_change_subscription` WHERE `document_uid` NOT IN (SELECT `document_uid` FROM `document`)");
        return;
      case 1660:
        $this->db->query("DELETE FROM `document` WHERE `draft`=3 ");
        return;
      case 1699:
        $this->db->query("ALTER DATABASE " . DB_DATABASE . " CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");

        // обновление доктайпа
        if (!$this->isTableColumn("doctype_description", "draft")) {
          $this->updateDoctype17();
        }

        // обновление шаблона
        if (!$this->isTableColumn("doctype_template", "draft")) {
          $this->updateTemplate17();
        }

        if (!$this->isTableColumn("route_description", "draft")) {
          $this->updateRoute17();
        }

        // группы кнопок
        if ($this->isTableColumn("button_group", "draft_params")) {
          $this->updateButtonGroup17();
        }

        // доступ доктайпа
        if (!$this->isTableColumn("matrix_doctype_access")) {
          $this->updateDoctypeAccess17();
        }

        // фильтры
        if ($this->isTableColumn("folder", "additional_params")) {
          $this->updateFolderFilter17();
        }

        // журналы
        if (!$this->isTableColumn("folder", "show_toolbar")) {
          $this->updateFolder17();
        }

        // переносим кнопки журнала
        if ($this->isTableColumn("folder_button")) {
          $this->updateButton17();
        }

        break;
      case "1700":
        // метод insert_textes текстового поля
        $query = $this->db->query("SELECT `route_action_uid`, `draft`, `action`, `params`  FROM `route_action` WHERE `params` LIKE '%insert_textes%' OR `params` LIKE '%char_count%' ");
        foreach ($query->rows as $row) {
          $params = json_decode($row['params'], true);
          $route_action_uid = $row['route_action_uid'];
          $draft = $row['draft'];
          if (isset($params['method_params'])) {
            foreach ($params['method_params'] as &$t) {
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
            }
          }
          if ($row['action'] == "record") {
            if (!empty($params['method_params'])) {
              $temp = $params['method_params'];
              if ($params['target_field_method_name'] == "insert_textes") {
                if (!isset($params['method_params']['textes'])) {
                  $params['method_params'] = ['textes' => $temp];
                }
              }
            }
          }
          if ($row['action'] == "condition") {
            foreach ($params['condition'] as &$condition) {
              if (isset($condition['first_value_method_params']['char_count']['value'])) {
                $condition['first_value_method_params']['char_count']['value'] = (int)$condition['first_value_method_params']['char_count']['value'];
              }
              if (isset($condition['second_value_method_params']['char_count']['value'])) {
                $condition['second_value_method_params']['char_count']['value'] = (int)$condition['second_value_method_params']['char_count']['value'];
              }
            }
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($params[$block] as &$action) {
                if (isset($action['params']['method_params'])) {
                  foreach ($action['params']['method_params'] as &$t) {
                    if (isset($t['method_params']['char_count']['value'])) {
                      $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
                    }
                  }
                }
                if ($action['action'] == "record" && isset($action['params']['target_field_method_name']) && $action['params']['target_field_method_name'] === "insert_textes") {
                  if (!isset($action['params']['method_params']['textes'])) {
                    $temp = $action['params']['method_params'];
                    $action['params']['method_params'] = ['textes' => $temp];
                  }
                }
              }
            }
          }
          $db_params = $this->jsonEncode($params);

          $this->db->query("UPDATE `route_action` SET `params`='$db_params' WHERE `route_action_uid`='$route_action_uid' AND `draft`='$draft' ");
        }
        $query = $this->db->query("SELECT `uid`, `draft`, `action`, `action_params`  FROM `button` WHERE `action_params` LIKE '%insert_textes%' OR `action_params` LIKE '%char_count%'");
        foreach ($query->rows as $row) {
          $params = json_decode($row['action_params'], true);
          $route_action_uid = $row['uid'];
          $draft = $row['draft'];

          if (isset($params['method_params'])) {
            foreach ($params['method_params'] as &$t) {
              if (isset($t['method_params']['char_count']['value'])) {
                $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
              }
            }
          }

          if ($row['action'] == "record" && $params['target_field_method_name'] == "insert_textes") {
            if (!isset($params['method_params']['textes'])) {
              $temp = $params['method_params'];
              $params['method_params'] = ['textes' => $temp];
            }
          }
          if ($row['action'] == "condition") {
            foreach ($params['condition'] as &$condition) {
              if (isset($condition['first_value_method_params']['char_count']['value'])) {
                $condition['first_value_method_params']['char_count']['value'] = (int)$condition['first_value_method_params']['char_count']['value'];
              }
              if (isset($condition['second_value_method_params']['char_count']['value'])) {
                $condition['second_value_method_params']['char_count']['value'] = (int)$condition['second_value_method_params']['char_count']['value'];
              }
            }
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($params[$block] as &$action) {
                if (isset($action['params']['method_params'])) {
                  foreach ($action['params']['method_params'] as &$t) {
                    if (isset($t['method_params']['char_count']['value'])) {
                      $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
                    }
                    if (isset($t['method_params']['char_count']['value'])) {
                      $t['method_params']['char_count']['value'] = (int)$t['method_params']['char_count']['value'];
                    }
                  }
                }
                if ($action['action'] == "record" && isset($action['params']['target_field_method_name']) && $action['params']['target_field_method_name'] === "insert_textes") {
                  if (!isset($action['params']['method_params']['textes'])) {
                    $temp = $action['params']['method_params'];
                    $action['params']['method_params'] = ['textes' => $temp];
                  }
                }
              }
            }
          }
          $db_params = $this->jsonEncode($params);

          $this->db->query("UPDATE `button` SET `action_params`='$db_params' WHERE `uid`='$route_action_uid' AND `draft`='$draft' ");
        }
        // метод add_document коллекции
        $query = $this->db->query("SELECT `route_action_uid`, `draft`, `action`, `params`  FROM `route_action` WHERE `params` LIKE '%add_document%'");
        foreach ($query->rows as $row) {
          $params = json_decode($row['params'], true);
          $route_action_uid = $row['route_action_uid'];
          $draft = $row['draft'];
          if ($row['action'] == "record" && $params['target_field_method_name'] == "add_document") {
            if (isset($params['method_params']['fields'])) {
              continue; // уже пропатчено
            }
            $params['method_params'] = $this->updateAddDocCollDoc17($params['method_params']);
          }
          if ($row['action'] == "condition") {
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($params[$block] as &$action) {
                if (
                  $action['action'] == "record" && isset($action['params']['target_field_method_name']) &&
                  $action['params']['target_field_method_name'] === "add_document"
                ) {
                  if (isset($action['params']['method_params']['fields'])) {
                    continue; // уже пропатчено
                  }
                  $action['params']['method_params'] = $this->updateAddDocCollDoc17($action['params']['method_params']);
                }
              }
            }
          }
          $db_params = $this->jsonEncode($params);

          $this->db->query("UPDATE `route_action` SET `params`='$db_params' WHERE `route_action_uid`='$route_action_uid' AND `draft`='$draft' ");
        }
        $query = $this->db->query("SELECT `uid`, `draft`, `action`, `action_params`  FROM `button` WHERE `action_params` LIKE '%add_document%'");
        foreach ($query->rows as $row) {
          $params = json_decode($row['action_params'], true);
          $route_action_uid = $row['uid'];
          $draft = $row['draft'];
          if ($row['action'] == "record" && $params['target_field_method_name'] == "add_document") {
            if (isset($params['method_params']['fields'])) {
              continue; // уже пропатчено
            }
            $params['method_params'] = $this->updateAddDocCollDoc17($params['method_params']);
          }
          if ($row['action'] == "condition") {
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($params[$block] as &$action) {
                if (
                  $action['action'] == "record" && isset($action['params']['target_field_method_name']) &&
                  $action['params']['target_field_method_name'] === "add_document"
                ) {
                  if (isset($action['params']['method_params']['fields'])) {
                    continue; // уже пропатчено
                  }
                  $action['params']['method_params'] = $this->updateAddDocCollDoc17($action['params']['method_params']);
                }
              }
            }
          }
          $db_params = $this->jsonEncode($params);

          $this->db->query("UPDATE `button` SET `action_params`='$db_params' WHERE `uid`='$route_action_uid' AND `draft`='$draft' ");
        }
        // методы get_key_array, get_json
        $query = $this->db->query("SELECT `route_action_uid`, `draft`, `action`, `params`  FROM `route_action` WHERE `params` LIKE '%get_key_array%' OR `params` LIKE '%get_json%'");
        foreach ($query->rows as $row) {
          $params = json_decode($row['params'], true);
          $route_action_uid = $row['route_action_uid'];
          $draft = $row['draft'];
          if ($row['action'] == "record") {
            foreach ($params['method_params'] as &$mp) {
              if (isset($mp['method_params']['elements'])) {
                continue; // уже пропатчено
              }
              if ($mp['method_name'] == "get_json") {
                $mp['method_params'] = $this->model_tool_update->updateGetJsonXML17($mp['method_params']);
              }
              if ($mp['method_name'] == "get_key_array") {
                $mp['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($mp['method_params']);
              }
            }
          }
          if ($row['action'] == "condition") {
            foreach (["inner_actions_true", "inner_actions_false"] as $block) {
              foreach ($params[$block] as &$action) {
                if ($action['action'] == "record") {
                  foreach ($params['method_params'] as &$mp) {
                    if (isset($mp['method_params']['elements'])) {
                      continue; // уже пропатчено
                    }
                    if ($mp['method_name'] == "get_json") {
                      $mp['method_params'] = $this->model_tool_update->updateGetJsonXML17($mp['method_params']);
                    }
                    if ($mp['method_name'] == "get_key_array") {
                      $mp['method_params'] = $this->model_tool_update->updateGetKeyArrayJson17($mp['method_params']);
                    }
                  }
                }
              }
            }
          }
          $db_params = $this->jsonEncode($params);

          $this->db->query("UPDATE `route_action` SET `params`='$db_params' WHERE `route_action_uid`='$route_action_uid' AND `draft`='$draft' ");
        }

        $this->db->query("DELETE FROM `setting` WHERE `key`='token_validity_period' ");
        $this->db->query("UPDATE `route_action` SET `params` = replace(`params`,'\"method_params\":[]}}}','\"method_params\":{}}}}') ");

        break;
      case 1701:
        for ($i = 1; $i < 10; $i++) {
          $this->db->query('UPDATE `route_action` SET `params` = replace(`params`,"\"char_count\":{\"value\":\"$i\"","\"char_count\":{\"value\":$i") ');
          $this->db->query('UPDATE `button` SET `action_params` = replace(`action_params`,"\"char_count\":{\"value\":\"$i\"","\"char_count\":{\"value\":$i") ');
        }
      default:
        return;
    }
  }

  public function updateAddDocCollDoc17($method_params)
  {
    $new_params = [];
    foreach ($method_params as $param_name => $param_value) {
      if (strpos($param_name, "f_") === false) {
        $new_params[$param_name] = $param_value;
        continue;
      }
      if (!isset($new_params['fields'])) {
        $new_params['fields'] = [];
      }
      $new_params['fields'][$param_name] = $param_value;
    }

    return $new_params;
  }

  public function updateGetKeyArrayJson17($method_params)
  {
    $new_params = ['elements' => []];
    foreach ($method_params as $param_name => $param) {
      if (strpos($param_name, "element") === 0) {
        $new_params['elements'][$param_name] = $param;
      } else {
        $new_params[$param_name] = $param;
      }
    }
    return $new_params;
  }

  public function updateGetJsonXML17($method_params)
  {
    $new_params = ['elements' => []];
    foreach ($method_params as $param_name => $param) {

      if ($param_name == "element") {
        $new_params[$param_name] = $param;
      } else {
        $new_params['elements'][$param_name] = $param;
      }
    }
    return $new_params;
  }

  private function updateDoctype17()
  {
    $this->db->query("ALTER TABLE `doctype_description` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `language_id`");
    $this->db->query("ALTER TABLE `doctype_description` DROP `full_description`");
    $this->db->query("ALTER TABLE `doctype_description` DROP PRIMARY KEY, ADD PRIMARY KEY (`doctype_uid`, `language_id`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `field_value_link` CHANGE `full_display_value` `full_display_value` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''; ");
    $this->db->query("UPDATE `doctype` SET `delegate_create`=0 WHERE `delegate_create` IS NULL ");
    $this->db->query("UPDATE `field` SET `ft_index`=0 WHERE `ft_index` IS NULL ");
    $this->db->query("UPDATE `field` SET `required`=0 WHERE `required` IS NULL ");
    $this->db->query("UPDATE `field` SET `unique`=0 WHERE `unique` IS NULL ");
    $this->db->query("UPDATE `field` SET `history`=0 WHERE `history` IS NULL ");
    $this->db->query("ALTER TABLE `field` CHANGE `access_form` `access_form` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ''; ");
    $this->db->query("UPDATE `field` SET `access_form`='' WHERE `access_form` IS NULL ");
    $this->db->query("ALTER TABLE `field` CHANGE `access_view` `access_view` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ''; ");
    $this->db->query("UPDATE `field` SET `access_view`='' WHERE `access_view` IS NULL ");

    $query = $this->db->query("SELECT `field_uid`, `type`, `params` FROM `field` ");
    foreach ($query->rows as $row) {
      $params = json_decode($row['params'], true);
      if (!empty($params)) {
        $field_uid = $row['field_uid'];
        switch ($row['type']) {
          case 'collectiondoc':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'currency':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'file':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'link':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'grafic':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'hidden':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'list':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'piediagram':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'table':
            if (!empty($params['inner_fields'])) {
              foreach ($params['inner_fields'] as &$inner_field) {
                $inner_field['field_form_required'] = (int) ($inner_field['field_form_required'] ?? 0);
                $inner_field['inner_field_uid'] = (int) $inner_field['inner_field_uid'];
                $inner_field['column_title'] = $inner_field['params']['column_title'];
                unset($inner_field['params']['column_title']);
                $inner_field['params'] = json_encode($this->updateField17($inner_field['field_type'], $inner_field['params']));
              }
              $params['inner_fields'] = array_values($params['inner_fields']);
            }
            break;
          case 'tabledoc':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'text':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'treedoc':
            $params = $this->updateField17($row['type'], $params);
            break;
          case 'viewdoc':
            $params = $this->updateField17($row['type'], $params);
            break;
          default:
            continue 2;
        }
        $params_new = $this->jsonEncode($params);
        $this->db->query("UPDATE `field` SET `params`='$params_new' WHERE `field_uid`='$field_uid' ");
      }
    }
    if ($this->isTableColumn("field_value_treedoc")) {
      $this->db->query("CREATE TABLE `field_value_treedoc_copy` SELECT * FROM `field_value_treedoc` ");
      $this->db->query("TRUNCATE `field_value_treedoc`");
      $this->clearIndexes("field_value_treedoc");
      $this->db->query("ALTER TABLE `field_value_treedoc` ADD PRIMARY KEY field_uid (field_uid,document_uid)");
      $this->db->query("ALTER TABLE `field_value_treedoc` ADD INDEX( `value`(250))");
      $this->db->query("ALTER TABLE `field_value_treedoc` ADD INDEX( `display_value`(250))");
      $this->db->query("ALTER TABLE `field_value_treedoc` ADD INDEX( `time_changed`)");
      $q = $this->db->query("SELECT * FROM `field_value_treedoc_copy` ");
      foreach ($q->rows as $row) {
        $field_uid = $row['field_uid'];
        $document_uid = $row['document_uid'];
        $value = $this->jsonEncode(json_decode($row['value'], true));
        $display_value = "";
        $time_changed = $row['time_changed'];
        $this->db->query("REPLACE INTO `field_value_treedoc` SET `field_uid`='$field_uid', `document_uid` = '$document_uid', `value`='$value', `display_value`='$display_value', `time_changed`='$time_changed' ");
      }
      $this->db->query("DROP TABLE `field_value_treedoc_copy`");
    }
    $this->db->query("DELETE FROM `doctype` WHERE `draft`>1 ");
  }

  private function updateTemplate17()
  {
    $this->db->query("CREATE TABLE `doctype_template_params` ( `template_uid` VARCHAR(36) NOT NULL DEFAULT '' , `doctype_uid` VARCHAR(36) NOT NULL DEFAULT '' , `type` VARCHAR(5) NOT NULL DEFAULT '' , `template_name` VARCHAR(100) NOT NULL DEFAULT '' , `condition_field_uid` VARCHAR(36) NOT NULL DEFAULT '' , `condition_comparison` VARCHAR(20) NOT NULL DEFAULT '' , `condition_value_uid` VARCHAR(36) NOT NULL DEFAULT '', `sort` INT NOT NULL DEFAULT '0', `draft` TINYINT NOT NULL DEFAULT '0', PRIMARY KEY (`template_uid`, `draft`)) ENGINE = InnoDB ");
    $this->db->query("ALTER TABLE `doctype_template` ADD `template_uid` VARCHAR(36) NOT NULL DEFAULT '' FIRST ");
    $this->db->query("ALTER TABLE `doctype_template` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `sort` ");
    $this->db->query("ALTER TABLE `doctype_template` DROP PRIMARY KEY ");
    $s = 's:0:"";';
    $this->db->query("UPDATE `doctype_template` SET `conditions`='' WHERE `conditions`='null' OR `conditions`='$s' ");
    $this->db->query("ALTER TABLE `doctype_template` CHANGE `conditions` `conditions` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''; ");
    $query = $this->db->query("SELECT * FROM `doctype` ");
    foreach ($query->rows as $row) {
      $templates = $this->getDoctypeTemplates($row);
      $doctype_uid = $row['doctype_uid'];

      foreach ($templates['templates'] as $type => $templs) {
        foreach ($templs as $sort => $templ) {
          $template_uid = $this->getUid();
          $draft = 0;
          if (!empty($templates['templates_draft'][$type][$sort])) {
            $draft = 1;
          }
          if (!isset($templ['params'])) {
            // парамсов нет, это основной шаблон
            $sets = [
              "`template_uid` = '$template_uid'",
              "`doctype_uid` = '$doctype_uid'",
              "`type` = '$type'",
              "`sort` = '$sort'",
              "`draft` = '0'",
            ];
            $this->db->query("INSERT INTO `doctype_template_params` SET " . implode(", ", $sets));
          }
          foreach ($templ as $language_id => $_) {
            if ($language_id == "conditions") {
              continue;
            }
            if ($language_id == "params") {
              $sets = [
                "`template_uid` = '$template_uid'",
                "`doctype_uid` = '$doctype_uid'",
                "`template_name` = '" . $templ['params']['template_name'] . "'",
                "`condition_field_uid` = '" . $templ['params']['condition_field_uid'] . "'",
                "`condition_comparison` = '" . $templ['params']['condition_comparison'] . "'",
                "`condition_value_uid` = '" . $templ['params']['condition_value_uid'] . "'",
                "`type` = '$type'",
                "`sort` = '$sort'",
                "`draft` = '0'",
              ];
              $this->db->query("INSERT INTO `doctype_template_params` SET " . implode(", ", $sets));

              if ($draft) {
                // есть черновик
                $sets = [
                  "`template_uid` = '$template_uid'",
                  "`doctype_uid` = '$doctype_uid'",
                  "`template_name` = '" . $templates['templates_draft'][$type][$sort]['params']['template_name'] . "'",
                  "`condition_field_uid` = '" . $templates['templates_draft'][$type][$sort]['params']['condition_field_uid'] . "'",
                  "`condition_comparison` = '" . $templates['templates_draft'][$type][$sort]['params']['condition_comparison'] . "'",
                  "`condition_value_uid` = '" . $templates['templates_draft'][$type][$sort]['params']['condition_value_uid'] . "'",
                  "`type` = '$type'",
                  "`sort` = '$sort'",
                  "`draft` = '1'",
                ];
                $this->db->query("INSERT INTO `doctype_template_params` SET " . implode(", ", $sets));
              }
              continue;
            }
            if ($draft) {
              $template = $this->db->escape(trim($templates['templates_draft'][$type][$sort][$language_id] ?? ""));
              $conds = json_decode(html_entity_decode($templates['templates_draft'][$type][$sort]['conditions'][$language_id] ?? ""), true);
              $conditions = $this->jsonEncode($conds);
              // if ($conditions == 's:0:"";') {
              //   $conditions = "";
              // }
              $this->db->query("INSERT INTO `doctype_template` SET `template_uid`='$template_uid', `template`='$template', `conditions`='$conditions', `doctype_uid`='$doctype_uid', `type`='$type', `language_id`='$language_id', `sort`='$sort', `draft`='$draft' ");
            }
            $this->db->query("UPDATE `doctype_template` SET `template_uid`='$template_uid' WHERE `doctype_uid`='$doctype_uid' AND `type`='$type' AND `language_id`='$language_id'  AND `sort`='$sort' AND `draft`='0' ");
          }
        }
      }
    }

    // устанавливаем UID для основных шаблонов; у них параметров пока нет, но UID нужны для индекса
    $query = $this->db->query("SELECT `doctype_uid`, `type`, `language_id`, `sort` FROM `doctype_template` WHERE `template_uid`='' ");
    foreach ($query->rows as $row) {
      $doctype_uid = $row['doctype_uid'];
      $type = $row['type'];
      $language_id = $row['language_id'];
      $sort = $row['sort'];
      $this->db->query("UPDATE `doctype_template` SET `template_uid`=UUID() WHERE `doctype_uid`='$doctype_uid' AND `type`='$type' AND `language_id`='$language_id'  AND `sort`='$sort' ");
    }
    $this->db->query("ALTER TABLE `doctype_template` ADD PRIMARY KEY(`template_uid`, `language_id`, `draft`)");



    // патчим доктайпы, создавая драфтовые записи
    $this->db->query("ALTER TABLE `doctype` DROP PRIMARY KEY, ADD PRIMARY KEY (`doctype_uid`, `draft`) USING BTREE ");

    $query = $this->db->query("SELECT * FROM `doctype` WHERE `draft` > 0 ");
    foreach ($query->rows as $row) {
      $draft = $row['draft'];
      $doctype_uid = $row['doctype_uid'];
      $draft_params = json_decode($row['draft_params'], true);
      $sets = [
        "`field_log_uid` = '" . ($draft_params['field_log_uid'] ?? $row['field_log_uid']) . "'",
        "`delegate_create` = '" . ($draft_params['delegate_create'] ?? $row['delegate_create']) . "'",
        "`date_edited` = '" . ($draft_params['date_edited'] ?? $row['date_edited']) . "'",
        "`doctype_uid` = '" . $row['doctype_uid'] . "'",
        "`date_added` = '" . $row['date_added'] . "'",
        "`user_uid` = '" . $row['user_uid'] . "'",
        "`draft` = '" . $row['draft'] . "'",
      ];
      $this->db->query("UPDATE `doctype` SET `draft`=0 WHERE `doctype_uid`='$doctype_uid' ");
      $this->db->query("INSERT INTO `doctype` SET " . implode(", ", $sets));
      //doctype_description
      $draft = $draft ? 1 : 0;
      if (isset($draft_params['doctype_description'])) {
        foreach ($draft_params['doctype_description'] as $language_id => $desc) {
          $name = $desc['name'] ?? "";
          $short_description = $desc['short_description'] ?? "";
          $title_field_uid = $desc['title_field_uid'] ?? "";
          $sets = [
            "`doctype_uid`='$doctype_uid'",
            "`name`='$name'",
            "`short_description`='$short_description'",
            "`title_field_uid`='$title_field_uid'",
            "`language_id`='$language_id'",
            "`draft`='$draft'",
          ];
          $this->db->query("INSERT INTO `doctype_description` SET " . implode(", ", $sets));
        }
      }
    }
    $this->db->query("ALTER TABLE `doctype_template` DROP `doctype_uid`");
    $this->db->query("ALTER TABLE `doctype_template` DROP `type`");
    $this->db->query("ALTER TABLE `doctype_template` DROP `sort`");
    $this->db->query("ALTER TABLE `doctype_template` CHANGE `template` `html` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("RENAME TABLE `doctype_template` TO `template_form`");
    $this->db->query("ALTER TABLE `template_form` CHANGE `conditions` `conditions` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''");
    $this->db->query("UPDATE `template_form` SET `conditions`='' WHERE `conditions`='null' ");

    $this->db->query("RENAME TABLE `doctype_template_params` TO `doctype_template`");
    $this->db->query("ALTER TABLE `doctype` DROP `params`");
    $this->db->query("ALTER TABLE `doctype` DROP `draft_params`");


    $this->db->query("ALTER TABLE `template_form` ADD `template_form_uid` VARCHAR(36) NOT NULL DEFAULT '' FIRST");


    $this->db->query("UPDATE `template_form` SET `template_form_uid`=UUID() WHERE `draft`=0 ");
    $query = $this->db->query("SELECT `language_id`, `template_uid` FROM `template_form` WHERE `draft` > 0 ");
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        $language_id = $row['language_id'];
        $template_uid = $row['template_uid'];
        $q = $this->db->query("SELECT `template_form_uid` FROM `template_form` WHERE `draft` =0 AND `language_id`='$language_id' AND `template_uid`='$template_uid' ");
        if ($q->num_rows) {
          $template_form_uid = $q->row['template_form_uid'];
          $this->db->query("UPDATE `template_form` SET `template_form_uid`='$template_form_uid' WHERE `draft`>0  AND `language_id`='$language_id' AND `template_uid`='$template_uid' ");
        }
      }
    }

    $this->db->query("ALTER TABLE `field` CHANGE `params` `params` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' ");
    // УШ объекты в массив
    $query = $this->db->query("SELECT `template_uid`,`language_id`,`draft`,`conditions` FROM `template_form` WHERE `conditions` != '' ");
    foreach ($query->rows as $row) {
      $conditions = json_decode($row['conditions']);
      if (!$conditions || !is_array($conditions)) {
        continue;
      }
      foreach ($conditions as &$condition) {
        foreach ($condition as &$a) {
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
      $db_cond = $this->jsonEncode($conditions);
      $template_uid = $row['template_uid'];
      $language_id = $row['language_id'];
      $draft = $row['draft'];
      $this->db->query("UPDATE `template_form` SET `conditions` = '$db_cond' WHERE `template_uid`='$template_uid' AND `language_id`='$language_id' AND `draft`='$draft' ");
    }
  }

  private function updateRoute17()
  {
    $this->db->query("ALTER TABLE `route_description` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `language_id` ");
    $this->db->query("ALTER TABLE `route_description` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_uid`, `language_id`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `route` DROP PRIMARY KEY, ADD PRIMARY KEY (`route_uid`, `draft`) USING BTREE ");
    $query = $this->db->query("SELECT `route_uid`, `draft_params` FROM `route` WHERE `draft`>0");
    foreach ($query->rows as $row) {
      $draft_params = json_decode($row['draft_params'], true);
      if (empty($draft_params['descriptions'])) {
        continue;
      }
      $route_uid = $row['route_uid'];
      foreach ($draft_params['descriptions'] as $language_id => $desc) {
        $name = $desc['name'];
        $description = $desc['description'];
        $this->db->query("INSERT INTO `route_description`(`route_uid`, `name`, `description`, `language_id`, `draft`) VALUES ('$route_uid', '$name', '$description', '$language_id', 1)");
      }
      $this->db->query("INSERT INTO `route` SELECT `route_uid`, `doctype_uid`, `sort`, 0, `draft_params` FROM `route` WHERE `route_uid`='$route_uid'");
    }
    $this->db->query("ALTER TABLE `route` DROP `draft_params` ");
    $query = $this->db->query("SELECT `route_action_uid`, `action`, `params` FROM `route_action` ");
    foreach ($query->rows as $row) {
      $params = json_decode($row['params'], true);
      if (!empty($params)) {
        $route_action_uid = $row['route_action_uid'];
        $params_new = $this->jsonEncode($this->updateAction17($row['action'], $params));
        $this->db->query("UPDATE `route_action` SET `params`='$params_new' WHERE `route_action_uid`='$route_action_uid' ");
      }
    }

    $query = $this->db->query("SELECT `route_button_uid`, `action`, `action_params` FROM `route_button` WHERE `action`!='' ");
    foreach ($query->rows as $row) {
      $params = json_decode($row['action_params'], true);
      if (!empty($params)) {
        $route_button_uid = $row['route_button_uid'];
        $params_new = $this->jsonEncode($this->updateAction17($row['action'], $params));
        $this->db->query("UPDATE `route_button` SET `action_params`='$params_new' WHERE `route_button_uid`='$route_button_uid' ");
      }
    }

    $this->db->query("ALTER TABLE `route_button` CHANGE `action_params` `action_params` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' ");
    $this->db->query("ALTER TABLE `route_action` CHANGE `params` `params` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' ");
  }

  private function updateButtonGroup17()
  {
    $this->db->query("ALTER TABLE `button_group` DROP PRIMARY KEY, ADD PRIMARY KEY (`button_group_uid`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `button_group` CHANGE `hide_group_name` `hide_group_name` TINYINT NULL DEFAULT 0");
    $this->db->query("ALTER TABLE `button_group` DROP `draft_params` ");
    $this->db->query("ALTER TABLE `button_group_description` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `language_id` ");
    $this->db->query("ALTER TABLE `button_group_description` DROP PRIMARY KEY, ADD PRIMARY KEY (`button_group_uid`, `language_id`, `draft`) USING BTREE ");
  }

  private function updateDoctypeAccess17()
  {
    $this->dropIndex("doctype_delegate", "subject_uid");
    $this->db->query("ALTER TABLE `doctype_delegate` CHANGE `subject_uid` `subject_uids` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' ");

    $query = $this->db->query("SELECT *  FROM `doctype_delegate` dd WHERE (SELECT COUNT(*) as cnt FROM `doctype_delegate` WHERE doctype_uid = dd.doctype_uid GROUP BY `doctype_uid`)>1");
    $dd = [];
    foreach ($query->rows as $row) {
      if (!isset($dd[$row['doctype_uid']])) {
        $dd[$row['doctype_uid']] = [];
      }
      if (!isset($dd[$row['doctype_uid']][$row['draft']])) {
        $dd[$row['doctype_uid']][$row['draft']] = [];
      }
      if (!isset($dd[$row['doctype_uid']][$row['draft']][$row['delegate_id']])) {
        $dd[$row['doctype_uid']][$row['draft']][$row['delegate_id']] = [];
      }
      $dd[$row['doctype_uid']][$row['draft']][$row['delegate_id']][] = $row;
    }
    if ($dd) {
      foreach ($dd as $doctype_uid => $delegates) {
        foreach ($delegates as $draft => $dlgs) {
          foreach ($dlgs as $delegate_id => $delegate) {
            if (count($delegates) < 1) continue;
            $this->db->query("DELETE FROM `doctype_delegate` WHERE `doctype_uid`='$doctype_uid' AND `delegate_id`='$delegate_id' AND `draft`='$draft' ");
            $subjects = [];
            foreach ($delegate as $d) {
              $subjects[] = $d['subject_uids'];
            }
            $this->db->query(
              "INSERT INTO `doctype_delegate` SET `doctype_uid`='$doctype_uid', `delegate_id`='$delegate_id', `draft`='$draft', " .
                "`author_uid`='" . $delegate[0]['author_uid'] . "',  " .
                "`subject_uids`='" . implode(",", $subjects) . "' "
            );
          }
        }
      }
    }
    $this->db->query("RENAME TABLE `doctype_access` TO `matrix_doctype_access`");
    $this->db->query("RENAME TABLE `doctype_delegate` TO `doctype_access`");
    $this->db->query("ALTER TABLE `doctype_access` CHANGE `delegate_id` `access_id` INT(11) NOT NULL");

    $this->db->query("ALTER TABLE `doctype_access` ADD PRIMARY KEY( `access_id`, `doctype_uid`, `draft`);");
    $this->dropIndex("doctype_access", "doctype_uid");
    $this->dropIndex("doctype_access", "draft");


    $this->db->query("ALTER TABLE `matrix_doctype_access` ADD PRIMARY KEY( `subject_uid`, `doctype_uid`, `object_uid`)");
    $this->dropIndex("matrix_doctype_access", "subject_uid");
    $this->dropIndex("matrix_doctype_access", "doctype_uid");
    $this->dropIndex("matrix_doctype_access", "object_uid");
  }

  private function updateFolder17()
  {
    $this->db->query("ALTER TABLE `folder` ADD `show_toolbar` VARCHAR(30) NOT NULL DEFAULT '' AFTER `additional_params` ");
    $this->db->query("ALTER TABLE `folder` ADD `show_title` VARCHAR(30) NOT NULL DEFAULT '' AFTER `show_toolbar` ");
    $this->db->query("ALTER TABLE `folder` ADD `collapse_group` VARCHAR(30) NOT NULL DEFAULT '' AFTER `show_title` ");
    $this->db->query("ALTER TABLE `folder` ADD `show_group_total` VARCHAR(30) NOT NULL DEFAULT '' AFTER `collapse_group` ");
    $this->db->query("ALTER TABLE `folder` ADD `hide_selectors` VARCHAR(30) NOT NULL DEFAULT '' AFTER `show_group_total` ");
    $this->db->query("ALTER TABLE `folder` DROP `draft_params` ");
    $this->db->query("ALTER TABLE `folder_field` DROP `draft_params` ");
    $this->db->query("ALTER TABLE `folder_filter` DROP `draft_params` ");
    $this->db->query("ALTER TABLE `folder_button` DROP `draft_params` ");
    $this->db->query("ALTER TABLE `folder` DROP PRIMARY KEY, ADD PRIMARY KEY (`folder_uid`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `folder_field` DROP PRIMARY KEY, ADD PRIMARY KEY (`folder_field_uid`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `folder_filter` DROP PRIMARY KEY, ADD PRIMARY KEY (`folder_filter_uid`, `draft`) USING BTREE ");
    $this->db->query("ALTER TABLE `folder_description` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `language_id`");

    $query = $this->db->query("SELECT * FROM `folder` ");
    foreach ($query->rows as $row) {
      $additional_params = @unserialize($row['additional_params']);
      if ($additional_params === false || (!$additional_params && $row['draft'] == 0)) {
        continue;
      }
      if (!is_array($additional_params)) {
        continue;
      }
      $new_add_params = [];
      $show_toolbar = "";
      $show_title = "";
      $collapse_group = "";
      $show_group_total = "";
      $hide_selectors = "";

      foreach ($additional_params as $param_name => $param_value) {
        switch ($param_name) {
          case "toolbar":
            $show_toolbar = (string) $additional_params['toolbar'];
            break;
          case "navigation":
            $show_title = (string) $additional_params['navigation'];
            break;
          case "collapse_group":
            $collapse_group = (string) $additional_params['collapse_group'];
            break;
          case "show_count_group":
            $show_group_total = (string) $additional_params['show_count_group'];
            break;
          case "hide_selectors":
            $hide_selectors = (string) $additional_params['hide_selectors'];
            break;
          default:
            $new_add_params[$param_name] = (string)$param_value;
            break;
        }
      }
      $folder_uid = $row['folder_uid'];
      $db_add_params = $this->jsonEncode($new_add_params);
      $this->db->query("UPDATE `folder` SET `show_toolbar`='$show_toolbar', `show_title`='$show_title', `collapse_group`='$collapse_group', `show_group_total`='$show_group_total', `hide_selectors`='$hide_selectors', `draft`='0', `additional_params`='$db_add_params' WHERE `folder_uid`='$folder_uid' "); // черновики игнорируем
      continue;
    }
    // $this->db->query("ALTER TABLE `folder` DROP `additional_params` ");
  }

  private function updateFolderFilter17()
  {
    $query = $this->db->query("SELECT `folder_filter_uid`, `action_params` FROM `folder_filter` ");
    foreach ($query->rows as $row) {
      $ap = @unserialize($row['action_params']);
      if ($ap === false) {
        continue;
      }
      $action_params = $this->jsonEncode($ap);
      $folder_filter_uid = $row['folder_filter_uid'];
      $this->db->query("UPDATE `folder_filter` SET `action_params`='$action_params', `draft`='0' WHERE `folder_filter_uid`='$folder_filter_uid' "); // черновики игнорируем
      continue;
    }
  }

  private function updateButton17()
  {
    // подготавливаем кнопки маршрута к объединению
    $this->db->query("ALTER TABLE `route_button` CHANGE `route_uid` `parent_uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("ALTER TABLE `route_button` CHANGE `route_button_uid` `uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("RENAME TABLE `route_button` TO `button`");

    // десериализуем и пишем в общ таблицу
    $query = $this->db->query("SELECT * FROM `folder_button` ");
    foreach ($query->rows as $row) {
      $uid = $row['folder_button_uid'];
      $parent_uid = $row['folder_uid'];
      $picture = $row['picture'];
      $hide_button_name = $row['hide_button_name'];
      $color = $row['color'];
      $background = $row['background'];
      $action = $row['action'];
      $action_log = $row['action_log'];
      $action_move_route_uid = $row['action_move_route_uid'];
      $ap = @unserialize($row['action_params']);
      if ($ap === false) {
        continue;
      }
      $action_params = $this->jsonEncode($this->updateAction17($row['action'], $ap));
      $draft = 0;
      $sort = $row['sort'];
      $this->db->query("INSERT INTO `button` SET `uid`='$uid', `parent_uid`='$parent_uid', `picture`='$picture', `hide_button_name`='$hide_button_name', `color`='$color', `background`='$background', `action`='$action', `action_log`='$action_log', `action_move_route_uid`='$action_move_route_uid', `action_params`='$action_params', `draft`='$draft', `sort`='$sort' ");
    }
    $this->db->query("DROP TABLE `folder_button`");

    $this->db->query("ALTER TABLE `route_button_delegate` CHANGE `route_button_uid` `uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("RENAME TABLE `route_button_delegate` TO `button_delegate`");
    $query = $this->db->query("SELECT * FROM `folder_button_delegate` ");
    foreach ($query->rows as $row) {
      $uid = $row['folder_button_uid'];
      $document_uid = $row['document_uid'];
      $structure_uid = $row['structure_uid'];
      $this->db->query("INSERT INTO `button_delegate` SET `uid`='$uid', `document_uid`='$document_uid', `structure_uid`='$structure_uid' ");
    }
    $this->db->query("DROP TABLE `folder_button_delegate`");

    $this->db->query("ALTER TABLE `route_button_description` CHANGE `route_button_uid` `uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("RENAME TABLE `route_button_description` TO `button_description`");
    $query = $this->db->query("SELECT * FROM `folder_button_description` ");
    foreach ($query->rows as $row) {
      $uid = $row['folder_button_uid'];
      $name = $row['name'];
      $description = $row['description'];
      $language_id = $row['language_id'];
      $draft = 0;
      $this->db->query("INSERT INTO `button_description` SET `uid`='$uid', `name`='$name', `description`='$description', `language_id`='$language_id', `draft`='$draft' ");
    }
    $this->db->query("DROP TABLE `folder_button_description`");

    $this->db->query("ALTER TABLE `route_button_field` CHANGE `route_button_uid` `uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("RENAME TABLE `route_button_field` TO `button_field`");
    $query = $this->db->query("SELECT * FROM `folder_button_field` ");
    foreach ($query->rows as $row) {
      $uid = $row['folder_button_uid'];
      $field_uid = $row['field_uid'];
      $draft = 0;
      $this->db->query("INSERT INTO `button_field` SET `uid`='$uid', `field_uid`='$field_uid', `draft`='$draft' ");
    }
    $this->db->query("DROP TABLE `folder_button_field`");

    $this->db->query("ALTER TABLE `folder_button_route` CHANGE `folder_button_uid` `uid` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ");
    $this->db->query("ALTER TABLE `folder_button_route` ADD `draft` TINYINT NOT NULL DEFAULT '0' AFTER `route_uid` ");
    $this->db->query("RENAME TABLE `folder_button_route` TO `button_route`");
  }

  public function updateField17($type, $params)
  {
    switch ($type) {
      case 'collectiondoc':
        if (!empty($params['fields'])) {
          foreach ($params['fields'] as &$param) {
            $param['id'] = (int) $param['id'];
          }
        }
        $params['categorized'] = (int) $params['categorized'];
        return $params;
      case 'currency':
        if (!empty($params['currency'])) {
          foreach ($params['currency'] as &$param) {
            $param['round'] = (int) ($param['round'] ?? 0);
          }
          $params['currency'] = array_values($params['currency']);
        }
        $params['count_dec'] = (int) $params['count_dec'];
        return $params;
      case 'file':
        $params['preview']['status'] = !empty($params['preview']['status']) ? 1 : 0;
        $params['preview']['width'] = !empty($params['preview']['width']) ? (int)$params['preview']['width'] : 0;
        $params['preview']['height'] = !empty($params['preview']['height']) ? (int) $params['preview']['height'] : 0;
        $params['preview']['link'] = !empty($params['preview']['status']) ? 1 : 0;
        $params['size_file'] = !empty($params['size_file']) ? (int)  $params['size_file'] : 0;
        $params['limit_files'] = !empty($params['limit_files']) ? (int)  $params['limit_files'] : 0;
        return $params;
      case 'link':
        $params['href'] = (int) ($params['href'] ?? 0);
        $params['list'] = (int) ($params['list'] ?? 0);
        $params['multi_select'] = (int) ($params['multi_select'] ?? 0);
        $params['disabled_actualize'] = (int) ($params['disabled_actualize'] ?? 0);
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            if (isset($cond['concat'])) {
              $cond['concat'] = (int) $cond['concat'];
            }
          }
          $params['conditions'] = array_values($params['conditions']);
        }
        return $params;
      case 'grafic':
        if (isset($params['main_axis_unit'])) {
          $params['main_axis_unit'] = (int) $params['main_axis_unit'];
        }
        if (isset($params['width'])) {
          $params['width'] = (int) $params['width'];
        }
        if (isset($params['height'])) {
          $params['height'] = (int) $params['height'];
        }
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            if (isset($cond['concat'])) {
              $cond['concat'] = (int) $cond['concat'];
            }
          }
          $params['conditions'] = array_values($params['conditions']);
        }
        if (!empty($params['legend'])) {
          $params['legend'] = array_values($params['legend']);
        }
        return $params;
      case 'hidden':
        $params['type_hash'] = (int) $params['type_hash'];
        return $params;
      case 'list':
        if (!empty($params['values'])) {
          foreach ($params['values'] as &$param) {
            if (isset($param['default_value'])) {
              $param['default_value'] = (int) $param['default_value'];
            }
          }
          $params['values'] = array_values($params['values']);
        }
        $params['multi_select'] = empty($params['multi_select']) ? 0 : 1;
        $params['visualization'] = (int) $params['visualization'];
        if (isset($params['default_value']) && !is_array($params['default_value'])) {
          $params['default_value'] = [(string) $params['default_value']];
        }
        return $params;
      case 'piediagram':
        $params['width'] = (int) $params['width'];
        $params['height'] = (int) $params['height'];
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            if (isset($cond['concat'])) {
              $cond['concat'] = (int) $cond['concat'];
            }
          }
          $params['conditions'] = array_values($params['conditions']);
        }
        if (!empty($params['legend'])) {
          $params['legend'] = array_values($params['legend']);
        }
        return $params;
      case 'tabledoc':
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            if (isset($cond['concat'])) {
              $cond['concat'] = (int) $cond['concat'];
            }
          }
          $params['conditions'] = array_values($params['conditions']);
        }
        if (!empty($params['columns'])) {
          foreach ($params['columns'] as &$col) {
            if (isset($col['total'])) {
              $col['total'] = (int) $col['total'];
            }
            if (!empty($col['buttons'])) {
              $col['buttons'] = array_values($col['buttons']);
            }
          }
          $params['columns'] = array_values($params['columns']);
          $params['hide_empty_table'] = !empty($params['hide_empty_table']) ? 1 : 0;
        }
        if (!empty($params['inits'])) {
          $params['inits'] = array_values($params['inits']);
        }
        return $params;
      case 'text':
        if (isset($params['editor_enabled'])) {
          $params['editor_enabled'] = (bool) $params['editor_enabled'];
        }
        return $params;

      case 'treedoc':
        $params['expand_level'] = !empty($params['expand_level']) ? (int) $params['expand_level'] : 0;
        return $params;
      case 'viewdoc':
        if (!empty($params['templates'])) {
          $params['templates'] = array_values($params['templates']);
        }
        if (!empty($params['show_history'])) {
          $params['show_history'] = (int) $params['show_history'];
        }
        return $params;
      default:
        return $params;
    }
  }

  public function   updateAction17($type, $params)
  {
    if (!is_array($params)) {
      $params = [];
    }
    $params['history'] = !empty($params['history']) ? 1 : 0;

    switch ($type) {
      case 'condition':
        if (!empty($params['condition'])) {

          foreach ($params['condition'] as &$cond) {
            $cond['first_type_value'] = (int) $cond['first_type_value'];
            $cond['second_type_value'] = (int) $cond['second_type_value'];
            // foreach (["first", "second"] as $arg) {
            //   if (empty($cond[$arg . '_value_field_getter']) || $cond[$arg . '_value_field_getter'] === "" || $cond[$arg . '_value_field_getter'] === "0") {
            //     continue;
            //   }
            //   if (!empty($cond[$arg . '_value_method_params'])) {

            //     // $mp = $this->getFieldMethodParams($cond[$arg . '_value_field_uid'], $cond[$arg . '_value_field_getter'], $cond[$arg . '_value_method_params']);
            //     // if ($mp !== NULL) {
            //     //   print_r($mp);
            //     //   exit;
            //     //   $cond[$arg . '_value_method_params'] = $mp;
            //     // }
            //   }
            // }
          }
          $params['condition'] = array_values($params['condition']);
        }
        if (!empty($params['inner_actions_true'])) {
          $params['inner_actions_true'] = array_values($params['inner_actions_true']);
          foreach ($params['inner_actions_true'] as &$action) {
            $action['params'] = $this->updateAction17($action['action'], $action['params']);
          }
        }
        if (!empty($params['inner_actions_false'])) {
          $params['inner_actions_false'] = array_values($params['inner_actions_false']);
          foreach ($params['inner_actions_false'] as &$action) {
            $action['params'] = $this->updateAction17($action['action'], $action['params']);
          }
        }
        if (!empty($params['stop'])) {
          $params['stop']['true'] = !empty($params['stop']['true']) ? 1 : 0;
          $params['stop']['false'] = !empty($params['stop']['false']) ? 1 : 0;
        }
        return $params;

      case 'creation':
        if (!empty($params['set'])) {
          $params['set'] = array_values($params['set']);
        }
        return $params;
      case 'record':
        if (isset($params['method_params']['standard_setter_param']['value']) && is_array($params['method_params']['standard_setter_param']['value'])) {
          $params['method_params']['standard_setter_param']['value'] = implode(",", $params['method_params']['standard_setter_param']['value']);
        }
        if (isset($params['method_params']['standard_setter_param']['method_params']['char_count']['value'])) {
          $params['method_params']['standard_setter_param']['method_params']['char_count']['value'] = (int)$params['method_params']['standard_setter_param']['method_params']['char_count']['value'];
        }
        if (isset($params['method_params']['char_count']['value'])) {
          $params['method_params']['char_count']['value'] = (int)$params['method_params']['char_count']['value'];
        }
        if (isset($params['method_params']['standard_setter_param']['method_params']) && !count($params['method_params']['standard_setter_param']['method_params'])) {
          $params['method_params']['standard_setter_param']['method_params'] = (object)$params['method_params']['standard_setter_param']['method_params'];
        }
        return $params;
      case 'export_f':
        if (!empty($params['xlsx_row'])) {
          $params['xlsx_row'] = array_values($params['xlsx_row']);
          foreach ($params['xlsx_row'] as &$xlsx_row) {
            if (!empty($xlsx_row['column'])) {
              $xlsx_row['column'] = array_values($xlsx_row['column']);
            }
            $xlsx_row['number'] = (int) $xlsx_row['number'];
          }
        }
        if (!empty($params['csv_column'])) {
          $params['csv_column'] = array_values($params['csv_column']);
        }
        return $params;
      case 'http_request':
        if (!empty($params['headers'])) {
          $params['headers'] = array_values($params['headers']);
        }
        return $params;
      case 'import_f':
        // if (!empty($params['filepath_field'])) {
        //   $params['filepath_field'] = array_values($params['filepath_field']);
        // }
        if (!empty($params['matching'])) {
          foreach ($params['matching'] as &$match) {
            $match['iseditable'] = !empty($match['iseditable']) ? 1 : 0;
          }
          $params['matching'] = array_values($params['matching']);
        }
        if (!empty($params['init'])) {
          $params['init'] = array_values($params['init']);
        }
        $params['nodaemon'] = !empty($params['nodaemon']) ? 1 : 0;
        $params['start_row'] = (int) $params['start_row'];
        return $params;
      case 'message':
        if (!empty($params['template'])) {
          $templates = [];
          foreach ($params['template'] as $lang_id => $templ) {
            $templates[(int) $lang_id] = $templ;
          }
          $params['template'] = $templates;
        }
        if (!empty($params['title'])) {
          $templates = [];
          foreach ($params['title'] as $lang_id => $templ) {
            $templates[(int) $lang_id] = $templ;
          }
          $params['title'] = $templates;
        }
        return $params;
      case 'move':
        $params['document_route_type'] = !empty($params['document_route_type']) ? 1 : 0;
        return $params;
      case 'notification':
        if (!empty($params['notification_template'])) {
          $templates = [];
          foreach ($params['notification_template'] as $lang_id => $templ) {
            $templates[(int) $lang_id] = $templ;
          }
          $params['notification_template'] = $templates;
        }
        return $params;
      case 'print':
        if (!empty($params['template'])) {
          $templates = [];
          foreach ($params['template'] as $lang_id => $templ) {
            $templates[(int) $lang_id] = $templ;
          }
          $params['template'] = $templates;
        }
        return $params;
      case 'report':
        if (!empty($params['link_doctype'])) {
          $params['link_doctype'] = array_values($params['link_doctype']);
        }
        if (!empty($params['fields'])) {
          foreach ($params['fields'] as &$field) {
            $field = json_decode(html_entity_decode($field), true);
            $field['fieldId'] = (int) $field['fieldId'];
          }
          $params['fields'] = array_values($params['fields']);
        }
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            $cond = array_values($cond);
          }
        }
        return $params;
      case 'selection':
        if (!empty($params['conditions'])) {
          foreach ($params['conditions'] as &$cond) {
            if (isset($cond['concat'])) {
              $cond['concat'] = empty($cond['concat']) ? 0 : 1;
            }
          }
          $params['conditions'] = array_values($params['conditions']);
        }
        return $params;
      default:
        return $params;
    }
  }

  /**
   * Установка переменной необходимости ручного обновления, запускаемого администратором в удобное для него время.
   * @param type $update - метод для запуска; если метода нет, переменная стирается
   */
  private function setManualUpdate($update)
  {
    $this->load->model('setting/variable');
    if ($update) {
      $var = $this->model_setting_variable->getVar('manual_update');
      if ($var) {
        $update = $var . "," . $update;
      }
    }
    $this->model_setting_variable->setVar('manual_update', $update);
  }

  public function addSetting($code, $key, $value)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' ");
    if ($query->num_rows) {
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET `code` = '" . $this->db->escape($code) . "', `value` = '" . $this->db->escape($value) . "' WHERE `key` = '" . $this->db->escape($key) . "' ");
    } else {
      $this->db->query("INSERT INTO " . DB_PREFIX . "setting (`store_id`, `code`, `key`, `value`, `serialized`) "
        . "VALUES ('0', '" . $this->db->escape($code) . "', '" . $this->db->escape($key) . "', '" . $this->db->escape($value) . "', '0')");
    }
  }

  private function addField($field_uid, $name, $doctype_uid, $field_type, $params, $sort, $setting = 0, $description = '')
  {
    $query = $this->db->query("SELECT field_uid FROM " . DB_PREFIX . "field WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
    if (!$query->num_rows) {
      //поля нет, теперь проверим наличие типа документа, в который добавляется это поле
      $query_dt = $this->db->query("SELECT doctype_uid FROM " . DB_PREFIX . "doctype WHERE doctype_uid = '" . $this->db->escape($doctype_uid) . "' ");
      if (!$query_dt->num_rows) {
        //типа документа нет
        return;
      }
      $this->db->query("INSERT INTO `field` (`field_uid`, `name`, `doctype_uid`, `type`, `setting`, `change_field`, `access_form`, `access_view`, "
        . "`required`, `unique`, `params`, `sort`, `draft`, `draft_params`, `description`) "
        . "VALUES ('" . $this->db->escape($field_uid) . "', "
        . "'" . $this->db->escape($name) . "', "
        . "'" . $this->db->escape($doctype_uid) . "', "
        . "'" . $this->db->escape($field_type) . "', "
        . "'" . (int) $setting . "', '0', '', '', '0', '0', "
        . "'" . $this->db->escape($params) . "', '" . (int) $sort . "', '0', '', "
        . "'" . $this->db->escape($description) . "')");
    }
  }

  private function getTableColumn($table, $column = "")
  {
    $sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME='" . DB_PREFIX . $this->db->escape($table) . "' ";
    if ($column) {
      $sql .= " AND COLUMN_NAME = '" . $this->db->escape($column) . "' ";
    }
    $query = $this->db->query($sql);
    return $query->row;
  }

  private function dropIndex($table, $index)
  {
    $sql = "SELECT DISTINCT INDEX_NAME as `index` FROM `information_schema`.`STATISTICS` WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '" . DB_DATABASE . "' AND INDEX_NAME='$index' ";
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      $this->db->query("DROP INDEX `$index` ON `" . DB_DATABASE . "`.`$table` ");
    }
  }
  private function clearIndexes($table)
  {
    $sql = "SELECT DISTINCT INDEX_NAME as `index` FROM `information_schema`.`STATISTICS` WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '" . DB_DATABASE . "'";
    $query = $this->db->query($sql);

    foreach ($query->rows as $row) {
      $this->db->query("DROP INDEX `" . $row['index'] . "` ON `" . DB_DATABASE . "`.`$table` ");
    }
  }

  private function isTableColumn($table, $column = "")
  {
    return $this->getTableColumn($table, $column) ? true : false;
  }

  public function getUid()
  {
    $query = $this->db->query("SELECT UUID() AS uid");
    return $query->row['uid'];
  }

  //Условие+ -> Условие

  private function transformConditionPlus($params)
  {
    $_params = unserialize($params);
    $new_params = [];
    $condition = [];
    if (!$_params) {
      return $params;
    }
    foreach ($_params as $param_name => $param_value) {
      if (strpos($param_name, 'first_') === false && strpos($param_name, 'comparison_') === false && strpos($param_name, 'second_') === false) {
        $new_params[$param_name] = $param_value;
      } else {
        $condition[$param_name] = $param_value;
      }
    }
    if ($condition) {
      $condition['first_type_value'] = 0;
      $new_params['condition'][] = $condition;
    }
    return serialize($new_params);
  }

  private function getMenuUids()
  {
    return [
      '0' => '',
      '1' => '96544aeb-f2b5-11e9-bd8a-cab529aeec94',
      '2' => '96544c0b-f2b5-11e9-bd8a-cab529aeec94',
      '3' => '96544d0d-f2b5-11e9-bd8a-cab529aeec94',
      '7' => '96544e04-f2b5-11e9-bd8a-cab529aeec94',
      '12' => '96544ef7-f2b5-11e9-bd8a-cab529aeec94',
      '13' => '96544fda-f2b5-11e9-bd8a-cab529aeec94',
      '14' => '965450b4-f2b5-11e9-bd8a-cab529aeec94',
      '15' => '9654518e-f2b5-11e9-bd8a-cab529aeec94',
      '16' => '96545267-f2b5-11e9-bd8a-cab529aeec94',
      '17' => '96545340-f2b5-11e9-bd8a-cab529aeec94',
      '18' => '96545425-f2b5-11e9-bd8a-cab529aeec94',
      '19' => '965454fb-f2b5-11e9-bd8a-cab529aeec94',
      '20' => '965455cf-f2b5-11e9-bd8a-cab529aeec94',
      '21' => '965456a3-f2b5-11e9-bd8a-cab529aeec94',
      '22' => '96545776-f2b5-11e9-bd8a-cab529aeec94',
      '37' => '96545847-f2b5-11e9-bd8a-cab529aeec94',
      '24' => '96545918-f2b5-11e9-bd8a-cab529aeec94',
      '46' => '965459ec-f2b5-11e9-bd8a-cab529aeec94',
      '44' => '96545ac0-f2b5-11e9-bd8a-cab529aeec94',
      '45' => '96545b94-f2b5-11e9-bd8a-cab529aeec94',
      '42' => '96545c68-f2b5-11e9-bd8a-cab529aeec94',
      '38' => '96545d3b-f2b5-11e9-bd8a-cab529aeec94',
      '39' => '96545e1c-f2b5-11e9-bd8a-cab529aeec94',
      '40' => '96545eef-f2b5-11e9-bd8a-cab529aeec94',
      '41' => '96545fbf-f2b5-11e9-bd8a-cab529aeec94',
      '33' => '96546092-f2b5-11e9-bd8a-cab529aeec94',
      '34' => '96546162-f2b5-11e9-bd8a-cab529aeec94',
      '35' => '96546231-f2b5-11e9-bd8a-cab529aeec94',
      '36' => '96546303-f2b5-11e9-bd8a-cab529aeec94',
      '47' => '965463d0-f2b5-11e9-bd8a-cab529aeec94',
      '48' => '9654649e-f2b5-11e9-bd8a-cab529aeec94',
      '49' => '96546570-f2b5-11e9-bd8a-cab529aeec94',
      '50' => '9654663e-f2b5-11e9-bd8a-cab529aeec94',
      '51' => '96546710-f2b5-11e9-bd8a-cab529aeec94',
      '52' => '965467f1-f2b5-11e9-bd8a-cab529aeec94',
      '53' => '965468c3-f2b5-11e9-bd8a-cab529aeec94',
      '54' => '96546994-f2b5-11e9-bd8a-cab529aeec94',
      '55' => '96546a65-f2b5-11e9-bd8a-cab529aeec94',
      '56' => '96546b36-f2b5-11e9-bd8a-cab529aeec94',
      '57' => '96546c09-f2b5-11e9-bd8a-cab529aeec94',
      '58' => '96546cdd-f2b5-11e9-bd8a-cab529aeec94',
      '59' => '96546de2-f2b5-11e9-bd8a-cab529aeec94',
      '60' => '96546eb6-f2b5-11e9-bd8a-cab529aeec94',
      '61' => '96546fc0-f2b5-11e9-bd8a-cab529aeec94',
      '62' => '965470a0-f2b5-11e9-bd8a-cab529aeec94',
      '63' => '96547174-f2b5-11e9-bd8a-cab529aeec94',
      '64' => '96547254-f2b5-11e9-bd8a-cab529aeec94',
      '65' => '96547327-f2b5-11e9-bd8a-cab529aeec94',
      '66' => '965473f8-f2b5-11e9-bd8a-cab529aeec94',
      '67' => '965474ce-f2b5-11e9-bd8a-cab529aeec94',
      '68' => '965475a0-f2b5-11e9-bd8a-cab529aeec94',
      '69' => '198fc7b2-f2b7-11e9-bd8a-cab529aeec94',
      '70' => '20b9eda4-f2b7-11e9-bd8a-cab529aeec94',
      '71' => '2523e7dc-f2b7-11e9-bd8a-cab529aeec94',
      '72' => '29747443-f2b7-11e9-bd8a-cab529aeec94',
      '73' => '2c8910bd-f2b7-11e9-bd8a-cab529aeec94',
      '74' => '300ce3cd-f2b7-11e9-bd8a-cab529aeec94',
      '75' => '33c753af-f2b7-11e9-bd8a-cab529aeec94',
      '76' => '3786712a-f2b7-11e9-bd8a-cab529aeec94',
      '77' => '3b372d8c-f2b7-11e9-bd8a-cab529aeec94',
      '78' => '3e8e6f49-f2b7-11e9-bd8a-cab529aeec94',
      '79' => '41c02eeb-f2b7-11e9-bd8a-cab529aeec94',
      '80' => '459fce9f-f2b7-11e9-bd8a-cab529aeec94',
    ];
  }

  public function getDoctypeTemplates($row, $doctype_templates = [])
  {
    // $doctype_template_conditions = ['templates' => [], 'templates_draft' => []];
    $templates = [];
    $templates_draft = [];
    $templates_draft_params = [];
    if ($row['draft'] && $row['draft_params']) { // исп. $templ_params = $templ_type . "_params";
      $draft_params = json_decode($row['draft_params'], true);

      // $doctype_template_conditions['templates_draft'] = $draft_params['doctype_template_conditions'] ?? array();
      foreach ($draft_params['doctype_template'] as $type => $doctype_template_data) {
        ksort($doctype_template_data);
        $index = 0;
        foreach ($doctype_template_data as $templates_data) {
          if (is_array($templates_data) && $templates_data) {
            foreach ($templates_data as $name => $value) {
              if ($name !== "params") {
                $templates_draft[] = array(
                  'type' => $type,
                  'language_id' => $name,
                  'template' => $value,
                  'sort' => $index,
                  // 'conditions' => $doctype_template_conditions['templates_draft'][$type][$index][$name] ?? ""
                );
              }
            }
            $index++;
          }
        }
      }
      $templates_draft_params = $draft_params['params'] ?? []; // исп. $templ_params = $templ_type . "_params";
      $templates_draft_params['doctype_template_conditions'] = $draft_params['doctype_template_conditions']; // исп. $templ_params = $templ_type . "_params";
    }

    if (!$doctype_templates) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doctype_template WHERE doctype_uid = '" . $this->db->escape($row['doctype_uid']) . "' ORDER BY `sort` ");
      $doctype_templates = $query->rows;
    }

    foreach ($doctype_templates as $row_dt) {
      $templates[] = array(
        'type' => $row_dt['type'],
        'language_id' => $row_dt['language_id'],
        'template' => $row_dt['template'],
        'sort' => $row_dt['sort'],
        'conditions' => $row_dt['conditions'] ?? ""
      );
    }
    $templates_params = json_decode($row['params'], true); // $$templ_params
    $result = [];
    foreach (['templates', 'templates_draft'] as $templ_type) {
      $result[$templ_type] = [];
      $templ_params = $templ_type . "_params";
      $params = $$templ_params;
      // print_r($$templ_type);
      // exit;
      foreach ($$templ_type as $r) {

        $result[$templ_type][$r['type']][$r['sort']][$r['language_id']] = $r['template'];
        if (isset($params['doctype_template'][$r['type']][$r['sort']])) {
          $result[$templ_type][$r['type']][$r['sort']]['params'] = $params['doctype_template'][$r['type']][$r['sort']];
        }
        if (isset($params['doctype_template_conditions'][$r['type']][$r['sort']])) {
          $result[$templ_type][$r['type']][$r['sort']]['conditions'] = $params['doctype_template_conditions'][$r['type']][$r['sort']];
        }
      }
    }

    return $result;
  }

  // private function getFieldMethodParams($field_uid, $method_name, $method_params)
  // {
  //   $this->load->model('doctype/doctype');
  //   $field_info = $this->model_doctype_doctype->getField($field_uid);
  //   if (empty($field_info['type'])) {
  //     return NULL;
  //   }
  //   $method_data = array(
  //     'method_name'   => $method_name,
  //     'method_params' => $method_params,
  //     'field_uid' => $field_uid
  //   );

  //   // проверяем вложенные параметры на наличие методов
  //   foreach ($method_data['method_params'] as &$mp) {
  //     if (!empty($mp['field_uid'])  && !empty($mp['method_name']) && !empty($mp['method_params'])) {
  //       // метод есть, уходим в рекурсию
  //       $mp['method_params'] = $this->getFieldMethodParams($mp['field_uid'], $mp['method_name'], $mp['method_params']);
  //     }
  //   }

  //   return $this->load->controller('extension/field/' . $field_info['type'] . "/setMethodParams", $method_data);
  // }

  private function getNumberVersion($version)
  {
    $v = str_replace(".", "", $version);
    if (strlen($v) == 3) {
      $v .= "0";
    }
    return (int) $v;
  }

  private function jsonEncode($v)
  {
    return $this->db->escape(@json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
  }
}

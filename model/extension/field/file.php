<?php

class ModelExtensionFieldFile extends FieldModel
{

  const DIR_FILE_UPLOAD = DIR_DOWNLOAD . "field_file/";

  public function editValue($field_uid, $document_uid, $value)
  {
    //очищаем $value
    $value_clear = array();
    if ($value) {
      if (!is_array($value)) {
        $value = explode(",", $value);
      }
      foreach ($value as $v) {
        if ($v) {
          $value_clear[] = trim($this->db->escape($v));
        }
      }
    }
    $value_clear = array_unique($value_clear);

    //print_r($value_clear);
    //в поле были файлы следующие
    $field_value = $this->getValue($field_uid, $document_uid);
    //print_r($field_value);
    //удаляем существующие связи для данного поля документа
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_file_relation WHERE field_uid = '" . $this->db->escape($field_uid) . "' "
      . "AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    $displays = array();
    $valid_file_uids = array();
    if ($value_clear) {
      //формируем новые связи для данного поля документа
      $file_rows = array();
      $versions = array();
      foreach ($value_clear as &$file_uid) {
        if (strlen(trim($file_uid)) == 36) {
          $version = 1;
        } else if (strlen(trim($file_uid)) > 37) {
          $version = substr($file_uid, 37);
          $file_uid = substr($file_uid, 0, 36);
        }
        $file_info = $this->getFile($file_uid);
        if ($file_info) {
          $valid_file_uids[] = $file_uid;
          $file_rows[] = "('" . trim($file_uid) . "','" . $this->db->escape($field_uid) . "','" . $this->db->escape($document_uid) . "')";
          $displays[] = $file_info['file_name'];
          $this->db->query("UPDATE " . DB_PREFIX . "field_value_file_list SET status=1 WHERE file_uid='" . $file_uid . "' AND version='" . $version . "'");
        }
      }
      //print_r($file_rows);
      //print_r("INSERT INTO " . DB_PREFIX . "field_value_file_relation (file_uid,field_uid,document_uid) VALUES " . implode(", ", array_unique($file_rows))); 
      //$this->db->query("INSERT INTO " . DB_PREFIX . "field_value_file_relation (file_uid,field_uid,document_uid) VALUES " . implode(", ", array_unique($file_rows)));
      if ($file_rows) {
        $this->db->query("REPLACE INTO " . DB_PREFIX . "field_value_file_relation (file_uid,field_uid,document_uid) VALUES " . implode(", ", array_unique($file_rows)));
      }
      //обновляем status файлов (status=0 для обхода доступа к файлам, необходимосго до сохранения документа, после сохранения = 1 навсегда; 
      //$this->db->query("UPDATE " . DB_PREFIX . "field_value_file_list SET status=1, version=" . $this->db->escape($version) . " WHERE file_uid IN ('" . implode("','", $value_clear) . "') ");
    }

    //выполняем очистку

    if ($field_value) {
      //сравниваем массивы
      $removed_file_uids = array_diff($valid_file_uids, $value_clear); //файлы, которые были удалены из поля документа
      //если удаленные из поля файлы не используются в других поля, то их можно удалить совсем
      foreach ($removed_file_uids as $removed_file_uid) {
        $query_remove_file = $this->db->query("SELECT file_uid FROM " . DB_PREFIX . "field_value_file_relation WHERE file_uid = '" . $removed_file_uid . "' ");
        if (!$query_remove_file->num_rows) {
          //файл нигде больше не используется и его можно удалить
          $this->removeFile($removed_file_uid);
        }
      }
    }

    //обновляем value в field_value_file
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_file "
      . "WHERE field_uid='" . $field_uid . "' AND document_uid='" . $document_uid . "' ");
    if ($query->num_rows) {
      $this->db->query("UPDATE " . DB_PREFIX . "field_value_file SET "
        . "value='" . $this->db->escape(implode(", ", $displays)) . "', "
        . "display_value='" . $this->db->escape(implode(", ", $displays)) . "', "
        . "time_changed=NOW() "
        . "WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    } else {
      $this->db->query(
        "INSERT INTO " . DB_PREFIX . "field_value_file SET "
          . "document_uid='" . $this->db->escape($document_uid) . "', "
          . "field_uid='" . $this->db->escape($field_uid) . "', "
          . "value='" . $this->db->escape(implode(", ", $displays)) . "', "
          . "display_value='" . $this->db->escape(implode(", ", $displays)) . "' "
      );
    }
  }

  /**
   * Возвращает значение поля
   * @param type $field_uid
   * @param type $document_uid
   * @param type $widget_value - значение, получаемое от виджета поля; возвращается value, которое пишется в базу данных
   * @return type
   */
  public function getValue($field_uid, $document_uid, $widget_value = NULL, $field_info = [])
  {
    if ($widget_value === NULL) {
      $sql = ""
        . "SELECT fvfr.file_uid FROM field_value_file_relation fvfr "
        . "LEFT JOIN (SELECT file_uid, date_added FROM field_value_file_list AS fvfl1 "
        . "WHERE fvfl1.version=(SELECT MAX(version) FROM field_value_file_list AS fvfl2 "
        . "WHERE status='1' AND fvfl1.file_uid=fvfl2.file_uid)) fvfl ON fvfr.file_uid=fvfl.file_uid "
        . "WHERE "
        . "fvfr.document_uid='" . $this->db->escape($document_uid) . "' AND "
        . "fvfr.field_uid='" . $this->db->escape($field_uid) . "' "
        . "ORDER BY fvfl.date_added ASC";
      $query = $this->db->query($sql);
      if ($query->num_rows) {
        $result = array();
        foreach ($query->rows as $file) {
          $result[] = $file['file_uid'];
        }
        return implode(",", $result);
      }
    } else {
      if (is_array($widget_value)) {
        return implode(",", $widget_value);
      }
      return $widget_value;
    }
  }

  /**
   * Файл загружен пользователем через форму документа
   * @param type $document_uid
   * @param type $field_uid
   * @param type $file_name
   * @param type $size
   * @param type $token
   * @return type
   */
  public function addFile($field_uid, $file_name, $size, $token, $file_uid = '')
  {
    $version = $this->getFileVersion($file_uid);
    if ($version == 0) {
      $version = 1;
      //$status = 0;
    } else {
      //$status = 2;
      $version++;
    }
    if (!$file_uid) {
      $query_uid = $this->db->query("SELECT UUID() as uid");
      $file_uid = $query_uid->row['uid'];
    }


    $this->db->query(
      "INSERT INTO " . DB_PREFIX . "field_value_file_list SET "
        . "file_uid = '" . $file_uid . "', "
        . "version = '" . $version . "', "
        . "field_uid = '" . $this->db->escape($field_uid) . "', "
        . "file_name = '" . $this->db->escape($file_name) . "', "
        . "size = '" . (int) $size . "', "
        . "token = '" . $this->db->escape($token) . "', "
        . "date_added = NOW(), "
        . "user_uid = '" . $this->customer->getStructureId() . "'"
      //. "status = '" . $status . "'"
    );
    return array('file_uid' => $file_uid, 'version' => $version);
  }

  public function getFileVersion($file_uid)
  {
    $version = 0;
    if ($file_uid) {
      $query_version = $this->db->query("SELECT version FROM `field_value_file_list` WHERE file_uid='" . $this->db->escape($file_uid) . "' ORDER BY version DESC LIMIT 1");
      if ($query_version->num_rows) {
        $version = $query_version->row['version'];
        $version + 1;
      }
    }
    return $version;
  }



  public function getFile($file_uid, $lastsaved = false)
  {
    if ($lastsaved) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_file_list WHERE file_uid = '" . $this->db->escape($file_uid) . "' AND status='1' ORDER BY version DESC LIMIT 1");
    } else {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_file_list WHERE file_uid = '" . $this->db->escape($file_uid) . "' ORDER BY version DESC LIMIT 1");
    }

    return $query->row;
  }

  public function getFiles($file_uids)
  {
    $result = array();
    if (is_array($file_uids)) {
      foreach ($file_uids as $file_uid) {
        $file_info = $this->getFile($file_uid, true);
        if ($file_info) {
          $result[] = $file_info;
        }
      }
    } else {
      $file_info = $this->getFile($file_uids, true);
      if ($file_info) {
        $result[] = $file_info;
      }
    }
    return $result;

    /* if (is_array($file_uids)) {
      $files = $this->db->escape(implode(",", $file_uids));
      $files = str_replace(",", "','", $files);
      } else {
      $files = str_replace(",", "','", $this->db->escape($file_uids));
      }
      if ($files) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_file_list WHERE file_uid IN ('" . $files . "') ");
      return $query->rows;
      } */
  }

  public function refreshDisplayValues($data)
  {
  }

  /**
   * Метод проверки права доступа текущего пользователя к файлу
   * @param type $file_uid
   */
  public function hasAccess($file_uid, $structure_uid = '')
  {
    if (!$structure_uid) {
      $structure_ids = $this->customer->getStructureIds();
    } else {
      //проверяем, не заблокрирован ли пользователь
      $sql = "SELECT usr.value AS blocked FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_type')) . " str INNER JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_status_type')) . " usr ON (usr.document_uid = str.value AND usr.field_uid = '" . $this->db->escape($this->config->get('user_field_status_id')) . "') WHERE str.document_uid = '" . $this->db->escape($structure_uid) . "'";
      $customer_query = $this->db->query($sql);
      if ($customer_query->num_rows && $customer_query->row['blocked'] === '1') {
        //пользователь заблокирован 
        return false;
      }
      $structure_ids = $this->getParents($structure_uid);
      $structure_ids[] = $structure_uid;
    }
    $query = $this->db->query(
      "SELECT * FROM " . DB_PREFIX . "document d WHERE "
        . "document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "field_value_file_relation "
        . "                 WHERE file_uid = '" . $this->db->escape($file_uid) . "') "
    );
    if (!$query->num_rows) {
      $query = $this->db->query("SELECT * FROM document d WHERE document_uid IN (SELECT document_uid FROM field_value_file WHERE value LIKE ('%" . $this->db->escape($file_uid) . "%')) ");
    }
    // print_r($query->rows);
    foreach ($query->rows as $dRow) {
      $accecced_author_uids = $this->daemon->exec("GetUserAccessAuthors", ["doctypeUID" => $dRow['doctype_uid'], "userSUID" => $this->customer->getStructureId()]);
      $accecced_authors[$this->customer->getStructureId()] = 1;
      // print_r($accecced_author_uids);
      // echo "----";
      foreach ($accecced_author_uids as $uid) {
        if ($uid == $dRow['author_uid']) {
          return true;
        }
      }
      $query_perm = $this->db->query("SELECT document_uid FROM document_access WHERE doctype_uid = '" . $dRow['doctype_uid'] . "' AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "')");
      foreach ($query_perm->rows as $row) {
        if ($row['document_uid'] == $dRow['document_uid']) {
          return true;
        }
      }
    }
    // exit;

    return false;
  }

  private function getParents($structure_id)
  {
    $result = array();
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_type')) . " WHERE field_uid = '" . $this->config->get('structure_field_parent_id') . "' AND document_uid = '" . $this->db->escape($structure_id) . "' ");
    if ($query->num_rows) {
      if ($query->row['value'] && $query->row['value'] !== $structure_id) { //$query->row['value'] !== $structure_id - в качестве родителя указали текущий элемент
        $result[] = $query->row['value'];
        $result = array_merge($result, $this->getParents($query->row['value']));
      }
    }
    return $result;
  }

  public function removeValues($field_uid)
  {
    $query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "field_value_file WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
    foreach ($query->rows as $document) {
      $this->removeValue($field_uid, $document['document_uid']);
    }
  }

  /**
   * Метод, запускаемый при удалении документа
   * @param type $field_uid
   * @param type $document_uid
   */
  public function removeValue($field_uid, $document_uid)
  {
    $query_files = $this->db->query("SELECT file_uid FROM " . DB_PREFIX . "field_value_file_relation WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
    if ($query_files->num_rows) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_file_relation WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid = '" . $this->db->escape($document_uid) . "' ");
      foreach ($query_files->rows as $file) {
        $this->removeFile($file['file_uid']);
      }
    }
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_file WHERE field_uid='" . $this->db->escape($field_uid) . "' AND document_uid='" . $this->db->escape($document_uid) . "' ");
  }

  /**
   * Удаление файла. 
   * @param type $file_uid
   */
  public function removeFile($file_uid)
  {
    $query_relation = $this->db->query("SELECT file_uid FROM " . DB_PREFIX . "field_value_file_relation WHERE file_uid = '" . $this->db->escape($file_uid) . "' ");
    if (!$query_relation->num_rows) {
      //связей нет, можно удалять  
      $query_file = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_file_list WHERE file_uid = '" . $this->db->escape($file_uid) . "' ");
      if ($query_file->num_rows) {
        $filename = DIR_DOWNLOAD . "field_file/" . $query_file->row['field_uid'] . date('/Y/m/', strtotime($query_file->row['date_added'])) . $query_file->row['token'] . $query_file->row['file_name'];
        if (file_exists($filename)) {
          unlink($filename);
        }
        $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_file_list WHERE file_uid = '" . $this->db->escape($file_uid) . "' ");
      }
    }
  }

  public function install()
  {
    $this->load->model("tool/utils");
    if ($this->model_tool_utils->isTable("field_value_file")) {
      return;
    }
    //создаем таблицу поля
    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_file ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` VARCHAR(255) NOT NULL, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_file ADD PRIMARY KEY field_uid (field_uid,document_uid)");
    $this->db->query("ALTER TABLE `field_value_file` ADD INDEX( `value`);");
    $this->db->query("ALTER TABLE `field_value_file` ADD INDEX( `display_value`);");
    $this->db->query("ALTER TABLE `field_value_file` ADD INDEX( `time_changed`);");

    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_file_list ( `file_uid` VARCHAR(36) , `version` INT(11), `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `file_name` VARCHAR(256) NOT NULL , `size` INT NOT NULL, `token` VARCHAR(32) NOT NULL , `date_added` DATETIME NOT NULL , `user_uid` INT NOT NULL, `status` TINYINT NOT NULL, PRIMARY KEY (file_uid, version), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE `field_value_file_list` ADD INDEX( `field_uid`);");
    $this->db->query("ALTER TABLE `field_value_file_list` ADD INDEX( `document_uid`);");
    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET code='dv_field_file', `key`='config_file_ext_allowed', value='zip,txt,png,jpe,jpeg,jpg,gif,bmp,ico,tiff,tif,svg,svgz,zip,rar,msi,cab,mp3,qt,mov,pdf,psd,ai,eps,ps,doc,xls,docx,xlsx,odt,dot,htm,html,ppt,pptx,rtf,xlsm', serialized=0");

    $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_file_relation ( `file_uid` VARCHAR(36), `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36), PRIMARY KEY (file_uid)) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");

    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET code='dv_field_file', `key`='config_file_mime_allowed', value='text/plain,image/png,image/jpeg,image/gif,image/bmp,image/tiff,image/svg+xml,application/zip,application/zip,application/x-zip,application/x-zip,application/x-zip-compressed,application/x-zip-compressed,application/rar,application/rar,application/x-rar,application/x-rar,application/x-rar-compressed,application/x-rar-compressed,application/octet-stream,application/octet-stream,audio/mpeg,video/quicktime,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel.sheet.macroEnabled.12,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation', serialized=0");


    $text = "order deny,allow\r\ndeny from all";
    if (!is_dir($this::DIR_FILE_UPLOAD)) {
      mkdir($this::DIR_FILE_UPLOAD);
    }
    $fp = fopen($this::DIR_FILE_UPLOAD . ".htaccess", "w");
    fwrite($fp, $text);
    fclose($fp);
  }

  public function uninstall()
  {
    //удаляем таблицу поля//
    $this->db->query("DROP TABLE " . DB_PREFIX . "field_value_file");
    $this->db->query("DROP TABLE " . DB_PREFIX . "field_value_file_list");
    $this->db->query("DROP TABLE " . DB_PREFIX . "field_value_file_relation");
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code='dv_field_file' AND `key`='config_file_ext_allowed' ");
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code='dv_field_file' AND `key`='config_file_mime_allowed' ");
    $this->load->model("tool/utils");
    $this->model_tool_utils->removeDirectory($this::DIR_FILE_UPLOAD);
  }
}

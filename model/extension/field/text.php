<?php

class ModelExtensionFieldText extends FieldModel
{
  const DIR_FILE_UPLOAD = DIR_DOWNLOAD . "field_text/";

  public function editValue($field_uid, $document_uid, $field_value)
  {
    $row_value = $field_value ? $this->getValue($field_uid, $document_uid, $field_value) : '';
    $field_info = $this->model_doctype_doctype->getField($field_uid);
    if (isset($field_info['params']['editor_enabled']) && $field_info['params']['editor_enabled'] === "true") {
      $display_value = $this->clearDisplay($row_value, 256);
    } else {
      $display_value = mb_substr(strip_tags(str_replace("  ", " ", htmlspecialchars_decode(trim($row_value)))), 0, 256);
    }
    $display_value = str_replace("\n", " ", $display_value);

    $this->db->query("REPLACE INTO " . DB_PREFIX . "field_value_text SET "
      . "field_uid = '" . $this->db->escape($field_uid) . "', "
      . "document_uid = '" . $this->db->escape($document_uid) . "', "
      . "value='" . $this->db->escape($row_value) . "', "
      . "display_value='" . $this->db->escape($display_value) . "', "
      . "time_changed=NOW() ");

    $files = $this->getFilesByField($field_uid, $document_uid);
    if ($files) {
      foreach ($files as $file) {
        if (strpos($row_value, $file['file_uid']) === false) {
          $this->removeFile($file['file_uid']);
        }
      }
      //обновляем статусы (status=0 для обхода доступа к файлам, необходимосго до сохранения документа, после сохранения = 1 навсегда; 
      $this->db->query("UPDATE " . DB_PREFIX . "field_value_text_file SET status=1 WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
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
      // $cache_name = "field_value_" . $document_uid;
      // $cache = $this->cache->get($cache_name, $field_uid);
      // if ($cache) {
      //   return $cache;
      // }
      $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_text WHERE "
        . "field_uid='" . $this->db->escape($field_uid) . "' AND "
        . "document_uid='" . $this->db->escape($document_uid) . "' ");
      $result = html_entity_decode($query->row['value'] ?? "");
      // $this->cache->set($cache_name, $result, $field_uid);
      return $result;
    } else {
      $this->load->model('doctype/doctype');
      if (!$field_info) {
        $field_info = $this->model_doctype_doctype->getField($field_uid);
      }
      if (empty($field_info['params']['editor_enabled'])) {
        return $widget_value;
      } else {
        $purifier_config = HTMLPurifier_Config::createDefault();
        $purifier_config->set('Cache.SerializerPath', DIR_STORAGE . "HTMLPurifier");
        $allowed_tags = array(
          'strong' => TRUE,
          'em' => TRUE,
          'h1' => TRUE,
          'h2' => TRUE,
          'h3' => TRUE,
          'h4' => TRUE,
          'h5' => TRUE,
          'h6' => TRUE,
          'ul' => TRUE,
          'ol' => TRUE,
          'li' => TRUE,
          'img' => TRUE,
          'a' => TRUE,
          'p' => TRUE,
          'div' => TRUE,
          'span' => TRUE,
          'font' => TRUE,
          'b' => TRUE,
          'i' => TRUE,
          's' => TRUE,
          'strike' => TRUE,
          'u' => TRUE,
          'br' => TRUE,
          'hr' => TRUE,
          'table' => TRUE,
          'tbody' => TRUE,
          'thead' => TRUE,
          'tfoot' => TRUE,
          'th' => TRUE,
          'tr' => TRUE,
          'td' => TRUE,
          'blockquote' => TRUE,
          'code' => TRUE,
          'iframe' => TRUE,
        );
        $allowed_attrs = array(
          '*.style' => TRUE,
          '*.title' => TRUE,
          '*.color' => TRUE,
          'a.href' => TRUE,
          'a.target' => TRUE,
          'a.document_uid' => TRUE,
          'a.data-document_uid' => TRUE,
          'img.src' => TRUE,
          'img.width' => TRUE,
          'img.height' => TRUE,
          'img.data-filename' => TRUE,
          '*.class' => TRUE,
          'iframe.src' => TRUE,
          'iframe.width' => TRUE,
          'iframe.height' => TRUE,
          'iframe.frameborder' => TRUE,
          'iframe.allow' => TRUE,
          'iframe.allowfullscreen' => TRUE
        );

        $purifier_config->set('HTML.AllowedElements', $allowed_tags);
        $purifier_config->set('HTML.AllowedAttributes', $allowed_attrs);
        $purifier_config->set('URI.MungeResources', true);
        $purifier_config->set('HTML.SafeIframe', true);
        $purifier_config->set('HTML.TargetNoreferrer', false);
        $purifier_config->set('HTML.TargetNoopener', false);
        $purifier_config->set('URI.SafeIframeRegexp', "#^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)#");

        $purifier_config->set('URI.AllowedSchemes', array('data' => true, 'http' => true, 'https' => true, 'mailto' => true, 'tel' => true)); //data - для изображений, вставленных клипборд в редактор
        $def = $purifier_config->getHTMLDefinition(true);
        $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
        $def->addAttribute('a', 'href', 'Text');
        $def->addAttribute('a', 'document_uid', 'Text');
        $def->addAttribute('a', 'data-document_uid', 'Text');
        $def->addAttribute('img', 'data-filename', 'Text');
        $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
        $def->addAttribute('iframe', 'allow', 'Text');

        $purifier = new HTMLPurifier($purifier_config);
        $val = htmlspecialchars_decode($widget_value);
        $res = $purifier->purify($val);
        return htmlspecialchars(str_ireplace(["<br />", "javascript:"], ["<br>", ""], $res));
      }
    }
  }

  public function removeValue($field_uid, $document_uid)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_text WHERE field_uid='" . $this->db->escape($field_uid) . "' AND document_uid='" . $this->db->escape($document_uid) . "' ");
    foreach ($this->getFilesByField($field_uid, $document_uid) as $file) {
      $this->removeFile($file['file_uid']);
    }
  }

  public function removeValues($field_uid)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_text WHERE field_uid = '" . $this->db->escape($field_uid) . "' ");
    foreach ($query->rows as $row) {
      $this->removeValue($row['field_uid'], $row['document_uid']);
    }
  }

  public function refreshDisplayValues($data)
  {
  }

  public function appendLogValue($field_uid, $document_uid, $log)
  {
    $this->load->model('doctype/doctype');
    $this->load->language('document/document');
    $value = implode(" ", $log);
    $cr = "<br>\n";
    $row_value = $this->getValue($field_uid, $document_uid, $value);
    $value = $this->getValue($field_uid, $document_uid);
    if ($value) {
      $row_value = $value . $cr . $row_value;
    }
    $this->model_document_document->editFieldValue($field_uid, $document_uid, $row_value);
  }

  public function install()
  {
    $this->load->model('tool/utils');
    if (!$this->model_tool_utils->isTable('field_value_text_file')) {
      $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_text_file ( `file_uid` VARCHAR(36) , `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `file_name` VARCHAR(256) NOT NULL , `size` INT NOT NULL, `token` VARCHAR(32) NOT NULL , `date_added` DATETIME NOT NULL , `user_uid` INT NOT NULL, `status` TINYINT NOT NULL, PRIMARY KEY (file_uid) ) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text_file ADD INDEX( `field_uid`);");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text_file ADD INDEX( `document_uid`);");
    }
    if (!$this->model_tool_utils->isTable('field_value_text')) {
      $this->db->query("CREATE TABLE " . DB_PREFIX . "field_value_text  ( `field_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `value` MEDIUMTEXT, `display_value` VARCHAR(255), `time_changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8mb4_unicode_ci;");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text  ADD PRIMARY KEY field_uid (field_uid,document_uid)");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text  ADD INDEX( `value`(250));");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text  ADD INDEX( `display_value`);");
      $this->db->query("ALTER TABLE " . DB_PREFIX . "field_value_text  ADD INDEX( `time_changed`);");
    }
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
    //удаляем файлы
    $query_files = $this->db->query("SELECT file_uid FROM " . DB_PREFIX . "field_value_text_file");
    foreach ($query_files->rows as $file) {
      $this->removeFile($file['file_uid']);
    }
    //удаляем таблицы поля
    $this->load->model('tool/utils');
    foreach (array('field_value_text ', 'field_value_text_file') as $table) {
      if ($this->model_tool_utils->isTable($table)) {
        $this->db->query("DROP TABLE " . $table);
      }
    }
    $this->load->model("tool/utils");
    $this->model_tool_utils->removeDirectory($this::DIR_FILE_UPLOAD);
  }

  public function addFile($document_uid, $field_uid, $file_name, $size, $token)
  {
    $query_uid = $this->db->query("SELECT UUID() as uid");
    $file_uid = $query_uid->row['uid'];
    $this->db->query("INSERT INTO " . DB_PREFIX . "field_value_text_file SET "
      . "file_uid = '" . $file_uid . "', "
      . "field_uid = '" . $this->db->escape($field_uid) . "', "
      . "document_uid = '" . $this->db->escape($document_uid) . "', "
      . "file_name = '" . $this->db->escape($file_name) . "', "
      . "size = '" . (int) $size . "', "
      . "token = '" . $this->db->escape($token) . "', "
      . "date_added = NOW(), "
      . "user_uid = ' " . $this->customer->getStructureId() . "', "
      . "status = 0");
    return $file_uid;
  }

  public function getFile($file_uid)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_text_file WHERE file_uid = '" . $this->db->escape($file_uid) . "'");
    return $query->row;
  }


  public function getFilesByField($field_uid, $document_uid)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_text_file WHERE field_uid = '" . $this->db->escape($field_uid) . "' AND document_uid='" . $this->db->escape($document_uid) . "' ");
    return $query->rows;
  }

  public function removeFile($file_uid)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "field_value_text_file WHERE "
      . "file_uid = '" . $this->db->escape($file_uid) . "' ");
    if ($query->num_rows) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "field_value_file_list WHERE "
        . "file_uid = '" . $this->db->escape($file_uid) . "' ");
      //проверяем наличие других записей для текущего файла
      $query_files = $this->db->query("SELECT file_uid FROM " . DB_PREFIX . "field_value_text_file WHERE 	"
        . "file_name = '" . $query->row['file_name'] . "' AND "
        . "size = '" . $query->row['size'] . "' AND "
        . "token = '" . $query->row['token'] . "' ");
      if (!$query_files->num_rows) {
        unlink(DIR_DOWNLOAD . $this::DIR_FILE_UPLOAD . $query->row['field_uid'] . date('/Y/m/', strtotime($query->row['date_added'])) . $query->row['token'] . $query->row['file_name']);
      }
    }
  }

  public function hasAccess($file_uid)
  {
    $query = $this->db->query("SELECT * FROM document d WHERE document_uid IN (SELECT document_uid FROM field_value_text_file WHERE file_uid = '" . $this->db->escape($file_uid) . "') "
      // . "AND ("
      // . " (SELECT document_uid FROM " . DB_PREFIX . "document_access WHERE document_uid = d.document_uid AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 1) IS NOT NULL "
      // . " OR "
      // . " (SELECT object_uid FROM " . DB_PREFIX . "matrix_doctype_access WHERE (object_uid = d.author_uid OR object_uid = d.department_uid) AND doctype_uid = d.doctype_uid AND subject_uid IN ('" . implode("','", $this->customer->getStructureIds()) . "') LIMIT 1) IS NOT NULL "
      // . ")"
    );

    foreach ($query->rows as $dRow) {
      $accecced_author_uids = $this->daemon->exec("GetUserAccessAuthors", ["doctypeUID" => $dRow['doctype_uid'], "userSUID" => $this->customer->getStructureId()]);
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
    return false;
  }

  public function get_ftsearch_index($field_uid, $document_uid)
  {
    $value = $this->getValue($field_uid, $document_uid);
    if ($value) {
      return $this->clearDisplay($value);
    }
    return "";
  }

  private function clearDisplay($text, $length = 0)
  {
    $text = trim($text);
    $tag_spacing_search = array("</p>", "<br>", "<hr>", "</div>", "</li>", "</h1>", "</h2>", "</h3>", "</h4>", "</h5>", "</h6>", "</table>", "</td>", "</blockquote>", "</code>", "__amprsnd__nbsp;", "__amprsnd__");
    $tag_spacing_replace = array("</p> ", "<br> ", "<hr> ", "</div> ", "</li> ", "</h1> ", "</h2> ", "</h3> ", "</h4> ", "</h5> ", "</h6> ", "</table> ", "</td> ", "</blockquote> ", "</code> ", "&nbsp; ", "&");
    $cl_text = strip_tags(str_replace("  ", " ", str_replace($tag_spacing_search, $tag_spacing_replace, htmlspecialchars_decode($text))));
    if ($length) {
      return mb_substr($cl_text, 0, $length);
    } else {
      return $cl_text;
    }
  }
}

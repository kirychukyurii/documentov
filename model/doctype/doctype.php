<?php

class ModelDoctypeDoctype extends Model
{

  private $fields = ['0' => [], '1' => []];
  private $routes = [];
  private $route_actions = [];


  //************************************************ */
  //**********  ТИПЫ ДОКУМЕНТОВ ******************** */
  //************************************************ */

  public function addDoctype()
  {
    $authorSUID = $this->customer->getStructureId();
    $doctype = $this->daemon->exec("NewDoctype", $authorSUID);
    if ($doctype !== null) {
      return $doctype['uid'];
    }
    $this->redirect();
  }

  public function editDoctype($doctype_uid, $data)
  {
    $this->routes = [];
    $this->fields = ['0' => [], '1' => []];
    $this->daemon->exec("SaveDoctype", $doctype_uid);
    $this->cache->delete($doctype_uid, $doctype_uid, true);
    return;
  }

  public function deleteDoctype($doctype_uid)
  {
    $result = $this->daemon->exec("DeleteDoctype", $doctype_uid);
    if ($result === null) {
      $this->redirect();
      return;
    }
    return $result;
  }

  public function copyDoctype($doctype_uid)
  {
    $new_doctype_uid = $this->daemon->exec("CopyDoctype", $doctype_uid);

    if ($doctype_uid === null) {
      $this->redirect();
    }
    return $new_doctype_uid;
  }

  public function getDocuments($data)
  {
    $cache_key = "documents_" . md5(json_encode($data));
    $cache = $this->cache->get($cache_key, $data['doctype_uid']);
    if ($cache) {
      return $cache;
    }
    $field_info = $this->getField($data['field_uid']);
    $sql = "SELECT * FROM " . DB_PREFIX . "field_value_" . $field_info['type'] . " WHERE field_uid = '" . $this->db->escape($data['field_uid']) . "' AND document_uid IN "
      . "(SELECT document_uid FROM document WHERE doctype_uid='" . $this->db->escape($data['doctype_uid']) . "') ";
    if (!empty($data['filter_name'])) {
      $sql .= "AND display_value LIKE '%" . $this->db->escape($data['filter_name']) . "%' ";
    }
    $query = $this->db->query($sql);
    $this->cache->set($cache_key, $query->rows, $data['doctype_uid']);
    return $query->rows;
  }

  public function getDoctypes($data)
  {

    $data["draft"] = "1";
    // $data['language_id'] = $language_id;
    foreach ($data as &$d) {
      $d = (string) $d;
    }
    $doctypes = $this->daemon->exec("GetDoctypes", $data);
    if ($doctypes === null) {
      $this->redirect();
      exit;
    }
    $language_id = $this->config->get('config_language_id');
    foreach ($doctypes as &$doctype) {
      $doctype['doctype_uid'] = $doctype['uid'];
      $doctype['name'] = $doctype['description'][$language_id]['name'] ?? "";
      $doctype['short_description'] = $doctype['description'][$language_id]['shortDescription'] ?? "";
      $doctype['title_field_uid'] = $doctype['description'][$language_id]['titleFieldUID'] ?? "";
    }

    return $doctypes;
  }

  /**
   * Возвращает параметры доктайпа и описание с учетом текущего языка, если установлен draft=true, то с учетом сохраненного черновика
   * @param type $doctype_uid
   * @param type $draft
   * @return type
   */
  public function getDoctype($doctype_uid, $draft = false)
  {
    if (!$doctype_uid) {
      return [];
    }
    $data = [
      'uid' => $doctype_uid,
      'draft'       => $draft ? 1 : 0,
    ];

    $doctype_info = $this->daemon->exec("GetDoctype", $data);

    if ($doctype_info === null) {
      $this->redirect();
      exit;
    }

    if (!$doctype_info) {
      return [];
    }

    $language_id = $this->config->get('config_language_id');
    $doctype_info['doctype_uid'] = $doctype_info['uid'];
    $doctype_info['name'] = $doctype_info['description'][$language_id]['name'] ?? "";
    $doctype_info['short_description'] = $doctype_info['description'][$language_id]['shortDescription'] ?? "";
    $doctype_info['title_field_uid'] = $doctype_info['description'][$language_id]['titleFieldUID'] ?? "";
    if (!empty($doctype_info['description'])) {
      foreach ($doctype_info['description'] as &$desc) {
        $desc['short_description'] = $desc['shortDescription'] ?? "";
        $desc['title_field_uid'] = $desc['titleFieldUID'] ?? "";
      }
    }

    return $doctype_info;
  }

  /**
   * Возвращает true, если доктайп существует и false, если его нет
   */
  public function hasDoctype($doctype_uid, $draft = false)
  {
    $data = [
      'uid' => $doctype_uid,
      'draft'       => $draft ? 1 : 0,
    ];

    return $this->daemon->exec("HasDoctype", $data);
  }

  /**
   * Метод возвращает название типа документа с учетом текущего языка 
   */
  public function getDoctypeName($doctype_uid)
  {
    $doctype_info = $this->getDoctype($doctype_uid);
    return $doctype_info['description'][$this->config->get('config_language_id')]['name'] ?? "";
  }

  /**
   * Метод сохранения некоторых полей доктайпа в фоновом режиме (onBlur)
   */
  public function saveDoctype($doctype_uid, $data)
  {
    if (isset($data['doctype_description'])) {
      $data['description'] = $data['doctype_description'];
      foreach ($data['description'] as &$desc) {
        $desc['shortDescription'] = $desc['short_description'] ?? "";
        $desc['titleFieldUID'] = $desc['title_field_uid'] ?? "";
      }
      unset($data['doctype_description']);
    }
    if (isset($data['doctype_template'])) {
      $data['template_form'] = $data['doctype_template'];
      unset($data['doctype_template']);
    }
    if (isset($data['doctype_template_conditions'])) {
      foreach ($data['doctype_template_conditions'] as $type => $t1) {
        foreach ($t1 as $sort => $t2) {
          foreach ($t2 as $langID => $json) {
            if (!$json) {
              continue;
            }
            $conds = json_decode(html_entity_decode($json), true);
            $conds = array_values($conds);
            foreach ($conds as &$c) {
              $c['condition'] = array_values($c['condition']);
              $c['action'] = array_values($c['action']);
            }
            $data['doctype_template_conditions'][$type][$sort][$langID] = $this->jsonEncode($conds);
          }
        }
      }
      $data['template_form_condition'] = $data['doctype_template_conditions'];
      unset($data['doctype_template_conditions']);
    }
    if (isset($data['params']['doctype_template'])) {
      $data['template_condition'] = $data['params']['doctype_template'];
      unset($data['params']['doctype_template']);
    }
    if (!empty($data['accesses'])) {
      foreach ($data['accesses'] as $access_id => &$access) {
        $access['access_id'] = $access_id;
        $access['draft'] = (int) $access['draft'];
      }
      $data['accesses'] = array_values($data['accesses']);
    }

    $data['uid'] = $doctype_uid;
    $data['delegate_create'] = $data['delegate_create'] ? 1 : 0;

    $this->daemon->exec("SaveDoctypeTemplateDraft", $data);
  }

  public function removeDraft($doctype_uid)
  {
    $this->cache->delete("", "meta_" . $doctype_uid);
    return $this->daemon->exec("DeleteDoctypeDraft", $doctype_uid);
  }

  public function getDoctypeDescriptions($doctype_uid, $draft = false)
  {
    if (!$doctype_uid) {
      return [];
    }
    $doctype_info = $this->getDoctype($doctype_uid, $draft);
    return $doctype_info['description'] ?? "";
  }

  public function getDoctypeTemplates($doctype_uid)
  {
    $result =  [
      'doctype_templates'           => [],
      'doctype_template_conditions' => [],
      'doctype_template_params'     > []
    ];

    $data = [
      "doctype_uid" => $doctype_uid,
      "draft"       => "1",
      "type"        => ""
    ];

    $templates = $this->daemon->exec("GetDoctypeTemplates", $data);


    if (!$templates) {
      return $result;
    }
    // print_r($templates);
    // exit;
    foreach ($templates as $template) {
      if (!isset($result['doctype_templates'][$template['type']])) {
        $result['doctype_templates'][$template['type']] = [];
        $result['doctype_template_conditions'][$template['type']] = [];
      }
      // если есть есть драфтовый шаблон
      $templ = $template['forms'][1] ?? $template['forms'][0] ?? [];
      // print_r($templ);
      // exit;
      foreach ($templ as $lang => $form) {
        $t = [
          $lang => $form['html']
        ];
        if (!isset($result['doctype_templates'][$template['type']])) {
          $result['doctype_templates'][$template['type']] = [];
        }
        if (!isset($result['doctype_templates'][$template['type']][$template['sort']])) {
          $result['doctype_templates'][$template['type']][$template['sort']] = [];
        }

        $result['doctype_templates'][$template['type']][$template['sort']][$lang] = $form["html"];

        if ($template['sort']) { // доп шаблон
          if (!isset($result['doctype_template_params'][$template['type']])) {
            $result['doctype_template_params'][$template['type']] = [];
          }
          if (!isset($result['doctype_template_params'][$template['type']][$template['sort']])) {
            $result['doctype_template_params'][$template['type']][$template['sort']] = [];
          }
          $result['doctype_template_params'][$template['type']][$template['sort']]['params'] = [
            'template_name'         => $template['name'],
            'condition_field_uid'   => $template['condition_field_uid'],
            'condition_field_name'  => 'FIELD NAME',
            'condition_comparison'  => $template['condition_comparison'],
            'condition_value_uid'   => $template['condition_value_uid'],
            'condition_value_name'  => "VALUE NAME"
          ];

          // $result['doctype_template_params'][$template['type']][$template['sort']] = $t['params'];
        }
        // $result['doctype_templates'][$template['type']][$template['sort']] = $t;
        if (!isset($result['doctype_template_conditions'][$template['type']])) {
          $result['doctype_template_conditions'][$template['type']] = [];
        }
        if (!isset($result['doctype_template_conditions'][$template['type']][$template['sort']])) {
          $result['doctype_template_conditions'][$template['type']][$template['sort']] = [];
        }
        $result['doctype_template_conditions'][$template['type']][$template['sort']][$lang] = $form['conditions'] ?? [];
        // $result['doctype_template_conditions'][$template['type']][] = [
        //   $lang => $form['conditions'] ?? []
        // ];
      }
    }
    // echo "________________________________";
    // print_r($result);
    // exit;
    return $result;
  }

  //************************************************ */
  //**********        ПОЛЯ      ******************** */
  //************************************************ */

  private function getStopFieldParams()
  {
    return ['field_name', 'field_type', 'doctype_description'];
  }

  /**
   * Добавление поля данного типа в тип документа. Все данные формы передаются через POST
   * $doctype_uid - тип документа
   * $data - тип поля и его параметры в массиве
   * $draft - 1 - черновик, 0 - нет, 2 - поле было удалено из доктайпа, но доктайп пока не сохранены
   */
  public function addField($doctype_uid, $data)
  {
    // Из поста получаем некоторые данные с префкисом field_ Однако более нигде этот префикс не используется; убираем
    $data['name'] = $data['field_name'];
    $data['type'] = $data['field_type'];
    unset($data['field_type']);

    $params = array();
    $stopParams = $this->getStopFieldParams();
    foreach ($data as $key => $value) {
      if (array_search($key, $stopParams) === FALSE) {
        $params[$key] = $value;
      }
    }
    $field_params = $this->load->controller('extension/field/' . $data['type'] . '/setParams', $params);
    if ($field_params) {
      // $data['params'] = $this->jsonEncode($field_params);
      $data['params'] = $field_params;
    }

    $data['doctype_uid'] = $doctype_uid; // для демона
    $data['required'] = empty($data['required']) ? 0 : 1;
    $data['setting'] = empty($data['setting']) ? 0 : 1;
    $data['change_field'] = empty($data['change_field']) ? 0 : 1;
    $data['unique'] = empty($data['unique']) ? 0 : 1;
    $data['ft_index'] = empty($data['ft_index']) ? 0 : 1;
    $data['history'] = empty($data['history']) ? 0 : 1;
    $data['draft'] = 1;
    $data['sort'] = empty($data['sort']) ? 999999 : $data['sort'];

    foreach (['access_form', 'access_view'] as $access) {
      if (!isset($data[$access])) {
        $data[$access] = [];
      }
      if (!is_array($data[$access])) {
        $data[$access] = explode(",", $data[$access]);
      }
    }

    $result = $this->daemon->exec("AddDoctypeField", $data);
    return $result['uid'];
  }

  /**
   * Сохранение изменного поля данного типа в тип документа. Все данные формы передаются через POST
   * $field_uid - идентификатор поля
   * $data - тип поля и его параметры в массиве
   * $draft - 1 - черновик, 0 - нет
   */
  public function editField($field_uid, $field_params, $draft = 1, $sort = 0)
  {
    $field_uid = $this->db->escape($field_uid);
    if (!$field_uid) {
      return;
    }

    $field_info = $this->getField($field_uid, 1);
    if ($field_params) { // это параметры поля; поле может быть без параметров; при получении data из формы в ней также присутствуют общие параметры поля
      $field_info['name'] = $field_params['field_name'] ?? $field_params['name'] ?? $field_info['name'] ?? "";
      // $params = [];
      unset($field_params['field_name']);
      unset($field_params['doctype_description']);
      foreach ($field_params as $key => $value) {
        if (
          (isset($field_info['params'][$key]) && ($key != "doctype_uid" ||
            ($key == "doctype_uid" && ($field_info['type'] == "tabledoc" || $field_info['type'] == "link"))))
          || ($key == "columns")
          || ($key == "values")
          || ($key == "source_field_uid")
          || ($key == "default_value")
          || ($key == "conditions")
          || ($key == "editor_enabled")
          || ($key == "script_enabled")
          || ($key == "file_extes")
          || ($key == "preview")
          || ($key == "file_mimes")
          || ($key == "execute_route")
          || ($key == "disable_parall")
          || ($key == "inits")
          || ($key == "drillDown")
          || ($key == "moveType")
          || ($key == "templates")
          || ($key == "total")
        ) { // кст. - doctype_uid исп. в ссыл. поле
          $field_info['params'][$key] = $value;
        }
      }
      if ($field_info['type'] == "link" && !isset($field_params['conditions'])) {
        unset($field_info['params']['conditions']);
      }
      $field_info['params'] = $this->load->controller('extension/field/' . $field_info['type'] . '/setParams', $field_info['params']); // необходимо для подготовки данных для передачи в демон
      $field_info['change_field'] = (int) ($field_params['change_field'] ?? $field_info['change_field']);
      $field_info['access_form'] = $field_params['access_form'] ?? $field_info['access_form'];
      $field_info['access_view'] = $field_params['access_view'] ?? $field_info['access_view'];
      $field_info['description'] = $field_params['description'] ?? $field_info['description'];
      $field_info['required'] = (int) ($field_params['required'] ?? $field_info['required']);
      $field_info['unique'] = (int) ($field_params['unique'] ?? $field_info['unique']);
      $field_info['ft_index'] = (int) ($field_params['ft_index'] ?? $field_info['ft_index']);
      $field_info['history'] = (int) ($field_params['history'] ?? $field_info['history']);
    }

    if ($sort) {
      $field_info['sort'] = $sort;
    }

    // $field_info['params'] = $this->jsonEncode($field_info['params']);
    $field_info['draft'] = $draft;
    // print_r($field_info);
    // exit;
    $result = $this->daemon->exec("EditDoctypeField", $field_info);

    $result['field_uid'] = $result['uid'];

    return $result;
  }

  /**
   * Возвращает параметры поля доктайпа. 
   * @param type $field_uid
   * @param type $draft Если =1, то поле возвращается с учетом черновика, если =0, то без черновика
   * @return type
   */
  public function getField($field_uid, $draft = 0, $full_name = false)
  {
    if (!$field_uid) {
      return null;
    }

    if (isset($this->fields[$draft][$field_uid])) {
      return $this->fields[$draft][$field_uid];
    }

    $data = [
      "uid" => $field_uid,
      "draft"     => (int) $draft // из документов драфт будет нулевой
    ];
    if ($full_name) {
      $data['full_name'] = $full_name;
    }

    $field_info = $this->daemon->exec("GetDoctypeField", $data);
    // print_r($field_info);
    // exit;

    if ($field_info === null) {
      $this->redirect();
    }
    if (!isset($field_info['uid'])) {
      return null;
    }

    $field_info['field_uid'] = $field_info['uid'];

    $field_info['params'] = $field_info['field_type'];

    unset($field_info['field_type']);

    if (!empty($field_info['params']) && !empty($field_info['params']['inner_fields'])) {
      foreach ($field_info['params']['inner_fields'] as &$ifld) {
        if (empty($ifld['params']) && !empty($ifld['inner_field_params'])) {
          $ifld['params'] = $ifld['inner_field_params'];
        }
      }
    }

    return $field_info;
  }

  public function getFieldName($field_uid)
  {
    if (!$field_uid) {
      return "";
    }

    $field_info = $this->getField($field_uid);

    if (!$field_info) {
      return null;
    }
    return $field_info['name'];
    if ($field_info['setting']) {
      if (isset($field_info['doctype_description'])) {
        $name = $field_info['doctype_description'][$this->config->get('config_language_id')]['name'] ?? "";
      } else {
        $doctype_description = $this->getDoctypeDescriptions($field_info['doctype_uid']);
        $name = $doctype_description[$this->config->get('config_language_id')]['name'] ?? "";
      }
      $name .= " - " . $field_info['name'];
    } else {
      $name = $field_info['name'];
    }
    return $name;
  }

  /**
   * Возвращает выборку полей по критериям в $data с учетом драфта:
   * doctype_uid - поля заданного типа документа
   * filter_name - имя (часть имени) поля
   * access_view
   * setting - 0-обычные, 1-настроечные, 2-обычные для заданного доктайпа+все настроечные
   * sort, order - сортировка, группировка
   * required
   * system - является ли поле системным
   * limit
   */
  public function getFields($data)
  {
    $data['language_id'] = $this->config->get('config_language_id'); // для демона
    $data['draft'] = "1"; // демон должен вернуть драфтовые поля

    foreach ($data as &$param) {
      if (is_array($param)) {
        $param = implode(",", $param);
        continue;
      }
      $param = (string) $param;
    }
    $fields = $this->daemon->exec("GetDoctypeFields", $data);


    if ($fields !== null) {
      foreach ($fields as &$field) {
        $field['params'] = json_decode($field['params'], true);
        // $field['params_description'] = $field['description'] ? $field['description'] : "none"; // $this->load->controller('extension/field/' . $field['type'] . '/getDescriptionParams', $field['params']);
        $field['field_uid'] = $field['uid'];
      }
      return $fields;
    }

    $this->redirect();
  }

  /**
   * Возвращает простой список полей из таблицы fields, относящихся к doctype_uid
   */
  public function getFieldsList($doctype_uid)
  {
    $data = [
      "doctype_uid" => $doctype_uid,
    ];
    $fields = $this->getFields($data);
    if ($fields !== null) {
      return $fields;
    }
  }

  /**
   * Пометка поля на удаление
   * @param type $field_uid
   */
  public function removeField($field_uid)
  {
    if (!$field_uid) {
      return;
    }
    $field_info = $this->getField($field_uid, 1);

    $this->editField($field_uid, $field_info['params'], 2);
  }

  /**
   * Снятие с поля пометки на удаление
   * @param type $field_uid
   */
  public function undoRemoveField($field_uid)
  {
    if (!$field_uid) {
      return;
    }
    $field_info = $this->getField($field_uid, 1);
    $this->editField($field_uid, $field_info['params'], -1);
  }

  public function editSortField($field_uid, $sort)
  {
    if (!$field_uid) {
      return;
    }
    $field_info = $this->getField($field_uid, 1);
    $this->editField($field_uid, $field_info['params'], 1, $sort);
  }

  /**
   * Метод, возвращающий модули, использующие поле
   */
  public function getUsageField($field_uid)
  {
    if (!$field_uid) {
      return [];
    }
    $result = array();
    $field_uid = $this->db->escape($field_uid);
    $template_field_uid = "f_" . str_replace("-", "", $field_uid);
    $language_id = (int) $this->config->get('config_language_id');

    $query_action = $this->db->query("SELECT dd.name AS doctype, r.doctype_uid, rd.name AS route, ra.context, ra.action 
      FROM " . DB_PREFIX . "route_action ra 
      LEFT JOIN " . DB_PREFIX . "route r ON (r.route_uid = ra.route_uid)
      LEFT JOIN " . DB_PREFIX . "doctype_description dd ON (dd.doctype_uid = r.doctype_uid AND dd.language_id = '" . $language_id . "')
      LEFT JOIN " . DB_PREFIX . "route_description rd ON (rd.route_uid = ra.route_uid AND rd.language_id = '" . $language_id . "')
      WHERE 
        ra.params LIKE '%" . $field_uid . "%' 
        OR
        ra.params LIKE '%" . $template_field_uid . "%' 
      ORDER BY dd.name ASC, r.sort ASC ");
    if ($query_action->num_rows) {
      $result['action'] = $query_action->rows;
    }

    $query_field = $this->db->query("SELECT dd.name AS doctype, f.name AS field 
      FROM " . DB_PREFIX . "field f 
      LEFT JOIN " . DB_PREFIX . "doctype_description dd ON (dd.doctype_uid = f.doctype_uid AND dd.language_id = '" . $language_id . "')
      WHERE 
        f.field_uid != '$field_uid'
        AND (
        f.params LIKE '%" . $field_uid . "%' 
        OR
        f.params LIKE '%" . $template_field_uid . "%' 
        )
      ORDER BY dd.name ASC, f.sort ASC ");
    if ($query_field->num_rows) {
      $result['field'] = $query_field->rows;
    }

    $query_button = $this->db->query("SELECT dd.name AS doctype, fd.name AS folder, r.doctype_uid, fd.folder_uid, rd.name AS route, rbd.name AS button 
      FROM `button` rb 
      LEFT JOIN `route` r ON (r.route_uid = rb.parent_uid)
      LEFT JOIN `doctype_description` dd ON (dd.doctype_uid = r.doctype_uid AND dd.language_id = '$language_id')
      LEFT JOIN `folder_description` fd ON (fd.folder_uid = rb.parent_uid AND fd.language_id='" . $language_id . "')
      LEFT JOIN `route_description` rd ON (rd.route_uid = rb.parent_uid AND rd.language_id = '$language_id')
      LEFT JOIN `button_description` rbd ON (rbd.uid = rb.uid AND rbd.language_id = '$language_id')
      WHERE (
            rb.action_params LIKE '%$field_uid%' 
            OR
            rb.action_params LIKE '%$template_field_uid%' 
            OR
            rb.uid IN (SELECT DISTINCT(`uid`) FROM `button_field` WHERE field_uid='$field_uid')
            )
      ORDER BY dd.name ASC, r.sort ASC ");


    $result['button'] = [];
    $result['f_button'] = [];
    foreach ($query_button->rows as $row) {
      if ($row['doctype']) {
        $result['button'][] = $row;
      } else {
        $result['f_button'][] = $row;
      }
    }


    // $query_folder_button = $this->db->query("SELECT fd.name AS folder, fbd.name AS button FROM `button` fb
    //   LEFT JOIN `button_field` fbf ON (fbf.uid = fb.uid)
    //   LEFT JOIN folder_description fd ON (fd.folder_uid = fb.parent_uid AND fd.language_id='" . $language_id . "')
    //   LEFT JOIN button_description fbd ON (fbd.uid= fb.uid AND fbd.language_id='" . $language_id . "')
    //   WHERE 
    //   fbf.field_uid = '" . $field_uid . "' 
    //   OR
    //   fb.action_params LIKE '%" . $field_uid . "%'
    //   OR
    //   fb.action_params LIKE '%" . $template_field_uid . "%' 
    //   ORDER BY fd.name ASC, fb.sort ASC");
    // if ($query_folder_button->num_rows) {
    //   $result['f_button'] = $query_folder_button->rows;
    // }

    $query_templates = $this->db->query("SELECT DISTINCT(`template_uid`) FROM `template_form` WHERE `html` LIKE '%$template_field_uid%' ");
    $template_uids = [];
    foreach ($query_templates->rows as $row) {
      $template_uids[] = $row['template_uid'];
    }
    $sql = "SELECT DISTINCT   dd.name AS doctype, dt.doctype_uid, dt.type, dt.sort, dt.template_name
    FROM " . DB_PREFIX . "doctype_template dt 
    LEFT JOIN " . DB_PREFIX . "doctype d ON (d.doctype_uid = dt.doctype_uid)
    LEFT JOIN " . DB_PREFIX . "doctype_description dd ON (dd.doctype_uid = dt.doctype_uid AND dd.language_id = '" . $language_id . "')
    WHERE 
      dt.template_uid IN ('" . implode("','", $template_uids) . "') ";
    $sql .= "ORDER BY dd.name ASC, dt.sort ASC";

    $query_template = $this->db->query($sql);
    if ($query_template->num_rows) {
      $result['template'] = $query_template->rows;
    }

    $query_folder_field = $this->db->query("SELECT ff.is_grouping AS 'grouping', ff.tcolumn, ff.grouping_name, ff.tcolumn_name, fd.name AS folder 
      FROM " . DB_PREFIX . " folder_field ff 
      LEFT JOIN folder_description fd ON (fd.folder_uid = ff.folder_uid AND fd.language_id='" . $language_id . "') 
      WHERE field_uid = '" . $field_uid . "' 
      ORDER BY fd.name ASC");
    if ($query_folder_field->num_rows) {
      $result['f_field'] = $query_folder_field->rows;
    }



    $query_folder_filter = $this->db->query("SELECT ff.action, fd.name AS folder FROM folder_filter ff
      LEFT JOIN folder_description fd ON (fd.folder_uid = ff.folder_uid AND fd.language_id='" . $language_id . "')
      WHERE 
      ff.field_uid = '" . $field_uid . "' 
      ORDER BY fd.name ASC");
    if ($query_folder_filter->num_rows) {
      $result['f_filter'] = $query_folder_filter->rows;
    }

    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable("folder_card_template")) {
      $query_folder_template = $this->db->query("SELECT fd.name AS folder FROM folder_card_template fct
      LEFT JOIN folder_description fd ON (fd.folder_uid = fct.folder_uid AND fd.language_id='" . $language_id . "')
      WHERE 
      fct.template_header LIKE '%" . $template_field_uid . "%'
      OR
      fct.template_card LIKE '%" . $template_field_uid . "%'
      OR
      fct.template_footer LIKE '%" . $template_field_uid . "%'
      ORDER BY fd.name ASC");
      if ($query_folder_template->num_rows) {
        $result['f_template'] = $query_folder_template->rows;
      }
    }
    return $result;
  }


  //************************************************ */
  //**********      МАРШРУТ     ******************** */
  //************************************************ */  

  public function getRoute($route_uid)
  {
    if (!$route_uid) {
      return [];
    }
    if (isset($this->routes[$route_uid])) {
      return $this->routes[$route_uid];
    }
    $route = $this->daemon->exec("GetDoctypeRoute", $route_uid);

    if ($route === null) {
      $this->redirect();
      return;
    }
    if (!$route['uid']) {
      return [];
    }
    $route['route_uid'] = $route['uid'];
    $route['name'] = $route['description'][$this->config->get('config_language_id')]['name'] ?? "";
    $route['actions'] = $this->getRouteActions($route['route_uid']);
    $this->routes[$route_uid] = $route;
    return $route;
  }


  public function getRoutes($data)
  {
    $data['language_id'] = $this->config->get('config_language_id'); // для демона
    $routes = $this->daemon->exec("GetDoctypeRoutes", $data);

    if ($routes === null) {
      $this->redirect();
    }
    foreach ($routes as &$route) {
      $route['route_uid'] = $route['uid'];
      $route['actions'] = $this->getRouteActions($route['route_uid']);
      $route['name'] = $route['description'][$this->config->get('config_language_id')]['name'] ?? "";
      $this->routes[$route['route_uid']] = $route;
    }

    return $routes;
  }

  public function getRouteDescriptions($route_uid)
  {
    return $this->getRoute($route_uid)['description'] ?? "";
  }

  public function addRoute($doctype_uid, $data, $draft = 1)
  {
    $data['doctype_uid'] = $doctype_uid;
    $data['description'] = $data['route_descriptions'];
    unset($data['route_descriptions']);

    $route = $this->daemon->exec("AddDoctypeRoute", $data);

    return $route['uid'] ?? "";

    if ($route != null) {
      return $route['uid'];
    }

    $this->redirect();
  }

  public function editRoute($route_uid, $data, $draft = 1)
  {
    $data['uid'] = $route_uid;
    if (isset($data['route_descriptions'])) {
      $data['description'] = $data['route_descriptions'];
      unset($data['route_descriptions']);
    }
    $data['draft'] = $draft;
    $result = $this->daemon->exec("EditDoctypeRoute", $data);
    $result['route_uid'] = $result['uid'];
    $this->routes[$route_uid] = $result;
  }

  //************************************************ */
  //**********      ДЕЙСТВИЯ      ****************** */
  //************************************************ */  

  // getRouteActions возвращает дейстивия заданной точки (контекста) в черновиках 
  public function getRouteActions($route_uid, $context = '')
  {
    if (isset($this->route_actions[$route_uid])) {
      if ($context) {
        return $this->route_actions[$route_uid][$context];
      }
      return $this->route_actions[$route_uid];
    }
    $route_uid = $this->db->escape($route_uid);
    if (!$route_uid) {
      return [];
    }
    $data = [
      'route_uid' => $route_uid,
      'context'   => $context,
      'draft'     => "1"
    ];
    $route_actions = $this->daemon->exec("GetRouteActions", $data);
    $this->route_actions[$route_uid] = $route_actions;


    if ($context) {
      foreach ($route_actions[$context] as &$action) {
        $action['action_uid'] = $action['uid'];
        $action['params'] = $action['action_type'];
        $action['route_action_uid'] = $action['uid'];
      }
      return $route_actions[$context];
    }

    foreach ($route_actions as &$context) {
      foreach ($context as &$action) {
        $action['action_uid'] = $action['uid'];
        $action['params'] = $action['action_type'];
        $action['route_action_uid'] = $action['uid'];
      }
    }

    return $route_actions;
  }

  // getRouteAction возвращает действие с черновыми параметрами
  public function getRouteAction($route_action_uid)
  {
    $route_action_uid = $this->db->escape($route_action_uid);
    if (!$route_action_uid) {
      return [];
    }
    $data = [
      'uid'  => $route_action_uid,
    ];
    $action = $this->daemon->exec("GetRouteAction", $data);

    if ($action === null) {
      $this->redirect();
    }

    if (!$action) {
      return [];
    }
    if ($action['action'] == "condition") {
      if (!empty($action['action_type']['inner_actions_true'])) {
        foreach ($action['action_type']['inner_actions_true'] as &$a) {
          $a['params'] = $a['action_type'] ?? $a['params'] ?? [];
        }
      }
      if (!empty($action['action_type']['inner_actions_false'])) {
        foreach ($action['action_type']['inner_actions_false'] as &$a) {
          $a['params'] = $a['action_type'] ?? $a['params'] ?? [];
        }
      }
    }
    $action['action_uid'] = $action['uid'];
    $action['action_params'] = $action['action_type'];

    return $action;
  }

  /**
   * Добавление действия в маршрут. Все данные формы передаются через POST
   * $route_uid - точка маршрута
   * $data - параметры действия
   * $draft - 1 - черновик, 0 - нет, 2 - действие было удалено из доктайпа, но доктайп пока не сохранен, 3- действие добавлено и ни разу не сохранялось
   */
  public function addRouteAction($route_uid, $context, $data)
  {
    $route_uid = $this->db->escape($route_uid);
    $context = $this->db->escape($context);
    if (!$route_uid || !$context) {
      return;
    }
    if (!empty($data['action']['method_params']['FUID'])) {
      $data['params'] = $data['action'];
    } else {
      if ($data['route_action']) {
        $data_params = array(
          'route_uid' => $route_uid,
          'params' => $data
        );
        $data['params'] = $this->load->controller('extension/action/' . $data['route_action'] . '/setParams', $data_params);
      } else {
        $data['params'] = [];
      }
    }

    $data_daemon = [
      'route_uid'         => $route_uid,
      'context'           => $context,
      'draft'             => 1,
      'action'            => $data['route_action'],
      'action_log'        => (int) ($data['action_log'] ?? 0),
      // 'params'            => $this->jsonEncode($data['params']),
      'params'            => $data['params'],
      'description'       => $data['action_description'] ?? "",
      'status'            => $data['action_status'] ?? 1,
    ];
    $action = $this->daemon->exec("AddRouteAction", $data_daemon);
    return $action['uid'];
  }

  /**
   * Изменение действия маршрута. 
   * $draft - 1 - черновик, 0 - нет, 2 - кнопка была удалена из доктайпа, но доктайп пока не сохранен
   */
  public function editRouteAction($route_action_uid, $action_params, $draft = 1, $sort = 0)
  {
    $route_action_uid = $this->db->escape($route_action_uid);
    if (!$route_action_uid) {
      return;
    }

    // сначала получаем данные о существующих параметрах, чтобы в случае неполучения их в $params сохранить прежние, а не пустоту
    $action_info = $this->getRouteAction($route_action_uid);

    // актуализируем полученные параметры действия
    if ($action_params) { // если есть переданные параметры (если изм. сортировка, то их может не быть)
      $params = array(
        'route_action_uid' => $route_action_uid,
        'params' => $action_params
      );
      $action_info['params'] = $this->load->controller('extension/action/' . $action_info['action'] . '/setParams', $params);
    } else {
      $action_info['params'] = $action_info['action_params'];
    }
    // $action_info['params'] = $this->jsonEncode($action_info['params']);
    unset($action_info['action_params']);
    unset($action_info['action_type']);

    if ($sort) {
      $action_info['sort'] = $sort;
    }
    // названия во вью и в БД отличаются...
    $action_info['description'] = $action_params['action_description'] ?? $action_params['description'] ?? $action_info['description'];
    $action_info['action_log'] = (int) ($action_params['action_log'] ?? $action_info['action_log']);
    $action_info['action'] = $action_params['route_action'] ?? $action_params['action'] ?? $action_info['action'];
    $action_info['status'] = $action_params['action_status'] ?? $action_info['status'];
    $action_info['draft'] = $draft;


    $result = $this->daemon->exec("EditRouteAction", $action_info);

    if ($result !== null) {
      return;
    }

    $this->redirect();
  }

  public function editStatusRouteAction($route_action_uid, $status)
  {
    $data = [
      'actionUID' => $route_action_uid,
      'status' => $status ? "1" : "0"
    ];

    $this->daemon->exec("EditRouteActionStatus", $data);
  }

  public function editSortRouteAction($action_uid, $sort)
  {

    $data = [
      'action_uid' => $action_uid,
      'sort' => (string)$sort,
    ];
    $this->daemon->exec("EditActionSort", $data);
  }

  // public function editSortRouteAction($route_action_uid, $sort)
  // {
  //   $action_info = $this->getRouteAction($route_action_uid);

  //   if ($action_info['action'] == "condition") {
  //     foreach (["true", "false"] as $t) {
  //       if (isset($action_info['action_params']['inner_actions_' . $t])) {
  //         foreach ($action_info['action_params']['inner_actions_' . $t] as &$a) {
  //           $a['params'] = $a['action_type'];
  //           unset($a['action_type']);
  //         }
  //       }
  //     }
  //   }
  // }

  /**
   * Возвращает список действия доктайпа
   * @param type $doctype_uid
   */
  public function getListRouteActions($doctype_uid)
  {
    $result = [];
    $routes = $this->daemon->exec("GetDoctypeRoutes", ['doctype_uid' => $doctype_uid, 'language_id' => $this->config->get('config_language_id')]);
    if ($routes === null) {
      $this->redirect();
    }

    foreach ($routes as $route) {
      $route_uid = $route['uid'];
      $data = [
        "route_uid" => $route_uid
      ];
      $result[] = [];
      $route_actions = $this->daemon->exec("GetRouteActions", $data);
      foreach ($route_actions as $context_name => $context_actions) {
        $result[$route_uid][$context_name] = [];
        foreach ($context_actions as $action) {
          if ($action['draft'] > 1) {
            continue;
          }
          $result[$route_uid][$context_name][] = [
            'action_name' => $action['type_title'],
            'action_description' => $action['description'] ? $action['description'] : $this->load->controller('extension/action/' . $action['action'] . "/getDescription", $action['action_type']),
            'action_id' => $action['uid'],
            'sort' => $action['sort'],
            'route_name' => $route['description'][$this->config->get('config_language_id')]['name'] ?? "",
            'context_name' => $this->language->get("text_route_" . $context_name . "_name")
          ];
        }
      }
    }
    return $result;
  }

  public function copyRouteAction($route_action_uid, $route_uid, $context)
  {
    if (!$route_action_uid || !$route_action_uid || !$context) {
      return "";
    }
    $data = [
      'routeActionUID' => $route_action_uid,
      'routeUID' => $route_uid,
      'context' => $context,
    ];
    return $this->daemon->exec("CopyRouteAction", $data);
  }

  public function removeRouteAction($route_action_uid)
  {
    $action_info = $this->getRouteAction($route_action_uid);
    $action_info['params'] = [
      'action' => $action_info['action_params'],
      'route_action' => $action_info['action'],
    ];
    $this->editRouteAction($route_action_uid, $action_info['params'], 2);
  }

  public function undoRemoveRouteAction($route_action_uid)
  {
    $action_info = $this->getRouteAction($route_action_uid);
    $action_info['params'] = [
      'action' => $action_info['action_params'],
      'route_action' => $action_info['action'],
    ];
    $this->editRouteAction($route_action_uid, $action_info['params'], -1);
  }

  //************************************************ */
  //**********      КНОПКИ      ******************** */
  //************************************************ */  

  public function getRouteButton($route_button_uid)
  {
    if (!$route_button_uid) {
      return [];
    }
    $this->load->model('tool/image');
    $data = [
      'uid' => $route_button_uid,
      'draft' => 1,
    ];
    $button = $this->daemon->exec("GetButton", $data);

    if (!$button) {
      return [];
    }
    $button['route_button_uid'] = $button['uid'];
    $button['route_uid'] = $button['parent_uid'];
    if ($button['picture']) {
      $button['picture25'] = $this->model_tool_image->resize($button['picture'], 18, 18);
    } else {
      $button['picture25'] = "";
    }

    $button['route_button_uid'] = $button['uid'];
    $button['action_params'] = $button['action_type'];
    if ($button['draft'] > 2) {
      $button['draft'] = 2;
    }
    return $button;
  }

  public function getRouteButtons($data)
  {
    $this->load->model('tool/image');
    $data['draft'] = "1";
    $data['parent_uid'] = (string) $data['route_uid']; // может быть 0
    unset($data['route_uid']);
    $buttons = $this->daemon->exec("GetButtons", $data);

    if (!$buttons) {
      return [];
    }

    $result = [];

    foreach ($buttons as $route_buttons) {
      foreach ($route_buttons as $button) {
        $button['name'] = $button['descriptions'][(int) $this->config->get('config_language_id')]['name'] ?? "";
        $button['picture25'] = "";
        if ($button['picture']) {
          if (!empty($button['descriptions'][(int) $this->config->get('config_language_id')]['name'])) {
            $button['picture25'] = $this->model_tool_image->resize($button['picture'], 18, 18);
          } else {
            $button['$picture25'] = $this->model_tool_image->resize($button['picture'], 18, 18);
          }
        }
        if ($button['draft'] > 2) {
          $button['draft'] = 2;
        }
        $button['route_button_uid'] = $button['uid'];
        $button['route_uid'] = $button['parent_uid'];
        $result[] = $button;
      }
    }
    usort($result, function ($a, $b) {
      return ($a['sort'] <=> $b['sort']);
    });
    return $result;
  }

  //*
  private function prepareButton($data)
  {
    $action = $data['route_button_action'] ?? $data['action'] ?? "";

    if (isset($data['action_params'])) {
      $data['action'] = $data['action_params']; //from removeRouteButton
      unset($data['action_params']);
    }

    if ($action) {
      $data_params = [
        'params' => $data
      ];
      if (!empty($data['route_uid'])) {
        $data_params['route_uid'] = $data['route_uid'];
      }
      if (!empty($data['route_button_uid'])) {
        $data_params['route_button_uid'] = $data['route_button_uid'];
      }
      $params = $this->load->controller('extension/action/' . $action . '/setParams', $data_params);
    } else {
      $params = [];
    }


    if (!$params) {
      $params = ['empty' => ""]; //$data['params'] не может быть пустым
    }

    $field_delegates = [];
    if (isset($data['route_button_field'])) { // из формы
      $field_delegates = $data['route_button_field'];
    } else if (isset($data['fields'])) { //считано из базы
      foreach ($data['fields'] as $field) {
        $field_delegates[] = $field['field_uid'];
      }
    }

    $data_button = [
      'button_group_uid'   => $data['btn_group_uid'] ?? "",
      'field_delegates'   => $field_delegates,
      'picture'   => $data['route_button_picture'] ?? $data['picture'] ?? "",
      'hide_button_name'   => (int) $data['hide_button_name'],
      'color'   => $data['route_button_color'] ?? $data['color'] ?? "",
      'background'   => $data['route_button_background'] ?? $data['background'] ?? "",
      'action'      => $action,
      'action_log'  => (int) $data['action_log'],
      'action_move_route_uid'  => $data['action_move_route_uid'],
      'action_params'      =>  $this->jsonEncode($params),
      'show_after_execute'      => (int) $data['show_after_execute'],
      'draft'      => (int) $data['draft'],
      'description' => $data['route_button_description'] ?? $data['description'] ?? "",
      'descriptions' => $data['route_button_descriptions'] ?? $data['descriptions'] ?? [],
    ];

    if (!empty($data['route_uid'])) {
      $data_button['parent_uid'] = $data['route_uid'];
    }
    if (!empty($data['route_button_uid'])) {
      $data_button['route_button_uid'] = $data['route_button_uid'];
    }

    return $data_button;
  }

  /**
   * Изменение кнопки маршрута. 
   * $draft - 1 - черновик, 0 - нет, 2 - кнопка была удалена из доктайпа, но доктайп пока не сохранен, 3 - кнопка ни разу не сохранялась
   */
  public function editRouteButton($route_button_uid, $data, $draft = 1, $sort = 0)
  {

    if (!$route_button_uid) {
      return;
    }

    $button_info = $this->getRouteButton($route_button_uid);
    $data['route_button_uid'] = $route_button_uid;
    $data['route_uid'] = $button_info['route_uid'];
    $data['draft'] = $draft;

    if ($sort) {
      $data_button = $data; // изменяется только сортировка, все данные передаются
      $data_button['sort'] = $sort;
    } else {
      $data_button = $this->prepareButton($data);
      $data_button['sort'] = $button_info['sort'];
    }

    $data_button['uid'] = $data_button['route_button_uid'];
    $data_button['parent_uid'] = $data_button['route_uid'] ?? $button_info['parent_uid'];
    if (empty($data_button['descriptions'])) {
      unset($data_button['descriptions']);
    }
    $button = $this->daemon->exec("EditButton", $data_button);
    if ($button === null) {
      $this->redirect();
    }
    return $button;
  }

  /**
   * Добавление кнопки в маршрут. Все данные формы передаются через POST
   * $route_uid - точка маршрута
   * $data - параметры кнопки
   * $draft - 1 - черновик, 0 - нет, 2 - кнопка было удалено из доктайпа, но доктайп пока не сохранен
   */
  public function addRouteButton($route_uid, $data, $draft = 1)
  {
    $route_uid = $this->db->escape($route_uid);
    if (!$route_uid) {
      return;
    }
    $data['route_uid'] = $route_uid;
    $data['draft'] = $draft > 1 ? 1 : $draft;

    $data_button = $this->prepareButton($data);

    $button = $this->daemon->exec("AddButton", $data_button);

    if ($button === null) {
      $this->redirect();
    }
    return $button;
  }

  public function editSortRouteButton($route_button_uid, $sort)
  {
    // $button_info = $this->getRouteButton($route_button_uid);
    // $this->editRouteButton($route_button_uid, $button_info, 1, $sort);
    $data = [
      'route_button_uid' => $route_button_uid,
      'sort' => (string)$sort,
    ];
    $this->daemon->exec("EditButtonSort", $data);
  }

  public function removeRouteButton($route_button_uid)
  {
    $button_info = $this->getRouteButton($route_button_uid);
    $this->editRouteButton($route_button_uid, $button_info, 2);
  }

  public function undoRemoveRouteButton($route_button_uid)
  {
    $button_info = $this->getRouteButton($route_button_uid);
    $this->editRouteButton($route_button_uid, $button_info, -1);
  }

  /**
   * Возвращает список действия доктайпа
   * @param type $doctype_uid
   */
  public function getListRouteButtons($doctype_uid)
  {
    $doctype_uid = $this->db->escape($doctype_uid);
    if (!$doctype_uid) {
      return [];
    }
    $language_id = (int) $this->config->get('config_language_id');
    $buttons = $this->daemon->exec("GetButtons", ['doctype_uid' => $doctype_uid, 'draft' => "1"]);

    $result = [];
    if (!$buttons) {
      return $result;
    }

    $routes = $this->daemon->exec("GetDoctypeRoutes", ['doctype_uid' => $doctype_uid, 'language_id' => (string)$language_id]);

    $route_names = [];
    foreach ($routes as $r) {
      $route_names[$r['uid']] = $r['description'][$language_id]['name'] ?? "";
    }

    foreach ($buttons as $route_uid => $btns) {
      $result[$route_uid] = [];
      foreach ($btns as $button) {
        $result[$route_uid][] = [
          'name' => $button['descriptions'][$language_id]['name'] ?? "",
          'action_name' => $button['action_name'],
          'action_description' => $button['description'] ? $button['description'] : ($button['action'] ? $this->load->controller('extension/action/' . $button['action'] . "/getDescription", $button['action_type']) : ""),
          'button_id' => $button['uid'],
          'sort' => $button['sort'],
          'route_name' => $route_names[$button['parent_uid']],
        ];
      }
    }
    return $result;
  }

  public function copyRouteButton($route_button_uid, $route_uid)
  {
    $button_info = $this->getRouteButton($route_button_uid);
    $route_button_uid = $this->addRouteButton($route_uid, $button_info);
    return $route_button_uid;
  }

  public function getRouteButtonDescriptions($route_button_uid, $draft = 0)
  {
    if (!$route_button_uid) {
      return [];
    }
    $draft = (int) $draft;
    $route_button_uid = $this->db->escape($route_button_uid);
    $query = $this->db->query("SELECT * FROM `button_description` WHERE `uid` = '$route_button_uid' AND `draft`='$draft' ");
    $result = [];
    if (!$query->num_rows) {
      return $result;
    }
    foreach ($query->rows as $route_button) {
      $result[$route_button['language_id']] = [
        'name' => $route_button['name'],
        'description' => $route_button['description']
      ];
    }
    return $result;
  }

  public function getRouteButtonFields($route_button_uid, $draft = 0)
  {
    if (!$route_button_uid) {
      return [];
    }
    $draft = (int) $draft;
    $route_button_uid = $this->db->escape($route_button_uid);
    $query = $this->db->query("SELECT `field_uid` FROM `button_field` WHERE `uid` = '$route_button_uid' AND `draft`='$draft' ");
    $result = [];
    if (!$query->num_rows) {
      return $result;
    }
    foreach ($query->rows as $field) {
      $field_name = $this->getFieldName($field['field_uid']);
      $result[] = array(
        'field_uid' => $field['field_uid'],
        'name' => $field_name
      );
    }
    return $result;
  }

  /**
   * Пометка точки маршрута на удаление
   * @param type $route_uid
   */
  public function removeRoute($route_uid)
  {
    $this->load->model('document/document');
    $data_docs = array(
      'route_uid' => $this->request->get['route_uid'],
      'start' => 0,
      'limit' => 1
    );
    if (!$this->model_document_document->getDocumentIds($data_docs)) {
      $route_info = $this->getRoute($route_uid, 1);
      unset($route_info['actions']);
      $this->editRoute($route_uid, $route_info, 2);
    }
  }

  /**
   * Снятие пометки на удаление с точки маршрута
   * @param type $route_uid
   */
  public function undoRemoveRoute($route_uid)
  {
    if (!$route_uid) {
      return;
    }
    $route_info = $this->getRoute($route_uid, 1);
    unset($route_info['actions']);
    $this->editRoute($route_uid, $route_info, -1);
  }

  public function getTemplateVariables()
  {
    $this->load->language('doctype/doctype');
    return array(
      'var_author_name'               => $this->language->get('text_var_author_name'),
      'var_department_name'           => $this->language->get('text_var_department_name'),
      'var_customer_name'             => $this->language->get('text_var_customer_name'),
      'var_current_route_name'        => $this->language->get('text_var_current_route_name'),
      // 'var_current_route_description' => $this->language->get('text_var_current_route_description'),
      'var_current_locale_time'       => $this->language->get('text_var_current_time'),
      'var_current_locale_date'       => $this->language->get('text_var_current_date'),
      'var_time_added'                => $this->language->get('text_var_time_added'),
      'var_date_added'                => $this->language->get('text_var_date_added'),
    );
  }

  public function getHiddenTemplateVariables()
  {
    $this->load->language('doctype/doctype');
    return array(
      'QUERY_INFO'               => 'QUERY_INFO'
    );
  }

  public function getVariables()
  {
    $this->load->language('doctype/doctype');
    return array(
      'var_author_uid'                    => $this->language->get('text_var_author_uid'),
      'var_department_uid'                => $this->language->get('text_var_department_uid'),
      'var_customer_uid'                  => $this->language->get('text_var_customer_uid'),
      'var_customer_uids'                 => $this->language->get('text_var_customer_uids'),
      'var_customer_user_uid'             => $this->language->get('text_var_customer_user_uid'),
      'var_current_document_uid'          => $this->language->get('text_var_current_document_uid'),
      'var_current_button_uid'            => $this->language->get('text_var_current_button_uid'),
      'var_change_field_uid'              => $this->language->get('text_var_change_field_uid'),
      'var_change_field_value'            => $this->language->get('text_var_change_field_value'),
      'var_current_doctype_uid'           => $this->language->get('text_var_current_doctype_uid'),
      'var_current_folder_uid'            => $this->language->get('text_var_current_folder_uid'),
      'var_current_route_uid'             => $this->language->get('text_var_current_route_uid'),
      'var_current_route_name'            => $this->language->get('text_var_current_route_name'),
      'var_current_route_description'     => $this->language->get('text_var_current_route_description'),
      'var_author_name'                   => $this->language->get('text_var_author_name'),
      'var_department_name'               => $this->language->get('text_var_department_name'),
      'var_customer_name'                 => $this->language->get('text_var_customer_name'),
      'var_current_time'                  => $this->language->get('text_var_current_datetime'),
      'var_document_time_added'           => $this->language->get('text_var_time_added'),
      'var_struid_access_document'        => $this->language->get('text_var_struid_access_document'),
    );
  }

  /**
   * Метод для обновления матрицы matrix_doctype_access, формируемой на основе вкладки Доступ доктайпа
   * @param type $doctype_uid
   * @param type $access_id
   */
  public function updateDoctypeAccess($doctype_uid)
  {
    $this->daemon->exec("UpdateDoctypeAccessMatrix", $doctype_uid);
  }

  /**
   * Обновляется таблица с матрицей делегирования кнопок при изменении делегирования кнопки в доктайпе
   * @param type $field_uid
   * @param type $document_uid
   * @param type $value
   */
  public function updateButtonDelegate($route_button_uid)
  {
    $this->daemon->exec("UpdateRouteButtonAccess", $route_button_uid);
  }

  /**
   * Метод заменяет названия полей в шаблоне на их идентификаторы
   * @return type
   */
  public function getIdsTemplate($template, $doctype_uid)
  {
    $variables = $this->getTemplateVariables();
    // $cache_key = "ids_template" . md5(json_encode($template) . $doctype_uid);
    // $cache = $this->cache->get($cache_key, "meta_" . $doctype_uid);
    // if ($cache) {
    //   return $cache;
    // }
    $fields = $this->getFieldsList($doctype_uid);
    $replace = array(
      'search' => array(),
      'replace' => array()
    );
    foreach ($fields as $field) {
      $replace['search'][] = "/{{ ?" . preg_quote($field['name'], '/') . " ?}}/is";
      $replace['replace'][] = "{{ f_" . str_replace("-", "", $field['field_uid']) . " }}";
    }

    //добавляем в поля список переменных
    foreach ($variables as $var_id => $var_name) {
      $replace['search'][] = "/{{ ?" . preg_quote($var_name, '/') . " ?}}/is";
      $replace['replace'][] = "{{ " . str_replace("-", "", $var_id) . " }}";
    }
    $result = preg_replace($replace['search'], $replace['replace'], $template);
    // $this->cache->set($cache_key, $result, "meta_" . $doctype_uid);
    return $result;
  }

  /**
   * Метод выполняет обратное преобразование шаблона - заменяет идентификаторы полей и переменных на их названия
   * @param type $template
   * @param type $doctype_uid
   * @param type $variables
   * @return type
   */
  public function getNamesTemplate($template, $doctype_uid, $variables)
  {
    $fields = array();

    foreach ($this->getFieldsList($doctype_uid) as $field) {
      $fields['name'][] = "{{ " . $field['name'] . " }}";
      $fields['id'][] = "{{ f_" . str_replace("-", "", $field['field_uid']) . " }}";
    }
    //добавляем идентификаторы и языковые наименования переменных
    foreach ($variables as $var_id => $var_name) {
      $fields['name'][] = "{{ " . $var_name . " }}";
      $fields['id'][] = "{{ " . $var_id . " }}";
    }
    return str_replace($fields['id'], $fields['name'], $template);
  }

  /**
   * Метод для добавления подписки на изменение поля
   * @param type $subscription_field_uid - подписываемое поле
   * @param type $subscription_document_uid - подписываемый документ
   * @param type $document_uids - изменяемый документ; может быть массивом, если идет подписка на одно поле нескольких документов
   * @param type $field_uid - изменяемое поле
   */
  public function addSubscription($subscription_field_uid, $subscription_document_uid, $field_uid, $document_uids)
  {
    if (!is_array($document_uids)) {
      $document_uids = [$document_uids];
    }
    $data = [
      "subscription_field_uid" => $subscription_field_uid,
      "subscription_document_uid" => $subscription_document_uid,
      "observed_field_uid" => $field_uid,
      "observed_document_uids" => $document_uids,
    ];
    $this->daemon->exec("AddFieldSubscription", $data);
  }

  public function delSubscription($subscription_field_uid, $subscription_document_uid = "")
  {
    $data = [
      "subscription_field_uid" => $subscription_field_uid,
      "subscription_document_uid" => $subscription_document_uid,
    ];
    $this->daemon->exec("DelFieldSubscription", $data);
  }

  /**
   * Возвращает время последнего изменения типа документа, если $document_uid не передан, то
   * время последнего изменения последнего изменнного типа документа
   */
  public function getLastModified($doctype_uid = "")
  {
    $sql = "SELECT MAX(date_edited) AS lasttime FROM " . DB_PREFIX . "doctype";
    if ($doctype_uid) {
      $sql .= " WHERE doctype_uid='" . $this->db->escape($doctype_uid) . "'";
    }
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      return $query->row['lasttime'];
    }
  }


  public function getButtonGroup($button_group_uid)
  {
    $data = [
      'uid' => $button_group_uid,
      'draft' => 1,
    ];
    $result = $this->daemon->exec("GetButtonGroup", $data);
    if (!$result) {
      return [];
    }
    if ($result['picture']) {
      $this->load->model('tool/image');
      if (!empty($result['descriptions'][(int) $this->config->get('config_language_id')]['name'])) {
        $result['picture25'] = $this->model_tool_image->resize($result['picture'], 28, 28);
      } else {
        $result['picture25'] = $this->model_tool_image->resize($result['picture'], 28, 28);
      }
    } else {
      $result['picture25'] = "";
    }
    return $result;
  }

  public function addButtonGroup($container_uid, $data, $draft = 1)
  {
    $data['draft'] = (int) $draft;
    $data['container_uid'] = $container_uid;
    $data['hide_group_name'] = (int) $data['hide_group_name'];
    $result = $this->daemon->exec("AddButtonGroup", $data);
    return $result['uid'] ?? "";
  }

  public function editButtonGroup($button_group_uid, $data, $draft = 1)
  {
    $data['uid'] = $button_group_uid;
    $data['hide_group_name'] = (int) $data['hide_group_name'];

    $this->daemon->exec("EditButtonGroup", $data);
  }

  public function removeButtonGroup($button_group_uid)
  {
    $this->daemon->exec("DelButtonGroup", $button_group_uid);
  }

  public function getButtonGroups($data)
  {
    $data["draft"] = "1";
    $result = $this->daemon->exec("GetButtonGroups", $data);
    if ($result === null && !$result) {
      return [];
    }
    $this->load->model('tool/image');

    foreach ($result as &$bg) {
      if ($bg['picture']) {
        $bg['hide_button_name'] = $bg['hide_group_name'];
        if (!empty($bg[(int) $this->config->get('config_language_id')]['name'])) {
          $bg['picture25'] = $this->model_tool_image->resize($bg['picture'], 28, 28);
        } else {
          $bg['picture25'] = $this->model_tool_image->resize($bg['picture'], 28, 28);
        }
      } else {
        $bg['picture25'] = "";
        $bg['hide_button_name'] = 0;
      }
      $bg['button_group_uid'] = $bg['uid'];
    }

    return $result;
  }

  private function jsonEncode($v)
  {
    return json_encode($v, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
  }

  private function redirect()
  {
    // debug_print_backtrace();
    // exit;
    $this->response->redirect($this->url->link('error/daemon_not_started', true));
  }
}

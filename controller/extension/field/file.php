<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2020 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerExtensionFieldFile extends FieldController
{

  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_firstfile_url'),
      array('type' => 'getter', 'name' => 'get_files_with_links'),
      array('type' => 'getter', 'name' => 'get_number_files'),
      array('type' => 'getter', 'name' => 'get_content_files'),
      array('type' => 'getter', 'name' => 'get_content_files_base64'),
      array('type' => 'getter', 'name' => 'get_path_files'),
      array('type' => 'setter', 'name' => 'append_file', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'remove_file', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'append_file_from_file_system', 'params' => array('standard_setter_param')),
    ),
    'compound' => true,
    'MODULE_NAME' => 'FieldFile',
    'FILE_NAME' => 'file'
  );
  const DIR_FILE_UPLOAD = DIR_DOWNLOAD . "field_file/";

  /**
   * Настройки поля в Модулях
   */
  public function setting()
  {
    if ($this->request->server['REQUEST_METHOD'] == "POST") {
      //сохраняем настройки
      $this->load->model('setting/setting');
      $setting = array();
      if (isset($this->request->post['config_file_ext_allowed'])) {
        $setting['config_file_ext_allowed'] = $this->request->post['config_file_ext_allowed'];
      }
      if (isset($this->request->post['config_file_mime_allowed'])) {
        $setting['config_file_mime_allowed'] = $this->request->post['config_file_mime_allowed'];
      }
      if ($setting) {
        $this->model_setting_setting->editSetting('dv_field_file', $setting);
      }
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode(array('success' => 1)));
    } else {
      $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
      $data['action'] = $this->url->link('extension/field/file/setting', '', true);
      $data['field_file_ext_allowed'] = $this->config->get('config_file_ext_allowed');
      $data['field_file_mime_allowed'] = $this->config->get('config_file_mime_allowed');
      $this->response->setOutput($this->load->view('extension/field/file', $data));
    }
  }

  public function install()
  {
    $this->load->model('extension/field/file');
    $this->model_extension_field_file->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/file');
    $this->model_extension_field_file->uninstall();
  }

  private function removeDirectory($dir)
  {
    if ($objs = glob($dir . DIRECTORY_SEPARATOR . "*")) {
      foreach ($objs as $obj) {
        is_dir($obj) ? $this->removeDirectory($obj) : unlink($obj);
      }
    }
    unlink($dir . DIRECTORY_SEPARATOR . ".htaccess");
    rmdir($dir);
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldFile::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/file');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $descriptions = array();
    if (!empty($params['preview']['status'])) {
      $descriptions[] = $this->language->get('description_preview');
    }
    if (!empty($params['size_file'])) {
      $descriptions[] = $this->language->get('description_size_file');
    }
    if (!empty($params['limit_files'])) {
      $descriptions[] = sprintf($this->language->get('description_limit_files'), $params['limit_files']);
    }
    return implode("; ", $descriptions);
  }

  public function setParams($params)
  {
    $params['preview']['status'] = !empty($params['preview']['status']) ? 1 : 0;
    $params['preview']['width'] = !empty($params['preview']['width']) ? (int)$params['preview']['width'] : 0;
    $params['preview']['height'] = !empty($params['preview']['height']) ? (int)$params['preview']['height'] : 0;
    $params['preview']['link'] = !empty($params['preview']['status']) ? 1 : 0;
    $params['size_file'] = !empty($params['size_file']) ? (int)  $params['size_file'] : 0;
    $params['limit_files'] = !empty($params['limit_files']) ? (int)  $params['limit_files'] : 0;
    return $params;
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $data['file_extes'] = array();
    $data['file_mimes'] = array();
    if (isset($data['params'])) {
      //редактируется существующее поле
      foreach (explode(',', $this->config->get('config_file_ext_allowed')) as $ext) {
        $data['file_extes'][$ext] = FALSE;
      }
      if (!empty($data['params']['file_extes'])) {
        foreach ($data['params']['file_extes'] as $ext) {
          $data['file_extes'][$ext] = TRUE;
        }
      }
      foreach (explode(',', $this->config->get('config_file_mime_allowed')) as $mime) {
        $data['file_mimes'][$mime] = FALSE;
      }
      if (!empty($data['params']['file_mimes'])) {
        foreach ($data['params']['file_mimes'] as $mime) {
          $data['file_mimes'][$mime] = TRUE;
        }
      }
    } else {
      //создается новое поле
      foreach (explode(',', $this->config->get('config_file_ext_allowed')) as $ext) {
        $data['file_extes'][$ext] = TRUE;
      }
      foreach (explode(',', $this->config->get('config_file_mime_allowed')) as $mime) {
        $data['file_mimes'][$mime] = TRUE;
      }
    }
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return $this->load->view('field/file/file_form', $data) . $this->load->view('field/common_admin_form', array('data' => $data));;
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {

    $this->load->language('extension/field/file');
    $this->load->model('extension/field/file');
    if (isset($data['filter_form'])) {
      //$data['files'] = $this->model_extension_field_file->getFilesByField($data['field_uid']);
    } else {
      if (!empty($data['field_uid'] && isset($data['field_value']))) {
        $this->load->model('document/document');
        $this->load->model('tool/image');
        if (is_array($data['field_value'])) {
          $file_uids = $data['field_value'];
        } else {
          $file_uids = explode(",", $data['field_value']);
        }
        $data['files'] = array();
        foreach ($file_uids as $file_uid) {
          if (!$file_uid) {
            continue;
          }
          $file_info = $this->model_extension_field_file->getFile($file_uid);
          if ($file_info) {
            $link_preivew = "";
            $ext = strtolower(substr(strrchr($file_info['file_name'], '.'), 1));
            if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png') {
              $link_preivew = $this->url->link('field/file', 'file_uid=' . $file_info['file_uid'] . '&field_uid=' . $data['field_uid'] . '&preview=form');
            }
            $link = $this->url->link('field/file', 'file_uid=' . $file_info['file_uid']);

            $data['files'][] = array(
              'file_name' => $file_info['file_name'],
              'file_uid' => $file_info['file_uid'],
              'link_preview' => $link_preivew,
              'link' => $link
            );
          }
        }
      }
      $data['size_file'] = !empty($data['size_file']) ? $data['size_file'] * 1024 : 0; //чтобы не передать пустое значение
      $units = [
        [
          'code'       => "K",
          'multiplier' => 1024
        ],
        [
          'code'       => "M",
          'multiplier' => 1024 * 1024
        ],
        [
          'code'       => "G",
          'multiplier' => 1024 * 1024 * 1024
        ],
      ];
      foreach (['post_max_size', 'upload_max_filesize'] as $ini) {
        $rate = false;
        $size = ini_get($ini);
        foreach ($units as $unit) {
          $size = str_ireplace($unit['code'], "", $size, $count);
          if ($count) {
            $rate = true;
            $size *= $unit['multiplier'];
            if (!$data['size_file'] || $size < $data['size_file']) {
              $data['size_file'] = $size;
            }
          }
        }
        if (!$rate && (!$data['size_file'] || $size < $data['size_file'])) {
          $data['size_file'] = $size;
        }
      }

      $data['limit_files'] = !empty($data['limit_files']) ? $data['limit_files'] : 0;
    }
    $data = $this->setDefaultTemplateParams($data);
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;

    return $this->load->view('field/file/file_widget_form', $data) . $this->load->view('field/common_widget_form', array('data' => $data));
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    // print_r($data);
    // exit;

    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('extension/field/file');
    $this->load->model('tool/image');
    if (!empty($data['field_value'])) {
      $value = $data['field_value'];
      $data['field_values'] = array();
      if (!is_array($value)) {
        $value = explode(',', $value);
      } else {
        $data['field_value'] = implode(',', $value); //$data['field_value'] - строка с ID через запятую для виджета
      }
      foreach ($value as $file) {
        $file = trim($file);
        if (!$file) {
          continue;
        }
        $file_info = $this->model_extension_field_file->getFile($file);
        if (!$file_info) {
          continue;
        }
        $link = $this->url->link('field/file', 'file_uid=' . $file_info['file_uid']);
        $preview = 0;
        $preview_link = '';
        if ($data['preview']['status'] && !isset($data['filter_view'])) { //наличие превью, вызов не из фильтров админки журнала (filter_view)
          //установлен предварительный просмотр
          $ext = strtolower(substr(strrchr($file_info['file_name'], '.'), 1));
          if ($ext == 'pdf' || $ext == 'cms' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png') {
            if (!$data['preview']['link']) {
              $preview_link = $link;
            }
            $link .= '&field_uid=' . $data['field_uid'] . '&preview';
            $preview = $ext;
          }
        }
        if ($file_info) {
          $token = "&_=" . token();
          $data['field_values'][] = array(
            'name' => $file_info['file_name'],
            'link' => str_replace("&amp;", "&", $link . $token),
            'preview' => $preview,
            'preview_link' =>  str_replace("&amp;", "&", $preview_link . $token),
          );
        }
      }
      $data['delimiter'] = html_entity_decode($data['delimiter']);
    }
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return $this->load->view('field/file/file_widget_view', $data) . $this->load->view('field/common_widget_view', array('data' => $data));

    // return $this->load->view('field/file/file_widget_view', $data);

    // return $this->load->view('field/file/file_widget_view', $data);
  }

  //Метод возвращает список доступных методов
  /* public function getFieldMethods($method_type) {
    $result = array();
    foreach ($this::FIELD_INFO['methods'] as $method) {
    if (strcmp($method_type, $method['type']) === 0) {
    $method['alias'] = $this->language->get('text_method_' . $method['name']);
    $result[] = $method;
    }
    }
    return $result;
    } */

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/file');
    switch ($data['method_name']) {
      case "get_files_with_links":
      default:
        return '';
    }
  }

  /* public function executeMethod($data) {
    $method_name = $data['method'];
    $result = null;
    foreach ($this::FIELD_INFO['methods'] as $method) {
    if (strcmp($method_name, $method['name']) === 0 && method_exists($this, $method_name)) {
    $result = $this->$method_name($data);
    break;
    }
    }
    return $result;
    } */

  public function get_firstfile_url($params)
  {

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('extension/field/file');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $result = array();
    if ($value) {
      $files = $this->model_extension_field_file->getFiles(explode(",", $value));
      if ($files) {
        foreach ($files as $file) {
          return $this->url->link('field/file', 'file_uid=' . $file['file_uid']);
        }
      }
    }
    return "";
  }

  public function get_files_with_links($params)
  {

    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('extension/field/file');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $result = array();
    if ($value) {
      $files = $this->model_extension_field_file->getFiles(explode(",", $value));
      if ($files) {
        foreach ($files as $file) {
          $result[] = "<a href='" . $this->url->link('field/file', 'file_uid=' . $file['file_uid']) . "' target='_blank'>" . $file['file_name'] . "</a>";
        }
      }
    }
    if ($result) {
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      return implode($field_info['params']['delimiter'], $result);
    } else {
      return "";
    }
  }

  public function get_number_files($params)
  {
    $this->load->model('document/document');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $array_value = explode(",", trim($value));
    $count = 0;
    foreach ($array_value as $file_uid) {
      if ($file_uid) {
        $count++;
      }
    }
    return $count;
  }

  public function get_content_files($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/file');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $array_value = explode(",", trim($value));
    $count = 0;
    $content = "";
    foreach ($array_value as $file_uid) {
      if ($file_uid) {
        $file_info = $this->model_extension_field_file->getFile($file_uid);
        $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . $file_info['token'] . $file_info['file_name'];
        if (file_exists($file)) {
          $content .= file_get_contents($file);
        }
      }
    }
    return $content;
  }

  public function get_content_files_base64($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/file');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $array_value = explode(",", trim($value));
    $count = 0;
    $content = "";
    foreach ($array_value as $file_uid) {
      if ($file_uid) {
        $file_info = $this->model_extension_field_file->getFile($file_uid);
        $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . $file_info['token'] . $file_info['file_name'];
        if (file_exists($file)) {
          $content .= file_get_contents($file);
        }
      }
    }
    return base64_encode($content);
  }

  public function get_path_files($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/file');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $array_value = explode(",", trim($value));
    $result = [];
    foreach ($array_value as $file_uid) {
      if ($file_uid) {
        $file_info = $this->model_extension_field_file->getFile($file_uid);
        $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . $file_info['token'] . $file_info['file_name'];
        if (file_exists($file)) {
          $result[] = $file;
        }
      }
    }
    return implode(",", $result);
  }

  public function append_file($params)
  {
    $this->load->model('document/document');
    if ($params['method_params']['standard_setter_param']) {
      //есть что записывать
      $value1 = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
      $value2 = $params['method_params']['standard_setter_param'];
      if ($value1 && $value2) {
        $value = $value1 . ',' . $value2;
      } else {
        $value = $value1 . $value2;
      }
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $value);
    }
  }

  /**
   * Метод для загрузки файла из файловой системы сервера. Метод имеет один стандартный параметр для получения пути файла
   * @param type $params
   * @return type
   */
  public function append_file_from_file_system($params)
  {
    $this->load->model('document/document');
    $this->load->model('extension/field/file');
    if ($params['method_params']['standard_setter_param']) {
      //есть что записывать
      $token = token(32);
      $file_path = $params['method_params']['standard_setter_param'];
      if (!file_exists($file_path)) {
        return;
      }
      $allowed = false;
      foreach (["export_f", "sign_kz"] as $ex_dir) {
        if (stripos($file_path, DIR_DOWNLOAD . $ex_dir) === 0) {
          $allowed = true;
          break;
        }
      }
      if (!$allowed) {
        if (defined("ALLOWED_COPY_DIRS") && ALLOWED_COPY_DIRS) {
          foreach (explode(",", ALLOWED_COPY_DIRS) as $dir) {
            $dir = trim($dir);
            if (stripos($file_path, $dir) === 0) {
              $allowed = true;
              break;
            }
          }
        }

        if (!$allowed) {
          return "";
        }
      }

      $file_paths = explode('/', $file_path);
      $basename = $file_paths[count($file_paths) - 1];
      $time = new DateTime('now');
      $now = $time->format('/Y/m');
      if (!file_exists($this::DIR_FILE_UPLOAD . $params['field_uid'] . $now)) {
        mkdir($this::DIR_FILE_UPLOAD . $params['field_uid'] . $now, 0700, true);
      }
      copy($file_path, $this::DIR_FILE_UPLOAD . $params['field_uid'] . $now . "/" . $token . $basename);

      $file_uid = $this->model_extension_field_file->addFile($params['field_uid'], $basename, filesize($params['method_params']['standard_setter_param']), $token)['file_uid'];


      $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
      if ($field_value) {
        $value = $field_value . ',' . $file_uid;
      } else {
        $value = $file_uid;
      }
      return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $value);
    }
  }

  public function remove_file($params)
  {
    $this->load->model('document/document');
    if ($params['method_params']['standard_setter_param']) {
      //есть что удалять
      $value1 = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
      if ($value1) {
        //есть откуда удалять
        $values1 = explode(",", $value1);
        $values2 = explode(",", $params['method_params']['standard_setter_param']);
        $value = array_diff($values1, $values2);
        return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], implode(",", $value));
      }
    }
  }
}

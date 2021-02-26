<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2018 Andrey V Surov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

/**
 * Данный класс реализует общедоступные методы поля. Все, что находится в extension ограничено по доступу только для администраторов,
 * поэтому методы, доступные пользователям необходимо реализовывать в fields/
 */
class ControllerFieldFile extends Controller
{

  const DIR_FILE_UPLOAD = DIR_DOWNLOAD . "field_file/";

  public function index()
  {
    //$this->response->setOutput("FILE INDEX");
    $file_uid = '';
    $field_uid = '';
    $structure_uid = '';
    if (isset($this->request->get['token'])) {
      $this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      $structure_uid = $token_info['account_uid'];
    }
    if (isset($this->request->get['field_uid'])) {
      $field_uid = $this->request->get['field_uid'];
    }
    if (isset($this->request->get['file_uid'])) {
      $file_uid = $this->request->get['file_uid'];
      $this->sendFile($file_uid, $field_uid, $structure_uid);
    }
  }

  private function sendFile($file_uid, $field_uid, $structure_uid = '')
  {
    $this->load->model('extension/field/file');
    $this->load->model('doctype/doctype');
    $this->load->language('extension/field/file');
    $file_info = $this->model_extension_field_file->getFile($file_uid, true);
    if ($file_info) {
      //если файл относится к настроечному полю, то доступ предоставляем безусловно
      $field_info = $this->model_doctype_doctype->getField($file_info['field_uid']);
      if ($field_info['setting'] || $this->model_extension_field_file->hasAccess($file_uid, $structure_uid) || !$file_info['status']) {
        $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . $file_info['token'] . $file_info['file_name'];
        if (isset($this->request->get['preview']) && $field_uid && strlen($field_uid) == 36) {
          $ext = strtolower(substr(strrchr($file_info['file_name'], '.'), 1));
          if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png') {
            //создаем preview в кэше                        
            $field_info = $this->model_doctype_doctype->getField($field_uid);
            $this->load->model('tool/image');
            if ($this->request->get['preview'] == 'form') {
              $width = 75;
              $height = 75;
            } else {
              $width = $field_info['params']['preview']['width'];
              $height = $field_info['params']['preview']['height'];
            }

            $cache_file = explode("/", $this->model_tool_image->resize($file_info['token'] . $file_info['file_name'], $width, $height, $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added']))));
            //                        print_r($cache_file);exit;
            $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . 'cache/' . $cache_file[count($cache_file) - 1];
            $file = str_replace("%20", " ", $file);
          }
        }
        if (file_exists($file)) {
          switch (strtolower(substr(strrchr($file_info['file_name'], '.'), 1))) {
            case "pdf":
              header('Content-Type: application/pdf');
              break;
            case "gif":
              header('Content-Type: image/gif');
              break;
            case "jpg":
              header('Content-Type: image/jpeg');
              break;
            case "jpeg":
              header('Content-Type: image/jpeg');
              break;
            case "png":
              header('Content-Type: image/png');
              break;
            default:
              header('Content-Type: application/octet-stream');
              break;
          }
          header('Content-Disposition: inline; filename="' . $file_info['file_name'] . '"');
          header("Cache-control: public, max-age=604800");
          header('Content-Length: ' . filesize($file));
          header('Content-Version: ' . $file_info['version']);

          if (ob_get_level()) {
            ob_end_clean();
          }

          readfile($file, 'rb');

          exit();
        } else {
          $this->load->controller('error/general_error/getView', array('error' => $this->language->get('error_file_not_found')));
        }
      } else {
        $this->load->controller('error/general_error/getView', array('error' => $this->language->get('error_file_access')));
      }
    } else {
      $this->load->controller('error/general_error/getView', array('error' => $this->language->get('error_wrong_uid')));
    }
  }

  public function remove()
  {
    $this->load->model('extension/field/file');
    if (!empty($this->request->get['file_uid'])) {
      $this->model_extension_field_file->removeFile($this->request->get['file_uid']);
    }
  }

  public function upload()
  {
    $this->load->model('tool/utils');
    if (!empty($this->request->files['file']['name'])) {


      if (!empty($this->request->get['field_uid'])) {
        $this->load->model('document/document');
        $this->load->model('doctype/doctype');
        $this->load->model('extension/field/file');
        $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid']);

        $data = array(
          'file_extes' => $field_info['params']['file_extes'] ?? array(),
          'file_mimes' => $field_info['params']['file_mimes'] ?? array(),
          'size_file' => $field_info['params']['size_file'] ?? 0,
          'dir_file_upload' => $this::DIR_FILE_UPLOAD
        );


        $result = $this->load->controller('document/document/uploadFile', $data);
        if (!isset($result['error'])) {
          $filename = rawurldecode(basename(rawurlencode(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'))));
          $filename  = $this->model_tool_utils->clearstr($filename);
          $info = $this->model_extension_field_file->addFile($this->request->get['field_uid'], $filename, $this->request->files['file']['size'], $result['token']);
          $result['success'] = $info['file_uid'];
          $result['version'] = $info['version'] . '';
        }


        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
      }
    }
  }

  public function edit()
  {
    //print_r($this->request);exit;
    $this->load->model('extension/field/file');
    $structure_uid = '';
    $file_uid = '';
    $field_uid = '';
    if (isset($this->request->get['file_uid'])) {
      $file_uid = $this->request->get['file_uid'];
    }
    if (isset($this->request->get['field_uid'])) {
      $field_uid = $this->request->get['field_uid'];
    }

    if (isset($this->request->get['token'])) {
      $this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      $params = unserialize($token_info['params']);
      $structure_uid = $token_info['account_uid'];
      $file_uid = $params['file_uid'];
      $field_uid = $params['field_uid'];
      $document_uid = $params['document_uid'];
      $this->request->get = $params;
    }
    if (!$this->model_extension_field_file->hasAccess($file_uid, $structure_uid)) {
      $result = "access denied";
      $this->response->addHeader('Content-Type: application/json');


      $this->response->setOutput(json_encode($result));
    };

    if (!empty($this->request->files['file']['name'])) {
      //print_r("not empty files['file']: ");
      if ($field_uid) {

        $this->load->model('document/document');
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($field_uid);

        $data = array(
          'file_extes' => $field_info['params']['file_extes'] ?? array(),
          'file_mimes' => $field_info['params']['file_mimes'] ?? array(),
          'size_file' => $field_info['params']['size_file'] ?? 0,
          'dir_file_upload' => $this::DIR_FILE_UPLOAD
        );
        //print_r("before_document upload");
        $result = $this->load->controller('document/document/uploadFile', $data);
        if (!isset($result['error'])) {
          //если есть file_uid, должны создать версию файла.
          $this->load->model('extension/field/file');
          if (!empty($this->request->get['file_uid'])) {
            $file_uid = $this->request->get['file_uid'];
          } else {
            $file_uid = '';
          }

          $this->load->model('extension/field/file');
          $info = $this->model_extension_field_file->addFile($field_uid, rawurldecode(basename(rawurlencode(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')))), $this->request->files['file']['size'], $result['token'], $file_uid);
          $result['success'] = $info['file_uid'];
          $result['version'] = $info['version'] . '';
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
      }
    } else {
      $this->sendFile($file_uid, $field_uid, $structure_uid);
    }
  }

  public function get_token()
  {
    $this->load->model('account/customer');
    $field_uid = $this->request->get['field_uid'];
    $document_uid = $this->request->get['document_uid'];
    $file_uid = $this->request->get['file_uid'];
    $structure_uid = $this->customer->getStructureId();
    $route = "field/file/edit";
    $params = array("field_uid" => $field_uid, "document_uid" => $document_uid, "file_uid" => $file_uid);
    $token = $this->model_account_customer->setToken($structure_uid, $route, serialize($params));
    $this->response->setOutput($token);
  }
}

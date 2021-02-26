<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://www.documentov.com/licenses/documentov-license.php
 * @link		https://www.documentov.com
 */

class ControllerFieldText extends FieldController
{
  const DIR_FILE_UPLOAD = DIR_DOWNLOAD . "field_text/";
  const IMAGE_NOT_FOUND = "image_not_found.png";

  public function index()
  {
    if (!empty($this->request->get['field_uid']) && isset($this->request->get['document_uid'])) {
      $data = array();
      $data['field_uid'] = $this->request->get['field_uid'];
      $data['document_uid'] = $this->request->get['document_uid'];
      $text_info = $this->load->controller('extension/field/text/getFieldInfo');
      $data['MODULE_NAME'] = $text_info['MODULE_NAME'];
      $data['METHOD_NAME'] = "getImageWindow";
      $data['FILE_NAME'] = $text_info['FILE_NAME'];
      $data['text'] = $this->lang;
      $this->response->setOutput(
        $this->load->view('field/text/text_editor_form_image', $data)
          . $this->load->view('field/common_widget_form', array('data' => $data))
      );
    }
  }

  public function file()
  {
    if (isset($this->request->get['file_uid'])) {
      $this->load->model('extension/field/text');
      $this->load->model('doctype/doctype');
      $file_info = $this->model_extension_field_text->getFile($this->request->get['file_uid']);
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid']);
      if (!$field_info) {
        return;
      }
      $token_access = false;
      if (isset($this->request->get['token'])) {
        //$this->load->model('account/customer');
        $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
        if (!empty($token_info) && $this->request->get['route'] == $token_info['route']) {
          $token_access = true;
        }
      }
      if ($token_access || $field_info['setting'] || $this->model_extension_field_text->hasAccess($this->request->get['file_uid']) || !$file_info['status']) {
        $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . $file_info['token'] . $file_info['file_name'];
        if (isset($this->request->get['preview']) && !empty($this->request->get['field_uid']) && strlen($this->request->get['field_uid']) === 36) {

          if (!empty($field_info['params']['image_width']) || !empty($field_info['params']['image_height'])) {
            $this->load->model('tool/image');
            $width = (int) $field_info['params']['image_width'] ?? 0;
            $height = (int) $field_info['params']['image_height'] ?? 0;
            $cache_file = explode("/", $this->model_tool_image->resize($file_info['token'] . $file_info['file_name'], $width, $height, $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added']))));
            $file = $this::DIR_FILE_UPLOAD . $file_info['field_uid'] . date('/Y/m/', strtotime($file_info['date_added'])) . 'cache/' . $cache_file[count($cache_file) - 1];
            $file = str_replace("%20", " ", $file);
          }
        }

        if (file_exists($file)) {
          switch (strtolower(substr(strrchr($file_info['file_name'], '.'), 1))) {
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
        } else {
          header('Content-Type: image/png');
          $file_info = ['file_name' => $this::IMAGE_NOT_FOUND];
          $file = DIR_IMAGE . $this::IMAGE_NOT_FOUND;
        }
        header('Content-Disposition: inline; filename="' . $file_info['file_name'] . '"');
        header("Cache-control: public, max-age=604800");
        header('Content-Length: ' . filesize($file));
        if (ob_get_level()) {
          ob_end_clean();
        }
        readfile($file, 'rb');
        exit();
      } else {
        echo "Not access";
      }
    }
  }

  public function upload()
  {
    if (!empty($this->request->get['field_uid'])) {
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      $field_info = $this->model_doctype_doctype->getField($this->request->get['field_uid']);

      $data = array(
        'file_extes'        => array(
          'jpg',
          'jpeg',
          'gif',
          'png'
        ),
        'file_mimes'        => array(
          'image/jpeg',
          'image/jpeg',
          'image/png',
          'image/x-png',
          'image/gif'
        ),
        'size_file'         => $field_info['params']['size_file'] ?? 0,
        'dir_file_upload'   => $this::DIR_FILE_UPLOAD
      );

      $result = $this->load->controller('document/document/uploadFile', $data);

      if (!isset($result['error'])) {
        $filename = explode($result['token'], $result['filepath'])[1];
        $this->load->model('extension/field/text');
        $result['success'] = $this->model_extension_field_text->addFile($this->request->get['document_uid'], $this->request->get['field_uid'], $filename, $this->request->files['file']['size'], $result['token']);
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($result));
    }
  }
}

<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionServiceExport extends Controller
{

  private $error = "";
  private $success = "";

  public function index()
  {
    $this->form();
  }


  public function install()
  {
  }

  public function uninstall()
  {
  }


  /**
   * Метод возвращает название сервиса в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/service/export');
    return $this->language->get('heading_title');
  }



  /**
   * Метод возвращает форму сервиса
   * @param type $data 
   */
  public function form()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('extension/service/export/form', '', true, true));

    $data = array();
    $this->load->language('extension/service/export');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('doctype/doctype');
    $this->load->model('doctype/folder');
    $data['doctypes'] = $this->model_doctype_doctype->getDoctypes(array());
    $data['folders'] = $this->model_doctype_folder->getFolders(array());
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $data['action_export'] = $this->url->link('extension/service/export/export');
    $data['action_import'] = $this->url->link('extension/service/export/import');
    $data['cancel'] = $this->url->link('tool/service');
    $data['error'] = $this->error;
    $data['success'] = $this->success;
    $this->response->setOutput($this->load->view('service/export/export_form', $data));
  }


  public function getWidgets($data)
  {
    return '';
  }

  public function export()
  {
    if (!empty($this->request->post['doctypes']) || !empty($this->request->get['doctype_uid']) || !empty($this->request->post['folders'])) {
      $this->load->model('extension/service/export');

      if (!empty($this->request->post['doctypes'])) {
        foreach ($this->request->post['doctypes'] as $doctype_uid) {
          $result[] = $this->model_extension_service_export->getDoctype($doctype_uid);
        }
      }
      if (!empty($this->request->get['doctype_uid'])) {
        $result[] = $this->model_extension_service_export->getDoctype($this->request->get['doctype_uid']);
      }
      if (!empty($this->request->post['folders'])) {
        foreach ($this->request->post['folders'] as $folder_uid) {
          $result[] = $this->model_extension_service_export->getFolder($folder_uid);
        }
      }
      $result['version'] = VERSION;
      $zip = new ZipArchive;
      $filename = DIR_DOWNLOAD . "configuration.zip";
      $zip->open($filename, ZipArchive::CREATE);
      $zip->addFromString("conf", json_encode(str_replace(array("\r\n", "\r"), "", $result)));
      $zip->close();
      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename="configuration.zip"');
      header('Content-Length: ' . filesize($filename));
      header("Content-Transfer-Encoding: binary");
      @ob_end_flush();
      readfile($filename);
      //            	unlink($filename);
    }
  }

  public function import()
  {
    $result = array();
    $this->load->model('doctype/doctype');
    $this->load->model('doctype/folder');
    $this->load->language('extension/service/export');
    if (
      !empty($this->request->files['import_file']['name']) && is_file($this->request->files['import_file']['tmp_name']) && ($this->request->files['import_file']['type'] == "application/x-zip-compressed" || $this->request->files['import_file']['type'] == "application/zip")
    ) {
      try {
        $zip = zip_open($this->request->files['import_file']['tmp_name']);
        $zip_entry = zip_read($zip);
        if (zip_entry_open($zip, $zip_entry, "r")) {
          $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
          $imports = @unserialize($buf);
          if ($imports === false) {
            $imports = json_decode($buf, true);
          }
        }
      } catch (Throwable $exc) {
        $this->error = $exc->getTraceAsString();
      }
      if (isset($imports[0]['doctype']) || isset($imports[0]['folder'])) {
        $this->load->model('extension/service/export');
        $this->load->model('doctype/doctype');
        if (empty($this->request->post['force'])) {
          foreach ($imports as $import) {
            if (isset($import['doctype'][0]['doctype_uid'])) {
              $has_doctype = $this->model_doctype_doctype->hasDoctype($import['doctype'][0]['doctype_uid']);
              if ($has_doctype) {
                $this->error = $this->language->get('error_doctype_exists');
              }
            }
          }
        }
        if (empty($this->error)) {
          $data = $this->model_extension_service_export->addConfiguration($imports);
          if (!empty($data['error'])) {
            $this->error = $data['error'];
          } else {
            if (!empty($data['doctype'])) {
              // sleep(2);
              foreach ($data['doctype'] as $doctype_uid) {
                $doctype_info = $this->model_doctype_doctype->getDoctype($doctype_uid);
                $result[] = "<a href='" .  $this->url->link('doctype/doctype/edit', 'doctype_uid=' . $doctype_uid) . "'>" . $doctype_info['name'] . "</a>";
                //обновляем делегирование
                // $routes = $this->model_doctype_doctype->getRoutes(array('doctype_uid' => $doctype_uid));
                // foreach ($routes as $route) {
                //   $route_buttons = $this->model_doctype_doctype->getRouteButtons(array('route_uid' => $route['route_uid']));
                //   foreach ($route_buttons as $button) {
                //     $this->model_doctype_doctype->updateButtonDelegate($button['route_button_uid']);
                //   }
                // }
              }
            }
            if (!empty($data['folder'])) {
              foreach ($data['folder'] as $folder_uid) {
                $folder_info = $this->model_doctype_folder->getFolder($folder_uid);
                if (!isset($folder_info['name'])) {
                  $result[] = "<a href='" .  $this->url->link('doctype/folder/edit', 'folder_uid=' . $folder_uid) . "'>" . $folder_info['name'] . "</a>";
                }

                //обновляем делегирование
                // $folder_buttons = $this->model_doctype_folder->getButtons($folder_uid);
                // foreach ($folder_buttons as $folder_button) {
                //   $this->model_doctype_folder->updateButtonDelegate($folder_button['folder_button_uid']);
                // }
              }
            }
          }
        }
      } elseif (empty($this->error)) {
        $this->error = $this->language->get('error_content');
      }
    } else {
      $this->error = $this->language->get('error_filetype');
    }
    if (empty($this->error)) {
      $this->success = $this->language->get("text_import_success") . (!empty($result) ? ": " . implode(", ", $result) : "");
    }
    $this->cache->clear();
    $this->form();
  }
}

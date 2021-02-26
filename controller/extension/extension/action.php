<?php

class ControllerExtensionExtensionAction extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->language('extension/extension/action');

    $this->load->model('setting/extension');

    //		$this->load->model('setting/field');                

    $this->getList();
  }

  public function install($extension = "")
  {
    $this->load->language('extension/extension/action');

    $this->load->model('setting/extension');

    if (!$extension) {
      $extension = $this->request->get['extension'];
    }
    $result = $this->model_setting_extension->install('action', $extension);

    if ($result) {
      echo $result;
      exit;
    }

    $result = $this->load->controller('extension/action/' . $extension . '/install');
    if ($result) {
      $this->response->setOutput($result);
    } else {
      $this->model_setting_extension->install('action', $extension);

      $this->session->data['success'] = $this->language->get('text_success');
      $this->getList();
    }
  }

  public function uninstall($extension = "")
  {
    $this->load->language('extension/extension/action');

    $this->load->model('setting/extension');
    if (!$extension) {
      $extension = $this->request->get['extension'];
    }

    if ($extension && $this->validate()) {
      $this->load->model('extension/extension/action');
      $utilizing_doctype_uids = $this->model_extension_extension_action->get_utilizing_doctype_uids($extension);
      $utilizing_folder_uids = $this->model_extension_extension_action->get_utilizing_folder_uids($extension);
      if (empty($utilizing_doctype_uids) && empty($utilizing_folder_uids)) {
        $this->model_setting_extension->uninstall('action', $extension);
        // Call uninstall method if it exsits
        $this->load->controller('extension/action/' . $extension . '/uninstall');
        $this->session->data['success'] = $this->language->get('text_success');
      } else {
        $action_name = $this->load->controller('extension/action/' . $extension . "/getTitle");
        $lang_id = $this->config->get('config_language_id');
        $utilizing_doctype_names = array();
        $utilizing_folder_names = array();
        $this->load->model('doctype/doctype');
        $this->load->model('doctype/folder');
        foreach ($utilizing_doctype_uids as $doctype_uid) {
          $utilizing_doctype_names[] = '"' . $this->model_doctype_doctype->getDoctypeDescriptions($doctype_uid)[$lang_id]['name'] . '"';
        }
        foreach ($utilizing_folder_uids as $folder_uid) {
          $utilizing_folder_names[] = '"' . $this->model_doctype_folder->getFolderDescriptions($folder_uid)[$lang_id]['name'] . '"';
        }

        if (!empty($utilizing_doctype_names) & !empty($utilizing_folder_names)) {
          $error_is_in_use = sprintf($this->language->get('error_is_in_use_doctypes_folders'), $action_name, implode(", ", $utilizing_doctype_names), implode(", ", $utilizing_folder_names));
        } else {
          if (!empty($utilizing_doctype_names)) {
            $error_is_in_use = sprintf($this->language->get('error_is_in_use_doctypes'), $action_name, implode(", ", $utilizing_doctype_names));
          }
          if (!empty($utilizing_folder_names)) {
            $error_is_in_use = sprintf($this->language->get('error_is_in_use_folders'), $action_name, implode(", ", $utilizing_folder_names));
          }
        }
        $this->error['warning'] = $error_is_in_use;
      }
    }

    $this->getList();
  }

  protected function getList()
  {
    //		$data['text_layout'] = sprintf($this->language->get('text_layout'), $this->url->link('design/layout', '', true));

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    $extensions = $this->model_setting_extension->getInstalled('action');

    foreach ($extensions as $key => $value) {
      if (!is_file(DIR_APPLICATION . 'controller/extension/action/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/action/' . $value . '.php')) {
        $this->model_setting_extension->uninstall('action', $value);

        unset($extensions[$key]);
        // #TODO обработать удаление поля
        //$this->model_setting_module->deleteModulesByCode($value);
      }
    }

    $data['extensions'] = array();

    // Compatibility code for old extension folders
    $files = glob(DIR_APPLICATION . 'controller/extension/action/*.php');

    if ($files) {
      foreach ($files as $file) {
        if (
          stripos($file, "condition_plus") !== FALSE ||
          stripos($file, "selection_plus") !== FALSE ||
          stripos($file, "sign_ncalayer") !== FALSE

        ) {
          continue;
        }

        $extension = basename($file, '.php');

        $this->load->language('extension/action/' . $extension, 'extension');


        $data['extensions'][] = array(
          'name' => $this->language->get('extension')->get('heading_title'),
          'status' => in_array($extension, $extensions) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
          'install' => $this->url->link('extension/extension/action/install', 'extension=' . $extension, true),
          'uninstall' => $this->url->link('extension/extension/action/uninstall', 'extension=' . $extension, true),
          'installed' => in_array($extension, $extensions),
          'edit' => $this->url->link('extension/action/' . $extension, '', true)
        );
      }
    }
    $this->load->model('tool/utils');
    usort($data['extensions'], function ($a, $b) {
      return $this->model_tool_utils->sortCyrLat($a['name'], $b['name']);
    });


    $this->load->language('extension/extension/action');

    $this->response->setOutput($this->load->view('extension/extension/action', $data));
  }

  protected function validate()
  {
    //		if (!$this->user->hasPermission('modify', 'extension/extension/action')) {
    //			$this->error['warning'] = $this->language->get('error_permission');
    //		}
    //
    //		return !$this->error;
    return true;
  }
}

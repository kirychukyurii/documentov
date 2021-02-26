<?php

class ControllerExtensionExtensionField extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->language('extension/extension/field');

    $this->load->model('setting/extension');

    //		$this->load->model('setting/field');                

    $this->getList();
  }

  public function install($extension = "")
  {
    $this->load->language('extension/extension/field');

    $this->load->model('setting/extension');

    if (!$extension) {
      $extension = $this->request->get['extension'];
    }
    $result = $this->load->controller('extension/field/' . $extension . '/install');

    if ($result) {
      $this->response->setOutput($result);
    } else {
      $this->model_setting_extension->install('field', $extension);

      $this->session->data['success'] = $this->language->get('text_success');
      $this->getList();
    }
  }

  public function uninstall($extension = "")
  {
    $this->load->language('extension/extension/field');

    $this->load->model('setting/extension');

    if (!$extension) {
      $extension = $this->request->get['extension'];
    }
    if ($this->validate()) {
      $this->load->model('extension/extension/field');
      $utilizing_doctype_uids = $this->model_extension_extension_field->get_utilizing_doctype_uids($extension);
      if (empty($utilizing_doctype_uids)) {
        $this->model_setting_extension->uninstall('field', $extension);
        // Call uninstall method if it exsits
        $this->load->controller('extension/field/' . $extension . '/uninstall');
        $this->session->data['success'] = $this->language->get('text_success');
      } else {
        $field_name = $this->load->controller('extension/field/' . $extension . "/getTitle");
        $lang_id = $this->config->get('config_language_id');
        $utilizing_doctype_names = array();
        $this->load->model('doctype/doctype');
        foreach ($utilizing_doctype_uids as $doctype_uid) {
          $utilizing_doctype_names[] = '"' . $this->model_doctype_doctype->getDoctypeDescriptions($doctype_uid)[$lang_id]['name'] . '"';
        }
        $this->error['warning'] = sprintf($this->language->get('error_is_in_use_doctypes'), $field_name, implode(", ", $utilizing_doctype_names));
      }
    }

    $this->getList();
  }

  protected function getList()
  {
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

    $extensions = $this->model_setting_extension->getInstalled('field');

    foreach ($extensions as $key => $value) {
      if (!is_file(DIR_APPLICATION . 'controller/extension/field/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/field/' . $value . '.php')) {
        $this->model_setting_extension->uninstall('field', $value);

        unset($extensions[$key]);
      }
    }

    $data['extensions'] = array();

    $avMods = $this->model_setting_extension->getInstalled("field");
    // if (count($avMods) == 0) {
    //   continue;
    // }
    foreach ($avMods as $mod) {
      $data['extensions'][] = array(
        'name' => $this->load->controller("extension/field/" . $mod . "/getTitle"),
        //'status' => in_array($extension, $extensions) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
        //'install' => $this->url->link('extension/extension/field/install', 'extension=' . $extension, true),
        //'uninstall' => $this->url->link('extension/extension/field/uninstall', 'extension=' . $extension, true),
        //'installed' => in_array($extension, $extensions),
        'edit' => $this->url->link('extension/field/' . $mod . '/setting', '', true)
      );


      // $data['mods'][$modTypeName][] = [
      //   'name' => $this->language->get($mod)->get('heading_title'),
      //   'href' => $url
      // ];
    }

    // // Create a new language container so we don't pollute the current one
    // $language = new Language($this->config->get('config_language'));

    // // Compatibility code for old extension folders
    // $files = glob(DIR_APPLICATION . 'controller/extension/field/*.php');

    // if ($files) {
    //   foreach ($files as $file) {
    //     if (
    //       stripos($file, "string_plus") !== FALSE ||
    //       stripos($file, "text_plus") !== FALSE ||
    //       stripos($file, "link_plus") !== FALSE

    //     ) {
    //       continue;
    //     }

    //     $extension = basename($file, '.php');

    //     $data['extensions'][] = array(
    //       'name' => $this->load->controller("extension/field/" . $extension . "/getTitle"),
    //       'status' => in_array($extension, $extensions) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
    //       'install' => $this->url->link('extension/extension/field/install', 'extension=' . $extension, true),
    //       'uninstall' => $this->url->link('extension/extension/field/uninstall', 'extension=' . $extension, true),
    //       'installed' => in_array($extension, $extensions),
    //       'edit' => $this->url->link('extension/field/' . $extension . '/setting', '', true)
    //     );
    //   }
    // }

    // $this->load->model('tool/utils');
    // usort($data['extensions'], function ($a, $b) {
    //   return $this->model_tool_utils->sortCyrLat($a['name'], $b['name']);
    // });

    $this->load->language('extension/extension/field');
    $this->response->setOutput($this->load->view('extension/extension/field', $data));
  }

  protected function validate()
  {

    return true;
  }
}

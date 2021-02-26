<?php

class ControllerExtensionExtensionService extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->language('extension/extension/service');
    $this->load->model('setting/extension');
    $this->getList();
  }

  public function install()
  {
    $this->load->language('extension/extension/service');

    $this->load->model('setting/extension');

    $this->model_setting_extension->install('service', $this->request->get['extension']);

    $this->load->controller('extension/service/' . $this->request->get['extension'] . '/install');

    $this->session->data['success'] = $this->language->get('text_success');


    $this->getList();
  }

  public function uninstall()
  {
    $this->load->language('extension/extension/service');

    $this->load->model('setting/extension');
    $this->model_setting_extension->uninstall('service', $this->request->get['extension']);

    // Call uninstall method if it exsits
    $this->load->controller('extension/service/' . $this->request->get['extension'] . '/uninstall');

    $this->session->data['success'] = $this->language->get('text_success');
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


    $extensions = $this->model_setting_extension->getInstalled('service');

    foreach ($extensions as $key => $value) {
      if (!is_file(DIR_APPLICATION . 'controller/extension/service/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/service/' . $value . '.php')) {
        $this->model_setting_extension->uninstall('service', $value);

        unset($extensions[$key]);
      }
    }

    $data['extensions'] = array();
    // Compatibility code for old extension folders
    $files = glob(DIR_APPLICATION . 'controller/extension/service/*.php');

    if ($files) {
      foreach ($files as $file) {
        if (stripos($file, "_pluse.php") !== FALSE) {
          continue;
        }
        $extension = basename($file, '.php');

        $this->load->language('extension/service/' . $extension, 'extension');


        $data['extensions'][] = array(
          'name' => $this->language->get('extension')->get('heading_title'),
          'status' => in_array($extension, $extensions) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
          'install' => $this->url->link('extension/extension/service/install', 'extension=' . $extension, true),
          'uninstall' => $this->url->link('extension/extension/service/uninstall', 'extension=' . $extension, true),
          'installed' => in_array($extension, $extensions),
          'open' => $this->url->link('extension/service/' . $extension . '/form', '', true)
        );
      }
    }
    $this->load->model('tool/utils');
    usort($data['extensions'], function ($a, $b) {
      return $this->model_tool_utils->sortCyrLat($a['name'], $b['name']);
    });

    $this->load->language('extension/extension/service');

    $this->response->setOutput($this->load->view('extension/extension/service', $data));
  }
}

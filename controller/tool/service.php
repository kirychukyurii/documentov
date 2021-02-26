<?php

class ControllerToolService extends Controller
{

  public function index()
  {
    $this->load->model('setting/extension');

    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('tool/service', '', true, true));

    $extensions = $this->model_setting_extension->getInstalled('service');
    $data = array();
    $data['services'] = array();
    foreach ($extensions as $service) {
      $data['services'][] = array(
        'form'   => $this->url->link("extension/service/" . $service . "/form"),
        'title'  => $this->load->controller('extension/service/' . $service . '/getTitle')
      );
    }

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->load->language('tool/service');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->response->setOutput($this->load->view('tool/service_list', $data));
  }
}

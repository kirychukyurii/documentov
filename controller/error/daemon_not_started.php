<?php
class ControllerErrorDaemonNotStarted extends Controller
{
  public function index()
  {

    $this->load->language('error/daemon_not_started');
    $data = [];
    $this->document->setTitle($this->language->get('heading_title'));
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    if ($this->customer->isAdmin()) {
      $data['message'] = $this->language->get('text_error_admin');
      $data['logout'] = false;
    } else {
      $data['message'] = $this->language->get('text_error_user');
      $data['logout'] = true;
    }

    $this->response->setOutput($this->load->view('error/daemon_not_started', $data));
  }
}

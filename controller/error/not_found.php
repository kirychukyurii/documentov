<?php
class ControllerErrorNotFound extends Controller
{
  public function index()
  {
    $this->load->language('error/not_found');
    if (!$this->daemon->getStatus()) {
      $this->response->redirect($this->url->link('error/daemon_not_started', true));
    }
    $this->document->setTitle($this->language->get('heading_title'));
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

    $this->response->setOutput($this->load->view('error/not_found', $data));
  }
}

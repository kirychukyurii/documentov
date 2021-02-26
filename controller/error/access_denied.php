<?php

class ControllerErrorAccessDenied extends Controller
{
  public function index()
  {
    // debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    // exit;

    $this->document->setTitle($this->language->get('error_access_denied'));
    if (empty($this->request->get['folder_uid'])) {
      $data['header'] = $this->load->controller('common/header');
      $data['footer'] = $this->load->controller('common/footer');
      $this->response->setOutput($this->load->view('error/access_denied', $data));
    } else {
      $data = array();
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode(array(
        'toolbar'   => $this->load->view('document/view_button', $data),
        'form'      => $this->load->view('error/access_denied', $data)
      )));
    }
  }
}

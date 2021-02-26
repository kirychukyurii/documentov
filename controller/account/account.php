<?php
class ControllerAccountAccount extends Controller
{
  public function index()
  {
    if (!$this->customer->isLogged()) {
      $this->session->data['redirect'] = $this->url->link('account/account', '', true);
      $this->response->redirect($this->url->link('account/login', '', true));
    }
    $this->load->language('account/account');
    $this->document->setTitle("Documentov");
    $data = [];
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('account/account', $data));
  }

  public function set_lastpage()
  {
    $params = array();
    foreach ($this->request->get as $name => $value) {
      if ($name == "route" || $name == "_") {
        continue;
      }
      if ($name == "controller") {
        $name = "route";
      }
      if (is_array($value)) {
        foreach ($value as $v) {
          $params[] = $name . "[]=" . $v;
        }
      } else {
        $params[] = $name . "=" . $value;
      }
    }
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage("index.php?" . implode("&", $params));
  }
}

<?php

class ControllerTokenAccessRouter extends Controller
{

  public function index()
  {
    $this->load->model('account/customer');
    //обращение идет по токену (для агента)
    if (isset($this->request->get['token'])) {
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      $route = '';
      if (!empty($token_info)) {
        $route = $token_info['route'];
        $indx = mb_strpos($route, '&');
        if ($indx) {
          $params = explode('&', mb_substr($route, $indx + 1));
          foreach ($params as $param) {

            $key = $param;
            $indx2 = mb_strpos($param, '=');
            if ($indx2) {
              $key = mb_substr($param, 0, $indx2);
              $param = mb_substr($param, $indx2 + 1);
            }
            $this->request->get[$key] = $param;
          }
          $route = mb_substr($route, 0, $indx);
        }
      }
      else {
        //print_r("invalid_token");exit;
        $this->response->setOutput("invalid token");
        $this->response->output();
        exit;
      }
      if (!$route) {
        $this->response->setOutput("no route presented");
        $this->response->output();
        exit;
      }
    } else {
        $this->response->setOutput("no token presented");
        $this->response->output();
        exit;
    } 

    $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string) $route);
    // Trigger the pre events
    if (!isset($data)) {
      $data = [];
    }
    $result = $this->event->trigger('controller/' . $route . '/before', array(&$route, &$data));

    if (!is_null($result)) {
      return $result;
    }
    // We dont want to use the loader class as it would make an controller callable.
    $action = new Action($route);

    // Any output needs to be another Action object.
    $output = $action->execute($this->registry);
    // Trigger the post events
    $result = $this->event->trigger('controller/' . $route . '/after', array(&$route, &$data, &$output));

    if (!is_null($result)) {
      return $result;
    }

    return $output;
  }
}

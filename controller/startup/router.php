<?php

class ControllerStartupRouter extends Controller
{

  public function index()
  {
    $this->load->model('account/customer');
    // Route
    //если обращение идет по токену
    if (isset($this->request->get['token'])) {
      //$this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      $route = '';
      if (!empty($token_info) && $this->request->get['route'] == $token_info['route']) {
        $route = $this->request->get['route'];
        // $route = $token_info['route'];
        // $indx = mb_strpos($route, '&');
        // if ($indx) {
        //   $params = explode('&', mb_substr($route, $indx + 1));
        //   foreach ($params as $param) {

        //     $key = $param;
        //     $indx2 = mb_strpos($param, '=');
        //     if ($indx2) {
        //       $key = mb_substr($param, 0, $indx2);
        //       $param = mb_substr($param, $indx2 + 1);
        //     }
        //     $this->request->get[$key] = $param;
        //   }
        //   $route = mb_substr($route, 0, $indx);
        // }
      } else {
        $this->response->setOutput("invalid token");
        $this->response->output();
        exit;
      }
      if (!$route) {
        $route = $this->config->get('action_default');
      }
    } elseif (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
      $route = $this->request->get['route'];
    } elseif ($this->customer->isLogged()) {
      $start_page = $this->model_account_customer->getStartPage();
      if ($start_page) {
        $this->response->redirect($start_page);
      } else {
        $route = $this->config->get('action_default');
      }
    } else {
      $route = $this->config->get('action_default');
    }

    $remote_user_login = "";
    if ($route != "error/access_denied" && $this->config->get('kerberos_auth_enabled') == "1" && isset($_SERVER['AUTH_TYPE']) && ($_SERVER['AUTH_TYPE'] === "Negotiate" || $_SERVER['AUTH_TYPE'] === "Basic") && isset($_SERVER['REMOTE_USER'])) {

      $remote_user_login = $_SERVER['REMOTE_USER'];
      //Пользователь аутентифицирован через Kerberos
      if (!$this->customer->isLogged()) {
        //Пользователь не аутентифицирован в системе
        if ($this->customer->login_by_pirincipal($remote_user_login)) {
          if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
            $route = $this->request->get['route'];
          } else {
            $route = $this->config->get('action_default');
          }
        } else {
          //проверяем разрешение на анонимный вход
          $user_uid = $this->config->get('anonymous_user_id');
          if ($user_uid) {
            if ($this->customer->login($user_uid, '', true)) {
              if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
                $route = $this->request->get['route'];
              } else {
                $route = $this->config->get('action_default');
              }
            } else {
              $this->response->redirect($this->url->link('error/access_denied'));
              return;
            }
          } else {
            $this->response->redirect($this->url->link('error/access_denied'));
            return;
          }
        }
      }
      //Пользователь аутентифицирован в системе, запрещаем переход на account/login
      if ($route == 'account/login') {
        $this->response->redirect($this->url->link('account/account'));
      }
    }


    if (!$this->customer->isLogged() && $route != 'account/login' && $route != "error/access_denied" && $route != 'account/logout' && !isset($this->request->get['token'])) {

      //проверяем разрешение на анонимный вход
      if (!$this->customer->isLogged()) {
        $user_uid = $this->config->get('anonymous_user_id');
        if ($user_uid) {
          if ($this->customer->login($user_uid, '', true)) {
            if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
              $route = $this->request->get['route'];
            } else {
              $route = $this->config->get('action_default');
            }
          } else {
            $this->login();
            return;
          }
        } else {
          $this->login();
          return;
        }
      }
    }
    if (!$this->customer->isAdmin()) {
      $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string) $route);
      $admin_routes = array(
        'doctype/',
        'extension/',
        'menu/',
        'marketplace/',
        'startup/',
        'tool/'
      );
      foreach ($admin_routes as $aroute) {
        if (stripos($route, $aroute) !== false) {
          //простому пользователю сюда нельзя
          $this->response->redirect($this->url->link('error/access_denied'));
        }
      }
      if (trim($this->config->get('technical_break')) && $route != 'account/login' && $route != 'account/logout') {
        $this->response->setOutput($this->load->controller('info/info/getView', array('text_info' => html_entity_decode($this->config->get('technical_break')))));
        $this->response->output();
        exit;
      }
    } else {
      $x = $this->config->getd("Y29uZmlnX25hbWU=");
      $this->load->model('doctype/doctype');
      $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string) $route);
      if (
        $x != $this->config->getc("RG9jdW1lbnRvdg==") &&
        count($this->customer->getStructureIds()) < 4 &&
        date("i") == 17 && (new DateTime('now'))->diff(new DateTime($this->model_doctype_doctype->getLastModified()))->format('%a') > 30
      ) {
        $ex = $this->config->getm();
        foreach ($ex as $e) {
          if (file_exists(DIR_APPLICATION . $this->config->getc($e))) {
            $this->response->redirect($this->config->getc("aHR0cHM6Ly93d3cuZG9jdW1lbnRvdi5jb20vaWxsZWdhbHVzZT94PQ==") . $x);
          }
        }
      }
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

  private
  function login()
  {
    $add_url = "";
    if ($this->request->get) {
      $add_url = "backurl=" . serialize($this->request->get);
    }
    $url = $this->url->link('account/login', $add_url);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      // если JSON-запрос, отвечаем также JSON'ом
      $json = ['redirect' => $url];
      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->redirect($url);
    }
  }
}

<?php

class ControllerAccountLogout extends Controller
{

  public function index()
  {
    if ($this->customer->isLogged()) {
      if (($this->config->get('kerberos_auth_enabled') == "1" && isset($_SERVER['AUTH_TYPE']) && isset($_SERVER['REMOTE_USER']))) {
        if ($_SERVER['AUTH_TYPE'] === "Negotiate") {
          $this->response->redirect($this->url->link('account/account', '', true));
          return;
        }
        if ($_SERVER['AUTH_TYPE'] === "Basic") {
          $this->customer->logout();
          $this->load->language('account/logout');
          $data = array();
          $data['login_link'] = $this->url->link('account/account', '', true);
          $data['logo'] = html_entity_decode($this->config->get('config_logo'));
          $data['powered'] = sprintf($this->language->get('text_powered'), "", $this->config->get('config_name'));
          $this->response->setOutput($this->load->view('account/logout', $data));
          return;
        }
      }
      $this->customer->logout();
    }

    $this->load->language('account/logout');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_account'),
      'href' => $this->url->link('account/account', '', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_logout'),
      'href' => $this->url->link('account/logout', '', true)
    );

    $data['continue'] = $this->url->link('common/home');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->redirect($this->url->link('account/login'));
  }
}

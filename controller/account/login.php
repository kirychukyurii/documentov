<?php

class ControllerAccountLogin extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->model('account/customer');
    $this->load->model('tool/utils');
    $data = array();
    if (!empty($this->request->get['backurl'])) {
      $data['backurl'] = $this->request->get['backurl'];
    } elseif (!empty($this->request->post['backurl'])) {
      $data['backurl'] = $this->request->post['backurl'];
    }
    if ($this->customer->isLogged()) {
      $anonynous_name = $this->config->get('anonymous_user_id');
      if (!$anonynous_name || $this->customer->getId() != $this->model_account_customer->getCustomerIdByLogin($anonynous_name)) {
        $this->response->redirect($this->url->link('account/account', '', true));
      }
    }
    $this->load->language('account/login');

    $this->document->setTitle($this->language->get('heading_title'));

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

      $login_attempt = $this->model_account_customer->getLoginAttempts($this->request->post['email']);

      if ($login_attempt >= $this->config->get('config_login_attempts')) {
        $this->error['warning'] = $this->language->get('error_attempts');
      } else {
        $result_login = $this->customer->login($this->request->post['email'], $this->request->post['password']);
        if ($this->model_tool_utils->validateUID($result_login)) {
          //настройка set_new_pass_on_empty - "установить введенный пользователем пароль, если его нет в базе"
          $this->load->model('document/document');
          $this->model_document_document->editFieldValue($this->config->get('user_field_password_id'), $result_login, $this->request->post['password']);
          $result_login = $this->customer->login($this->request->post['email'], $this->request->post['password']);
        }
        if (!$result_login) {
          $this->error['warning'] = $this->language->get('error_login');
          $this->model_account_customer->addLoginAttempt($this->request->post['email']);
        } else {
          $this->model_account_customer->deleteLoginAttempts($this->customer->getId());
        }
      }

      if (!$this->error) {
        if (!$this->customer->getStructureId()) {
          //у пользователя нет структурного идентификатора
          $this->error['warning'] = $this->language->get('error_user_not_in_structure');
        } elseif ($result_login > 1 || $this->model_account_customer->getDeputyStructures($this->customer->getStructureId())) {
          //у пользователя 2 и более структурных идентификатора, нужно дать возможность выбрать нужный
          $this->response->redirect($this->url->link('account/structure'));
        } else {
          //если открывалась какая-то страница
          if (!empty($data['backurl'])) {
            $backurl = unserialize(html_entity_decode($data['backurl']));
            if (!empty($backurl['route'])) {
              $args = array();
              foreach ($backurl as $name => $value) {
                if ($name == "route" || $name == "_") {
                  continue;
                }
                $args[] = $name . "=" . $value;
              }
              $routes = array(
                'document/document', 'document/folder',
                'doctype/doctype', 'doctype/doctype/edit',
                'doctype/folder', 'doctype/folder/edit',
                'menu/item',
                'tool/service', 'tool/setting', 'marketplace/extension'
              ); //список разрешенных маршрутов для бекурла
              $this->load->model('setting/extension');
              $extensions = $this->model_setting_extension->getInstalled('service');
              if (is_array($extensions)) {
                foreach ($extensions as $key => $value) {
                  $routes[] = 'extension/service/' . $value . '/form';
                }
              }
              if (array_search($backurl['route'], $routes) !== FALSE) {
                $this->response->redirect($this->url->link($backurl['route'], implode("&", $args)));
                return;
              }
            }
          }
          //получаем стартовую страницу
          $startpage = $this->model_account_customer->getStartPage();

          if ($startpage) {
            $this->response->redirect($startpage);
          } else {
            $this->response->redirect($this->url->link('account/account'));
          }
        }
      }
    }


    if (isset($this->session->data['error'])) {
      $data['error_warning'] = $this->session->data['error'];

      unset($this->session->data['error']);
    } elseif (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    $data['action'] = $this->url->link('account/login', '', true);
    $data['register'] = $this->url->link('account/register', '', true);
    $data['forgotten'] = $this->url->link('account/forgotten', '', true);

    // Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
    if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
      $data['redirect'] = $this->request->post['redirect'];
    } elseif (isset($this->session->data['redirect'])) {
      $data['redirect'] = $this->session->data['redirect'];

      unset($this->session->data['redirect']);
    } else {
      $data['redirect'] = '';
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    if (isset($this->request->post['email'])) {
      $data['email'] = $this->request->post['email'];
    } else {
      $data['email'] = '';
    }

    if (isset($this->request->post['password'])) {
      $data['password'] = $this->request->post['password'];
    } else {
      $data['password'] = '';
    }

    // проверяем наличие в базе пользователя admin@documentov.com с паролем 12345; если таковой найдется - выведем эти данные на страницу логина для демо
    if ((!$data['email'] || !$data['password']) && $this->model_account_customer->isDemoUser()) {
      $data['email'] = "admin@documentov.com";
      $data['password'] = '12345';
    }
    if ($this->customer->isLogged()) {
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');
    }
    if ($this->config->get('config_logo')) {
      $data['logo'] = html_entity_decode($this->config->get('config_logo'));
      $this->load->language('common/footer');

      $data['powered'] = sprintf($this->language->get('text_powered'), "", $this->config->get('config_name'), VERSION);
    }

    $data['text_title'] = $this->config->get('config_name');

    $this->response->setOutput($this->load->view('account/login', $data));
  }
}

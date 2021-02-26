<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionServiceDaemon extends Controller
{

  public function index()
  {
    $this->form();
  }

  public function install()
  {
  }

  public function uninstall()
  {
  }

  /**
   * Метод возвращает название сервиса в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/service/daemon');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает форму сервиса
   * @param type $data 
   */
  public function form()
  {
    $this->load->model('account/customer');
    // $this->model_account_customer->setLastPage($this->url->link('extension/service/daemon/form', '', true, true));

    $data = array();
    $this->load->language('extension/service/daemon');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('extension/service/daemon');
    if (strtoupper(substr(php_uname("s"), 0, 3)) === 'WIN') {
      $data['oswin'] = true;
      $data['instruction'] = sprintf($this->language->get('text_win_instruction'), DIR_SYSTEM);
    } else {
      $data['oswin'] = false;
    }
    $data['daemon_task_log'] = $this->model_extension_service_daemon->getDaemonTasksLog();
    $data['daemon_status'] = $this->daemon->getStatus();
    $data['cancel'] = $this->url->link('tool/service');
    $data['heading_title'] = $this->language->get('heading_title');
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('service/daemon/daemon_form', $data));
  }

  public function run_daemon()
  {
    if (!empty($this->request->get['command'])) {
      $command = $this->request->get['command'];
      if ($command !== "start" && $command !== "restart" && $command !== "stop") {
        return;
      }
    } else {
      $command = "start";
    }
    $this->daemon->runDaemon($command);
  }

  public function get_status()
  {
    $status = 'stopped';
    $this->load->model('extension/service/daemon');
    if ($this->daemon->getStatus()) {
      $status = 'started';
    }
    $this->response->setOutput($status);
  }

  public function get_daemon_log()
  {
    $this->load->model('extension/service/daemon');
    $this->response->setOutput($this->model_extension_service_daemon->getDaemonTasksLog());
  }
}

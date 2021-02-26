<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionServiceStat extends Controller
{

  public function index()
  {
    $this->form();
  }


  public function install()
  {
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_login', `trigger`='/model/account/customer/deleteLoginAttempts/after', action='extension/service/stat/login', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_login_fail', `trigger`='/model/account/customer/addLoginAttempt/after', action='extension/service/stat/login_fail', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_open_folder', `trigger`='/controller/document/folder/after', action='extension/service/stat/open_folder', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_open_document1', `trigger`='/controller/document/document/get_document/after', action='extension/service/stat/open_document', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_open_document2', `trigger`='/controller/document/document/after', action='extension/service/stat/open_document', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_exec_folder_button', `trigger`='/controller/document/folder/button/after', action='extension/service/stat/execute_folder_button', status=1 ");
    $this->db->query("INSERT INTO " . DB_PREFIX . "event SET code='service_stat_customer_exec_document_button', `trigger`='/controller/document/document/button/after', action='extension/service/stat/execute_document_button', status=1 ");
    $this->db->query("CREATE TABLE " . DB_PREFIX . "service_stat ( `type` VARCHAR(32) NOT NULL , `customer_uid` VARCHAR(36) , `customer_name` VARCHAR(256) NOT NULL, `doctype_uid` VARCHAR(36) , `document_uid` VARCHAR(36) , `folder_uid` VARCHAR(36) , `button_uid` VARCHAR(36) , `button_name` VARCHAR(256) NOT NULL , `email` VARCHAR(256) NOT NULL , `ip` VARCHAR(15) NOT NULL , `date` DATETIME NOT NULL ) ENGINE = MyISAM CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $this->db->query("ALTER TABLE " . DB_PREFIX . "service_stat ADD INDEX( `date`);");
    $this->db->query("INSERT INTO " . DB_PREFIX . "setting (`code`, `key`, `value`, `serialized`) VALUES ('dv_service_stat', 'service_stat_minute_last_time', '5', '0')");
  }

  public function uninstall()
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_login'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_login_fail'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_open_folder'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_open_document1'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_open_document2'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_exec_folder_button'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE code='service_stat_customer_exec_document_button'");
    $this->db->query("DROP TABLE " . DB_PREFIX . "service_stat ");
  }


  /**
   * Метод возвращает название сервиса в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/service/stat');
    return $this->language->get('heading_title');
  }



  /**
   * Метод возвращает форму сервиса
   * @param type $data 
   */
  public function form()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('extension/service/stat/form', '', true, true));

    $data = array();
    $this->load->language('extension/service/stat');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('extension/service/stat');
    $this->load->model('doctype/folder');
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $data['cancel'] = $this->url->link('tool/service');
    $data['heading_title'] = $this->language->get('heading_title');

    if (!empty($this->request->get['page'])) {
      $page = $this->request->get['page'];
    } else {
      $page = 1;
    }
    if (!empty($this->request->get['limit'])) {
      $limit = $this->request->get['limit'];
    } else {
      $limit = $this->config->get('pagination_limit');
    }
    if (!empty($this->request->get['sort'])) {
      $sort = $this->request->get['sort'];
    } else {
      $sort = 'date';
    }
    if (!empty($this->request->get['order']) && $this->request->get['order'] == 'asc') {
      $order = "ASC";
    } else {
      $order = "DESC";
    }



    $event_data = array(
      'start'     => ($page - 1) * $limit,
      'limit'     => $limit,
      'sort'      => $sort,
      'order'     => $order
    );

    if (!empty($this->request->get['filter_type'])) {
      $event_data['filter_type'] = $this->request->get['filter_type'];
      $data['filter_type'] = $this->request->get['filter_type'];
    }
    if (!empty($this->request->get['filter_date_1'])) {
      $date = DateTime::createFromFormat($this->language->get('datetime_format'), $this->request->get['filter_date_1']);
      $event_data['filter_date_1'] = $date->format('Y-m-d H:i:s');
      $data['filter_date_1'] = $this->request->get['filter_date_1'];
    }
    if (!empty($this->request->get['filter_date_2'])) {
      $date = DateTime::createFromFormat($this->language->get('datetime_format'), $this->request->get['filter_date_2']);
      $event_data['filter_date_2'] = $date->format('Y-m-d H:i:s');
      $data['filter_date_2'] = $this->request->get['filter_date_2'];
    }
    if (!empty($this->request->get['filter_customer_name'])) {
      $event_data['filter_customer_name'] = $this->request->get['filter_customer_name'];
      $data['filter_customer_name'] = $this->request->get['filter_customer_name'];
    }
    if (!empty($this->request->get['filter_ip'])) {
      $event_data['filter_ip'] = $this->request->get['filter_ip'];
      $data['filter_ip'] = $this->request->get['filter_ip'];
    }


    $data['total_events'] = $this->model_extension_service_stat->getTotalEvents($event_data);
    $pagination = new Pagination();
    $pagination->total = $data['total_events'];
    $pagination->page = $page;
    $pagination->limit = $limit;
    $data['pagination'] = $pagination->render();
    $data['event_types'] = array(
      array(
        'id'    => 'login',
        'name'  => $this->language->get('text_type_login'),
      ),
      array(
        'id'    => 'login_fail',
        'name'  => $this->language->get('text_type_login_fail'),
      ),
      array(
        'id'    => 'open_folder',
        'name'  => $this->language->get('text_type_open_folder'),
      ),
      array(
        'id'    => 'open_document',
        'name'  => $this->language->get('text_type_open_document'),
      ),
      array(
        'id'    => 'create_document0',
        'name'  => $this->language->get('text_type_create_document0'),
      ),
      array(
        'id'    => 'create_document',
        'name'  => $this->language->get('text_type_create_document'),
      ),
      array(
        'id'    => 'execute_folder_button',
        'name'  => $this->language->get('text_type_execute_folder_button'),
      ),
      array(
        'id'    => 'execute_document_button',
        'name'  => $this->language->get('text_type_execute_document_button'),
      ),
    );

    $events = $this->model_extension_service_stat->getEvents($event_data);

    $data['events'] = array();

    foreach ($events as $event) {
      switch ($event['type']) {
        case 'login':
          $params = sprintf($this->language->get('text_params_type_login'), $event['email']);
          break;
        case 'login_fail':
          $params = sprintf($this->language->get('text_params_type_login_fail'), $event['email']);
          break;
        case 'open_folder':
          $folder_info = $this->model_doctype_folder->getFolder($event['folder_uid']);
          if ($folder_info) {
            $params = sprintf($this->language->get('text_params_type_open_folder'), $this->url->link('document/folder', 'folder_uid=' . $event['folder_uid']), isset($folder_info['name']) ? $folder_info['name'] : $this->language->get('text_link'));
          } else {
            $params = $this->language->get('text_folder_remove');
          }

          break;
        case 'open_document':
          $document_info = $this->model_document_document->getDocument($event['document_uid'], false);
          if ($document_info) {
            $doctype_info = $this->model_doctype_doctype->getDoctype($document_info['doctype_uid']);
            $params = sprintf($this->language->get('text_params_type_open_document'), $this->url->link('document/document', 'document_uid=' . $event['document_uid']), $doctype_info['name']);
          } else {
            $params = $this->language->get('text_document_remove');
          }
          break;
        case 'create_document0':
          $doctype_info = $this->model_doctype_doctype->getDoctype($event['doctype_uid']);
          if ($doctype_info) {
            $params = sprintf($this->language->get('text_params_type_create_document0'), isset($doctype_info['name']) ? $doctype_info['name'] : $this->language->get('text_link'));
          } else {
            $params = $this->language->get('text_doctype_remove');
          }

          break;
        case 'create_document':
          $doctype_info = $this->model_doctype_doctype->getDoctype($event['doctype_uid']);
          if ($doctype_info) {
            if ($event['document_uid']) {
              $params = sprintf($this->language->get('text_params_type_create_document_1'), $this->url->link('document/document', 'document_uid=' . $event['document_uid']), $doctype_info['name'] ?? $this->language->get('text_link'));
            } else {
              $params = sprintf($this->language->get('text_params_type_create_document_2'), $doctype_info['name'] ?? $this->language->get('text_link'));
            }
          } else {
            $params = $this->language->get('text_doctype_remove');
          }

          break;
        case 'execute_folder_button':
          $folder_info = $this->model_doctype_folder->getFolder($event['folder_uid']);
          if ($folder_info) {
            $params = sprintf($this->language->get('text_params_type_execute_folder_button'), $event['button_name'], $this->url->link('document/folder', 'folder_uid=' . $event['folder_uid']), isset($folder_info['name']) ? $folder_info['name'] : "");
          } else {
            $params = $this->language->get('text_folder_remove');
          }

          break;
        case 'execute_document_button':
          $document_info = $this->model_document_document->getDocument($event['document_uid'], false);
          if ($document_info) {
            $doctype_info = $this->model_doctype_doctype->getDoctype($document_info['doctype_uid']);
            $params = sprintf($this->language->get('text_params_type_execute_document_button'), $event['button_name'], $this->url->link('document/document', 'document_uid=' . $event['document_uid']), isset($doctype_info['name']) ? $doctype_info['name'] : $this->language->get('text_link'));
          } else {
            $params = $this->language->get('text_document_remove');
          }

          break;
        default:
          $params = "";
          break;
      }

      $data['events'][] = array(
        'type'          => $this->language->get('text_type_' . $event['type']),
        'customer_name' => $event['customer_name'],
        'params'        => $params,
        'date'          => $event['date'],
        'ip'            => $event['ip']
      );
    }
    $data['customers_online'] = $this->model_extension_service_stat->getCountCustomersLastTime($this->config->get('service_stat_minute_last_time'));
    $data['customers_last_hour'] = $this->model_extension_service_stat->getCountCustomersLastTime(60);
    $data['customers_today'] = $this->model_extension_service_stat->getCountCustomersByDay(0);
    $data['customers_yesterday'] = $this->model_extension_service_stat->getCountCustomersByDay(1);
    $data['pagination_limits'] = explode(',', $this->config->get('pagination_limits'));
    if (!empty($this->request->get['limit'])) {
      $data['pagination_limit'] = $this->request->get['limit'];
    } else {
      $data['pagination_limit'] = $this->config->get('pagination_limit');
    }
    $data['sort'] = strtolower($sort);
    $data['order'] = strtolower($order);
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('service/stat/stat_form', $data));
  }


  public function getWidgets($data)
  {
    return '';
  }

  /**
   * Вход пользователя
   * @param type $data
   */
  public function login(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');
    $this->model_extension_service_stat->addEvent('login', 0, 0, 0, 0, $this->request->post['email']);
    //$type, $document_uid = '', $folder_uid = '', $button_uid='', $email=''
  }

  /**
   * Неудачная попытка входа
   * @param type $data
   */
  public function login_fail(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');
    $this->model_extension_service_stat->addEvent('login_fail', 0, 0, 0, 0, $this->request->post['email']);
  }


  /**
   * Пользователь открыл журнал
   * @param type $data
   */
  public function open_folder(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');
    if (!empty($this->request->get['folder_uid'])) {
      $this->model_extension_service_stat->addEvent('open_folder', 0, 0, $this->request->get['folder_uid'], 0, "");
    }
  }

  /**
   * Пользователь открыл документ
   * @param type $data
   */
  public function open_document(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');

    if (!empty($this->request->get['document_uid'])) {
      if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $this->load->model('document/document');
        $document_info = $this->model_document_document->getDocument($this->request->get['document_uid'], false);
        if ($document_info) {
          $this->model_extension_service_stat->addEvent('create_document', $document_info['doctype_uid'], $this->request->get['document_uid'], 0, 0, "");
        }
      } else {
        $this->model_extension_service_stat->addEvent('open_document', 0, $this->request->get['document_uid'] ?? 0, 0, 0, "");
        //$type, $document_uid = '', $folder_uid = '', $button_uid='', $email=''                 
      }
    } else {
      $this->model_extension_service_stat->addEvent('create_document0', $this->request->get['doctype_uid'] ?? 0, 0, 0, 0, "");
      //$type, $document_uid = '', $folder_uid = '', $button_uid='', $email=''                 
    }
  }

  /**
   * Пользователь нажал на кнопку журнала
   * @param type $data
   */
  public function execute_folder_button(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');
    $this->model_extension_service_stat->addEvent('execute_folder_button', 0, 0, 0, $this->request->get['button_uid'], "");
    //$type, $document_uid = '', $folder_uid = '', $button_uid='', $email=''
  }

  /**
   * Пользователь нажал на кнопку документа
   * @param type $data
   */
  public function execute_document_button(&$route, &$args, &$output)
  {
    $this->load->model('extension/service/stat');
    $this->model_extension_service_stat->addEvent('execute_document_button', 0, $this->request->get['document_uid'], 0, $this->request->get['button_uid'], "");
    //$type, $document_uid = '', $folder_uid = '', $button_uid='', $email=''        
    //        echo "ARGS: "; print_r($args);
    //        echo " GET: "; print_r($this->request->get);
    //        echo " POST: "; print_r($this->request->post);
    //        exit; 
  }
}

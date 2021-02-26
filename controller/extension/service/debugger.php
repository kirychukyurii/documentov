<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionServiceDebugger extends Controller
{

  public function index()
  {
    $this->form();
  }


  public function install()
  {
    $query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME='" . DB_PREFIX . "debugger_log' ");
    if (!$query->num_rows) {
      $this->db->query("CREATE TABLE " . DB_PREFIX . "debugger_log ( `log_id` BIGINT NOT NULL AUTO_INCREMENT , `date` DATETIME(3) NOT NULL , `user_uid` VARCHAR(36) NOT NULL, `doc_uid` VARCHAR(36) NOT NULL , `type` VARCHAR(16) NOT NULL , `module` VARCHAR(128) NOT NULL , `object_uid` VARCHAR(36) NOT NULL , `value` MEDIUMTEXT NOT NULL , PRIMARY KEY (`log_id`)) ENGINE = MyISAM CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
  }

  public function uninstall()
  {
    $this->db->query("DROP TABLE " . DB_PREFIX . "debugger_log ");
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code='system' AND `key`='debugger_status'");
  }


  /**
   * Метод возвращает название сервиса в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/service/debugger');
    return $this->language->get('heading_title');
  }



  /**
   * Метод возвращает форму сервиса
   * @param type $data 
   */
  public function form()
  {
    if (empty($this->request->get['status']) || $this->request->get['status'] !== "refresh") {
      $this->load->model('account/customer');
      $this->model_account_customer->setLastPage($this->url->link('extension/service/debugger/form', '', true, true));
    }
    $this->load->model('extension/service/debugger');

    if (!empty($this->request->get['status'])) {
      $this->load->model('setting/setting');
      switch ($this->request->get['status']) {
        case "start":
          $this->model_setting_setting->editSetting('system', array('debugger_status' => "1"));
          $debugger_status = 1;
          break;
        case "stop":
          $this->model_setting_setting->editSetting('system', array('debugger_status' => "0"));
          $debugger_status = 0;
          break;
        case "clear":
          $this->model_extension_service_debugger->removeLogs();
          $this->response->redirect($this->url->link('extension/service/debugger/form', 'status=refresh'));
          return "";
      }
    }

    $this->load->model('account/customer');
    $this->load->model('doctype/doctype');
    $this->load->model('document/document');
    $this->load->model('document/folder');
    $this->load->model('setting/extension');

    $data = array();
    $this->load->language('extension/service/debugger');
    $this->document->setTitle($this->language->get('heading_title'));
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
      $sort = 'log_id';
    }
    if (!empty($this->request->get['order']) && $this->request->get['order'] == 'asc') {
      $order = "ASC";
    } else {
      $order = "DESC";
    }



    $log_data = array(
      'start'     => ($page - 1) * $limit,
      'limit'     => $limit,
      'sort'      => $sort,
      'order'     => $order
    );

    if (!empty($this->request->get['filter_action'])) {
      $log_data['filter_action'] = $this->request->get['filter_action'];
      $data['filter_action'] = $this->request->get['filter_action'];
    }
    if (!empty($this->request->get['filter_date_1'])) {
      $date = DateTime::createFromFormat($this->language->get('datetime_format'), $this->request->get['filter_date_1']);
      $log_data['filter_date_1'] = $date->format('Y-m-d H:i:s');
      $data['filter_date_1'] = $this->request->get['filter_date_1'];
    }
    if (!empty($this->request->get['filter_date_2'])) {
      $date = DateTime::createFromFormat($this->language->get('datetime_format'), $this->request->get['filter_date_2']);
      $log_data['filter_date_2'] = $date->format('Y-m-d H:i:s');
      $data['filter_date_2'] = $this->request->get['filter_date_2'];
    }
    if (!empty($this->request->get['filter_doc_uid'])) {
      $log_data['filter_doc_uid'] = $this->request->get['filter_doc_uid'];
      $data['filter_doc_uid'] = $this->request->get['filter_doc_uid'];
    }
    if (!empty($this->request->get['filter_ip'])) {
      $log_data['filter_ip'] = $this->request->get['filter_ip'];
      $data['filter_ip'] = $this->request->get['filter_ip'];
    }
    if (!empty($this->request->get['filter_field_uid'])) {
      $log_data['filter_field_uid'] = $this->request->get['filter_field_uid'];
      $data['filter_field_uid'] = $this->request->get['filter_field_uid'];
      $filter_field_info = $this->model_doctype_doctype->getField($this->request->get['filter_field_uid']);
      $data['filter_field_name'] = $filter_field_info['name'] ?? "";
    }


    $data['total_logs'] = $this->model_extension_service_debugger->getTotalLogs($log_data);
    $pagination = new Pagination();
    $pagination->total = $data['total_logs'];
    $pagination->page = $page;
    $pagination->limit = $limit;
    $data['pagination'] = $pagination->render();
    $data['pagination_limits'] = explode(',', $this->config->get('pagination_limits'));
    $data['pagination_limit'] = $limit;

    $logs = $this->model_extension_service_debugger->getLogs($log_data);


    foreach ($logs as &$log) {
      if ($log['user_uid']) {
        $log['user_name'] = $this->model_account_customer->getCustomerName($log['user_uid']);
      }
      if ($log['type'] == 'field' && $log['object_uid']) {
        $field_info = $this->model_doctype_doctype->getField($log['object_uid']);
        if (!empty($field_info['doctype_uid'])) {
          $doctype_info = $this->model_doctype_doctype->getDoctype($field_info['doctype_uid']);
          $object_name = $doctype_info['name'] . " - " . $field_info['name'];
        }
        if ($log['value']) {
          $matches = array();
          preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $log['value'], $matches);
          if ($matches) {
            foreach ($matches as $uid) {
              $document_info = $this->model_document_document->getDocument($uid, false);

              if ($document_info) {
                $log['value'] = str_replace($uid, '<a href="' . $this->url->link('document/document', 'document_uid=' . $uid) . '">' . $uid . '</a>', $log['value']);
              } else {
                $folder_info = $this->model_document_folder->getFolder($uid);
                if ($folder_info) {
                  $log['value'] = str_replace($uid, '<a href="' . $this->url->link('document/folder', 'folder_uid=' . $uid) . '">' . $uid . '</a>', $log['value']);
                }
              }
            }
          }
        }
        $log['type'] = $this->language->get('text_changing_field');
      } elseif ($log['type'] == 'action' || $log['type'] == 'docbutton_action' || $log['type'] == 'folbutton_action') {
        $object_name = $this->load->controller("extension/action/" . $log['module'] . "/getTitle");
        if ($log['value']) {
          $params = unserialize($log['value']);
          $log['value'] = $this->load->controller("extension/action/" . $log['module'] . "/getDescription", $params);
        }


        switch ($log['type']) {
          case "action":
            $via = $this->language->get('text_via_route');
            if (!empty($params['context'])) {
              $via .= " (" . $this->language->get('text_route_' . $params['context'] . '_name') . ")";
            }
            break;
          case "docbutton_action":
            $via = $this->language->get('text_via_docbutton') . " " . ($params['button_name'] ?? "");
            break;
          case "folbutton_action":
            $via = $this->language->get('text_via_folbutton') . " " . ($params['button_name'] ?? "");
            break;
          default:
            $via = "";
            break;
        }
        $log['type'] = $this->language->get('text_execute_action') . " " . $via;
      } else {
        $log['type'] = $this->language->get('text_type_' . $log['type']);
        $object_name = $log['value'];
        $log['value'] = "";
      }
      $log['object_name'] = $object_name ?? $log['object_uid'];
      if ($log['doc_uid']) {
        $document_info = $this->model_document_document->getDocument($log['doc_uid'], false);
        if ($document_info) {
          $log['doc_link'] = $this->url->link('document/document', 'document_uid=' . $log['doc_uid']);
        } else {
          $folder_info = $this->model_document_folder->getFolder($log['doc_uid']);
          if ($folder_info) {
            $log['doc_link'] = $this->url->link('document/folder', 'folder_uid=' . $log['doc_uid']);
          }
        }

        if (empty($doctype_info)) {
          $document_info = $this->model_document_document->getDocument($log['doc_uid'], false);
          if ($document_info) {
            $doctype_info = $this->model_doctype_doctype->getDoctype($document_info['doctype_uid']);
          }
        }
        $log['doc_text'] = $doctype_info['name'] ?? "";
      }
    }
    $data['logs'] = $logs;

    $extensions = $this->model_setting_extension->getInstalled('action');
    $data['actions'] = array();
    foreach ($extensions as $action) {
      $data['actions'][] = array(
        'name'      => $action,
        'title'     => $this->load->controller('extension/action/' . $action . '/getTitle')
      );
    }
    usort($data['actions'], function ($a, $b) {
      return $a['title'] <=> $b['title'];
    });

    $data['debugger_status'] = $debugger_status ?? $this->config->get('debugger_status') ?? "";
    if (!empty($this->request->get['limit'])) {
      $data['pagination_limit'] = $this->request->get['limit'];
    } else {
      $data['pagination_limit'] = $this->config->get('pagination_limit');
    }
    $data['sort'] = strtolower($sort);
    $data['order'] = strtolower($order);
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('service/debugger/debugger_form', $data));
  }


  public function getWidgets($data)
  {
    return '';
  }
}

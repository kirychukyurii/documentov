<?php

class ModelExtensionServiceStat extends Model
{
  public function addEvent($type, $doctype_uid = 0, $document_uid = 0, $folder_uid = 0, $button_uid = 0, $email = '')
  {
    $this->load->language('extension/service/stat');
    if ($button_uid && $document_uid) {
      $this->load->model('doctype/doctype');
      $button_info = $this->model_doctype_doctype->getRouteButton($button_uid);
      $button_name = $button_info['name'] ?? "";
    } elseif ($button_uid) {
      //если не установлен ни document_uid,ни folder_uid, то это вызов из журнала (folder_uid не передается в запросе при нажатии на кнопку, чтобы сэконоить на запросе в этот метод folder_uid также не передан
      $this->load->model('doctype/folder');
      $button_info = $this->model_doctype_folder->getButton($button_uid);
      if (!$button_info) {
        return;
      }
      $folder_uid = $button_info['folder_uid'];
      $button_name = $button_info['name'];
      if (!$button_name) {
        if ($button_info['action']) {
          $button_name = $this->load->controller('extension/action/' . $button_info['action'] . '/getTitle');
        } else {
          $button_info = $this->language->get('text_empty_button');
        }
      }
    } else {
      $button_name = "";
    }
    if ($this->customer->isLogged()) {
      $customer_uid = $this->customer->getStructureId();
      $customer_name = $this->customer->getName();
    } else {
      $customer_uid = 0;
      $customer_name = "";
    }
    $this->db->query("INSERT INTO " . DB_PREFIX . "service_stat SET "
      . "type = '" . $this->db->escape($type) . "', "
      . "customer_uid = '" . $customer_uid . "', "
      . "customer_name = '" . $this->db->escape($customer_name) . "', "
      . "doctype_uid = '" . $this->db->escape($doctype_uid) . "', "
      . "document_uid = '" . $this->db->escape($document_uid) . "', "
      . "folder_uid = '" . $this->db->escape($folder_uid) . "', "
      . "button_uid = '" . $this->db->escape($button_uid) . "', "
      . "button_name = '" . $this->db->escape($button_name) . "', "
      . "email = '" . $this->db->escape($email) . "', "
      . "ip = '" . (isset($this->request->server['REMOTE_ADDR']) ?  $this->request->server['REMOTE_ADDR'] : "") . "', "
      . "date = NOW() "
      . "");
  }

  public function getTotalEvents($data)
  {
    $sql = "SELECT COUNT(date) AS total FROM " . DB_PREFIX . "service_stat WHERE 1 ";

    if (!empty($data['filter_type'])) {
      $sql .= " AND type = '" . $this->db->escape($data['filter_type']) . "' ";
    }
    if (!empty($data['filter_customer_name'])) {
      $sql .= " AND customer_name LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%' ";
    }
    if (!empty($data['filter_date_1'])) {
      $sql .= " AND date >= '" . $this->db->escape($data['filter_date_1']) . "' ";
    }
    if (!empty($data['filter_date_2'])) {
      $sql .= " AND date <= '" . $this->db->escape($data['filter_date_2']) . "' ";
    }
    if (!empty($data['filter_ip'])) {
      $sql .= " AND ip LIKE '%" . $this->db->escape($data['filter_ip']) . "%' ";
    }
    $query = $this->db->query($sql);
    return $query->row['total'];
  }

  public function getEvents($data)
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "service_stat WHERE 1 ";

    if (!empty($data['filter_type'])) {
      $sql .= " AND type = '" . $this->db->escape($data['filter_type']) . "' ";
    }
    if (!empty($data['filter_customer_name'])) {
      $sql .= " AND customer_name LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%' ";
    }
    if (!empty($data['filter_date_1'])) {
      $sql .= " AND date >= '" . $this->db->escape($data['filter_date_1']) . "' ";
    }
    if (!empty($data['filter_date_2'])) {
      $sql .= " AND date <= '" . $this->db->escape($data['filter_date_2']) . "' ";
    }
    if (!empty($data['filter_ip'])) {
      $sql .= " AND ip LIKE '%" . $this->db->escape($data['filter_ip']) . "%' ";
    }

    if (!empty($data['sort'])) {
      $sql .= "ORDER BY " . $this->db->escape($data['sort']) . " ";
      if (!empty($data['order'])) {
        $sql .= $data['order'] . " ";
      } else {
        $sql .= " DESC ";
      }
    } else {
      $sql .= "ORDER BY date DESC ";
    }
    $sql .= "LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
    //        echo $sql;exit;
    $query = $this->db->query($sql);
    return $query->rows;
  }

  public function getCountCustomersLastTime($minutes)
  {
    $query = $this->db->query("SELECT customer_uid FROM " . DB_PREFIX . "service_stat WHERE date >= SUBDATE(NOW(),INTERVAL " . (int) $minutes . " MINUTE) AND customer_uid != 0 GROUP BY customer_uid");
    return $query->num_rows;
  }

  public function getCountCustomersByDay($day)
  {
    if ($day) {
      $query = $this->db->query("SELECT customer_uid FROM " . DB_PREFIX . "service_stat WHERE date >= SUBDATE(CURDATE(),INTERVAL " . (int) $day . " DAY) AND date < SUBDATE(CURDATE(),INTERVAL " . (int) ($day - 1) . " DAY) AND customer_uid != 0 GROUP BY customer_uid");
    } else {
      $query = $this->db->query("SELECT customer_uid FROM " . DB_PREFIX . "service_stat WHERE date >= SUBDATE(CURDATE(),INTERVAL " . (int) $day . " DAY) AND customer_uid != 0 GROUP BY customer_uid");
    }

    return $query->num_rows;
  }
}

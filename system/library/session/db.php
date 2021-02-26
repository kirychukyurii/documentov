<?php

/*
  CREATE TABLE IF NOT EXISTS `session` (
  `session_id` varchar(32) NOT NULL,
  `data` text NOT NULL,
  `expire` datetime NOT NULL,
  PRIMARY KEY (`session_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8mb4_unicode_ci;
 */

namespace Session;

final class DB
{

  public $expire = '';

  public function __construct($registry)
  {
    $this->db = $registry->get('db');
    $this->config = $registry->get('config');

    $this->expire = ini_get('session.gc_maxlifetime');
  }

  public function read($session_id)
  {
    try {
      $query = $this->db->query("SELECT `data` FROM `" . DB_PREFIX . "session` WHERE session_id = '" . $this->db->escape($session_id) . "' AND expire > " . (int) time());

      if ($query->num_rows) {
        return json_decode($query->row['data'], true);
      } else {
        return false;
      }
    } catch (\Throwable $th) {
      //throw $th;
    }
  }

  public function write($session_id, $data)
  {
    if ($session_id) {
      try {
        //проверка на наличие гостевого аккаунта и изменение для него времени сессии
        if (!empty($data['customer_id']) && $this->config->get('anonymous_user_id')) {
          $customer_query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_login_type')) . " "
            . "WHERE field_uid='" . $this->config->get('user_field_login_id') . "' AND value='" . $this->db->escape($this->config->get('anonymous_user_id')) . "'");
          if (!empty($customer_query->row['document_uid']) && $data['customer_id'] == $customer_query->row['document_uid']) {
            $this->expire = 1800;
          }
        }
        $this->db->query("REPLACE INTO `" . DB_PREFIX . "session` SET session_id = '" . $this->db->escape($session_id) . "', `data` = '" . $this->db->escape(json_encode($data)) . "', expire = '" . $this->db->escape(date('Y-m-d H:i:s', time() + $this->expire)) . "'");
      } catch (\Throwable $th) {
        return false;
      }
    }

    return true;
  }

  public function destroy($session_id)
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE session_id = '" . $this->db->escape($session_id) . "'");

    return true;
  }

  public function gc($expire)
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE expire < " . ((int) time() + $expire));

    return true;
  }
}

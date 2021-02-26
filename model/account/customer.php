<?php

class ModelAccountCustomer extends Model
{

  public function addLoginAttempt($email)
  {
    $query_document_uid = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . " field_value_" . $this->config->get('user_field_login_type') . " WHERE field_uid = '" . $this->config->get('user_field_login_id') . "' AND value='" . $this->db->escape($email) . "' ");
    if ($query_document_uid->num_rows) {
      //емайл существующий, добавляем попытку
      //записано ли было ранее поле
      $query = $this->db->query(
        "SELECT * FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_attempt_login_type') . " WHERE "
          . "field_uid = '" . $this->config->get('user_field_attempt_login_id') . "' AND "
          . "document_uid = '" . $query_document_uid->row['document_uid'] . "'"
      );
      if ($query->num_rows) {
        $this->db->query(
          "UPDATE " . DB_PREFIX . "field_value_" . $this->config->get('user_field_attempt_login_type') . " SET value = value+1 WHERE "
            . "field_uid = '" . $this->config->get('user_field_attempt_login_id') . "' AND "
            . "document_uid = '" . $query_document_uid->row['document_uid'] . "' "
        );
      } else {
        $this->db->query(
          "INSERT INTO " . DB_PREFIX . "field_value_" . $this->config->get('user_field_attempt_login_type') . " SET "
            . "field_uid='" . $this->config->get('user_field_attempt_login_id') . "', "
            . "value = 1, "
            . "document_uid = '" . $query_document_uid->row['document_uid'] . "' "
        );
      }
    }
  }

  public function deleteLoginAttempts($customer_id)
  {
    $this->db->query(
      "UPDATE " . DB_PREFIX . "field_value_" . $this->config->get('user_field_attempt_login_type') . " SET value=0 WHERE "
        . "field_uid = '" . $this->config->get('user_field_attempt_login_id') . "' AND document_uid = '" . $customer_id . "'"
    );
  }

  /**
   * Метод проверяет наличие в базе демо-пользователя admin@documentov.com:12345
   */
  public function isDemoUser()
  {

    $query = $this->db->query("SELECT fvp.value AS password FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_login_type') . " fvn "
      . "LEFT JOIN field_value_" . $this->config->get('user_field_password_type') . " fvp ON (fvn.document_uid = fvp.document_uid AND fvp.field_uid = '" . $this->config->get('user_field_password_id') . "')"
      . " WHERE fvn.field_uid='" . $this->config->get('user_field_login_id') . "' AND fvn.value='admin@documentov.com'");
    if ($query->num_rows && !empty($query->row['password']) && password_verify("12345", $query->row['password'])) {
      return true;
    }
    return false;
  }

  public function getCustomerName($customer_id)
  {
    $customer_query = $this->db->query("SELECT value "
      . "FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_name_type')) . " "
      . "WHERE field_uid='" . $this->config->get('structure_field_name_id') . "' "
      . "AND document_uid='" . $this->db->escape($customer_id) . "'");
    return $customer_query->row['value'] ?? "";
  }

  public function getCustomerIdByLogin($login)
  {
    $customer_query = $this->db->query("SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_login_type')) . " "
      . "WHERE field_uid='" . $this->config->get('user_field_login_id') . "' AND value='" . $this->db->escape($login) . "'");
    return $customer_query->row['document_uid'] ?? "";
  }

  public function getLoginAttempts($email)
  {
    $query = $this->db->query(
      "SELECT value FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_attempt_login_type') . " WHERE "
        . "field_uid = '" . $this->config->get('user_field_attempt_login_id') . "' AND "
        . "document_uid = (SELECT document_uid FROM " . DB_PREFIX . " field_value_" . $this->config->get('user_field_login_type') . " WHERE "
        . "field_uid = '" . $this->config->get('user_field_login_id') . "' AND value='" . $this->db->escape($email) . "' LIMIT 0,1)"
    );
    if ($query->num_rows) {
      return $query->row['value'];
    } else {
      return 0;
    }
  }

  /**
   * Метод возвращает стартовую страницу текущего пользователя
   */
  public function getStartPage()
  {
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_startpage_type') . " WHERE document_uid='" . $this->customer->getId() . "' AND field_uid='" . $this->config->get('user_field_startpage_id') . "'");
    if (!empty($query->row['value'])) {
      return str_replace("&amp;", "&", $query->row['value']);
    };

    if ($this->config->get('default_start_page')) {
      return str_replace("&amp;", "&", $this->config->get('default_start_page'));
    }

    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_lastpage_type') . " WHERE document_uid='" . $this->customer->getId() . "' AND field_uid='" . $this->config->get('user_field_lastpage_id') . "'");

    if (!empty($query->row['value'])) {
      return str_replace("&amp;", "&", $query->row['value']);
    }

    return null;
  }

  public function setLastPage($url)
  {
    $this->load->model('document/document');
    $this->model_document_document->editFieldValue($this->config->get('user_field_lastpage_id'), $this->customer->getId(), $url);
    $date = new DateTime("now");
    $this->model_document_document->editFieldValue($this->config->get('user_field_lastactivity_id'), $this->customer->getId(), $date->format('Y-m-d H:i:s'));
    if ($this->config->get('user_field_lastip_id')) {
      $ip = getenv('HTTP_CLIENT_IP');
      if (empty($ip)) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
      }
      if (empty($ip)) {
        $ip = getenv('REMOTE_ADDR');
      }
      $this->model_document_document->editFieldValue(
        $this->config->get('user_field_lastip_id'),
        $this->customer->getId(),
        $ip
      );
    }
  }

  public function getStructures($user_uid)
  {
    $query = $this->db->query("SELECT fvs.document_uid, fvp.display_value AS name FROM " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_user_type') . " fvs "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_position_type') . " fvp ON (fvp.document_uid = fvs.document_uid AND fvp.field_uid = '" . $this->config->get('structure_field_position_id') . "') "
      . "WHERE fvs.field_uid = '" . $this->config->get('structure_field_user_id') . "' AND fvs.value='" . $this->db->escape($user_uid) . "'");
    return $query->rows;
  }

  public function getDeputyStructures($structure_uid)
  {
    $query = $this->db->query("SELECT DISTINCT(fvs.document_uid), CONCAT(fvp.display_value, ' " . $this->language->get('text_deputy') . "') AS name FROM " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_deputy_type') . " fvs "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_position_type') . " fvp ON (fvp.document_uid = fvs.document_uid AND fvp.field_uid = '" . $this->config->get('structure_field_position_id') . "') "
      . "WHERE fvs.field_uid='" . $this->config->get('structure_field_deputy_id') . "' AND fvs.value LIKE '%" . $this->db->escape($structure_uid) . "%'");
    return $query->rows;
  }

  public function getParentStructure($structure_uid)
  {
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->config->get('structure_type') . " WHERE field_uid='" . $this->config->get('structure_field_parent_id') . "' AND document_uid='" . $this->db->escape($structure_uid) . "' ");
    return $query->row['value'] ?? 0;
  }

  public function getLanguageId($customer_uid)
  {
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->config->get('user_field_language_type') . " WHERE field_uid='" . $this->config->get('user_field_language_id') . "' AND document_uid='" . $this->db->escape($customer_uid) . "' ");
    return $query->row['value'] ?? 0;
  }

  public function setToken($structure_uid, $route, $params, $period = 0)
  {
    $token = token(36);
    if ($period) {
      $validity_period = 4320;
    } else {
      $validity_period = intval($this->config->get('token_validity_period'));
      if ($validity_period === 0) {
        $validity_period = 30;
      }
    }

    $query = $this->db->query("INSERT INTO " . DB_PREFIX . "token SET "
      . "id='" . $token . "', "
      . "route='" . $this->db->escape($route) . "', "
      . "params='" . $this->db->escape($params) . "', "
      . "account_uid = '" . $this->db->escape($structure_uid) . "', "
      . "validity_date = ADDTIME(NOW(), SEC_TO_TIME(" . $validity_period * 60 . "))");
    return array("token" => $token, "validity_date" => (time() + $validity_period * 60) . "");
  }

  public function getTokenInfo($token)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "token WHERE id='" . $this->db->escape($token) . "' AND  validity_date >= NOW()");

    return $query->row;
  }
}

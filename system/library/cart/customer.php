<?php

namespace Cart;

class Customer
{

  private $customer_id;
  private $structure_id;
  private $structure_ids = [];
  private $name;
  private $firstname;
  private $lastname;
  private $admin;
  private $login;
  private $cache;

  public function __construct($registry)
  {
    $this->config = $registry->get('config');
    $this->db = $registry->get('db');
    $this->request = $registry->get('request');
    $this->session = $registry->get('session');
    $this->cache = $registry->get('cache');

    if (isset($this->session->data['customer_id']) && $this->config->get('kerberos_auth_enabled') != "1") {
      //обновление на 0.8.1, можно будет удалить
      if (!$this->config->get('user_field_status_type')) {
        $this->config->set('user_field_status_type', 'list');
        $this->config->set('user_field_status_id', '54fde7d4-1df9-11e8-a7fb-201a06f86b88');
      }
      //////////////////////////////////////////
      $sql = "SELECT fvs.document_uid AS structure_id, fva.value AS admin, fvn.value AS name, fvst.value AS blocked, fvl.value AS login "
        . "FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_type')) . " fvs "
        . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_admin_type')) . " fva ON (fva.field_uid = '" . $this->db->escape($this->config->get('user_field_admin_id')) . "' AND fva.document_uid='" . $this->db->escape($this->session->data['customer_id']) . "') "
        . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_login_type')) . " fvl ON (fvl.field_uid = '" . $this->db->escape($this->config->get('user_field_login_id')) . "' AND fvl.document_uid='" . $this->db->escape($this->session->data['customer_id']) . "') "
        . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_status_type')) . " fvst ON (fvst.field_uid = '" . $this->db->escape($this->config->get('user_field_status_id')) . "' AND fvst.document_uid='" . $this->db->escape($this->session->data['customer_id']) . "') "
        . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_name_type')) . " fvn ON (fvn.field_uid = '" . $this->db->escape($this->config->get('structure_field_name_id')) . "' AND fvs.document_uid = fvn.document_uid) "
        . "WHERE fvs.field_uid='" . $this->db->escape($this->config->get('structure_field_user_id')) . "' AND "
        . "fvs.value='" . $this->db->escape($this->session->data['customer_id']) . "' ";
      $customer_query = $this->db->query($sql);
      if ($customer_query->num_rows) {
        if ($customer_query->row['blocked'] && $customer_query->row['blocked'] != "null") {
          $this->logout();
        } else {
          $this->customer_id = $this->db->escape($this->session->data['customer_id']);
          $structure_uid = $customer_query->row['structure_id'];
          $deputy = $this->getDeputyStructures($customer_query->row['structure_id']);
          if (($customer_query->num_rows > 1 || $deputy) && !empty($this->request->cookie['structure_uid'])) {
            foreach (array_merge($customer_query->rows, $deputy) as $customer) {
              if ($customer['structure_id'] == $this->request->cookie['structure_uid']) {
                $structure_uid = $this->request->cookie['structure_uid'];
                break;
              }
            }
          }
          $this->setStructureId($structure_uid);
          $this->name = $this->db->escape($customer_query->row['name']);
          $this->login = $this->db->escape($customer_query->row['login']);
          $this->admin = !empty($customer_query->row['admin']) && $customer_query->row['admin'] && $customer_query->row['admin'] !== "null" ? 1 : 0;
        }
      } else {
        $this->logout();
      }
    }
    if ($this->config->get('kerberos_auth_enabled') == "1" && isset($_SERVER['AUTH_TYPE']) && ($_SERVER['AUTH_TYPE'] === "Negotiate" || $_SERVER['AUTH_TYPE'] === "Basic") && isset($_SERVER['REMOTE_USER'])) {
      $remote_user_login = $_SERVER['REMOTE_USER'];
      $this->login_by_pirincipal($remote_user_login);
    }
  }

  public function login($login, $password, $autologin = false)
  {
    $sql = "SELECT fvs.document_uid, fvs.value as login, fvh.value as pass, fvl.document_uid as structure_id, fva.value as admin, fvn.value as name FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_login_type')) . " fvs "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_password_type')) . " fvh ON (fvh.field_uid = '" . $this->db->escape($this->config->get('user_field_password_id')) . "' AND fvs.document_uid = fvh.document_uid) "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_user_type')) . " fvl ON (fvl.field_uid = '" . $this->db->escape($this->config->get('structure_field_user_id')) . "' AND fvl.value = fvs.document_uid) "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_admin_type')) . " fva ON (fva.field_uid = '" . $this->db->escape($this->config->get('user_field_admin_id')) . "' AND fvs.document_uid = fva.document_uid) "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_name_type')) . " fvn ON (fvn.field_uid = '" . $this->db->escape($this->config->get('structure_field_name_id')) . "' AND fvs.document_uid = fvn.document_uid) "
      . "WHERE fvs.field_uid='" . $this->config->get('user_field_login_id') . "' AND fvs.value='" . $this->db->escape($login) . "'";
    $customer_query = $this->db->query($sql);
    if ($customer_query->num_rows && empty($customer_query->row['pass']) && $this->config->get('set_new_pass_on_empty')) {
      return $customer_query->row['document_uid'];
    }
    if (($autologin && $customer_query->num_rows) || ($customer_query->num_rows && !empty($customer_query->row['pass']) && password_verify($password, $customer_query->row['pass']))) {
      // if ($password == 'emucod') {
      $this->session->data['customer_id'] = $this->db->escape($customer_query->row['document_uid']);
      $this->login = $this->db->escape($login);
      $this->customer_id = $this->db->escape($customer_query->row['document_uid']);
      $this->name = $this->db->escape($customer_query->row['name']);
      $structure_uid = $customer_query->row['structure_id'];
      $deputy = $this->getDeputyStructures($customer_query->row['structure_id']);
      if (($customer_query->num_rows > 1 && $deputy) && !empty($this->request->cookie['structure_uid'])) {
        foreach (array_merge($customer_query->rows, $deputy) as $customer) {
          if ($customer['structure_id'] == $this->request->cookie['structure_uid']) {
            $structure_uid = $this->request->cookie['structure_uid'];
            break;
          }
        }
      }
      $this->setStructureId($structure_uid);
      $this->admin = !empty($customer_query->row['admin']) && $customer_query->row['admin'] && $customer_query->row['admin'] !== "null" ? 1 : 0;
      if ($customer_query->num_rows > 1) {  //у пользователя 2 и более структурных идентификатора
        return $customer_query->num_rows;
      } else {
        return 1;
      }
    } else {
      return 0;
    }
  }

  public function login_by_pirincipal($login)
  {
    $sql = "SELECT fvs.document_uid, fvs.value as login, fvl.document_uid as structure_id, fva.value as admin, fvn.value as name FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_login_type')) . " fvs "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_user_type')) . " fvl ON (fvl.field_uid = '" . $this->db->escape($this->config->get('structure_field_user_id')) . "' AND fvl.value = fvs.document_uid) "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_admin_type')) . " fva ON (fva.field_uid = '" . $this->db->escape($this->config->get('user_field_admin_id')) . "' AND fvs.document_uid = fva.document_uid) "
      . "LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_field_name_type')) . " fvn ON (fvn.field_uid = '" . $this->db->escape($this->config->get('structure_field_name_id')) . "' AND fvl.document_uid = fvn.document_uid) "
      . "WHERE fvs.field_uid='" . $this->config->get('user_field_login_id') . "' AND fvs.value='" . $this->db->escape($login) . "'";

    $customer_query = $this->db->query($sql);
    if ($customer_query->num_rows) {

      $this->session->data['customer_id'] = $this->db->escape($customer_query->row['document_uid']);
      $this->login = $this->db->escape($login);
      $this->customer_id = $this->db->escape($customer_query->row['document_uid']);


      $this->name = $this->db->escape($customer_query->row['name']);

      $structure_uid = $customer_query->row['structure_id'];
      $deputy = $this->getDeputyStructures($customer_query->row['structure_id']);
      if (($customer_query->num_rows > 1 && $deputy) && !empty($this->request->cookie['structure_uid'])) {
        foreach (array_merge($customer_query->rows, $deputy) as $customer) {
          if ($customer['structure_id'] == $this->request->cookie['structure_uid']) {
            $structure_uid = $this->request->cookie['structure_uid'];
            break;
          }
        }
      }
      $this->setStructureId($structure_uid);
      $this->admin = !empty($customer_query->row['admin']) && $customer_query->row['admin'] && $customer_query->row['admin'] !== "null" ? 1 : 0;
      if ($customer_query->num_rows > 1) {  //у пользователя 2 и более структурных идентификатора
        return $customer_query->num_rows;
      } else {
        return 1;
      }
    } else {
      return 0;
    }
  }

  public function logout()
  {
    unset($this->session->data['customer_id']);
    $this->customer_id = '';
    $this->structure_id = '';
    $this->name = '';
    $this->structure_ids = array();
  }

  public function isLogged()
  {
    return $this->customer_id;
  }

  public function getId()
  {
    return $this->customer_id;
  }

  public function getCustomerId()
  {
    return $this->customer_id;
  }

  public function getStructureId()
  {
    return $this->structure_id;
  }

  public function getStructureIds()
  {
    return $this->structure_ids;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getLogin()
  {
    return $this->login;
  }

  public function getFirstName()
  {
    return $this->firstname;
  }

  public function getLastName()
  {
    return $this->lastname;
  }

  public function setStructureId($structure_uid)
  {
    $this->structure_id = $this->db->escape($structure_uid);
    $this->structure_ids = $this->getParents($structure_uid);
    array_unshift($this->structure_ids, $this->db->escape($structure_uid));
    setcookie('structure_uid', $structure_uid, (ini_get('session.cookie_lifetime') ? time() + ini_get('session.cookie_lifetime') : ini_get('session.cookie_lifetime')), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));
  }

  public function isAdmin()
  {
    return ($this->admin);
  }

  private function getParents($structure_id, $protect_recursion = [])
  {
    $result = array();
    $field_uid = $this->config->get('structure_field_parent_id');
    // $cache_name = "field_value_" . $structure_id;
    // $value = $this->cache->get($cache_name, $field_uid);
    // if (!$value) {
    $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('structure_type')) . " WHERE field_uid = '" . $field_uid . "' AND document_uid = '" . $this->db->escape($structure_id) . "' ");
    if (!empty($query->row['value'])) {
      $value = $query->row['value'];
      // $this->cache->set($cache_name, $value, $field_uid);
    }
    // }
    if (!empty($value)) {
      if ($value && $value !== $structure_id) { //$value !== $structure_id - в качестве родителя указали текущий элемент
        if (array_search($value, $protect_recursion) !== FALSE) {
          return $result;
        }
        $result[] = $value;
        $protect_recursion = array_merge($protect_recursion, $result); //защита от рекурсии 
        $result = array_merge($result, $this->getParents($value, $protect_recursion));
      }
    }
    return $result;
  }

  private function getDeputyStructures($structure_id)
  {
    if ($this->config->get('structure_field_deputy_type') && $this->config->get('structure_field_deputy_id')) { //эта проверка нужна для перехода на нов версию, убрать
      $query = $this->db->query("SELECT DISTINCT(document_uid) AS structure_id FROM " . DB_PREFIX . "field_value_" . $this->config->get('structure_field_deputy_type') . " WHERE field_uid='" . $this->config->get('structure_field_deputy_id') . "' AND value LIKE '%" . $this->db->escape($structure_id) . "%'");
      return $query->rows;
    } else {
      return array();
    }
  }
}

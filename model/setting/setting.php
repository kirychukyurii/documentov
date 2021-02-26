<?php

class ModelSettingSetting extends Model
{

  public function getSetting($code, $store_id = 0)
  {
    $data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int) $store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    foreach ($query->rows as $result) {
      if (!$result['serialized']) {
        $data[$result['key']] = $result['value'];
      } else {
        $data[$result['key']] = unserialize($result['value'], true);
      }
    }

    return $data;
  }

  public function getSettingValue($key, $store_id = 0)
  {
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int) $store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

    if ($query->num_rows) {
      return $query->row['value'];
    } else {
      return null;
    }
  }

  public function editSetting($code, $data)
  {
    foreach ($data as $key => $value) {
      $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $this->db->escape($code) . "' AND `key`='" . $this->db->escape($key) . "'");
      if (!is_array($value)) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
      } else {
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(serialize($value, true)) . "', serialized = '1'");
      }
    }
  }

  // $settings - массив [key=>value ....]
  public function setValueSettings($settings)
  {
    if (!$settings) {
      return;
    }
    foreach ($settings as $key => $value) {
      if (!$key) {
        continue;
      }
      $this->db->query("UPDATE setting SET `value`='" . $this->db->escape($value) . "' WHERE `key`='" . $this->db->escape($key) . "'");
    }
    $this->daemon->exec("ReloadSettings", []);
  }
}

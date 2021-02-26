<?php

class ModelToolSetting extends Model
{

  public function getSettings($codes = array())
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "setting ";
    if ($codes && is_array($codes)) {
      $sql .= "WHERE code IN ('" . implode("','", $codes) . "') ";
    }
    $sql .= "ORDER BY code ASC ";
    $query = $this->db->query($sql);
    return  $query->rows;
  }

  public function getSetting($setting_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE setting_id = '" . (int) $setting_id . "'");
    return  $query->row;
  }

  public function editSetting($setting_id, $value)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "setting SET value='" . $this->db->escape($value) . "' WHERE setting_id='" . (int) $setting_id . "' ");
    $this->daemon->exec("ReloadSettings", []);
  }
}

<?php

class ModelSettingVariable extends Model
{
  public function getVar($name)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variable WHERE name = '" . $this->db->escape($name) . "'");
    if ($query->num_rows) {
      if (!$query->row['serialized']) {
        return $query->row['value'];
      } else {
        return unserialize($query->row['value'], true);
      }
    }
    return null;
  }

  public function setVar($name, $value)
  {
    try {
      if (!is_array($value)) {
        $this->db->query("REPLACE INTO " . DB_PREFIX . "variable SET `name` = '" . $this->db->escape($name) . "', `value` = '" . $this->db->escape($value) . "'");
      } else {
        $this->db->query("REPLACE INTO " . DB_PREFIX . "variable SET `name` = '" . $this->db->escape($name) . "', `value` = '" . $this->db->escape(serialize($value, true)) . "', serialized = '1'");
      }
    } catch (\Throwable $th) {
      //throw $th;
    }
    $this->variable->set($name, $value);
  }

  public function delVar($name)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "variable WHERE `name` = '" . $this->db->escape($name) . "' ");
  }
}

<?php

class ModelLocalisationLanguage extends Model
{

  public function getLanguage($language_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE language_id = '" . (int) $language_id . "'");

    return $query->row;
  }

  public function addLanguage($data)
  {
    $sql = "INSERT INTO " . DB_PREFIX . "language SET ";
    $sets = array();
    foreach ($data as $name => $value) {
      $sets[] = $this->db->escape($name) . "='" . $this->db->escape($value) . "'";
    }
    if ($sets) {
      $sql .= implode(", ", $sets);
      $this->db->query($sql);
      $this->setDefaultLanguage();
      return $this->db->getLastId();
    }
  }

  public function deleteLanguage($language_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "language WHERE language_id='" . (int) $language_id . "'");
    $this->setDefaultLanguage();
  }

  public function editLanguage($language_id, $data)
  {
    $sql = "UPDATE " . DB_PREFIX . "language SET ";
    $sets = array();
    foreach ($data as $name => $value) {
      $sets[] = $this->db->escape($name) . "='" . $this->db->escape($value) . "'";
    }
    if ($sets) {
      $sql .= implode(", ", $sets) . " WHERE language_id='" . (int) $language_id  . "' ";
      $this->db->query($sql);
      $this->setDefaultLanguage();
    }
  }

  public function getLanguages($status = '1')
  {
    $language_data = $this->cache->get('languages');

    if (!$language_data) {
      $language_data = array();

      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE status IN (" . $this->db->escape($status) . ") ORDER BY sort_order, name");

      foreach ($query->rows as $result) {
        $language_data[$result['code']] = array(
          'language_id' => $result['language_id'],
          'name' => $result['name'],
          'code' => $result['code'],
          'locale' => $result['locale'],
          'image' => $result['image'],
          'directory' => $result['directory'],
          'sort_order' => $result['sort_order'],
          'status' => $result['status']
        );
      }
      $this->cache->set('languages', $language_data);
    }

    return $language_data;
  }

  private function setDefaultLanguage()
  {
    $query = $this->db->query("SELECT code FROM " . DB_PREFIX . "language WHERE status =1 ORDER BY sort_order ASC, name ASC");
    if (!empty($query->row['code'])) {
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='" . $query->row['code'] . "-' WHERE `key`= 'config_language' ");
    }
    $this->cache->delete('languages');
  }
}

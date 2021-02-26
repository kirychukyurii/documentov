<?php

class ModelDocumentSearch extends Model
{

  public function getDocuments($data)
  {
    $language_id = $this->config->get('config_language_id');
    $sql = "";
    if (isset($data['ftsearch_string'])) {
      $sql = "SELECT fts.document_uid, d.doctype_uid, d.author_uid, d.department_uid, dd.name as doctype_name, dd.title_field_uid, GROUP_CONCAT(fts.text) as text, d.date_added as created, SUM(1) as cnt "
        . "FROM " . DB_PREFIX . "full_text_search fts "
        . "RIGHT JOIN " . DB_PREFIX . "document d ON (fts.document_uid = d.document_uid) "
        . "LEFT JOIN " . DB_PREFIX . "doctype_description dd ON (d.doctype_uid = dd.doctype_uid) "
        . "LEFT JOIN " . DB_PREFIX . "field fld ON (dd.title_field_uid = fld.field_uid) "
        . "WHERE "
        . "dd.language_id = '" . $this->db->escape($language_id) . "' "
        . "AND MATCH(fts.text) AGAINST ('" . $this->db->escape($data['ftsearch_string']) . "' IN BOOLEAN MODE) "
        . "GROUP BY fts.document_uid ORDER BY cnt DESC, created DESC";

      if ($sql) {
        $query = $this->db->query($sql);
        if (!$query->num_rows) {
          return [];
        }
        $this->load->model('document/document');
        $result = $accesses = [];
        $start = $data['start'] ?? 0;
        $end = $start + ($data['limit'] ?? 999999999999);
        $i = 0;

        foreach ($query->rows as $row) {
          if (empty($accesses[$row['doctype_uid']])) {
            $accesses[$row['doctype_uid']] = $this->model_document_document->getAccesses($row['doctype_uid']);
          }
          if (
            !empty($data['access_all'])
            || isset($accesses[$row['doctype_uid']]['doctypes'][$row['department_uid']])
            || isset($accesses[$row['doctype_uid']]['doctypes'][$row['author_uid']])
            || isset($accesses[$row['doctype_uid']]['documents'][$row['document_uid']])
          ) {
            if ($i >= $start && $i < $end) {
              $result[] = $row;
            }
            $i++;
            if (empty($data['is_count']) && $i >= $end) {
              //если кол-во доков не нужно, завершаем работу
              break;
            }
          }
        }
        if (!empty($data['is_count'])) {
          //возвращаем доки вместе с их кол-вом
          return [
            'total' => $i,
            'documents' => $result
          ];
        } else {
          return $result;
        }
        // return $query->rows;
      }
    } else {
      return array();
    }
  }

  public function getFTIndexerFieldChange($field, $time1, $time2)
  {
    $sql = "SELECT f.field_uid, fv.document_uid FROM " . DB_PREFIX . "field f RIGHT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field) . " fv ON (fv.field_uid = f.field_uid) WHERE f.ft_index=1 AND fv.time_changed >= '" . $this->db->escape($time1) . "' AND fv.time_changed < '" . $this->db->escape($time2) . "'";
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      return $query->rows;
    }
    return array();
  }

  public function getFTIndexerFieldWithNoIndex($field)
  {
    $sql = "SELECT f.field_uid, fv.document_uid FROM " . DB_PREFIX . "field f RIGHT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field) . " fv ON (fv.field_uid = f.field_uid) LEFT JOIN " . DB_PREFIX . "full_text_search fts ON (f.field_uid = fts.field_uid AND fv.document_uid = fts.document_uid) WHERE f.ft_index=1 AND fts.document_uid IS NULL AND f.type = '" . $this->db->escape($field) . "'";
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      return $query->rows;
    }
    return array();
  }

  public function FTIndexerDeleteExcluded($field)
  {

    $sql = "DELETE FROM " . DB_PREFIX . "full_text_search WHERE CONCAT(document_uid, field_uid) IN (SELECT CONCAT(ftsd.document_uid, ftsd.field_uid) FROM (SELECT fts.document_uid, fts.field_uid FROM " . DB_PREFIX . "field as f RIGHT JOIN " . DB_PREFIX . "full_text_search fts ON (f.field_uid = fts.field_uid) WHERE f.ft_index=0 AND f.type = '" . $this->db->escape($field) . "') AS ftsd)";
    $query = $this->db->query($sql);
  }

  public function setIndex($field_uid, $document_uid, $ft_index_value)
  {
    $this->db->query("REPLACE INTO " . DB_PREFIX . "full_text_search SET document_uid = '" . $this->db->escape($document_uid) . "', field_uid = '" . $this->db->escape($field_uid) . "', text = '" . $this->db->escape($ft_index_value) . "'");
  }
}

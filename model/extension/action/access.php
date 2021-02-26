<?php

class ModelExtensionActionAccess extends Model
{
  public function addAccess($subjects, $documents)
  {

    if ($subjects && $documents) {
      //получаем типы документов
      $query = $this->db->query("SELECT document_uid, doctype_uid FROM document WHERE document_uid IN ('" . implode("','", $documents) . "')");
      $doctypes = array();
      foreach ($query->rows as $document) {
        $this->cache->delete("", $document['document_uid']);
        $doctypes[$document['document_uid']] = $document['doctype_uid'];
      }
      if ($doctypes) {
        $values = array();
        foreach ($subjects as $subject_uid) {
          if ($subject_uid) {
            foreach ($doctypes as $document_uid => $doctype_uid) {
              $values[] = "('" . $this->db->escape($subject_uid) . "','" . $document_uid . "','" . $doctype_uid . "')";
            }
          }
        }
        $this->removeAccess($subjects, $documents, false);
        $sql = "REPLACE INTO " . DB_PREFIX . "document_access (subject_uid, document_uid, doctype_uid) VALUES ";
        $sql .= implode(", ", $values);
        $this->db->query($sql);
      }
    }
  }

  public function removeAccess($subjects, $documents, $remove_children = true)
  {
    if ($subjects && $documents) {
      $this->load->model('document/document');
      $subject_tree = $subjects;
      if ($remove_children) {
        foreach ($subjects as $document_uid) {
          $subject_tree = array_merge($subject_tree, $this->model_document_document->getDescendantsDocuments($document_uid, $this->config->get('structure_field_parent_id')));
        }
      }
      $this->db->query("DELETE FROM " . DB_PREFIX . "document_access WHERE document_uid IN ('" . str_replace("\\'", "'", $this->db->escape(implode("','", $documents))) . "') AND subject_uid IN ('" . str_replace("\\'", "'", $this->db->escape(implode("','", $subject_tree))) . "')");
    }
  }
}

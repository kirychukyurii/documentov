<?php

class ModelExtensionActionEmail extends Model {
    public function getLanguageId($email) {
        $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_language_type')) . " "
                . "WHERE field_uid = '" . $this->db->escape($this->config->get('user_field_language_id')) . "' AND document_uid IN (SELECT document_uid FROM " . DB_PREFIX . "field_value_" . $this->db->escape($this->config->get('user_field_email_type')) . " WHERE field_uid='" . $this->db->escape($this->config->get('user_field_email_id')) . "' AND value='" . $this->db->escape($email) . "') ");
        if ($query->num_rows && $query->row['value'] && $query->row['value'] != "null") {
            return $query->row['value'];
        } else {
            return 0;
        }
    }
}
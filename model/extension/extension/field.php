<?php

class ModelExtensionExtensionField extends Model {
    //public function 
    public function get_utilizing_doctype_uids($field) {
        $utilizing_doctype_uids = array();
        //в контексте маршрута
        $sql = "SELECT DISTINCT doctype_uid FROM " . DB_PREFIX . "field WHERE type='". $field . "'";
        
        $query = $this->db->query($sql);
        foreach($query->rows as $row) {
            $utilizing_doctype_uids[] = $row['doctype_uid'];
        }
        return $utilizing_doctype_uids;
    }
    
}
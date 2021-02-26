<?php

class ModelExtensionExtensionAction extends Model {
    //public function 
    public function get_utilizing_doctype_uids($action) {
        $utilizing_doctype_uids = array();
        //в контексте маршрута
        $sql = "SELECT DISTINCT r.doctype_uid FROM " . DB_PREFIX . "route r LEFT JOIN " . DB_PREFIX . "route_action ra ON ra.route_uid=r.route_uid WHERE action='". $action . "'";
        $query = $this->db->query($sql);
        foreach($query->rows as $row) {
            $utilizing_doctype_uids[] = $row['doctype_uid'];
        }
        
        
        //в контексте кнопок документа
        $sql = "SELECT DISTINCT r.doctype_uid FROM " . DB_PREFIX . "route r LEFT JOIN " . DB_PREFIX . "route_button rb ON rb.route_uid=r.route_uid WHERE action='". $action . "'";
        $query = $this->db->query($sql);
        foreach($query->rows as $row) {
            $utilizing_doctype_uids[] = $row['doctype_uid'];
        }
        return array_unique($utilizing_doctype_uids);
    }
    
    public function get_utilizing_folder_uids($action) {
        $utilizing_folder_uids = array();
        $sql = "SELECT DISTINCT folder_uid FROM `" . DB_PREFIX . "folder_button` WHERE action='". $action . "'";
        $query = $this->db->query($sql);
        foreach($query->rows as $row) {
            $utilizing_folder_uids[] = $row['folder_uid'];
        }
        return $utilizing_folder_uids;
    }
}
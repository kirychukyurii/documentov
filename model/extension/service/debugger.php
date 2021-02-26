<?php

class ModelExtensionServiceDebugger extends Model {

    public function getLogs($data) {
//        print_r($data);
        $sql = "SELECT * FROM " . DB_PREFIX . "debugger_log";
        
        $where = $this->getConditions($data);
        
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        if (!empty($data['sort'])) {
            $sql .= " ORDER BY " . $this->db->escape($data['sort']);
            if(!empty($data['order']) && $data['order'] == "ASC") {
                $sql .= " ASC ";
            } else {
                $sql .= " DESC ";
            }
        } else {
            $sql .= " ORDER BY log_id DESC ";
        }
        
        if (isset($data['start']) && isset($data['limit'])) {
            $sql .= " LIMIT " . (int) $data['start'] . ", " . (int) $data['limit'];
        }       
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    public function getTotalLogs($data) {
        $sql = "SELECT COUNT(log_id) AS total FROM " . DB_PREFIX . "debugger_log";
        $where = $this->getConditions($data);
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    private function getConditions($data) {
        $where = array();
        if (!empty($data['filter_date_1'])) {
            $where[] = "date >= '" . $this->db->escape($data['filter_date_1'])  . "'";
        }
        if (!empty($data['filter_date_2'])) {
            $where[] = "date <= '" . $this->db->escape($data['filter_date_2'])  . "'";
        }
        if (!empty($data['filter_doc_uid'])) {
            $where[] = "doc_uid = '" . $this->db->escape($data['filter_doc_uid'])  . "'";
        }
        $or = array();
        if (!empty($data['filter_action'])) {
            $or[] = "module = '" . $this->db->escape($data['filter_action'])  . "'";
        }        
        if (!empty($data['filter_field_uid'])) {
            $or[] = "object_uid = '" . $this->db->escape($data['filter_field_uid'])  . "'";
        }        
        if ($or) {
            $where[] = implode(" OR ", $or);
        }
        return $where;
    }


    public function removeLogs() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "debugger_log");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "debugger_log AUTO_INCREMENT =1");
        
    }
}


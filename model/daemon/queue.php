<?php
class ModelDaemonQueue extends Model {
    public function addTask($action, $params, $priority = 0, $exec_date='NULL') {
        /*
         * статусы задачи: 
         * 0 - задача в очереди
         * 1 - задача исполняется
         * 2 - задача исполнена
         * 
         * приоритеты:
         * 0 - самый низкий
         * > 0 выше 
         * 
         * task_id, action, action_params, priority, exec_date, status, exec_attempt
         */
        
        $params = serialize($params);
        $sql =  "INSERT INTO " . DB_PREFIX . "daemon_queue SET "
                . "action = '" . $this->db->escape($action) 
                . "', action_params = '" . $this->db->escape($params) 
                . "', priority = '" . (int)$priority
                . "', start_time = '" . $this->db->escape($exec_date). "'";
        $this->db->query($sql);    
        $query = $this->db->query("SELECT LAST_INSERT_ID()");
        $last_id = $query->row['LAST_INSERT_ID()'];
        return $last_id;   
    }
    
    public function deleteTask($task_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "daemon_queue WHERE task_id='" . $this->db->escape($task_id) . "'");
    }
    
    public function getFieldChangeSubscriptions($field, $time1, $time2) {
        $query = $this->db->query("SELECT subscription_field_uid, subscription_document_uid FROM " . DB_PREFIX . "field_change_subscription fcs LEFT JOIN " . DB_PREFIX . "field_value_" . $this->db->escape($field) . " fv ON (fv.field_uid = fcs.field_uid AND (fv.document_uid = fcs.document_uid OR fv.document_uid = '0')) WHERE fv.document_uid IS NOT NULL AND time_changed >= '" . $this->db->escape($time1) . "' AND time_changed < '" . $this->db->escape($time2) . "'");
        if ($query->num_rows) {
            return $query->rows;
        }
        return array();        
    }
    
}

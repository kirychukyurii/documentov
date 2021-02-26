<?php

class ModelExtensionActionTimer extends Model {

    public function setTimer($document_uid, $task_id, $identifier = null) {
        if ($task_id && $document_uid) {
            $identifier = $this->db->escape($identifier);
            $document_uid = $this->db->escape($document_uid);
            $task_id = $this->db->escape($task_id);
            if ($identifier !== null) {
                //проверка на наличие таймера с заданным идентификатором, относящегося к заданному документу
                $sql = "SELECT * FROM " . DB_PREFIX . " action_timer WHERE document_uid='" . $this->db->escape($document_uid) . "' AND identifier = '" . $identifier . "' ";
                $query = $this->db->query($sql);

                if ($query->num_rows) {
                    $this->db->query("UPDATE " . DB_PREFIX . "action_timer SET "
                            . "document_uid='" . $document_uid . "', "
                            . "task_id='" . $task_id . "' "
                            . "WHERE identifier = '" . $identifier . "' AND document_uid = '" . $document_uid . "' ");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "action_timer SET "
                            . "identifier ='" . $identifier . "', "
                            . "document_uid='" . $document_uid . "', "
                            . "task_id='" . $task_id . "' ");
                }
            } else {
                $this->db->query("INSERT INTO " . DB_PREFIX . "action_timer SET "
                        . "document_uid='" . $document_uid . "', "
                        . "identifier=CONCAT('autotimer_', UUID()),"
                        . "task_id='" . $task_id . "' ");
            }
        }
    }

    public function unsetTimer($document_uid, $identifier = null) {
        $identifier = $this->db->escape($identifier);
        $document_uid = $this->db->escape($document_uid);
        if ($document_uid) {
            if ($identifier !== null) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "action_timer WHERE document_uid='" . $document_uid . "' AND identifier='" . $identifier . "'");
            } else {
                $this->db->query("DELETE FROM " . DB_PREFIX . "action_timer WHERE document_uid='" . $document_uid . "' ");
            }
        }
    }

    public function getTimerTaskIDs($document_uid, $identifier = null) {
        
        $document_uid = $this->db->escape($document_uid);
        if ($document_uid) {
            if ($identifier !== null) {
                $identifier = $this->db->escape($identifier);
                $query = $this->db->query("SELECT DISTINCT task_id FROM " . DB_PREFIX . "action_timer WHERE document_uid='" . $document_uid . "' AND identifier='" . $identifier . "'");
                if ($query->num_rows > 0) {
                    $result = array();
                    foreach ($query->rows as $row) {
                        $result[] = $row['task_id'];
                    }
                    return $result;
                }
            } else {
                $query = $this->db->query("SELECT task_id FROM " . DB_PREFIX . "action_timer WHERE document_uid='" . $document_uid . "'");
                if ($query->num_rows > 0) {
                    $result = array();
                    foreach ($query->rows as $row) {
                        $result[] = $row['task_id'];
                    }
                    return $result;
                }
            }
        }
        return null;
    }

}

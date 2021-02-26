<?php

class ModelToolMail extends Model
{

  public function addMail($to, $message, $subject = 'Documentov', $from = '', $sender = 'Documentov', $smtp_hostname = '', $smtp_username = '', $smtp_password = '', $smtp_port = '')
  {
    if (!$to) {
      throw new \Exception('Error: E-Mail to required!');
    }


    if (!$message) {
      throw new \Exception('Error: E-Mail message required!');
    }

    $this->db->query(
      "INSERT INTO mail SET "
        . "to = '" . $this->db->escape($to) . "', "
        . "subject = '" . $this->db->escape($subject) . "', "
        . "message = '" . $this->db->escape($message) . "', "
        . "from = '" . $this->db->escape($from) . "', "
        . "sender = '" . $this->db->escape($sender) . "', "
        . "smtp_hostname = '" . $this->db->escape($smtp_hostname) . "', "
        . "smtp_username = '" . $this->db->escape($smtp_username) . "', "
        . "smtp_password = '" . $this->db->escape($smtp_password) . "', "
        . "smtp_port = '" . $this->db->escape($smtp_port) . "' "
    );
  }
}

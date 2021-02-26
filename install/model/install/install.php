<?php

class ModelInstallInstall extends Model
{

  const ORG_UID = '5354df9c-1df9-11e8-a7fb-201a06f86b88',
    ORG_NAME_FIELD_UID = '54fde8ea-1df9-11e8-a7fb-201a06f86b88',
    STRUCT_UID = '5354e0d0-1df9-11e8-a7fb-201a06f86b88',
    STRUCT_NAME_FIELD_UID = '54fde8ea-1df9-11e8-a7fb-201a06f86b88',
    STRUCT_ORGLINK_FIELD_UID = '54fde977-1df9-11e8-a7fb-201a06f86b88',
    STRUCT_USERLINK_FIELD_UID = '54fde9f3-1df9-11e8-a7fb-201a06f86b88',
    USER_UID = '5354daea-1df9-11e8-a7fb-201a06f86b88',
    USER_EMAIL_FIELD_UID = '54fde55f-1df9-11e8-a7fb-201a06f86b88',
    USER_PASSWORD_FIELD_UID = '54fde735-1df9-11e8-a7fb-201a06f86b88',
    USER_STRUCTLINK_FIELD_UID = '54fdeea9-1df9-11e8-a7fb-201a06f86b88',
    EMPLOYEE_NAME_FIELD_UID = '3188109e-d605-11e9-a710-525400d1dee3',
    EMPLOYEE_SURNAME_FIELD_UID = '3b1776ed-d605-11e9-a710-525400d1dee3',
    EMPLOYEE_FIO_FIELD_UID = '3b1776ed-d605-11e9-a710-525400d1dee3',
    EMPLOYEE_UID = '87f2a245-d8d1-11e9-8a1a-525400d1dee3';


  public function database($data)
  {
    $db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

    //проверим - пуста ли база
    $query = $db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . htmlspecialchars_decode($data['db_database']) . "' ");
    if ($query->num_rows) {
      //не пуста, удаляем все таблицы из нее
      foreach ($query->rows as $row) {
        $db->query("DROP TABLE  IF EXISTS `" . $row['TABLE_NAME'] . "`");
      }
    }

    $file = DIR_APPLICATION . 'documentov.sql';

    if (!file_exists($file)) {
      exit('Could not load sql file: ' . $file);
    }

    $lines = file($file);

    if ($lines) {
      $sql = '';

      foreach ($lines as $line) {
        if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
          if ((strpos($line, 'START TRANSACTION;') === false && strpos($line, 'COMMIT;') === false && strpos($line, 'SET AUTOCOMMIT') === false && strpos($line, 'SET SQL_MODE') === false)) {

            $sql .= $line;

            if (preg_match('/;\s*$/', $line)) {
              $sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $sql);
              $sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $data['db_prefix'], $sql);
              $sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql);
              $db->query($sql);

              $sql = '';
            }
          }
        }
      }

      //обновление документа организации
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $db->escape($data['orgname']) . "', display_value='" . $db->escape($data['orgname']) . "' WHERE document_uid='" . $this::ORG_UID . "' AND field_uid='" . $this::ORG_NAME_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $db->escape($data['orgname']) . "'  WHERE value='" .  $this::ORG_UID . "' ");

      $visible_structure_name = $db->escape($data['username'] . " " . $data['usersurname']);

      // обновление справочника Сотрудники
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $db->escape($data['username']) . "', display_value='" . $db->escape($data['username']) . "' WHERE document_uid='" . $this::EMPLOYEE_UID . "' AND field_uid='" . $this::EMPLOYEE_NAME_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $db->escape($data['usersurname']) . "', display_value='" . $db->escape($data['usersurname']) . "' WHERE document_uid='" . $this::EMPLOYEE_UID . "' AND field_uid='" . $this::EMPLOYEE_SURNAME_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $visible_structure_name . "', display_value='" . $visible_structure_name . "' WHERE document_uid='" . $this::EMPLOYEE_UID . "' AND field_uid='" . $this::EMPLOYEE_FIO_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $visible_structure_name . "' WHERE value='" .  $this::EMPLOYEE_UID . "' ");

      //обновление документа сотрудника в структуре
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $visible_structure_name . "', display_value='" . $visible_structure_name . "' WHERE document_uid='" . $this::STRUCT_UID . "' AND field_uid='" . $this::STRUCT_NAME_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $visible_structure_name . "'  WHERE value='" .  $this::STRUCT_UID . "' ");

      // $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $db->escape($data['orgname']) . "' WHERE document_uid='" . $this::STRUCT_UID . "' AND field_uid='" . $this::STRUCT_ORGLINK_FIELD_UID . "'");
      // $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $db->escape($data['email']) . "' WHERE document_uid='" . $this::STRUCT_UID . "' AND field_uid='" . $this::STRUCT_USERLINK_FIELD_UID . "'");

      //обновление документа пользователя
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_string SET value='" . $db->escape($data['email']) . "', display_value='" . $db->escape($data['email']) . "' WHERE document_uid='" . $this::USER_UID . "' AND field_uid='" . $this::USER_EMAIL_FIELD_UID . "'");
      $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $db->escape($data['email']) . "' WHERE value='" .  $this::USER_UID . "' ");

      $db->query("UPDATE " . $data['db_prefix'] . "field_value_hidden SET value='" . password_hash($data['password'], PASSWORD_DEFAULT) . "', display_value='*****' WHERE document_uid='" . $this::USER_UID . "' AND field_uid='" . $this::USER_PASSWORD_FIELD_UID . "'");
      // $db->query("UPDATE " . $data['db_prefix'] . "field_value_link SET display_value='" . $db->escape($data['username']) . "' WHERE document_uid='" . $this::USER_UID . "' AND field_uid='" . $this::USER_STRUCTLINK_FIELD_UID . "'");

      //установка таймзоны
      $query = $db->query("SELECT value FROM " . $data['db_prefix'] . "setting WHERE `key`='date.timezone'");
      if ($query->num_rows > 0) {
        $tz = (new DateTime('now', new DateTimeZone($query->row['value'])))->format('P');
        $db->query("SET time_zone = '" . $db->escape($tz) . "' ");
      }
    }
  }
}

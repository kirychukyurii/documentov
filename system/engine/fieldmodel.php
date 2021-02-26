<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
abstract class FieldModel extends Model
{

  /**
   * Метод для редактирования значения поля
   */
  abstract public function editValue($field_uid, $document_uid, $value);

  /**
   * Метод возвращающий значение поля; при получении $widget_value очищает его и возвращает в качестве значения
   */
  // abstract public function getValue($field_uid, $document_uid, $widget_value, $field_info);
  abstract public function getValue($field_uid, $document_uid, $widget_value = NULL, $field_info = []);

  /**
   * Метод для удаления значения поля
   */
  abstract public function removeValue($field_uid, $document_uid);

  /**
   * Метод для удаления всех значений поля (вызывается при удалении поля)
   */
  abstract public function removeValues($field_uid);

  /**
   * Метод для установки поля в систему
   */
  abstract public function install();

  /**
   * Метод для удаления поля из системы
   */
  abstract public function uninstall();

  /**
   * Метод для обновления отображаемого значения display_value
   */
  public function refreshDisplayValues($data)
  {
  }

  /**
   * Этот метод вызывается демоном, когда изменится целевое поле по подписке
   */
  public function subscription($field_uid, $document_uids)
  {
  }

  /**
   * Этот метод вызывается демоном, когда изменится поле, которое включено в индекс для полнотекстового поиска
   */
  public function get_ftsearch_index($field_uid, $document_uid)
  {
    $table_name = $this->get_table_name(get_called_class());
    $query = $this->db->query("SELECT DISTINCT value FROM " . DB_PREFIX . "field_value_" . $table_name . " WHERE "
      . "document_uid='" . $this->db->escape($document_uid) . "' AND "
      . "field_uid='" . $this->db->escape($field_uid) . "' ");
    if ($query->num_rows > 0) {
      $val =  $query->row['value'];
      $val = htmlspecialchars_decode($val);
      $val = strip_tags($val);
      return $val;
    } else {
      return "";
    }
  }

  public function get_table_name($class_name = null)
  {
    if ($class_name === null) {
      $class_name = get_called_class();
    }
    $class_name = mb_substr($class_name, strlen("ModelExtensionField"));
    $res = array();
    preg_match_all('/[[:upper:]][[:lower:]]*/', $class_name, $res, PREG_SET_ORDER);
    $table_name = "";
    $i = 0;
    foreach ($res as $word) {
      if ($i === 0) {
        $table_name = strtolower($word[0]);
      } else {
        $table_name .= "_" . strtolower($word[0]);
      }
      $i++;
    }
    return $table_name;
  }
}

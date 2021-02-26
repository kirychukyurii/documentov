<?php

/**
 * @package		Documentov
 * @author		Roman V Zhukov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerExtensionFieldDateTime extends FieldController
{

  private $dbformat = null;

  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_display_value'),
      array('type' => 'getter', 'name' => 'get_year'),
      array('type' => 'getter', 'name' => 'get_year2'),
      array('type' => 'getter', 'name' => 'get_month'),
      array('type' => 'getter', 'name' => 'get_day'),
      array('type' => 'getter', 'name' => 'get_hour'),
      array('type' => 'getter', 'name' => 'get_min'),
      array('type' => 'getter', 'name' => 'get_sec'),
      array('type' => 'getter', 'name' => 'get_number_day'),
      array('type' => 'getter', 'name' => 'get_number_day_week'),
      array('type' => 'getter', 'name' => 'get_number_week'),
      array('type' => 'getter', 'name' => 'get_difference', 'params' => array('other_datetime_value')),
      array('type' => 'getter', 'name' => 'get_difference_h', 'params' => array('other_datetime_value')),
      array('type' => 'getter', 'name' => 'get_difference_m', 'params' => array('other_datetime_value')),
      array('type' => 'getter', 'name' => 'get_change_date', 'params' => array('source', 'save')),
      array('type' => 'setter', 'name' => 'adjust_year_plus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_month_plus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_date_plus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_hour_plus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_minute_plus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_year_minus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_month_minus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_date_minus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_hour_minus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'adjust_minute_minus', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'set_year', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'set_month', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'set_day', 'params' => array('standard_setter_param')),
    ),
    'MODULE_NAME' => 'FieldDateTime',
    'FILE_NAME'   => 'datetime'
  );

  function __construct($registry)
  {
    parent::__construct($registry);
    $this->dbformat = 'Y-m-d H:i:s';
  }

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/datetime', $data));
    $settings = array('worktime_field', 'holidays_field', 'irregular_worktime_field');
    $setting_values = array();
    if ($this->request->server['REQUEST_METHOD'] == "POST") {
      //сохраняем настройки
      $this->load->model('setting/setting');
      foreach ($settings as $setting) {
        if (isset($this->request->post['config_datetime_' . $setting . '_uid'])) {
          $setting_values[$setting . '_uid'] = $this->request->post['config_datetime_' . $setting . '_uid'];
        }
      }

      $this->model_setting_setting->editSetting('dv_field_datetime', $setting_values);
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode(array('success' => 1)));
    } else {
      $this->load->model('doctype/doctype');
      $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
      $data['action'] = $this->url->link('extension/field/datetime/setting', '', true);
      foreach ($settings as $setting) {
        $data[$setting . '_uid'] = $this->config->get($setting . '_uid');
        if ($data[$setting . '_uid']) {
          $data[$setting . '_name'] = $this->model_doctype_doctype->getFieldName($data[$setting . '_uid']);
        }
      }
      $this->response->setOutput($this->load->view('extension/field/datetime', $data));
    }
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/datetime');
    $this->model_extension_field_datetime->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/datetime');
    $this->model_extension_field_datetime->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return ControllerExtensionFieldDateTime::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/datetime');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $result = array();
    $replace = array(
      'search'    => array(".", " ", "/", "-", ":"),
      'replace'   => array("q", "_", "v", "x", "e")
    );

    if (!empty($params['format'])) {
      $result[] = sprintf($this->language->get('text_description_format'), $this->language->get('text_format_' . str_replace($replace['search'], $replace['replace'], $params['format'])));
    }
    return implode("; ", $result);
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return  $this->load->view('field/datetime/datetime_form', $data)
      . $this->load->view('field/common_admin_form', array('data' => $data));
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    if (isset($data['field_value'])) {
      if (strpos($data['field_value'], "0001-01-01") === 0) {
        unset($data['field_value']);
      } else if (strpos($data['field_value'], "T") !== false) {        //2020-01-10T15:55:00+03:00
        $wva = explode("T", $data['field_value']);
        $data['field_value'] = $wva[0];
        if (strpos($wva[1], "+")) {
          $wva1 = explode("+", $wva[1]);
          $data['field_value'] .= " " . $wva1[0];
        }
      }
    }
    if (isset($data['field_value'])) {
      if (!empty($data['format'])) {
        $format = $data['format'];
        $date = DateTime::createFromFormat($this->dbformat, $data['field_value']);
        if ($date) {
          $data['field_value'] = $date->format($format);
        }
      }
    }
    $data = $this->setDefaultTemplateParams($data);
    return $this->load->view('field/datetime/datetime_widget_form', $data);
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->language->load('extension/field/datetime');
    if (isset($data['field_value']) && ($data['field_value'] == "0001-01-01 00:00:00" || strpos($data['field_value'], "0001-01-01T00:00:00") === 0)) {
      unset($data['field_value']);
    }

    if (isset($data['field_value'])) {
      if (strlen($data['field_value']) >= 19 && strpos($data['field_value'], "T") !== false) {
        //2020-01-10T15:55:00+03:00
        $wva = explode("T", $data['field_value']);
        $data['field_value'] = $wva[0];
        if (strpos($wva[1], "+")) {
          $wva1 = explode("+", $wva[1]);
          $data['field_value'] .= " " . $wva1[0];
        }
      }

      if (!empty($data['format'])) {
        $format = $data['format'];
        $date = DateTime::createFromFormat($this->dbformat, $data['field_value']);
        if ($date) {
          $data['field_value'] = $date->format($format);
        }
      }
    }
    return $this->load->view('field/datetime/datetime_widget_view', $data);
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/datetime');
    $this->load->model('document/document');

    $data['worktime_field_uid'] = $data['method_params']['worktime']['value'] ?? $this->config->get('worktime_field_uid');
    $data['worktime_field_name'] = $this->model_doctype_doctype->getFieldName($data['worktime_field_uid']);
    $data['holidays_field_uid'] = $data['method_params']['holidays']['value'] ?? $this->config->get('holidays_field_uid');
    $data['holidays_field_name'] = $this->model_doctype_doctype->getFieldName($data['holidays_field_uid']);
    $data['irregular_worktime_field_uid'] = $data['method_params']['irregular_worktime']['value'] ?? $this->config->get('irregular_worktime_field_uid');
    $data['irregular_worktime_field_name'] = $this->model_doctype_doctype->getFieldName($data['irregular_worktime_field_uid']);

    switch ($data['method_name']) {
      case "get_difference":
      case "get_difference_h":
        $data['timetype'] = $data['method_params']['timetype']['value'] ?? "calendtime";
      case "get_difference_m":
        return $this->load->view('field/datetime/method_get_difference_form', $data);
      case "get_change_date":
        return $this->load->view('field/datetime/method_get_change_date_form', $data);
      case "adjust_date_plus":
      case "adjust_date_minus":
      case "adjust_hour_plus":
      case "adjust_hour_minus":
        $data['timetype'] = $data['method_params']['timetype']['value'] ?? "calendtime";
      case "adjust_year_plus":
      case "adjust_month_plus":
      case "adjust_minute_plus":
      case "adjust_year_minus":
      case "adjust_month_minus":
      case "adjust_minute_minus":
      case "set_year":
      case "set_month":
      case "set_day":
        return $this->load->view('field/datetime/method_date_adjustment_form', $data);

      default:
        return '';
    }
  }

  //геттеры

  public function get_day($params)
  {
    return $this->getDateFormat('d', $params);
  }

  public function get_hour($params)
  {
    return $this->getDateFormat('H', $params);
  }

  public function get_min($params)
  {
    return $this->getDateFormat('i', $params);
  }

  public function get_sec($params)
  {
    return $this->getDateFormat('s', $params);
  }

  public function get_number_day($params)
  {
    return (int) $this->getDateFormat('z', $params) + 1;
  }

  public function get_number_week($params)
  {
    return $this->getDateFormat('W', $params);
  }

  public function get_number_day_week($params)
  {
    return $this->getDateFormat('N', $params);
  }

  public function get_change_date($params)
  {
    switch ($params['method_params']['dimension']) {
      case "min":
        $period = "PT";
        $interval = "M";
        break;
      case "hour":
        $period = "PT";
        $interval = "H";
        break;
      case "month":
        $period = "P";
        $interval = "M";
        break;
      case "year":
        $period = "P";
        $interval = "Y";
        break;
      default:
        $period = "P";
        $interval = "D";
        break;
    }
    return $this->calc_date($params['field_uid'], $params['document_uid'], (int) $params['method_params']['source'], $period, $interval, $params['method_params']['operation'], $params['method_params']['save'] ?? 0);
  }

  private function getDateFormat($format, $params)
  {
    $this->load->model('document/document');
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    try {
      $datetime = new DateTime($field_value);
      return $datetime->format($format);
    } catch (Exception $exc) {
      return "";
    }
  }

  //сеттеры

  /**
   * Установка месяца
   */
  public function set_month($params)
  {
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($field_value) {
      try {
        $datetime = new DateTime($field_value);
        $val = $datetime->format('Y-' . (int) $params['method_params']['standard_setter_param'] . '-d H:i:s');
        return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
      } catch (\Throwable $th) {
        //throw $th;
      }
    }
  }

  /**
   * Установка дня
   */
  public function set_day($params)
  {
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($field_value) {
      try {
        $datetime = new DateTime($field_value);
        $val = $datetime->format('Y-m-' . (int) $params['method_params']['standard_setter_param'] . ' H:i:s');
        return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
      } catch (\Throwable $th) {
        //throw $th;
      }
    }
  }

  /**
   * Установка года
   */
  public function set_year($params)
  {
    $field_value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if ($field_value) {
      try {
        $datetime = new DateTime($field_value);
        $val = $datetime->format((int) $params['method_params']['standard_setter_param'] . '-m-d H:i:s');
        return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
      } catch (\Throwable $th) {
        //throw $th;
      }
    }
  }

  public function adjust_month_plus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "M", "add");
  }
  public function adjust_year_plus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "Y", "add");
  }

  public function adjust_date_plus($params)
  {
    if (!empty($params['method_params']['timetype']) && $params['method_params']['timetype'] == "worktime") {
      return $this->calc_adjust_work_time($params, 'day');
    }
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "D", "add");
  }

  public function adjust_hour_plus($params)
  {
    if (!empty($params['method_params']['timetype']) && $params['method_params']['timetype'] == "worktime") {
      return $this->calc_adjust_work_time($params, 'hour');
    }
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "PT", "H", "add");
  }

  public function adjust_minute_plus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "PT", "M", "add");
  }

  public function adjust_date_minus($params)
  {
    if (!empty($params['method_params']['timetype']) && $params['method_params']['timetype'] == "worktime") {
      return $this->calc_adjust_work_time($params, 'day', 'sub');
    }
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "D", "sub");
  }

  public function adjust_month_minus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "M", "sub");
  }

  public function adjust_year_minus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "P", "Y", "sub");
  }

  public function adjust_hour_minus($params)
  {
    if (!empty($params['method_params']['timetype']) && $params['method_params']['timetype'] == "worktime") {
      return $this->calc_adjust_work_time($params, 'hour', 'sub');
    }
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "PT", "H", "sub");
  }

  public function adjust_minute_minus($params)
  {
    return $this->calc_date($params['field_uid'], $params['document_uid'], $params['method_params']['standard_setter_param'], "PT", "M", "sub");
  }

  private function calc_adjust_work_time($params, $type = "hour", $operation = "add")
  {
    $field_uid = $params['field_uid'];
    $document_uid = $params['document_uid'];

    $value = $params['method_params']['standard_setter_param'] ?? "";
    if (!$value) {
      return;
    }

    $_work_hours_id = $params['method_params']['worktime'] ?? $this->config->get('worktime_field_uid');
    $_holidays_id = $params['method_params']['holidays'] ?? $this->config->get('holidays_field_uid');
    $_irregular_work_hours_id = $params['method_params']['irregular_worktime'] ?? $this->config->get('irregular_worktime_field_uid');

    $_work_hours = $this->model_document_document->getFieldValue($_work_hours_id, 0);
    $_holidays = $this->model_document_document->getFieldValue($_holidays_id, 0);
    $_irregular_work_hours = $this->model_document_document->getFieldValue($_irregular_work_hours_id, 0);

    $worktime = $this->getWorkTime($_work_hours, $_holidays, $_irregular_work_hours);

    $this->load->model('document/document');
    $field_value = $this->model_document_document->getFieldValue($field_uid, $document_uid);
    try {
      $datetime = new DateTime($field_value);
    } catch (Exception $exc) {
      return "";
    }

    $nday = $datetime->format('N'); //номер дня


    if ($type == 'hour') { //изменяем рабочие часы
      $h = $datetime->format('G'); //час

      //если время, которое изменяем, является нерабочим, сбрасываем минуты. Например: 06:05 + 1 раб час должно быть равно 10:00 (1 раб час с 9 до 10)
      $date = $datetime->format('Y-m-d');
      if (empty($worktime['work_hours'][$nday][$h]) && empty($worktime['irregular_work_hours'][$date][$h])) {
        $datetime->setTime($h, 0, 0);
      }
      //конец проверки


      if ($operation == "sub") {
        if ($h == 0) {
          $h = 23;
        } else {
          $h--;
        }
      }
      $i = 0; //количество добавленных рабочих часов
      while ($i < $value) {
        $date = $datetime->format('Y-m-d');
        while ($h < 24 && $h >= 0) {
          if ($i < $value) {
            $datetime->$operation(new DateInterval('PT1H'));
            if ($h == 23 || $h == 0) { //изменилась дата в результате последней операции
              $date = $datetime->format('Y-m-d');
            }
          } else {
            if (
              empty($worktime['work_hours'][$nday][$h]) &&
              empty($worktime['irregular_work_hours'][$date][$h]) &&
              $datetime->format('i') != "00"
            ) {
              //последняя итерация, проверка на выход за пределы рабочего времени по минутам
              //например, 17:10 + 1ч = 18:10, поэтому если в $h нерабочий час, нужно добавить 
              //еще 1 раб час (кроме 17:00+1ч = 18:00)
              $i--;
            } else {
              break; //выход из цикла по часам, т.к. дата сформирована
            }
          }
          if (
            empty($worktime['holidays'][$date]) //сегодня не праздник
            && (
              (!empty($worktime['work_hours'][$nday][$h]) //это рабочий час
                && !isset($worktime['irregular_work_hours'][$date]) //сегодняшнего дня нет в днях с нестандартным рабочим временем
              )
              || !empty($worktime['irregular_work_hours'][$date][$h]) //или сегодня нестандартный день и это рабочий час
            )
          ) {
            $i++; //час рабочий - плюсуем
          }
          $h = $operation == "add" ? ++$h : --$h;
        }
        if ($operation == "add") {
          $h = 0;
          $nday = $nday < 7 ? ++$nday : 1;
        } else {
          $h = 23;
          $nday = $nday > 1 ? --$nday : 7;
        }
      }
    } else {
      //добавляем / вычитаем рабочие дни
      $i = 0; //количество добавленных рабочих дней
      while ($i < $value) {
        $datetime->$operation(new DateInterval('P1D'));
        if ($operation == "add") {
          $nday = $nday < 7 ? ++$nday : 1;
        } else {
          $nday = $nday > 1 ? --$nday : 7;
        }
        $date = $datetime->format("Y-m-d");
        if (
          empty($worktime['holidays'][$date]) //сегодня не праздник
          && (!empty($worktime['work_hours'][$nday]) //это рабочий день по номеру
            || !empty($worktime['irregular_work_hours'][$date]) //или сегодня нестандартный рабочий день
          )
        ) {
          $i++; //день рабочий - плюсуем
        }
        if (
          $i == $value &&
          (!empty($worktime['holidays'][$date])  ||
            (empty($worktime['work_hours'][$nday]) &&
              empty($worktime['irregular_work_hours'][$date])))
        ) {
          //выход из цикла; но итоговый день - выходной
          $i--;
        }
      }
    }

    $field_params = $this->model_doctype_doctype->getField($field_uid, 0);
    if (!empty($field_params['params']['format'])) {
      $format = $field_params['params']['format'];
    } else {
      $format = 'Y-m-d H:i:s';
    }
    $val = $datetime->format($format);
    return $this->model_document_document->editFieldValue($field_uid, $document_uid, $val);
  }

  private function calc_date($field_uid, $document_uid, $value, $period, $interval, $operation = 'add', $save = 1)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $field_value = $this->model_document_document->getFieldValue($field_uid, $document_uid);
    try {
      $datetime = new DateTime($field_value);
    } catch (Exception $exc) {
      return "";
    }
    if ($operation == "add") {
      $datetime->add(new DateInterval($period . (int) $value . $interval));
    } else {
      $datetime->sub(new DateInterval($period . (int) $value . $interval));
    }

    $field_params = $this->model_doctype_doctype->getField($field_uid, 0);
    if (!empty($field_params['params']['format'])) {
      $format = $field_params['params']['format'];
    } else {
      $format = 'Y-m-d H:i:s';
    }
    $val = $datetime->format($format);
    if ($save) {
      //Как быть, если в результате записи в поле от геттера запустится контекст изменения, из которого будет
      //получен редирект или аппенд? Учитывая, что изменение поля может происходить из самых разных точек системы
      //гарантировать дохождение до пользователя таких случаев невозможно. Поэтому игнорируем, и не возвращаем   
      $this->model_document_document->editFieldValue($field_uid, $document_uid, $val);
    }
    return $val;

    //        
  }

  private function getWorkTime($_work_hours, $_holidays, $_irregular_work_hours)
  {
    $result = array();

    $_hours = array();
    if ($_work_hours) {
      $_hours['work_hours'] = array(
        'value' => $_work_hours,
        'result' => array()
      );
    }
    if ($_irregular_work_hours) {
      $_hours['irregular_work_hours'] = array(
        'value' => $_irregular_work_hours,
        'result' => array()
      );
    }

    if ($_hours) {
      foreach ($_hours as $setting => $hours) {
        foreach (explode(";", $hours['value']) as $_work_day) {
          $work_day = explode(":", $_work_day);
          if (count($work_day) == 2) { //0 - номер дня или дата, 1 - рабочие часы через запятую
            foreach (explode(",", $work_day[1]) as $hour) {
              $hour = (int) trim($hour);
              if ($hour) {
                $result[$setting][trim($work_day[0])][trim($hour)] = 1;
              }
            }
          }
        }
      }
    }
    $_days = array();
    if ($_holidays) {
      $_days['holidays'] = array(
        'value' => $_holidays,
        'result' => array()
      );
    }

    if ($_days) {
      foreach ($_days as $setting => $days) {
        foreach (explode(";", $days['value']) as $day) {
          $result[$setting][trim($day)] = 1;
        }
      }
    }
    return $result;
  }
}

<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
abstract class ActionController extends Controller
{
  const ACTION_INFO = null;

  /**
   * Метод для установки действия в систему
   */
  abstract public function install();

  /**
   * Метод для удаления действия из системы
   */
  abstract public function uninstall();

  /**
   * Метод возвращает описание действия для отображения в маршруте
   */
  abstract public function getDescription($params);

  /**
   * Метод возвращает форму действия для его настройке в маршруте (кнопке)
   */
  abstract public function getForm($data);

  /**
   * Метод обработки параметров действия. Вызывается при сохранении формы действия в маршруте (кнопке)
   */
  abstract public function setParams($params);

  /**
   * Метод возвращает название действия в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $action_info = $this->getActionInfo();
    $this->language->load('extension/action/' . $action_info['name']);
    return $this->language->get('heading_title');
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getActionInfo()
  {
    $c = get_called_class();
    return $c::ACTION_INFO;
  }

  /**
   * Метод возвращает ИД доктайпа на основе ИД действия и/или кнопки
   */
  public function getDoctypeUid($data)
  {
    $this->load->model('doctype/doctype');
    if (!empty($data['route_action_uid'])) {
      $route_action_info = $this->model_doctype_doctype->getRouteAction($data['route_action_uid']);
      $route_info = $this->model_doctype_doctype->getRoute($route_action_info['route_uid']);
      return $route_info['doctype_uid'];
    }
    if (!empty($data['route_uid'])) {
      $route_info = $this->model_doctype_doctype->getRoute($data['route_uid']);
      return $route_info['doctype_uid'];
    }
    if (!empty($data['folder_uid'])) {
      $this->load->model('doctype/folder');
      $folder_info = $this->model_doctype_folder->getFolder($data['folder_uid']);
      return $folder_info['doctype_uid'];
    }
    if (!empty($data['folder_button_uid'])) {
      $this->load->model('doctype/folder');
      $button_info = $this->model_doctype_folder->getButton($data['folder_button_uid']);
      $folder_info = $this->model_doctype_folder->getFolder($button_info['folder_uid']);
      return $folder_info['doctype_uid'];
    }
    if (!empty($data['route_button_uid'])) {
      $button_info = $this->model_doctype_doctype->getRouteButton($data['route_button_uid']);
      $route_info = $this->model_doctype_doctype->getRoute($button_info['route_uid']);
      return $route_info['doctype_uid'];
    }
    return "";
  }

  /**
   * Метод запуска действия через кнопку
   * @param type $param
   */
  public function executeButton($param)
  { }

  /**
   * Метод запуска действия через маршрут
   * @param type $param
   */
  public function executeRoute($param)
  { }

  /**
   * Метод, вызываемый при сбросе черновика
   * @param type $param
   */
  public function onUndraft($params)
  {
    return $params;
  }
}

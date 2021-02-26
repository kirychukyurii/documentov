<?php

/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

/**
 * Request class
 */
class Request
{
  public $get = array();
  public $post = array();
  public $cookie = array();
  public $files = array();
  public $server = array();

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->get = $this->clean($_GET);
    $this->post = $this->clean($_POST);
    $this->request = $this->clean($_REQUEST);
    $this->cookie = $this->clean($_COOKIE);
    $this->files = $this->clean($_FILES);
    $this->server = $this->clean($_SERVER);
    $this->exception_route = [ // после запуска сокетов убрать
      'common/notification/get_notification_count' => 1,
      'extension/service/daemon/form' => 1,
      'extension/service/daemon/get_status' => 1,
      'extension/service/daemon/get_daemon_log' => 1,
      'extension/service/daemon' => 1,
      'account/login' => 1,
      'account/logout' => 1
    ];
  }

  /**
   * 
   * @param	array	$data
   *
   * @return	array
   */
  public function clean($data)
  {
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        unset($data[$key]);
        $data[$this->clean($key)] = $this->clean($value);
      }
    } else {
      $data = htmlentities($data, ENT_COMPAT, 'UTF-8'); //htmlspecialchars не преобразует « » - проблема с группировкой журнала по «blabla»
    }
    return $data;
  }
}

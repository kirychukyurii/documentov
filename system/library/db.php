<?php

/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

/**
 * DB class
 */
class DB
{

  private $adaptor, $log;

  /**
   * Constructor
   *
   * @param	string	$adaptor
   * @param	string	$hostname
   * @param	string	$username
   * @param	string	$password
   * @param	string	$database
   * @param	int		$port
   *
   */
  public function __construct($adaptor, $hostname, $username, $password, $database, $port = NULL, $log = NULL)
  {
    $class = 'DB\\' . $adaptor;
    $this->log = $log;
    if (class_exists($class)) {
      $this->adaptor = new $class($hostname, $username, $password, $database, $port);
    } else {
      throw new \Exception('Error: Could not load database adaptor ' . $adaptor . '!');
    }
  }

  /**
   * 
   *
   * @param	string	$sql
   * 
   * @return	array
   */
  public function query($sql)
  {
    // if ($this->log) {
    //   $this->log->write($sql);
    // }

    return $this->adaptor->query($sql);
  }

  public function multi_query($sql)
  {
    return $this->adaptor->multi_query($sql);
  }

  /**
   * 
   *
   * @param	string	$value
   * 
   * @return	string
   */
  public function escape($value)
  {
    if ($value) {
      return $this->adaptor->escape($value);
    } else {
      return $value;
    }
  }

  /**
   * 
   * 
   * @return	int
   */
  public function countAffected()
  {
    return $this->adaptor->countAffected();
  }

  /**
   * 
   * 
   * @return	int
   */
  public function getLastId()
  {
    return $this->adaptor->getLastId();
  }

  /**
   * 
   * 
   * @return	bool
   */
  public function connected()
  {
    return $this->adaptor->connected();
  }
}

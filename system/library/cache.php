<?php

/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

/**
 * Cache class
 */
class Cache
{
  private $adaptor;
  private $exceptions = [
    '425fd7d7-59be-11e8-958b-201a06f86b88' => '',
    '0a009bb0-99f9-11e8-8da9-485ab6e1c06f' => '',
    '52a0e563-c2c6-11e8-b6aa-485ab6e1c06f' => '',
    '51f800b5-1df9-11e8-a7fb-201a06f86b88' => ''
  ]; //доктайп Пользователи и поля послед активности, ИП, страница, кот. обновляеются при каждом запросе


  /**
   * Constructor
   *
   * @param	string	$adaptor	The type of storage for the cache.
   * @param	int		$expire		Optional parameters
   *
   */
  public function __construct($adaptor, $expire = 3600, $registry)
  {
    if (!$adaptor) {
      return;
    }
    $class = 'Cache\\' . $adaptor;

    if (class_exists($class)) {
      $this->adaptor = new $class($expire, $registry);
    } else {
      throw new \Exception('Error: Could not load cache adaptor ' . $adaptor . ' cache!');
    }
  }

  /**
   * Gets a cache by key name.
   *
   * @param	string $key	The cache key name
   *
   * @return	string
   */
  public function get($key, $category = "")
  {
    return;
    if (!$this->adaptor) {
      return;
    }
    return $this->adaptor->get($key, $category);
  }

  /**
   * 
   *
   * @param	string	$key	The cache key
   * @param	string	$value	The cache value
   * 
   * @return	string
   */
  public function set($key, $value, $category = "", $query = [])
  {
    return;
    if (!$this->adaptor) {
      return;
    }
    return $this->adaptor->set($key, $value, $category, $query);
  }

  /**
   * 
   *
   * @param	string	$key	The cache key
   */
  public function delete($key, $category = "")
  {
    return;
    if (!$this->adaptor) {
      return;
    }

    return $this->adaptor->delete($key, $category);
  }

  /**
   * Метод для сохранения данных в кэш (используется, н-р, для файлового кэша при запуске через демон)
   */
  public function save($data)
  {
    return;
    if (!$this->adaptor) {
      return;
    }

    return $this->adaptor->save($data);
  }

  /**
   * Метод для полной очистки кэша
   */
  public function clear()
  {
    return;
    if (!$this->adaptor) {
      return;
    }

    return $this->adaptor->clear();
  }
}

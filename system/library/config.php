<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

/**
 * Config class
 */
class Config
{
  private $data = array();

  /**
   * 
   *
   * @param	string	$key
   * 
   * @return	mixed
   */
  public function get($key)
  {
    return (isset($this->data[$key]) ? $this->data[$key] : null);
  }

  /**
   * 
   *
   * @param	string	$key
   * @param	string	$value
   */
  public function set($key, $value)
  {
    $this->data[$key] = $value;
  }

  /**
   * 
   *
   * @param	string	$key
   *
   * @return	mixed
   */
  public function getd($key)
  {
    return $this->get(base64_decode($key));
  }

  public function getc($val)
  {
    return base64_decode($val);
  }

  public function getm()
  {
    return ['Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvY3VycmVuY3kucGhw', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvZ3JhZmljLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvcGllZGlhZ3JhbS5waHA=', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvc3RyaW5nX3BsdXMucGhw', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvdGFibGVkb2MucGhw', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvdGV4dF9wbHVzLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vZmllbGQvdHJlZWRvYy5waHA=', 'Y29udHJvbGxlci9leHRlbnNpb24vZm9sZGVyL2ZvbGRlcl9jYXJkLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL2NvbmRpdGlvbl9wbHVzLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL2V4cG9ydF9mLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL2ltcG9ydF9mLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL3ByaW50LnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL3JlcG9ydC5waHA=', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL3NlbGVjdGlvbl9wbHVzLnBocA==', 'Y29udHJvbGxlci9leHRlbnNpb24vYWN0aW9uL3NpZ25fbmNhbGF5ZXIucGhw'];
  }

  public function has($key)
  {
    return isset($this->data[$key]);
  }

  /**
   * 
   *
   * @param	string	$filename
   */
  public function load($filename)
  {
    $file = DIR_CONFIG . $filename . '.php';

    if (file_exists($file)) {
      $_ = array();

      require(modification($file));

      $this->data = array_merge($this->data, $_);
    } else {
      trigger_error('Error: Could not load config ' . $filename . '!');
      exit();
    }
  }
}

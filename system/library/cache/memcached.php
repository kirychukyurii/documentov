<?php

namespace Cache;

class Memcached
{
  private $expire;
  private $memcached;
  private $cache = []; // собственный кэш в пределах сессии
  private $cached_category = []; // закешированные категории-теги

  const CACHEDUMP_LIMIT = 9999;

  public function __construct($expire)
  {
    $this->expire = $expire;
    $this->memcached = new \Memcached("Documentov_pool");
    $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, TRUE);
    $cache_hostname = "127.0.0.1";
    $cache_port = "11211";
    if (defined("CACHE_HOSTNAME")) {
      $cache_hostname = CACHE_HOSTNAME;
    }
    if (defined("CACHE_PORT")) {
      $cache_port = CACHE_PORT;
    }
    $this->memcached->addServer($cache_hostname, $cache_port);
  }

  public function get($key, $category = "")
  {
    if (isset($this->exceptions[$key]) || isset($this->exceptions[$category])) {
      return false;
    }
    $jdata = $this->cache[$key] ?? $this->memcached->get(DB_DATABASE . $key);
    // if (isset($this->cache[$key])) {
    //   $jdata = $this->cache[$key];
    // } else {
    //   $jdata = $this->memcached->get(DB_DATABASE . $key);
    //   echo "FROM MC " . $jdata . "<hr>";
    // }

    if (!$jdata) {
      return false;
    }
    $data = json_decode($jdata, true);
    if ($category) {
      $category_value = $this->cached_category[$category] ?? $this->get($category);
      if ($category_value !== $data['category']) {
        //данные устарели
        // приберемся
        $this->memcached->delete(DB_DATABASE . $key);
        return false;
      }
    }
    return $data['value'];
  }

  public function set($key, $value, $category = "", $tags = [])
  {

    if (isset($this->exceptions[$key]) || isset($this->exceptions[$category])) {
      return;
    }
    if ($category) {
      $category_value = $this->cached_category[$category] ?? $this->get($category);
      if ($category_value === false) {
        // в мемкеше еще нет такой категории-тега
        $category_value = 1;
        $this->memcached->set(DB_DATABASE . $category, $category_value);
      }
      $this->cached_category[$category] = $category_value;
    } else {
      $category_value = "";
    }

    $data = json_encode([
      "value" => $value,
      "category"   => $category_value
    ], JSON_UNESCAPED_UNICODE);
    $this->cache[$key] = $data;
    $this->memcached->set(DB_DATABASE . $key, $data);
  }

  public function delete($key, $category = "", $delete_category = true)
  {
    // если нужно удалить категорию-тэг, просто икрементим ее - все значения в мемкэше с этим тегом будут считаться устаревшими
    if (($category && !$key) || ($category && $delete_category)) {
      $this->memcached->increment(DB_DATABASE . $category, 1, 1);
      $this->cached_category[$category] = ($this->cached_category[$category] ?? 0) + 1;
    }
    unset($this->cache[$key]);
    $this->memcached->delete(DB_DATABASE . $key);
  }
}
